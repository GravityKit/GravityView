<?php
/**
 * GravityView default templates and generic template class
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */


/**
 * Simple customizable templates: table and list
 */

/**
 * GravityView_Default_Template_Table class.
 * Defines Table(default) template
 */
class GravityView_Default_Template_Table extends GravityView_Template {

	function __construct( $id = 'default_table', $settings = array(), $field_options = array(), $areas = array() ) {

		$table_settings = array(
			'slug' => 'table',
			'type' => 'custom',
			'label' =>  __( 'Table (default)', 'gravityview' ),
			'description' => __('Display items in a table view.', 'gravityview'),
			'logo' => plugins_url('includes/presets/default-table/logo-default-table.png', GRAVITYVIEW_FILE),
			'css_source' => plugins_url('templates/css/table-view.css', GRAVITYVIEW_FILE),
		);

		$settings = wp_parse_args( $settings, $table_settings );

		/**
		 * @see  GravityView_Admin_Views::get_default_field_options() for Generic Field Options
		 * @var array
		 */
		$field_options = array(
			'show_as_link' => array(
				'type' => 'checkbox',
				'label' => __( 'Link to single entry', 'gravityview' ),
				'value' => false,
				'context' => 'directory'
			),
		);

		$areas = array(
			array(
				'1-1' => array(
					array(
						'areaid' => 'table-columns',
						'title' => __('Visible Table Columns', 'gravityview' ) ,
						'subtitle' => __('Each field will be displayed as a column in the table.', 'gravityview'),
					)
				)
			)
		);


		parent::__construct( $id, $settings, $field_options, $areas );

	}

}

/**
 * GravityView_Default_Template_Edit class.
 * Defines Edit Table(default) template (Edit Entry)
 */
class GravityView_Default_Template_Edit extends GravityView_Template {

	function __construct( $id = 'default_table_edit', $settings = array(), $field_options = array(), $areas = array() ) {

		$edit_settings = array(
			'slug' => 'edit',
			'type' => 'internal',
			'label' =>  __( 'Edit Table', 'gravityview' ),
			'description' => __('Display items in a table view.', 'gravityview'),
			'logo' => plugins_url('includes/presets/default-table/logo-default-table.png', GRAVITYVIEW_FILE),
			'css_source' => plugins_url('templates/css/table-view.css', GRAVITYVIEW_FILE),
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
						'areaid' => 'edit-fields',
						'title' => __('Visible Edit Fields', 'gravityview' )
					)
				)
			)
		);


		parent::__construct( $id, $settings, $field_options, $areas );

	}

}



/**
 * GravityView_Default_Template_List class.
 * Defines List (default) template
 */
class GravityView_Default_Template_List extends GravityView_Template {

	function __construct( $id = 'default_list', $settings = array(), $field_options = array(), $areas = array() ) {

		$list_settings = array(
			'slug' => 'list',
			'type' => 'custom',
			'label' =>  __( 'List (default)', 'gravityview' ),
			'description' => __('Display items in a listing view.', 'gravityview'),
			'logo' => plugins_url('includes/presets/default-list/logo-default-list.png', GRAVITYVIEW_FILE),
			'css_source' => plugins_url('templates/css/list-view.css', GRAVITYVIEW_FILE),
		);

		$settings = wp_parse_args( $settings, $list_settings );

		$field_options = array(
			'show_as_link' => array(
				'type' => 'checkbox',
				'label' => __( 'Link to single entry', 'gravityview' ),
				'value' => false,
				'context' => 'directory'
			),
		);

		$areas = array(
			array(
				'1-1' => array(
					array( 'areaid' => 'list-title', 'title' => __('Listing Title', 'gravityview' ) , 'subtitle' => '' ),
					array( 'areaid' => 'list-subtitle', 'title' => __('Subheading', 'gravityview' ) , 'subtitle' => 'Data placed here will be bold.' ),
				),
				'1-3' => array(
					array( 'areaid' => 'list-image', 'title' => __( 'Image', 'gravityview' ) , 'subtitle' => 'Leave empty to remove.' )
				),
				'2-3' => array(
					array( 'areaid' => 'list-description', 'title' => __('Other Fields', 'gravityview' ) , 'subtitle' => 'Below the subheading, a good place for description and other data.' ) )
				),
			array(
				'1-2' => array(
					array( 'areaid' => 'list-footer-left', 'title' => __('Footer Left', 'gravityview' ) , 'subtitle' => '' )
				),
				'2-2' => array(
					array( 'areaid' => 'list-footer-right', 'title' => __('Footer Right', 'gravityview' ) , 'subtitle' => ''  )
				)
			)
		);

		parent::__construct( $id, $settings, $field_options, $areas );

	}
}


abstract class GravityView_Template {

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


	function __construct( $id, $settings = array(), $field_options = array(), $areas = array() ) {

		if( empty( $id ) ) {
			return;
		}

		$this->template_id = $id;

		$this->merge_defaults( $settings );

		$this->field_options = $field_options;
		$this->active_areas = $areas;

		add_filter( 'gravityview_register_directory_template', array( $this, 'register_template' ) );

		// presets hooks:
		// form xml
		add_filter( 'gravityview_template_formxml', array( $this, 'assign_form_xml' ), 10 , 2);
		// fields config xml
		add_filter( 'gravityview_template_fieldsxml', array( $this, 'assign_fields_xml' ), 10 , 2);

		// assign active areas
		add_filter( 'gravityview_template_active_areas', array( $this, 'assign_active_areas' ), 10, 3 );

		// field options
		add_filter( 'gravityview_template_field_options', array( $this, 'assign_field_options' ), 10, 4 );

		// template slug
		add_filter( "gravityview_template_slug_{$id}", array( $this, 'assign_view_slug' ), 10, 2 );

		// register template CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ) );
	}

	/**
	 * Merge the template settings with the default settings
	 *
	 * Sets the `settings` object var.
	 *
	 * @param  array       $settings Defined template settings
	 * @return array                Merged template settings.
	 */
	function merge_defaults( $settings = array() ) {

		$defaults = array(
			'slug' => '',
			'css_source' => '',
			'type' => '',
			'label' => '',
			'description' => '',
			'logo' => '',
			'preview' => '',
			'buy_source' => '',
			'preset_form' => '',
			'preset_fields' => ''
		);

		$this->settings = wp_parse_args( $settings, $defaults);

		return $this->settings;
	}

	/**
	 * Register the template to display in the admin
	 *
	 * @access private
	 * @param mixed $templates
	 * @return array Array of templates available for GV
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
	 * @return array Array of active areas
	 */
	public function assign_active_areas( $areas, $template = '', $context = 'directory' ) {
		if( $this->template_id === $template ) {
			$areas = $this->get_active_areas( $context );
		}
		return $areas;
	}

    public function get_active_areas( $context ) {
        if( isset( $this->active_areas[ $context ] ) ) {
            return $this->active_areas[ $context ];
        } else {
            return $this->active_areas;
        }
    }


	/**
	 * Assign template specific field options
	 *
	 * @access protected
	 * @param array $options (default: array())
	 * @param string $template (default: '')
	 * @param string $field_id key for the field
	 * @param  string|array $context Context for the field; `directory` or `single` for example.
	 * @return array Array of field options
	 */
	public function assign_field_options(  $field_options, $template_id, $field_id = NULL, $context = 'directory' ) {

		if( $this->template_id === $template_id ) {

			foreach ($this->field_options as $key => $field_option) {

				$field_context = rgar($field_option, 'context');

				// Does the field option only apply to a certain context?
				// You can define multiple contexts as an array:  `context => array("directory", "single")`
				$context_matches = is_array($field_context) ? in_array($context, $field_context) : $context === $field_context;

				// If the context matches (or isn't defined), add the field options.
				if($context_matches) {
					$field_options[$key] = $field_option;
				}
			}
		}

		return $field_options;
	}

	/**
	 * Set the Gravity Forms import form information by using the `preset_form` field defined in the template.
	 * @see GravityView_Admin_Views::pre_get_form_fields()
	 * @see GravityView_Admin_Views::create_preset_form()
	 * @return string                Path to XML file
	 */
	public function assign_form_xml( $xml = '' , $template = '' ) {
		if( $this->settings['type'] === 'preset' && !empty( $this->settings['preset_form'] ) && $this->template_id === $template ) {
			return $this->settings['preset_form'];
		}

		return $xml;
	}

	/**
	 * Set the Gravity Forms import form by using the `preset_fields` field defined in the template.
	 * @see GravityView_Admin_Views::pre_get_form_fields()
	 * @return string                Path to XML file
	 */
	public function assign_fields_xml( $xml = '' , $template = '' ) {
		if( $this->settings['type'] === 'preset' && !empty( $this->settings['preset_fields'] ) && $this->template_id === $template ) {
			return $this->settings['preset_fields'];
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
			wp_register_style( 'gravityview_style_' . $this->template_id, $this->settings['css_source'], array(), GravityView_Plugin::version, 'all' );
		}
	}



}

/** Preset templates */

class GravityView_Preset_Business_Data extends GravityView_Default_Template_Table {

	function __construct() {

		$id = 'preset_business_data';

		$settings = array(
			'slug' => 'table',
			'type' => 'preset',
			'label' =>  __( 'Business Data', 'gravityview' ),
			'description' => __( 'Display business information in a table.', 'gravityview'),
			'logo' => plugins_url('includes/presets/business-data/logo-business-data.png', GRAVITYVIEW_FILE),
			'preview' => 'http://demo.gravityview.co/blog/view/business-table/',
			'preset_form' => GRAVITYVIEW_DIR . 'includes/presets/business-data/form-business-data.xml',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/business-data/fields-business-data.xml'
		);

		parent::__construct( $id, $settings );
	}
}


class GravityView_Preset_Resume_Board extends GravityView_Default_Template_Table {

	function __construct() {

		$id = 'preset_resume_board';

		$settings = array(
			'slug' => 'table',
			'type' => 'preset',
			'label' =>  __( 'Resume Board', 'gravityview' ),
			'description' => __( 'Allow job-seekers to post their resumes.', 'gravityview'),
			'logo' => plugins_url('includes/presets/resume-board/logo-resume-board.png', GRAVITYVIEW_FILE),
			'preview' => 'http://demo.gravityview.co/blog/view/resume-board/',
			'preset_form' => GRAVITYVIEW_DIR . 'includes/presets/resume-board/form-resume-board.xml',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/resume-board/fields-resume-board.xml'
		);

		parent::__construct( $id, $settings );

	}
}

class GravityView_Preset_Job_Board extends GravityView_Default_Template_List {

	function __construct() {

		$id = 'preset_job_board';

		$settings = array(
			'slug' => 'list',
			'type' => 'preset',
			'label' =>  __( 'Job Board', 'gravityview' ),
			'description' => __( 'Post available jobs in a simple job board.', 'gravityview'),
			'logo' => plugins_url('includes/presets/job-board/logo-job-board.png', GRAVITYVIEW_FILE),
			'preview' => 'http://demo.gravityview.co/blog/view/job-board/',
			'preset_form' => GRAVITYVIEW_DIR . 'includes/presets/job-board/form-job-board.xml',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/job-board/fields-job-board.xml'

		);

		parent::__construct( $id, $settings );
	}
}

class GravityView_Preset_People_Table extends GravityView_Default_Template_Table {

	function __construct() {

		$id = 'preset_people_table';

		$settings = array(
			'slug' => 'table',
			'type' => 'preset',
			'label' =>  __( 'People Table', 'gravityview' ),
			'description' => __( 'Display information about people in a table.', 'gravityview'),
			'logo' => plugins_url('includes/presets/people-table/logo-people-table.png', GRAVITYVIEW_FILE),
			'preview' => '',
			'preset_form' => GRAVITYVIEW_DIR . 'includes/presets/people-table/form-people-table.xml',
			#'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/people-table/fields-people-table.xml'

		);

		parent::__construct( $id, $settings );

	}
}

class GravityView_Preset_Issue_Tracker extends GravityView_Default_Template_Table {

	function __construct() {

		$id = 'preset_issue_tracker';

		$settings = array(
			'slug' => 'table',
			'type' => 'preset',
			'label' =>  __( 'Issue Tracker', 'gravityview' ),
			'description' => __( 'Manage issues and their statuses.', 'gravityview'),
			'logo' => plugins_url('includes/presets/issue-tracker/logo-issue-tracker.png', GRAVITYVIEW_FILE),
			'preview' => 'http://demo.gravityview.co/blog/view/issue-tracker/',
			'preset_form' => GRAVITYVIEW_DIR . 'includes/presets/issue-tracker/form-issue-tracker.xml',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/issue-tracker/fields-issue-tracker.xml'

		);

		parent::__construct( $id, $settings );

	}
}

class GravityView_Preset_Business_Listings extends GravityView_Default_Template_List {

	function __construct() {

		$id = 'preset_business_listings';

		$settings = array(
			'slug' => 'list',
			'type' => 'preset',
			'label' =>  __( 'Business Listings', 'gravityview' ),
			'description' => __( 'Display business profiles.', 'gravityview'),
			'logo' => plugins_url('includes/presets/business-listings/logo-business-listings.png', GRAVITYVIEW_FILE),
			'preview' => 'http://demo.gravityview.co/blog/view/business-listings/',
			'preset_form' => GRAVITYVIEW_DIR . 'includes/presets/business-listings/form-business-listings.xml',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/business-listings/fields-business-listings.xml'
		);

		parent::__construct( $id, $settings );

	}
}

class GravityView_Preset_Event_Listings extends GravityView_Default_Template_List {

	function __construct() {

		$id = 'preset_event_listings';

		$settings = array(
			'slug' => 'list',
			'type' => 'preset',
			'label' =>  __( 'Event Listings', 'gravityview' ),
			'description' => __( 'Present a list of your events.', 'gravityview'),
			'logo' => plugins_url('includes/presets/event-listings/logo-event-listings.png', GRAVITYVIEW_FILE),
			'preview' => 'http://demo.gravityview.co/blog/view/event-listings/',
			'preset_form' => GRAVITYVIEW_DIR . 'includes/presets/event-listings/form-event-listings.xml',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/event-listings/fields-event-listings.xml'
		);

		parent::__construct( $id, $settings );

	}
}

class GravityView_Preset_Profiles extends GravityView_Default_Template_List {

	function __construct() {

		$id = 'preset_profiles';

		$settings = array(
			'slug' => 'list',
			'type' => 'preset',
			'label' =>  __( 'People Profiles', 'gravityview' ),
			'description' => __( 'List people with individual profiles.', 'gravityview'),
			'logo' => plugins_url('includes/presets/profiles/logo-profiles.png', GRAVITYVIEW_FILE),
			'preview' => 'http://demo.gravityview.co/blog/view/people-profiles/',
			'preset_form' => GRAVITYVIEW_DIR . 'includes/presets/profiles/form-profiles.xml',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/profiles/fields-profiles.xml'
		);

		parent::__construct( $id, $settings );

	}
}

class GravityView_Preset_Staff_Profiles extends GravityView_Default_Template_List {

	function __construct() {

		$id = 'preset_staff_profiles';

		$settings = array(
			'slug' => 'list',
			'type' => 'preset',
			'label' =>  __( 'Staff Profiles', 'gravityview' ),
			'description' => __( 'List members of your team.', 'gravityview'),
			'logo' => plugins_url('includes/presets/staff-profiles/logo-staff-profiles.png', GRAVITYVIEW_FILE),
			'preview' => 'http://demo.gravityview.co/blog/view/staff-profiles/',
			'preset_form' => GRAVITYVIEW_DIR . 'includes/presets/staff-profiles/form-staff-profiles.xml',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/staff-profiles/fields-staff-profiles.xml',
		);

		parent::__construct( $id, $settings );

	}
}

class GravityView_Preset_Website_Showcase extends GravityView_Default_Template_List {

	function __construct() {

		$id = 'preset_website_showcase';

		$settings = array(
			'slug' => 'list',
			'type' => 'preset',
			'label' =>  __( 'Website Showcase', 'gravityview' ),
			'description' => __( 'Feature submitted websites with screenshots.', 'gravityview'),
			'logo' => plugins_url('includes/presets/website-showcase/logo-website-showcase.png', GRAVITYVIEW_FILE),
			'preview' => 'http://demo.gravityview.co/blog/view/website-showcase/',
			'preset_form' => GRAVITYVIEW_DIR . 'includes/presets/website-showcase/form-website-showcase.xml',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/website-showcase/fields-website-showcase.xml'
		);

		parent::__construct( $id, $settings );

	}
}

new GravityView_Default_Template_Table;
new GravityView_Default_Template_List;
new GravityView_Default_Template_Edit;

//presets
new GravityView_Preset_Business_Listings;
new GravityView_Preset_Business_Data;
new GravityView_Preset_Profiles;
new GravityView_Preset_Staff_Profiles;
new GravityView_Preset_Website_Showcase;
new GravityView_Preset_Issue_Tracker;
new GravityView_Preset_Resume_Board;
new GravityView_Preset_Job_Board;

new GravityView_Preset_Event_Listings;
#new GravityView_Preset_People_Table;
