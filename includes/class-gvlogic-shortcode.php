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
		if ( is_null( self::$instance ) ) {
			return self::$instance = new self(); // Nothing
		}

		return self::$instance;
	}

	public function shortcode( $atts, $content = '', $tag = '' ) {
		$shortcode = new \GV\Shortcodes\gvlogic();
		return $shortcode->callback( $atts, $content, $tag );
	}
}
