<?php
/**
 * GravityView default templates and generic template class
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2013, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */


/*
Generic Field Options (as defined in Gravityview_Admin_Views class)

$field_options = array( 
	'show_label' => array( 'type' => 'checkbox', 'label' => __( 'Show Label', 'gravity-view' ), 'default' => true ),
	'custom_label' => array( 'type' => 'input_text', 'label' => __( 'Custom Label:', 'gravity-view' ), 'default' => '' ),
	'custom_class' => array( 'type' => 'input_text', 'label' => __( 'Custom CSS Class:', 'gravity-view' ), 'default' => '' ),
	'show_as_link' => array( 'type' => 'checkbox', 'label' => __( 'Link to single entry', 'gravity-view' ), 'default' => false ),
	
);


*/

/**
 * GravityView_Default_Template_Table class.
 * Defines Table(default) template
 */
class GravityView_Default_Template_Table extends GravityView_Template {

	function __construct() {
		$settings = array(
			'slug' => 'table', 
			'type' => 'directory',
			'label' =>  __( 'Table (default)', 'gravity-view' ),
			'preview' => GRAVITYVIEW_URL . 'images/preview_table_directory.jpg'
		);
		
		$field_options = array(
			'show_as_link' => array( 'type' => 'checkbox', 'label' => __( 'Link to single entry', 'gravity-view' ), 'default' => false ),
		);
		
		$areas = array( array( 'id' => 'gv-table-columns', 'areaid' => 'table-columns', 'label' => __( 'Visible Table Columns', 'gravity-view') ) );
	
		parent::__construct( 'default_table', $settings, $field_options, $areas );
	
	}
	
}

// Table - single entry view
class GravityView_Default_Template_Table_Single extends GravityView_Template {

	function __construct() {
		$settings = array(
			'slug' => 'table', 
			'type' => 'single',
			'label' =>  __( 'Table (default)', 'gravity-view' ),
			'preview' => GRAVITYVIEW_URL . 'images/preview_table_directory.jpg'
		);
		
		$field_options = array();
		
		$areas = array( array( 'id' => 'gv-table-columns-single', 'areaid' => 'table-columns-single', 'label' => __( 'Visible Table Columns', 'gravity-view') ) );
	
		parent::__construct( 'default_s_table', $settings, $field_options, $areas );
	
	}
	
}


/**
 * GravityView_Default_Template_List class.
 * Defines List (default) template
 */
class GravityView_Default_Template_List extends GravityView_Template {

	function __construct() {
		$settings = array(
			'slug' => 'list', 
			'type' => 'directory',
			'label' =>  __( 'List (default)', 'gravity-view' ),
			'preview' => GRAVITYVIEW_URL . 'images/preview_list_directory.jpg'
		);
	
		$field_options = array(
			'show_as_link' => array( 'type' => 'checkbox', 'label' => __( 'Link to single entry', 'gravity-view' ), 'default' => false ),
		);
		
		$areas = array( 
			array( 'id' => 'gv-list-title', 'areaid' => 'list-title', 'label' => __( 'Entry title', 'gravity-view') ), 
			array( 'id' => 'gv-list-content', 'areaid' => 'list-content', 'label' => __( 'Entry Content', 'gravity-view') ), 
			array( 'id' => 'gv-list-footer', 'areaid' => 'list-footer', 'label' => __( 'Entry Footer', 'gravity-view') ),
		);
		
		parent::__construct( 'default_list', $settings, $field_options, $areas );

	}
}

// List - single entry view
class GravityView_Default_Template_List_Single extends GravityView_Template {

	function __construct() {
		$settings = array(
			'slug' => 'list', 
			'type' => 'single',
			'label' =>  __( 'List (default)', 'gravity-view' ),
			'preview' => GRAVITYVIEW_URL . 'images/preview_list_directory.jpg'
		);
		
		$field_options = array();
		
		$areas = array( 
			array( 'id' => 'gv-single-list-title', 'areaid' => 'single-list-title', 'label' => __( 'Entry title', 'gravity-view') ), 
			array( 'id' => 'gv-single-list-content', 'areaid' => 'single-list-content', 'label' => __( 'Entry Content', 'gravity-view') ), 
			array( 'id' => 'gv-single-list-footer', 'areaid' => 'single-list-footer', 'label' => __( 'Entry Footer', 'gravity-view') ),
		);
		
		parent::__construct( 'default_s_list', $settings, $field_options, $areas );

	}
}


class GravityView_Template {
	
	// template unique id
	private $template_id;
	
	// define template slug
	protected $template_slug;
	
	// template presentation label
	protected $template_label;
	
	// template type, possible values single or directory view
	private $template_type;
	
	// form fields extra options
	protected $field_options;
	
	// define the active areas
	protected $active_areas;
	
	
	function __construct( $id, $settings = array(), $field_options = array(), $areas ) {
		
		if( empty( $id ) ) {
			return;
		}
		
		extract( wp_parse_args( $settings, array( 'slug' => '', 'type' => '', 'label' => '', 'preview' => '' ) ) );
		
		// assign values
		$this->template_id = $id;
		$this->template_slug = $slug;
		$this->template_type = $type;
		$this->template_label = $label;
		$this->template_preview = $preview;
		$this->field_options = $field_options;
		$this->active_areas = $areas;
		
		
		// register template
		if( 'single' == $type ) {
			add_filter( 'gravityview_register_single_template', array( $this, 'register_template' ) );
		} else {
			add_filter( 'gravityview_register_directory_template', array( $this, 'register_template' ) );
		}
		
		// assign active areas
		add_filter( 'gravityview_template_active_areas', array( $this, 'assign_active_areas' ), 10, 2 );
		
		// field options
		add_filter( 'gravityview_template_field_options', array( $this, 'assign_field_options' ), 10, 2 );
		
		// template slug
		add_filter( "gravityview_template_slug_{$id}", array( $this, 'assign_view_slug' ), 10, 1 );
		
		
	}
	
	
	/**
	 * Register the template to display in the admin
	 * 
	 * @access private
	 * @param mixed $templates
	 * @return void
	 */
	public function register_template( $templates ) {
		$templates[ $this->template_id ] = array( 'label' => $this->template_label, 'preview' => $this->template_preview );
		return $templates;
	}
	
	
	/**
	 * Assign active areas (for admin configuration)
	 * 
	 * @access protected
	 * @param array $areas
	 * @param string $template (default: '')
	 * @return void
	 */
	public function assign_active_areas( $areas, $template = '' ) {
		if( $this->template_id === $template ) {
			$areas = $this->active_areas;
		}
		return $areas;
	}
	
	
	/**
	 * Assign template specific field options
	 * 
	 * @access protected
	 * @param array $options (default: array())
	 * @param string $template (default: '')
	 * @return void
	 */
	public function assign_field_options( $options = array(), $template = '' ) {
		
		if( $this->template_id === $template ) {
			$options = array_merge( $options, $this->field_options );
		}

		return $options;
	}
	
	
	/**
	 * Assign the template slug when loading the presentation template (frontend)
	 * 
	 * @access protected
	 * @param mixed $default
	 * @return void
	 */
	public function assign_view_slug( $default ) {
		
		if( !empty( $this->template_slug ) ) {
			return $this->template_slug;
		}
		if( !empty( $default ) ) {
			return $default;
		}
		// last resort, template_id
		return $this->template_id;
	}
	
	
	
}






