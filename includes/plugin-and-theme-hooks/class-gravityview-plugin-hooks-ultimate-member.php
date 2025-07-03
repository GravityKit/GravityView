<?php
/**
 * Add Ultimate Member plugin compatibility to GravityView
 *
 * @file      class-gravityview-theme-hooks-ultimate-member.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2016', Katz Web Services, Inc.
 *
 * @since 1.17.2
 */

/**
 * @inheritDoc
 * @since 1.17.2
 */
class GravityView_Theme_Hooks_Ultimate_Member extends GravityView_Plugin_and_Theme_Hooks {

	use GravityView_Permalink_Override_Trait;

	/**
	 * @inheritDoc
	 * @since 1.17.2
	 */
	protected $constant_name = 'ultimatemember_version';

	/**
	 * The function name to check if we are on a UM Core Page.
	 *
	 * @since TODO
	 *
	 * @var string
	 */
	protected $function_name = 'um_is_core_page';

	function add_hooks() {
		parent::add_hooks();

		// Needs to be early to be triggered before DataTables
		add_action( 'template_redirect', array( $this, 'parse_um_profile_post_content' ) );
	}

	/**
	 * Remove the permalink structure for Ultimate Member profile tabs.
	 *
	 * @since TODO
	 *
	 * @return bool Whether to remove the permalink structure from View rendered links.
	 */
	protected function should_disable_permalink_structure() {
		if ( um_is_core_page( 'user' ) || um_is_core_page( 'members' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Parse tab content in Ultimate Member profile tabs
	 *
	 * @since 1.17.2
	 *
	 * @param array $args Ultimate Member profile settings array
	 *
	 * @return void
	 */
	function parse_um_profile_post_content( $args = array() ) {
		global $ultimatemember;

		if ( ! $ultimatemember || ! is_object( $ultimatemember ) || ! class_exists( 'GravityView_View_Data' ) ) {
			return;
		}

		// @todo Support Ultimate Member 2.0 - for now, prevent fatal error
		if ( ! isset( $ultimatemember->profile ) ) {
			return;
		}

		$active_tab_args = array(
			'name'        => $ultimatemember->profile->active_tab(),
			'post_type'   => 'um_tab',
			'numberposts' => 1,
		);

		$active_tab = get_posts( $active_tab_args );

		if ( ! $active_tab ) {
			return;
		}

		GravityView_View_Data::getInstance()->parse_post_content( $active_tab[0]->post_content );

		wp_reset_postdata();
	}
}

new GravityView_Theme_Hooks_Ultimate_Member();
