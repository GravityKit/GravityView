<?php
/**
 * GravityView preset template
 *
 * @file      class-gravityview-preset-website-showcase.php
 * @since     1.15
 * @license   GPL2+
 * @author    GravityView <hello@gravityview.co>
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @package   GravityView
 */

use GravityKit\GravityView\LayoutBuilder\Extension;
use GV\Grid;

/**
 * GravityView_Default_Template_Edit class.
 * Defines Edit Table(default) template (Edit Entry) - this is not visible; it's an internal template only.
 */
class GravityView_Default_Template_Search extends GravityView_Template {

	function __construct( $id = 'search', $settings = [] ) {
		$search_settings = [
			'slug'        => 'search',
			'type'        => 'internal',
			'label'       => __( 'Search', 'gk-gravityview' ),
			'description' => __( 'Display a search bar.', 'gk-gravityview' ),
			'logo'        => plugins_url( 'includes/presets/default-table/logo-default-table.png', GRAVITYVIEW_FILE ),
			'css_source'  => gravityview_css_url( 'table-view.css', GRAVITYVIEW_DIR . 'templates/css/' ),
		];

		$settings = wp_parse_args( $settings, $search_settings );

		/**
		 * @see  GravityView_Admin_Views::get_default_field_options() for Generic Field Options
		 * @var array
		 */
		$field_options = [];

		$areas = [ Grid::get_row_by_type( '100' ) ];

		parent::__construct( $id, $settings, $field_options, $areas );
	}
}

new GravityView_Default_Template_Search();
