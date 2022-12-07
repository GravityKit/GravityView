<?php
/**
 * GravityView preset template
 *
 * @file class-gravityview-preset-website-showcase.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityView <hello@gravityview.co>
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15
 */

/**
 * GravityView_Default_Template_Edit class.
 * Defines Edit Table(default) template (Edit Entry) - this is not visible; it's an internal template only.
 */
class GravityView_Default_Template_Search extends GravityView_Template {

	function __construct( $id = 'search', $settings = array(), $field_options = array(), $areas = array() ) {

		$edit_settings = array(
			'slug' => 'search',
			'type' => 'internal',
			'label' =>  __( 'Search', 'gk-gravityview' ),
			'description' => __('Display a search bar.', 'gk-gravityview'),
			'logo' => plugins_url('includes/presets/default-table/logo-default-table.png', GRAVITYVIEW_FILE),
			'css_source' => gravityview_css_url( 'table-view.css', GRAVITYVIEW_DIR . 'templates/css/' ),
		);

		$settings = wp_parse_args( $settings, $edit_settings );

		/**
		 * @see  GravityView_Admin_Views::get_default_field_options() for Generic Field Options
		 * @var array
		 */
		$field_options = array();

		$areas = array(
			array(
				'1-1' => array(
					array(
						'areaid' => 'search-fields',
						'title' => __('Search Fields', 'gk-gravityview' )
					)
				)
			)
		);


		parent::__construct( $id, $settings, $field_options, $areas );

	}

}

new GravityView_Default_Template_Search;
