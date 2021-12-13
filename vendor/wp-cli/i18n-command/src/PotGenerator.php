<?php

namespace WP_CLI\I18n;

use Gettext\Generators\Po as PoGenerator;
use Gettext\Translations;
use Gettext\Utils\ParsedComment;

/**
 * POT file generator.
 *
 * The only difference to the existing PO file generator is that this
 * adds some comments at the very beginning of the file.
 */
class PotGenerator extends PoGenerator {
	protected static $comments_before_headers = [];

	/**
	 * Text to include as a comment before the start of the PO contents
	 *
	 * Doesn't need to include # in the beginning of lines, these are added automatically.
	 *
	 * @param string $comment File comment.
	 */
	public static function setCommentBeforeHeaders( $comment ) {
		$comments = explode( "\n", $comment );

		foreach ( $comments as $line ) {
			if ( '' !== trim( $line ) ) {
				static::$comments_before_headers[] = '# ' . $line;
			}
		}
	}

	/**
	 * {@parentDoc}.
	 */
	public static function toString( Translations $translations, array $options = [] ) {
		$lines   = static::$comments_before_headers;
		$lines[] = 'msgid ""';
		$lines[] = 'msgstr ""';

		$plural_form = $translations->getPluralForms();
		$plural_size = is_array( $plural_form ) ? ( $plural_form[0] - 1 ) : 1;

		foreach ( $translations->getHeaders() as $name => $value ) {
			$lines[] = sprintf( '"%s: %s\\n"', $name, $value );
		}

		$lines[] = '';

		foreach ( $translations as $translation ) {
			/** @var \Gettext\Translation $translation */
			if ( $translation->hasComments() ) {
				foreach ( $translation->getComments() as $comment ) {
					$lines[] = '# ' . $comment;
				}
			}

			if ( $translation->hasExtractedComments() ) {
				$unique_comments = array();

				/** @var ParsedComment|string $comment */
				foreach ( $translation->getExtractedComments() as $comment ) {
					$comment = ( $comment instanceof ParsedComment ? $comment->getComment() : $comment );
					if ( ! in_array( $comment, $unique_comments, true ) ) {
						$lines[]           = '#. ' . $comment;
						$unique_comments[] = $comment;
					}
				}
			}

			foreach ( $translation->getReferences() as $reference ) {
				$lines[] = '#: ' . $reference[0] . ( null !== $reference[1] ? ':' . $reference[1] : '' );
			}

			if ( $translation->hasFlags() ) {
				$lines[] = '#, ' . implode( ',', $translation->getFlags() );
			}

			$prefix = $translation->isDisabled() ? '#~ ' : '';

			if ( $translation->hasContext() ) {
				$lines[] = $prefix . 'msgctxt ' . self::convertString( $translation->getContext() );
			}

			self::addLines( $lines, $prefix . 'msgid', $translation->getOriginal() );

			if ( $translation->hasPlural() ) {
				self::addLines( $lines, $prefix . 'msgid_plural', $translation->getPlural() );

				for ( $i = 0; $i <= $plural_size; $i ++ ) {
					self::addLines( $lines, $prefix . 'msgstr[' . $i . ']', '' );
				}
			} else {
				self::addLines( $lines, $prefix . 'msgstr', $translation->getTranslation() );
			}

			$lines[] = '';
		}

		return implode( "\n", $lines );
	}

	/**
	 * Escapes and adds double quotes to a string.
	 *
	 * @param string $string Multiline string.
	 *
	 * @return string[]
	 */
	protected static function multilineQuote( $string ) {
		$lines = explode( "\n", $string );
		$last  = count( $lines ) - 1;

		foreach ( $lines as $k => $line ) {
			if ( $k === $last ) {
				$lines[ $k ] = self::convertString( $line );
			} else {
				$lines[ $k ] = self::convertString( $line . "\n" );
			}
		}

		return $lines;
	}

	/**
	 * Add one or more lines depending whether the string is multiline or not.
	 *
	 * @param array  &$lines Array lines should be added to.
	 * @param string $name   Name of the line, e.g. msgstr or msgid_plural.
	 * @param string $value  The line to add.
	 */
	protected static function addLines( array &$lines, $name, $value ) {
		$newlines = self::multilineQuote( $value );

		if ( count( $newlines ) === 1 ) {
			$lines[] = $name . ' ' . $newlines[0];
		} else {
			$lines[] = $name . ' ""';

			foreach ( $newlines as $line ) {
				$lines[] = $line;
			}
		}
	}
}
