<?php
namespace GV\Shortcodes;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The [gvlogic] shortcode.
 */
class gvlogic extends \GV\Shortcode {
	/**
	 * {@inheritDoc}
	 */
	public $name = 'gvlogic';

	/**
	 * {@inheritDoc}
	 */
	public static function add( $name = null ) {
		parent::add(); // Me, myself and...

		/**
		 * ...some aliases.
		 */
		parent::add( 'gvlogic2' );
		parent::add( 'gvlogicelse' );
	}

	/**
	 * Process and output the [gvfield] shortcode.
	 *
	 * @param array $atts The attributes passed.
	 * @param string $content The content inside the shortcode.
	 * @param string $tag The tag.
	 *
	 * @return string The output.
	 */
	public function callback( $atts, $content = '', $tag = '' ) {
		$request = gravityview()->request;

		if ( $request->is_admin() ) {
			return apply_filters( 'gravityview/shortcodes/gvlogic/output', '', $atts );
		}

		$atts = $this->parse_atts( $atts, $content, $tag );

		// An invalid operation
		if ( is_null( \GV\Utils::get( $atts, 'logged_in', null ) ) && false === \GV\Utils::get( $atts, 'if', false ) ) {
			gravityview()->log->error( '$atts->if/logged_in is empty.', array( 'data' => $atts ) );
			return apply_filters( 'gravityview/shortcodes/gvlogic/output', '', $atts );
		}

		$authed   = $this->authorized( $atts );
		$operator = $this->get_operator( $atts );
		$value    = $this->get_value( $atts );

		if ( false === $operator && is_null( $value ) ) {
			if ( false !== $atts['if'] ) { // Only-if test
				$match = $authed && ! in_array( strtolower( $atts['if'] ), array( '', '0', 'false', 'no' ) );
			} else {
				$match = $authed; // Just login test
			}
		} else { // Regular test
			$match = $authed && \GVCommon::matches_operation( $atts['if'], $value, $operator );
		}

		// Output and get recursive!
		$output = do_shortcode( $this->get_output( $match, $atts, $content ) );
		$output = \GFCommon::replace_variables( $output, array(), array(), false, true, false );

		return apply_filters( 'gravityview/shortcodes/gvlogic/output', $output, $atts );
	}

	/**
	 * Are we authorized to follow the if path?
	 *
	 * @param array $atts The attributes.
	 *
	 * @return bool Yes, or no.
	 */
	private function authorized( $atts ) {
		if ( is_null( $needs_login  = \GV\Utils::get( $atts, 'logged_in', null ) ) ) {
			return true; // No auth requirements have been set
		}
		return ! $needs_login ^ is_user_logged_in(); // XNOR
	}

	/**
	 * Fetch the operator.
	 *
	 * @param array $atts The attributes.
	 *
	 * @return bool|string The operator.
	 */
	private function get_operator( $atts ) {
		$valid_ops = $this->get_operators( false );

		foreach ( $atts as $op => $value ) {
			if ( in_array( $op, array( 'if', 'else' ) ) ) {
				continue;
			}

			if ( in_array( $op, $valid_ops, true ) ) {
				return $op;
			}
		}

		return false;
	}

	/**
	 * Fetch the value.
	 *
	 * @param array $atts The attributes.
	 *
	 * @return null|string The value.
	 */
	private function get_value( $atts ) {
		$valid_ops = $this->get_operators( false );

		foreach ( $atts as $op => $value ) {
			if ( in_array( $op, array( 'if', 'else' ) ) ) {
				continue;
			}

			if ( in_array( $op, $valid_ops, true ) ) {
				return $value;
			}
		}

		return null;
	}

	/**
	 * Get the ouput content.
	 *
	 * @param bool $match if or else?
	 * @param array $atts The attributes.
	 * @param string $content The content.
	 *
	 * @return string The output.
	 */
	private function get_output( $match, $atts, $content ) {
		if ( ! $match && ! empty( $atts['else'] ) ) {
			return $atts['else']; // Attributized else is easy :)
		}

		$if = '';
		$else = '';

		$opens = 0; // inner opens
		$found = false; // found split position

		while ( $content ) { // scan
			if ( ! preg_match( '#(.*?)(\[\/?(gvlogic|else).*?])(.*)#', $content, $matches ) ) {
				if ( ! $found ) { // We're still iffing.
					$if .= $content;
				} else { // We are elsing
					$else .= $content;
				}
				break; // No more shortcodes
			}

			list( $_, $before_shortcode, $shortcode, $_, $after_shortcode ) = $matches;

			if ( ! $found ) { // We're still iffing.
				$if .= $before_shortcode;
			} else { // We are elsing
				$else .= $before_shortcode;
			}

			if ( strpos( $shortcode, '[else]' ) === 0 && $opens === 0 ) {
				// This is the else we need!
				$found = true;
				if ( $match ) {
					break; // We just need the if on a match, no need to analyze further
				}
			} else if ( $match && strpos( $shortcode, '[else if' ) === 0 && $opens === 0 ) {
				$found = true; // We found a match, do not process further
				break;
			} else {
				// Increment inner tracking counters
				if ( strpos( $shortcode, '[gvlogic' ) === 0 ) {
					$opens++;
				}

				if ( strpos( $shortcode, '[/gvlogic' ) === 0 ) {
					$opens--;
				}

				// Tack on the shortcode
				if ( ! $found ) { // We're still iffing.
					$if .= $shortcode;
				} else { // We are elsing
					$else .= $shortcode;
				}
			}

			$content = $after_shortcode;
		}

		gravityview()->log->error( 'if => {if}, else => {else}', array( 'if' => $if, 'else' => $else ) );
		
		if ( ! $match ) {
			while ( ( $position = strpos( $if, '[else if=' ) ) !== false ) {
				// Try to match one of the elseif's
				$sentinel = wp_generate_password( 32, false );
				$if = substr( $if, $position ); // ...by replacing it with a gvlogic shortcode
				// ..and executing it!
				$result = do_shortcode( preg_replace( '#\[else if#', '[gvlogic if', $if, 1 ) . "[else]{$sentinel}[/gvlogic]" );
				if ( $result !== $sentinel ) {
					// We have an elseif match!
					return $result;
				}
				$if = substr( $if, 1 ); // Move over to get the next elseif match.. and repeat
			}
		}

		return $match ? $if : $else;
	}

	/**
	 * Get array of supported operators
	 * @param bool $with_values
	 *
	 * @return array
	 */
	private function get_operators( $with_values = false ) {

		$operators = array(
			'is', 'isnot', 'contains', 'starts_with', 'ends_with',
			'greater_than', 'less_than', 'in', 'not_in', 'isnot',
			'contains', 'equals', 'greater_than_or_is', 'greater_than_or_equals',
			'less_than_or_is', 'less_than_or_equals', 'not_contains',
		);

		if ( $with_values ) {
			return array_combine( 
				$operators,
				array_fill( 0, count( $operators ), '' )
			);
		}

		return $operators;
	}

	/**
	 * Process the attributes passed to the shortcode. Make sure they're valid
	 * @return void
	 */
	private function parse_atts( $atts, $content, $tag ) {
		$supplied_atts = $atts;

		if ( empty( $supplied_atts ) ) {
			$supplied_atts = array();
		}

		$atts = shortcode_atts( array(
			'if'        => null,
			'else'      => null,
			'logged_in' => null,
		) + $this->get_operators( true ), $atts, $tag );

		// Only keep the passed attributes after making sure that they're valid pairs
		$atts = array_intersect_key( $supplied_atts, $atts );

		// Strip whitespace if it's not default false
		if ( isset( $atts['if'] ) && is_string( $atts['if'] ) ) {
			$atts['if'] = trim( $atts['if'] );
		} else {
			$atts['if'] = false;
		}

		if ( isset( $atts['logged_in'] ) ) {
			// Truthy
			if ( in_array( strtolower( $atts['logged_in'] ), array( '0', 'false', 'no' ) ) ) {
				$atts['logged_in'] = false;
			} else {
				$atts['logged_in'] = true;
			}
		}

		/**
		 * @filter `gravityview/gvlogic/atts` The logic attributes.
		 *
		 * @since develop
		 *
		 * @param[in,out] array $atts The logic attributes.
		 */
		return apply_filters( 'gravityview/gvlogic/atts', $atts );
	}
}
