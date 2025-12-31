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
		parent::add( 'gvlogic3' ); // This level of nesting is not supported by GravityView support...but go for it!
		parent::add( 'gvlogicelse' );

		add_filter( 'no_texturize_shortcodes', array( __CLASS__, 'filter_no_texturize_shortcodes' ) );
	}

	/**
	 * Process and output the [gvfield] shortcode.
	 *
	 * @param array  $atts The attributes passed.
	 * @param string $content The content inside the shortcode.
	 * @param string $tag The tag.
	 *
	 * @return string The output.
	 */
	public function callback( $atts, $content = '', $tag = '' ) {
		$request = gravityview()->request;

		$atts = $this->parse_atts( $atts, $content, $tag );

		$content = \GravityView_Merge_Tags::replace_get_variables( $content );
		$atts    = gv_map_deep( $atts, array( '\GravityView_Merge_Tags', 'replace_get_variables' ) );

		$content = \GFCommon::replace_variables_prepopulate( $content );
		$atts    = gv_map_deep( $atts, array( '\GFCommon', 'replace_variables_prepopulate' ) );

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

			$output = $this->get_output( $match, $atts, $content );
		} else { // Regular test

			$output = $content;

			// Allow checking against multiple values at once
			$and_values = explode( '&&', $value );
			$or_values  = explode( '||', $value );

			// Cannot combine AND and OR
			if ( sizeof( $and_values ) > 1 ) {

				// Need to match all AND
				foreach ( $and_values as $and_value ) {
					$match = $authed && \GVCommon::matches_operation( $atts['if'], $and_value, $operator );
					if ( ! $match ) {
						break;
					}
				}
			} elseif ( sizeof( $or_values ) > 1 ) {

				// Only need to match a single OR
				foreach ( $or_values as $or_value ) {

					$match = \GVCommon::matches_operation( $atts['if'], $or_value, $operator );

					// Negate the negative operators
					if ( ( $authed && $match ) || ( $authed && ( ! $match && in_array( $operator, array( 'isnot', 'not_contains', 'not_in' ) ) ) ) ) {
						break;
					}
				}
			} else {
				$match = $authed && \GVCommon::matches_operation( $atts['if'], $value, $operator );
			}

			$output = $this->get_output( $match, $atts, $output );
		}

		// Output and get recursive!
		$output = do_shortcode( $output );
		$output = \GFCommon::replace_variables( $output, array(), array(), false, true, false );

		/**
		 * Filters the final output of the [gvlogic] shortcode.
		 *
		 * @since 2.5
		 *
		 * @param string $output The shortcode output.
		 * @param array  $atts   The shortcode attributes.
		 */
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

		$needs_login = \GV\Utils::get( $atts, 'logged_in', null );

		if ( is_null( $needs_login ) ) {
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
	 * Get the output content.
	 *
	 * @param bool   $match if or else?
	 * @param array  $atts The attributes.
	 * @param string $content The content.
	 *
	 * @return string The output.
	 */
	private function get_output( $match, $atts, $content ) {
		if ( ! $match && ! empty( $atts['else'] ) ) {
			return $atts['else']; // Attributized else is easy :)
		}

		$if   = '';
		$else = '';

		$opens = 0; // inner opens
		$found = false; // found split position

		while ( $content ) { // scan

			if ( ! preg_match( '#(.*?)(\[\/?(gvlogic|else).*?])(.*)#s', $content, $matches ) ) {
				if ( ! $found ) { // We're still iffing.
					$if .= $content;
				} else { // We are elsing
					$else .= $content;
				}
				break; // No more shortcodes
			}

			list( $_, $before_shortcode, $shortcode, $__, $after_shortcode ) = $matches;

			if ( ! $found ) { // We're still iffing.
				$if .= $before_shortcode;
			} else { // We are elsing
				$else .= $before_shortcode;
			}

			if ( 0 === strpos( $shortcode, '[else]' ) && 0 === $opens ) {
				// This is the else we need!
				$found = true;
				if ( $match ) {
					break; // We just need the if on a match, no need to analyze further
				}
			} elseif ( $match && 0 === strpos( $shortcode, '[else if' ) && 0 === $opens ) {
				$found = true; // We found a match, do not process further
				break;
			} else {
				// Increment inner tracking counters
				if ( 0 === strpos( $shortcode, '[gvlogic' ) ) {
					++$opens;
				}

				if ( 0 === strpos( $shortcode, '[/gvlogic' ) ) {
					--$opens;
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

		gravityview()->log->debug(
			'[gvlogic] output parsing:',
			array(
				'data' => array(
					'if'   => $if,
					'else' => $else,
				),
			)
		);

		if ( ! $match ) {
			while ( ( $position = strpos( $if, '[else if=' ) ) !== false ) {
				// Try to match one of the elseif's
				$sentinel = wp_generate_password( 32, false );
				$if       = substr( $if, $position ); // ...by replacing it with a gvlogic shortcode
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
	 *
	 * @param bool $with_values
	 *
	 * @return array
	 */
	private function get_operators( $with_values = false ) {

		$operators = array(
			'is',
			'isnot',
			'contains',
			'starts_with',
			'ends_with',
			'greater_than',
			'less_than',
			'in',
			'not_in',
			'contains',
			'equals',
			'greater_than_or_is',
			'greater_than_or_equals',
			'less_than_or_is',
			'less_than_or_equals',
			'not_contains',
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
	 *
	 * @return array Array of attributes parsed for the shortcode
	 */
	private function parse_atts( $atts, $content, $tag ) {

		$supplied_atts = ! empty( $atts ) ? $atts : array();

		$atts = shortcode_atts(
			array(
				'if'        => null,
				'else'      => null,
				'logged_in' => null,
			) + $this->get_operators( true ),
			$atts,
			$tag
		);

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
		 * Filter the logic attributes for the [gvlogic] shortcode.
		 *
		 * @since 2.5
		 *
		 * @param array $atts The logic attributes.
		 */
		return apply_filters( 'gravityview/gvlogic/atts', $atts );
	}

	/**
	 * Fixes formatting issues when embedding in posts/pages.
	 *
	 * @see https://github.com/GravityKit/GravityView/issues/1846
	 *
	 * @since 2.17.6
	 *
	 * @param array $atts
	 *
	 * @return array
	 */
	public static function filter_no_texturize_shortcodes( $shortcodes = array() ) {

		$shortcodes[] = 'gvlogic';
		$shortcodes[] = 'gvlogic2';
		$shortcodes[] = 'gvlogic3';
		$shortcodes[] = 'gvlogicelse';

		return $shortcodes;
	}
}
