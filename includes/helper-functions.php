<?php

/**
 * Theme function to get a GravityView view
 *
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
 * @since  1.5.4
 * @return bool|string False if not, single entry slug if true
 */
function gravityview_is_single_entry() {
	return GravityView_frontend::is_single_entry();
}


/**
 * Get `get_permalink()` without the home_url() prepended to it.
 *
 * get_permalink() does a lot of good stuff: it gets the correct permalink structure for custom post types, pages,
 * posts, etc. Instead of using `?p={id}`, `?page_id={id}`, or `?p={id}&post_type={post_type}`, by using
 * get_permalink(), we can use `?p=slug` or `?gravityview={slug}`
 *
 * We could do this in a cleaner fashion, but this prevents a lot of code duplication, checking for URL structure, etc.
 *
 * @param int|WP_Post $id        Optional. Post ID or post object. Default current post.
 *
 * @return array URL args, if exists. Empty array if not.
 */
function gravityview_get_permalink_query_args( $id = 0 ) {

	$parsed_permalink = parse_url( get_permalink( $id ) );

	$permalink_args =  isset( $parsed_permalink['query'] ) ? $parsed_permalink['query'] : false;

	if( empty( $permalink_args ) ) {
		return array();
	}

	parse_str( $permalink_args, $args );

	return $args;
}