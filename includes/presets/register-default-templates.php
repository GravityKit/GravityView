<?php
/**
 * GravityView default templates and generic template class
 *
 * @file      register-default-templates.php
 * @since     2.10
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @package   GravityView
 */

// Load default templates
add_action( 'init', 'gravityview_register_default_templates', 11 );

/**
 * Registers the default templates
 *
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
		'GravityView_DataTables_Template'       => array(
			'slug'        => 'dt_placeholder',
			'template_id' => 'datatables_table',
			'download_id' => 268,
			'label'       => __( 'DataTables Table', 'gv-datatables', 'gk-gravityview' ),
			'description' => __( 'Display items in a dynamic table powered by DataTables.', 'gk-gravityview' ),
			'logo'        => plugins_url( 'assets/images/templates/logo-datatables.png', GRAVITYVIEW_FILE ),
			'buy_source'  => 'https://www.gravitykit.com/pricing/?utm_source=plugin&utm_medium=buy_now&utm_campaign=view_type&utm_term=datatables',
			'preview'     => 'https://try.gravitykit.com/demo/view/datatables/?utm_source=plugin&utm_medium=try_demo&utm_campaign=view_type&utm_term=datatables',
			'license'     => esc_html__( 'All Access', 'gk-gravityview' ),
			'price_id'    => 2,
			'textdomain'  => 'gv-datatables|gk-datatables',
		),
		'GravityView_Maps_Template_Map_Default' => array(
			'slug'        => 'map_placeholder',
			'template_id' => 'map',
			'download_id' => 27,
			'label'       => __( 'Map', 'gravityview-maps', 'gk-gravityview' ),
			'description' => __( 'Display entries on a map.', 'gk-gravityview' ),
			'logo'        => plugins_url( 'assets/images/templates/default-map.png', GRAVITYVIEW_FILE ),
			'buy_source'  => 'https://www.gravitykit.com/pricing/?utm_source=plugin&utm_medium=buy_now&utm_campaign=view_type&utm_term=map',
			'preview'     => 'https://try.gravitykit.com/demo/view/map/?utm_source=plugin&utm_medium=try_demo&utm_campaign=view_type&utm_term=map',
			'license'     => esc_html__( 'All Access', 'gk-gravityview' ),
			'price_id'    => 2,
			'textdomain'  => 'gravityview-maps|gk-gravitymaps',
		),
		'GravityView_DIY_Template'              => array(
			'slug'        => 'diy_placeholder',
			'template_id' => 'diy',
			'download_id' => 550152,
			'label'       => _x( 'DIY', 'DIY means "Do It Yourself"', 'gk-gravityview' ),
			'description' => esc_html__( 'A flexible, powerful layout for designers & developers.', 'gk-gravityview' ),
			'buy_source'  => 'https://www.gravitykit.com/pricing/?utm_source=plugin&utm_medium=buy_now&utm_campaign=view_type&utm_term=diy',
			'logo'        => plugins_url( 'assets/images/templates/logo-diy.png', GRAVITYVIEW_FILE ),
			'preview'     => 'https://try.gravitykit.com/demo/view/diy/?utm_source=plugin&utm_medium=try_demo&utm_campaign=view_type&utm_term=diy',
			'license'     => esc_html__( 'All Access', 'gk-gravityview' ),
			'textdomain'  => 'gravityview-diy|gk-diy',
		),
	);

	if ( ! class_exists( 'GravityKitFoundation' ) ) {
		return;
	}

	$product_manager = GravityKitFoundation::licenses()->product_manager();

	if ( ! $product_manager ) {
		return;
	}

	try {
		$products_data = $product_manager->get_products_data( array( 'key_by' => 'id' ) );
	} catch ( Exception $e ) {
		$products_data = array();
	}

	foreach ( $placeholders as $placeholder ) {
		if ( GravityKit\GravityView\Foundation\Helpers\Arr::get( $products_data, "{$placeholder['download_id']}.active" ) ) {
			// Template will be loaded by the extension.
			continue;
		}

		$placeholder['type']     = 'custom';
		$placeholder['included'] = ! empty( GravityKitFoundation::helpers()->array->get( $products_data, "{$placeholder['download_id']}.licenses" ) );

		new GravityView_Placeholder_Template( $placeholder['slug'], $placeholder );
	}
}
