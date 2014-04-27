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

/** Preset templates */

class GravityView_Preset_Business_Data {

	function __construct() {
		$def_template = new GravityView_Default_Template_Table;

		$def_template->template_id = 'preset_business_data';

		$def_template->settings = array(
			'slug' => 'table',
			'type' => 'preset',
			'label' =>  __( 'Business Data', 'gravity-view' ),
			'description' => __( 'Display business information in a table.', 'gravity-view'),
			'logo' => GRAVITYVIEW_URL . 'includes/presets/business-data/logo-business-data.png',
			'preset_form' => GRAVITYVIEW_DIR . 'includes/presets/business-data/form-business-data.xml'
		);

	}
}


class GravityView_Preset_Resume_board {

	function __construct() {
		$def_template = new GravityView_Default_Template_Table;

		$def_template->template_id = 'preset_resume_board';

		$def_template->settings = array(
			'slug' => 'table',
			'type' => 'preset',
			'label' =>  __( 'Resume Board', 'gravity-view' ),
			'description' => __( 'Allow job-seekers to post their resumes.', 'gravity-view'),
			'logo' => GRAVITYVIEW_URL . 'includes/presets/resume-board/logo-resume-board.png',
			'preset_form' => GRAVITYVIEW_DIR . 'includes/presets/resume-board/form-resume-board.xml'
		);

	}
}

class GravityView_Preset_Job_Board {

	function __construct() {
		$def_template = new GravityView_Default_Template_Table;

		$def_template->template_id = 'preset_job_board';

		$def_template->settings = array(
			'slug' => 'table',
			'type' => 'preset',
			'label' =>  __( 'Job Board', 'gravity-view' ),
			'description' => __( 'Post available jobs in a simple job board.', 'gravity-view'),
			'logo' => GRAVITYVIEW_URL . 'includes/presets/job-board/logo-job-board.png',
			'preset_form' => GRAVITYVIEW_DIR . 'includes/presets/job-board/form-job-board.xml'

		);

	}
}

class GravityView_Preset_Business_Listings {

	function __construct() {
		$def_template = new GravityView_Default_Template_Table;

		$def_template->template_id = 'preset_business_listings';

		$def_template->settings = array(
			'slug' => 'table',
			'type' => 'preset',
			'label' =>  __( 'Business Listings', 'gravity-view' ),
			'description' => __( 'Display business profiles.', 'gravity-view'),
			'logo' => GRAVITYVIEW_URL . 'includes/presets/business-listings/logo-business-listings.png',
			'preset_form' => GRAVITYVIEW_DIR . 'includes/presets/business-listings/form-business-listings.xml'
		);

	}
}




/** Simple customizable templates: table and list */

/**
 * GravityView_Default_Template_Table class.
 * Defines Table(default) template
 */
class GravityView_Default_Template_Table extends GravityView_Template {

	function __construct() {
		$settings = array(
			'slug' => 'table',
			'type' => 'custom',
			'label' =>  __( 'Table (default)', 'gravity-view' ),
			'description' => __('Display items in a table view.', 'gravity-view'),
			'logo' => GRAVITYVIEW_URL . 'images/placeholder.png'
		);

		$field_options = array(
			'show_as_link' => array( 'type' => 'checkbox', 'label' => __( 'Link to single entry', 'gravity-view' ), 'default' => false ),
		);

		$areas = array(
			array( '1-1' => array( array( 'areaid' => 'table-columns', 'title' => __('Visible Table Columns', 'gravity-view' ) , 'subtitle' => ''  ) ) )
		);



		parent::__construct( 'default_table', $settings, $field_options, $areas );

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
			'type' => 'custom',
			'label' =>  __( 'List (default)', 'gravity-view' ),
			'description' => __('Display items in a listing view.', 'gravity-view'),
			'logo' => GRAVITYVIEW_URL . 'images/placeholder.png',
			'css_source' => GRAVITYVIEW_URL . 'templates/css/list-view.css',
		);

		$field_options = array(
			'show_as_link' => array( 'type' => 'checkbox', 'label' => __( 'Link to single entry', 'gravity-view' ), 'default' => false ),
		);

		$areas = array(
			array( '1-3' => array( array( 'areaid' => 'list-image', 'title' => __( 'Image', 'gravity-view' ) , 'subtitle' => '' ) ),
				'2-3' => array( array( 'areaid' => 'list-title', 'title' => __('Listing Title', 'gravity-view' ) , 'subtitle' => 'Large Font' ), array( 'areaid' => 'list-subtitle', 'title' => __('Subheading', 'gravity-view' ) , 'subtitle' => 'Data placed here will be bold.' ), array( 'areaid' => 'list-description', 'title' => __('Description', 'gravity-view' ) , 'subtitle' => 'Below the subheading, a good place for description and other data.' ) )	),
			array( '1-2' => array( array( 'areaid' => 'list-footer-left', 'title' => __('Footer Left', 'gravity-view' ) , 'subtitle' => '' ) ),
				'2-2' => array( array( 'areaid' => 'list-footer-right', 'title' => __('Footer Right', 'gravity-view' ) , 'subtitle' => ''  ) ) )
		);

		parent::__construct( 'default_list', $settings, $field_options, $areas );

	}
}


class GravityView_Template {

	// template unique id
	public $template_id;

	// define template settings
	public $settings;
	/**
	 * $settings:
	 * slug - template slug (frontend)
	 * css_source - url path to CSS file, to be enqueued (frontend)
	 * type - 'custom' or 'preset' (admin)
	 * label - template nicename (admin)
	 * description - short about text (admin)
	 * logo - template icon (admin)
	 * preview - template image for previewing (admin)
	 * buy_source - url source for buying this template
	 * preset_form - path to Gravity Form form XML file
	 * preset_config - path to View config (XML)
	 *
	 */

	// form fields extra options
	public $field_options;

	// define the active areas
	public $active_areas;


	function __construct( $id, $settings = array(), $field_options = array(), $areas ) {

		if( empty( $id ) ) {
			return;
		}

		$this->template_id = $id;

		$this->settings = wp_parse_args( $settings, array( 'slug' => '', 'css_source' => '', 'type' => '', 'label' => '', 'description' => '', 'logo' => '', 'preview' => '', 'buy_source' => '', 'preset_form' => '' ) );

		$this->field_options = $field_options;
		$this->active_areas = $areas;

		add_filter( 'gravityview_register_directory_template', array( $this, 'register_template' ) );

		// presets hooks:
		// form xml
		add_filter( 'gravityview_template_formxml', array( $this, 'assign_form_xml' ), 10 , 2);

		// fields config xml


		// assign active areas
		add_filter( 'gravityview_template_active_areas', array( $this, 'assign_active_areas' ), 10, 2 );

		// field options
		add_filter( 'gravityview_template_field_options', array( $this, 'assign_field_options' ), 10, 2 );

		// template slug
		add_filter( "gravityview_template_slug_{$id}", array( $this, 'assign_view_slug' ), 10, 2 );

		// register template CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ) );
	}


	/**
	 * Register the template to display in the admin
	 *
	 * @access private
	 * @param mixed $templates
	 * @return void
	 */
	public function register_template( $templates ) {
		$templates[ $this->template_id ] = $this->settings;
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


	public function assign_form_xml( $xml = '' , $template = '' ) {
		if( $this->settings['type'] === 'preset' && !empty( $this->settings['preset_form'] ) && $this->template_id === $template ) {
			return $this->settings['preset_form'];
		}

		return $xml;
	}


	/**
	 * Assign the template slug when loading the presentation template (frontend)
	 *
	 * @access protected
	 * @param mixed $default
	 * @return void
	 */
	public function assign_view_slug( $default, $context ) {

		if( !empty( $this->settings['slug'] ) ) {
			return $this->settings['slug'];
		}
		if( !empty( $default ) ) {
			return $default;
		}
		// last resort, template_id
		return $this->template_id;
	}

	/**
	 * Register styles
	 * @return void
	 */
	public function register_styles() {
		if( !empty( $this->settings['css_source'] ) ) {
			wp_register_style( 'gravityview_style_' . $this->template_id, $this->settings['css_source'], array(), null, 'all' );
		}
	}



}

new GravityView_Default_Template_Table;
new GravityView_Default_Template_List;

//presets
new GravityView_Preset_Resume_board();
new GravityView_Preset_Job_Board();
new GravityView_Preset_Business_Listings();
new GravityView_Preset_Business_Data();

