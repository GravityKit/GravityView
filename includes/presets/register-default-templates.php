<?php
/**
 * GravityView default templates and generic template class
 *
 * @file register-default-templates.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityView <hello@gravityview.co>
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 2.10
 */

// Load default templates
add_action( 'init', 'gravityview_register_default_templates', 11 );

/**
 * Registers the default templates
 * @return void
 */
function gravityview_register_default_templates() {
	/** @define "GRAVITYVIEW_DIR" "../../" */

	// The abstract class required by all template files.
	require_once GRAVITYVIEW_DIR . 'includes/class-gravityview-template.php';

	$path = GRAVITYVIEW_DIR . 'includes/presets/';
	include_once $path . 'default-table/class-gravityview-default-template-table.php';
	include_once $path . 'default-list/class-gravityview-default-template-list.php';
	include_once $path . 'default-edit/class-gravityview-default-template-edit.php';
	include_once $path . 'business-listings/class-gravityview-preset-business-listings.php';
	include_once $path . 'business-data/class-gravityview-preset-business-data.php';
	include_once $path . 'profiles/class-gravityview-preset-profiles.php';
	include_once $path . 'staff-profiles/class-gravityview-preset-staff-profiles.php';
	include_once $path . 'website-showcase/class-gravityview-preset-website-showcase.php';
	include_once $path . 'issue-tracker/class-gravityview-preset-issue-tracker.php';
	include_once $path . 'resume-board/class-gravityview-preset-resume-board.php';
	include_once $path . 'job-board/class-gravityview-preset-job-board.php';
	include_once $path . 'event-listings/class-gravityview-preset-event-listings.php';
}


// Register after other templates
add_action( 'init', 'gravityview_register_placeholder_templates', 2000 );

/**
 * Register the placeholder templates to make it clear what layouts are available
 *
 * @since 2.10
 *
 * @return void
 */
function gravityview_register_placeholder_templates() {

	require_once GRAVITYVIEW_DIR . 'includes/class-gravityview-placeholder-template.php';

	$placeholders = array(
		'GravityView_DataTables_Template' => array(
			'slug' => 'dt_placeholder',
			'label' =>  __( 'DataTables Table', 'gv-datatables', 'gravityview' ),
			'description' => __('Display items in a dynamic table powered by DataTables.', 'gravityview'),
			'logo' => plugins_url('assets/images/templates/logo-datatables.png', GRAVITYVIEW_FILE ),
			'buy_source' => 'https://gravityview.co/pricing/?utm_source=plugin&utm_medium=buy_now&utm_campaign=view_type&utm_term=datatables',
			'preview' => 'https://try.gravityview.co/demo/view/datatables/?utm_source=plugin&utm_medium=try_demo&utm_campaign=view_type&utm_term=datatables',
			'license' => esc_html__( 'All Access', 'gravityview' ),
			'price_id' => 2,
			'textdomain' => 'gv-datatables',
		),
		'GravityView_Maps_Template_Map_Default' => array(
			'slug' => 'map_placeholder',
			'label' =>  __( 'Map', 'gravityview-maps', 'gravityview' ),
			'description' => __( 'Display entries on a map.', 'gravityview' ),
			'logo' => plugins_url( 'assets/images/templates/default-map.png', GRAVITYVIEW_FILE ),
			'buy_source' => 'https://gravityview.co/pricing/?utm_source=plugin&utm_medium=buy_now&utm_campaign=view_type&utm_term=map',
			'preview' => 'https://try.gravityview.co/demo/view/map/?utm_source=plugin&utm_medium=try_demo&utm_campaign=view_type&utm_term=map',
			'license' => esc_html__( 'All Access', 'gravityview' ),
			'price_id' => 2,
			'textdomain' => 'gravityview-maps',
		),
		'GravityView_DIY_Template' => array(
			'slug'        => 'diy_placeholder',
			'label'       => _x( 'DIY', 'DIY means "Do It Yourself"', 'gravityview' ),
			'description' => esc_html__( 'A flexible, powerful layout for designers & developers.', 'gravityview' ),
			'buy_source' => 'https://gravityview.co/pricing/?utm_source=plugin&utm_medium=buy_now&utm_campaign=view_type&utm_term=diy',
			'logo' => plugins_url( 'assets/images/templates/logo-diy.png', GRAVITYVIEW_FILE ),
			'preview' => 'https://try.gravityview.co/demo/view/diy/?utm_source=plugin&utm_medium=try_demo&utm_campaign=view_type&utm_term=diy',
			'license' => esc_html__( 'All Access', 'gravityview' ),
			'price_id' => 2,
			'textdomain' => 'gravityview-diy',
		),
	);

	try {

		$license = gravityview()->plugin->settings->get( 'license_key_response', array() );

		// If the license is for Core, show placeholder. Otherwise, show Extensions page
		foreach ( $placeholders as $class_name => $placeholder ) {

			if ( class_exists( $class_name ) ) {
				continue;
			}

			$license_price_id = (int) \GV\Utils::get( $license, 'price_id', 0 );
			$placeholder_price_id = (int) \GV\Utils::get( $placeholder, 'price_id' );

			$placeholder['type']     = 'custom';
			$placeholder['included'] = ( $license_price_id >= $placeholder_price_id );

			new GravityView_Placeholder_Template( $placeholder['slug'], $placeholder );
		}

	} catch ( Exception $exception ) {
		gravityview()->log->critical( $exception->getMessage() );
	}

}
