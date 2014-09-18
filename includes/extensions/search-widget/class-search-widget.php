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
			'search_fields' => array(
				'type' => 'hidden',
				'label' => '',
				'class' => 'gv-search-fields-value'
			),
			'search_layout' => array(
				'type' => 'select',
				'label' => __( 'Search Layout', 'gravity-view' ),
				'options' => array(
					'horizontal' => __( 'Horizontal', 'gravity-view' ),
					'vertical' => __( 'Vertical', 'gravity-view' )
				),
			),
		);
		parent::__construct( __( 'New Search Bar', 'gravity-view' ) , 'search_widget', $default_values, $settings );


	//	add_filter( 'gravityview_fe_search_criteria', array( $this, 'filter_entries' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts_and_styles' ), 999 );
		add_filter( 'gravityview_noconflict_scripts', array( $this, 'register_no_conflict') );

		// ajax - get the searchable fields
		add_action( 'wp_ajax_gv_searchable_fields', array( 'GravityView_Widget_Search', 'get_searchable_fields' ) );

	}

	static function getInstance() {
		if( empty( self::$instance ) ) {
			self::$instance = new GravityView_Widget_Search;
		}
		return self::$instance;
	}


	/**
	 * Add script to Views edit screen (admin)
	 * @param  mixed $hook
	 */
	function add_scripts_and_styles( $hook ) {
		// Don't process any scripts below here if it's not a GravityView page.
		if( !gravityview_is_admin_page( $hook ) ) { return; }

		$script_min = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
		$script_source = empty( $script_min ) ? '/source' : '';
		wp_enqueue_script( 'gravityview_searchwidget_admin', plugins_url( 'assets/js'.$script_source.'/admin-search-widget'.$script_min.'.js', __FILE__ ), array( 'jquery' ), GravityView_Plugin::version );


		$input_types = array(
			'text' => array( 'input_text' => esc_html__( 'Text', 'gravity-view') ),
			'date' => array( 'date' => esc_html__('Date', 'gravity-view') ),
			'multi' => array( 'select' => esc_html__( 'Select', 'gravity-view' ), 'multi_select' => esc_html__( 'Select (multiple values)', 'gravity-view' ), 'radio' => esc_html__('Radio button', 'gravity-view'), 'checkbox' => esc_html__( 'Checkbox', 'gravity-view' ) )
			);


		wp_localize_script( 'gravityview_searchwidget_admin', 'gvSearchVar', array(
			'nonce' => wp_create_nonce( 'gravityview_ajaxsearchwidget'),
			'label_nofields' =>  esc_html__( 'No search fields configured yet.', 'gravity-view' ),
			'label_addfield' =>  esc_html__( 'Add Search Field', 'gravity-view' ),
			'label_searchfield' => esc_html__( 'Search Field', 'gravity-view' ),
			'label_inputtype' => esc_html__( 'Input Type', 'gravity-view' ),
			'inputs' => json_encode( $input_types ),
		) );

	}

	/**
	 * Add admin script to the whitelist
	 */
	function register_no_conflict( $required ) {
		$required[] = 'gravityview_searchwidget_admin';
		return $required;
	}

	/**
	 * Ajax
	 * Returns the form fields ( only the searchable ones )
	 *
	 * @access public
	 * @return void
	 */
	static function get_searchable_fields() {

		if( empty( $_POST['formid'] ) ) {
			exit(0);
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajaxsearchwidget' ) ) {
			exit(0);
		}

		// fetch form id assigned to the view
		$response = GravityView_Widget_Search::render_searchable_fields( $_POST['formid'] );

		exit( $response );
	}


	static function render_searchable_fields( $form_id = null, $current = '' ) {

		if( is_null( $form_id ) ) {
			return '';
		}

		// Get fields with sub-inputs and no parent
		$fields = gravityview_get_form_fields( $form_id, true, false );


		// start building output

		$output = '<select class="gv-search-fields">';

		$output .= '<option value="search_all" '. selected( 'search_all', $current, false ).' data-type="text">'. esc_html__( 'Search Everything', 'gravity-view') .'</option>';

		if( !empty( $fields ) ) {

			$blacklist_field_types = apply_filters( 'gravityview_blacklist_field_types', array() );

			foreach( $fields as $id => $field ) {

				if( in_array( $field['type'], $blacklist_field_types ) ) { continue; }

				$type = GravityView_Widget_Search::get_search_input_type( $field['type'] );

				$output .= '<option value="'. $id .'" '. selected( $id, $current, false ).'data-type="'. esc_attr( $type ) .'">'. esc_html( $field['label'] ) .'</option>';
			}

		}

		$output .= '</select>';

		return $output;

	}


	static function get_search_input_type( $field_type = null ) {

		if( in_array( $field_type, array( 'select', 'checkbox', 'radio', 'post_category', 'multiselect' ) ) ) {
			$type = 'multi';
		} elseif( in_array( $field_type, array( 'date' ) ) ) {
			$type = 'date';
		} else {
			$type = 'text';
		}

		return apply_filters( 'gravityview/extension/search/input_type', $type, $field_type );
	}


} // end class

new GravityView_Widget_Search;

endif; // class exists
