<?php
/**
 * The GravityView New Search widget
 *
 * @package   GravityView-DataTables-Ext
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if( class_exists('GravityView_Widget') ):

class GravityView_Widget_Search extends GravityView_Widget {

	static $file;
	static $instance;

	function __construct() {

		self::$instance = &$this;

		self::$file = plugin_dir_path( __FILE__ );

		$default_values = array( 'header' => 0, 'footer' => 0 );

		$settings = array(
			'search_filters' => array(
				'type' => 'hidden',
				'label' => '',
			),
		);
		parent::__construct( __( 'New Search Bar', 'gravity-view' ) , 'search_widget', $default_values, $settings );


	//	add_filter( 'gravityview_fe_search_criteria', array( $this, 'filter_entries' ) );




	}

	static function getInstance() {
		if( empty( self::$instance ) ) {
			self::$instance = new GravityView_Widget_Search;
		}
		return self::$instance;
	}







} // end class

new GravityView_Widget_Search;

endif; // class exists
