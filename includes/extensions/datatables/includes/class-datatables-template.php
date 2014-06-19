<?php
/**
 * GravityView Extension -- DataTables -- Template
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.3
 */



/**
 * GravityView_Default_Template_Table class.
 * Defines Table(DataTables) template
 */
class GravityView_DataTables_Template extends GravityView_Template {

	function __construct( $id = 'datatables_table', $settings = array(), $field_options = array(), $areas = array() ) {

		if( empty( $settings ) ) {
			$settings = array(
				'slug' => 'table-dt',
				'type' => 'custom',
				'label' =>  __( 'DataTables Table', 'gravity-view' ),
				'description' => __('Display items in a dynamic table powered by DataTables.', 'gravity-view'),
				'logo' => plugins_url('assets/img/logo-datatables.png', GV_DT_FILE ),
				'css_source' => plugins_url('assets/css/datatables.css', GV_DT_FILE ),
			);
		}

		/**
		 * @see  GravityView_Admin_Views::get_default_field_options() for Generic Field Options
		 * @var array
		 */
		$field_options = array(
			'show_as_link' => array( 'type' => 'checkbox', 'label' => __( 'Link to single entry', 'gravity-view' ), 'default' => false, 'context' => 'directory' ),
		);

		$areas = array(
			array(
				'1-1' => array(
					array( 'areaid' => 'table-columns', 'title' => __('Visible Table Columns', 'gravity-view' ) , 'subtitle' => ''  )
				)
			)
		);


		parent::__construct( $id, $settings, $field_options, $areas );

	}

}
new GravityView_DataTables_Template;
