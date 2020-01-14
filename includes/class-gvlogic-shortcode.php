<?php
/**
 * Shortcode to handle showing/hiding content in merge tags. Works great with GravityView Custom Content fields
 *
 * @deprecated
 * @since develop
 * @see \GV\Shortcodes\gvlogic
 */
class GVLogic_Shortcode {
	public static $instance = null;

	public static function get_instance() {

		_deprecated_function( __METHOD__, '2.0', '\GV\Shortcodes\gvlogic' );

		if ( is_null( self::$instance ) ) {
			return self::$instance = new self(); // Nothing
		}

		return self::$instance;
	}

	public function shortcode( $atts, $content = '', $tag = '') {

		_deprecated_function( __METHOD__, '2.0', '\GV\Shortcodes\gvlogic::callback()' );

		$shortcode = new \GV\Shortcodes\gvlogic();
		return $shortcode->callback( $atts, $content, $tag );
	}
}
