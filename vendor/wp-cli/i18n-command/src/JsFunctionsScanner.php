<?php

namespace WP_CLI\I18n;

use Gettext\Utils\JsFunctionsScanner as GettextJsFunctionsScanner;
use Gettext\Utils\ParsedComment;
use Peast\Peast;
use Peast\Syntax\Node;
use Peast\Traverser;

final class JsFunctionsScanner extends GettextJsFunctionsScanner {

	/**
	 * If not false, comments will be extracted.
	 *
	 * @var string|false|array
	 */
	private $extract_comments = false;

	/**
	 * Holds a list of source code comments already added to a string.
	 *
	 * Prevents associating the same comment to multiple strings.
	 *
	 * @var Node\Comment[] $comments_cache
	 */
	private $comments_cache = [];

	/**
	 * Enable extracting comments that start with a tag (if $tag is empty all the comments will be extracted).
	 *
	 * @param mixed $tag
	 */
	public function enableCommentsExtraction( $tag = '' ) {
		$this->extract_comments = $tag;
	}

	/**
	 * Disable comments extraction.
	 */
	public function disableCommentsExtraction() {
		$this->extract_comments = false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function saveGettextFunctions( $translations, array $options ) {
		// Ignore multiple translations for now.
		// @todo Add proper support for multiple translations.
		if ( is_array( $translations ) ) {
			$translations = $translations[0];
		}

		$peast_options = [
			'sourceType' => Peast::SOURCE_TYPE_MODULE,
			'comments'   => false !== $this->extract_comments,
			'jsx'        => true,
		];
		$ast           = Peast::latest( $this->code, $peast_options )->parse();

		$traverser = new Traverser();

		$all_comments = [];

		/**
		 * Traverse through JS code to find and extract gettext functions.
		 *
		 * Make sure translator comments in front of variable declarations
		 * and inside nested call expressions are available when parsing the function call.
		 */
		$traverser->addFunction(
			function ( $node ) use ( &$translations, $options, &$all_comments ) {
				$functions     = $options['functions'];
				$file          = $options['file'];
				$add_reference = ! empty( $options['addReferences'] );

				/** @var Node\Node $node */
				foreach ( $node->getLeadingComments() as $comment ) {
					$all_comments[] = $comment;
				}

				/** @var Node\CallExpression $node */
				if ( 'CallExpression' !== $node->getType() ) {
					return;
				}

				$callee = $this->resolveExpressionCallee( $node );

				if ( ! $callee || ! isset( $functions[ $callee['name'] ] ) ) {
					return;
				}

				/** @var Node\CallExpression $node */
				foreach ( $node->getArguments() as $argument ) {
					// Support nested function calls.
					$argument->setLeadingComments( $argument->getLeadingComments() + $node->getLeadingComments() );
				}

				foreach ( $callee['comments'] as $comment ) {
					$all_comments[] = $comment;
				}

				$domain   = null;
				$original = null;
				$context  = null;
				$plural   = null;
				$args     = [];

				/** @var Node\Node $argument */
				foreach ( $node->getArguments() as $argument ) {
					foreach ( $argument->getLeadingComments() as $comment ) {
						$all_comments[] = $comment;
					}

					if (
						'Identifier' === $argument->getType() ||
						'Expression' === substr( $argument->getType(), -strlen( 'Expression' ) )
					) {
						$args[] = ''; // The value doesn't matter as it's unused.
						continue;
					}

					if ( 'Literal' === $argument->getType() ) {
						/** @var Node\Literal $argument */
						$args[] = $argument->getValue();
						continue;
					}

					if ( 'TemplateLiteral' === $argument->getType() && 0 === count( $argument->getExpressions() ) ) {
						/** @var Node\TemplateLiteral $argument */
						/** @var Node\TemplateElement[] $parts */

						// Since there are no expressions within the TemplateLiteral, there is only one TemplateElement.
						$parts  = $argument->getParts();
						$args[] = $parts[0]->getValue();
						continue;
					}

					// If we reach this, an unsupported argument type has been encountered.
					// Do not try to parse this function call at all.
					return;
				}

				switch ( $functions[ $callee['name'] ] ) {
					case 'text_domain':
					case 'gettext':
						list( $original, $domain ) = array_pad( $args, 2, null );
						break;

					case 'text_context_domain':
						list( $original, $context, $domain ) = array_pad( $args, 3, null );
						break;

					case 'single_plural_number_domain':
						list( $original, $plural, $number, $domain ) = array_pad( $args, 4, null );
						break;

					case 'single_plural_number_context_domain':
						list( $original, $plural, $number, $context, $domain ) = array_pad( $args, 5, null );
						break;
				}

				if ( '' === (string) $original ) {
					return;
				}

				if ( $domain !== $translations->getDomain() && null !== $translations->getDomain() ) {
					return;
				}

				if ( isset( $options['line'] ) ) {
					$line = $options['line'];
				} else {
					$line = $node->getLocation()->getStart()->getLine();
				}

				$translation = $translations->insert( $context, $original, $plural );
				if ( $add_reference ) {
					$translation->addReference( $file, $line );
				}

				/** @var Node\Comment $comment */
				foreach ( $all_comments as $comment ) {
					// Comments should be before the translation.
					if ( ! $this->commentPrecedesNode( $comment, $node ) ) {
						continue;
					}

					if ( in_array( $comment, $this->comments_cache, true ) ) {
						continue;
					}

					$parsed_comment = ParsedComment::create( $comment->getRawText(), $comment->getLocation()->getStart()->getLine() );
					$prefixes       = array_filter( (array) $this->extract_comments );

					if ( $parsed_comment->checkPrefixes( $prefixes ) ) {
						$translation->addExtractedComment( $parsed_comment->getComment() );

						$this->comments_cache[] = $comment;
					}
				}

				if ( isset( $parsed_comment ) ) {
					$all_comments = [];
				}
			}
		);

		/**
		 * Traverse through JS code contained within eval() to find and extract gettext functions.
		 */
		$scanner = $this;
		$traverser->addFunction(
			function ( $node ) use ( &$translations, $options, $scanner ) {
				/** @var Node\CallExpression $node */
				if ( 'CallExpression' !== $node->getType() ) {
					return;
				}

				$callee = $this->resolveExpressionCallee( $node );

				if ( ! $callee || 'eval' !== $callee['name'] ) {
					return;
				}

				$eval_contents = '';
				/** @var Node\Node $argument */
				foreach ( $node->getArguments() as $argument ) {
					if ( 'Literal' === $argument->getType() ) {
						/** @var Node\Literal $argument */
						$eval_contents = $argument->getValue();
						break;
					}
				}

				if ( ! $eval_contents ) {
					return;
				}

				// Override the line location to be that of the eval().
				$options['line'] = $node->getLocation()->getStart()->getLine();

				$class = get_class( $scanner );
				$evals = new $class( $eval_contents );
				$evals->enableCommentsExtraction( $options['extractComments'] );
				$evals->saveGettextFunctions( $translations, $options );
			}
		);

		$traverser->traverse( $ast );
	}

	/**
	 * Resolve the callee of a call expression using known formats.
	 *
	 * @param Node\CallExpression $node The call expression whose callee to resolve.
	 *
	 * @return array|bool Array containing the name and comments of the identifier if resolved. False if not.
	 */
	private function resolveExpressionCallee( Node\CallExpression $node ) {
		$callee = $node->getCallee();

		// If the callee is a simple identifier it can simply be returned.
		// For example: __( "translation" ).
		if ( 'Identifier' === $callee->getType() ) {
			return [
				'name'     => $callee->getName(),
				'comments' => $callee->getLeadingComments(),
			];
		}

		// If the callee is a member expression resolve it to the property.
		// For example: wp.i18n.__( "translation" ) or u.__( "translation" ).
		if (
			'MemberExpression' === $callee->getType() &&
			'Identifier' === $callee->getProperty()->getType()
		) {
			// Make sure to unpack wp.i18n which is a nested MemberExpression.
			$comments = 'MemberExpression' === $callee->getObject()->getType()
				? $callee->getObject()->getObject()->getLeadingComments()
				: $callee->getObject()->getLeadingComments();

			return [
				'name'     => $callee->getProperty()->getName(),
				'comments' => $comments,
			];
		}

		// If the callee is a call expression as created by Webpack resolve it.
		// For example: Object(u.__)( "translation" ).
		if (
			'CallExpression' === $callee->getType() &&
			'Identifier' === $callee->getCallee()->getType() &&
			'Object' === $callee->getCallee()->getName() &&
			[] !== $callee->getArguments() &&
			'MemberExpression' === $callee->getArguments()[0]->getType()
		) {
			$property = $callee->getArguments()[0]->getProperty();

			// Matches minified webpack statements: Object(u.__)( "translation" ).
			if ( 'Identifier' === $property->getType() ) {
				return [
					'name'     => $property->getName(),
					'comments' => $callee->getCallee()->getLeadingComments(),
				];
			}

			// Matches unminified webpack statements:
			// Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])( "translation" );
			if ( 'Literal' === $property->getType() ) {
				$name = $property->getValue();

				// Matches mangled webpack statement:
				// Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__[/* __ */ "a"])( "translation" );
				$leading_property_comments = $property->getLeadingComments();
				if ( count( $leading_property_comments ) === 1 && $leading_property_comments[0]->getKind() === 'multiline' ) {
					$name = trim( $leading_property_comments[0]->getText() );
				}

				return [
					'name'     => $name,
					'comments' => $callee->getCallee()->getLeadingComments(),
				];
			}
		}

		// If the callee is an indirect function call as created by babel, resolve it.
		// For example: `(0, u.__)( "translation" )`.
		if (
			'ParenthesizedExpression' === $callee->getType()
			&& 'SequenceExpression' === $callee->getExpression()->getType()
			&& 2 === count( $callee->getExpression()->getExpressions() )
			&& 'Literal' === $callee->getExpression()->getExpressions()[0]->getType()
			&& [] !== $node->getArguments()
		) {
			// Matches any general indirect function call: `(0, __)( "translation" )`.
			if ( 'Identifier' === $callee->getExpression()->getExpressions()[1]->getType() ) {
				return [
					'name'     => $callee->getExpression()->getExpressions()[1]->getName(),
					'comments' => $callee->getLeadingComments(),
				];
			}

			// Matches indirect function calls used by babel for module imports: `(0, _i18n.__)( "translation" )`.
			if ( 'MemberExpression' === $callee->getExpression()->getExpressions()[1]->getType() ) {
				$property = $callee->getExpression()->getExpressions()[1]->getProperty();

				if ( 'Identifier' === $property->getType() ) {
					return [
						'name'     => $property->getName(),
						'comments' => $callee->getLeadingComments(),
					];
				}
			}
		}

		// Unknown format.
		return false;
	}

	/**
	 * Returns wether or not a comment precedes a node.
	 * The comment must be before the node and on the same line or the one before.
	 *
	 * @param Node\Comment $comment The comment.
	 * @param Node\Node    $node    The node.
	 *
	 * @return bool Whether or not the comment precedes the node.
	 */
	private function commentPrecedesNode( Node\Comment $comment, Node\Node $node ) {
		// Comments should be on the same or an earlier line than the translation.
		if ( $node->getLocation()->getStart()->getLine() - $comment->getLocation()->getEnd()->getLine() > 1 ) {
			return false;
		}

		// Comments on the same line should be before the translation.
		if (
			$node->getLocation()->getStart()->getLine() === $comment->getLocation()->getEnd()->getLine() &&
			$node->getLocation()->getStart()->getColumn() < $comment->getLocation()->getStart()->getColumn()
		) {
			return false;
		}

		return true;
	}
}
