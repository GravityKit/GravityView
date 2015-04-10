<?php

/**
 * Theme function to get a GravityView view
 *
 * @todo Move to functions file
 * @access public
 * @param string $view_id (default: '')
 * @param array $atts (default: array())
 * @return string HTML of the output. Empty string if $view_id is empty.
 */
function get_gravityview( $view_id = '', $atts = array() ) {
	if( !empty( $view_id ) ) {
		$atts['id'] = $view_id;
		$args = wp_parse_args( $atts, GravityView_View_Data::get_default_args() );
		$GravityView_frontend = GravityView_frontend::getInstance();
		$GravityView_frontend->setGvOutputData( GravityView_View_Data::getInstance( $view_id ) );
		$GravityView_frontend->set_context_view_id( $view_id );
		$GravityView_frontend->set_entry_data();
		return $GravityView_frontend->render_view( $args );
	}
	return '';
}

/**
 * Theme function to render a GravityView view
 *
 * @todo Move to functions file
 * @access public
 * @param string $view_id (default: '')
 * @param array $atts (default: array())
 * @return void
 */
function the_gravityview( $view_id = '', $atts = array() ) {
	echo get_gravityview( $view_id, $atts );
}


/**
 * Theme function to identify if it is a Single Entry View
 *
 * @todo Move to functions file
 * @since  1.5.4
 * @return bool|string False if not, single entry slug if true
 */
function gravityview_is_single_entry() {
	return GravityView_frontend::is_single_entry();
}