<?php

/**
 * GravityView_Default_Template_Table class.
 * Defines Table(default) template
 */
class GravityView_Default_Template_Table extends GravityView_Template {

	function __construct( $id = 'default_table', $settings = array(), $field_options = array(), $areas = array() ) {

		$table_settings = array(
			'slug'        => 'table',
			'type'        => 'custom',
			'label'       => __( 'Table (default)', 'gravityview' ),
			'description' => __( 'Display items in a table view.', 'gravityview' ),
			'logo'        => plugins_url( 'includes/presets/default-table/logo-default-table.png', GRAVITYVIEW_FILE ),
			'css_source'  => gravityview_css_url( 'table-view.css', GRAVITYVIEW_DIR . 'templates/css/' ),
		);

		$settings = wp_parse_args( $settings, $table_settings );

		/**
		 * @see  GravityView_Admin_Views::get_default_field_options() for Generic Field Options
		 * @var array
		 */
		$field_options = array(
			'show_as_link' => array(
				'type'    => 'checkbox',
				'label'   => __( 'Link to single entry', 'gravityview' ),
				'value'   => false,
				'context' => 'directory'
			),
		);

		$areas = array(
			array(
				'1-1' => array(
					array(
						'areaid'   => 'table-columns',
						'title'    => __( 'Visible Table Columns', 'gravityview' ),
						'subtitle' => __( 'Each field will be displayed as a column in the table.', 'gravityview' ),
					)
				)
			)
		);


		parent::__construct( $id, $settings, $field_options, $areas );

	}
}

new GravityView_Default_Template_Table;