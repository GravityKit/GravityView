<?php
/**
 * Improve compatibility with BuddyPress.
 *
 * @file      class-gravityview-plugin-hooks-buddypress.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15.2
 */

/**
 * @inheritDoc
 */
class GravityView_Plugin_Hooks_BuddyPress extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * The function name to check if we are on a BuddyPress page.
	 *
	 * @since TODO
	 *
	 * @var string
	 */
	protected $function_name = 'bp_is_user_profile';

	/**
	 * Remove the permalink structure for BuddyPress pages.
	 *
	 * @since TODO
	 *
	 * @return bool Whether to remove the permalink structure from View rendered links.
	 */
	public function should_disable_permalink_structure() {

		if ( ! bp_is_user_profile() ) {
			return parent::should_disable_permalink_structure();
		}

		return true;
	}

	/**
	 * Modify the edit link for BuddyPress profile pages.
	 *
	 * Needs to run on template_redirect to ensure that $post is set for
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	public function on_template_redirect() {

		if ( ! bp_is_user_profile() ) {
			return;
		}

		/**
		* Make sure that GravityView "sees" that we're in an entry.
		*
		* @see \GV\Request::is_entry()
		*/
		add_filter( 'gravityview/edit/link', [ $this, 'edit_link_filter' ], 10, 2 );

		parent::on_template_redirect();
	}

	/**
	 * Modify the edit link for BuddyPress profile pages.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	public function edit_link_filter( $url, $entry ) {
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		if( ! $entry ) {
			return $url;
		}

		$endpoint = \GV\Entry::get_endpoint_name();

		$query_args = self::get_query_args( $url );

		$query_args[ $endpoint ] = $entry->get_slug( true );

		$current_url = add_query_arg( [] );

		return add_query_arg( $query_args, $current_url );
	}

	/**
	 * Get the query args from the current URL.
	 *
	 * @since TODO
	 *
	 * @param string $url The URL to get the query args from.
	 *
	 * @return array
	 */
	static private function get_query_args( $url ) {
		$parsed_url = wp_parse_url( $url );

		$query_args = [];
		if ( ! empty( $parsed_url['query'] ) ) {
			parse_str( $parsed_url['query'], $query_args );
		}

		return $query_args;
	}
}

new GravityView_Plugin_Hooks_BuddyPress();
