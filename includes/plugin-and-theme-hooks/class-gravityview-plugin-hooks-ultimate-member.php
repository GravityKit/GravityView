<?php
/**
 * Add Ultimate Member plugin compatibility to GravityView
 *
 * @file      class-gravityview-theme-hooks-ultimate-member.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2016', Katz Web Services, Inc.
 *
 * @since 1.17.2
 */

/**
 * @inheritDoc
 * @since 1.17.2
 */
class GravityView_Theme_Hooks_Ultimate_Member extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.17.2
	 */
	protected $constant_name = 'ultimatemember_version';

	function add_hooks() {
		parent::add_hooks();

		// Needs to be early to be triggered before DataTables
		add_action( 'template_redirect', array( $this, 'parse_um_profile_post_content' ) );
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

		if( ! $ultimatemember || ! is_object( $ultimatemember ) || ! class_exists( 'GravityView_View_Data' ) ) {
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

new GravityView_Theme_Hooks_Ultimate_Member;