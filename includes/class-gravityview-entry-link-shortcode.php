<?php
/**
 * Shortcode to handle showing/hiding content in merge tags. Works great with GravityView Custom Content fields
 *
 * @deprecated 2.45
 * @see \GV\Shortcodes\gv_entry_link
 */
class GravityView_Entry_Link_Shortcode {

	/**
	 * @deprecated 2.45
	 * @see \GV\Shortcodes\gv_entry_link
	 */
	public function read_shortcode( $atts, $content = '', $tag = '' ) {
		_deprecated_function( __FUNCTION__, '2.45', '\GV\Shortcodes\gv_entry_link' );
		$shortcode = new \GV\Shortcodes\gv_entry_link();
		return $shortcode->callback( $atts, $content, $tag );
	}

	/**
	 * @deprecated 2.45
	 * @see \GV\Shortcodes\gv_entry_link
	 */
	public function edit_shortcode( $atts, $content = '', $tag = '' ) {
		_deprecated_function( __FUNCTION__, '2.45', '\GV\Shortcodes\gv_entry_link' );
		$shortcode = new \GV\Shortcodes\gv_entry_link();
		return $shortcode->callback( $atts, $content, $tag );
	}

	/**
	 * @deprecated 2.45
	 * @see \GV\Shortcodes\gv_entry_link
	 */
	public function delete_shortcode( $atts, $content = '', $tag = '' ) {
		_deprecated_function( __FUNCTION__, '2.45', '\GV\Shortcodes\gv_entry_link' );
		$shortcode = new \GV\Shortcodes\gv_entry_link();
		return $shortcode->callback( $atts, $content, $tag );
	}
}
