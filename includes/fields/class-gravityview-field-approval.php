<?php

/**
 * Add custom options for address fields
 * @since TODO
 */
class GravityView_Field_Approval extends GravityView_Field {

	var $name = 'approval';

	var $is_searchable = true;

	var $is_sortable = true;

	var $is_numeric = false;

	/**
	 * @var string Approval status is stored in entry meta under this key
	 * @since TODO
	 */
	var $entry_meta_key = 'is_approved';

	/**
	 * @var bool Don't add to the "columns to display" list; GravityView adds our own approval column
	 * @since TODO
	 */
	var $entry_meta_is_default_column = false;

	var $group = 'gravityview';

	var $contexts = array( 'single', 'multiple' );

	public function __construct() {

		$this->label = esc_attr__( 'Approval', 'gravityview' );

		$this->description =  esc_attr__( 'Approve entries from the View. Requires users have `gravityview_moderate_entries` capability or higher.', 'gravityview' );

		$this->add_hooks();

		parent::__construct();
	}

	/**
	 * Add filters and actions for the field
	 * @since TODO
	 * @return void
	 */
	private function add_hooks() {

		add_filter( 'gravityview_entry_default_fields', array( $this, 'filter_gravityview_entry_default_field' ), 10, 3 );

		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts_and_styles' ) );

		add_action( 'gravityview/field/approval/load_scripts', array( $this, 'enqueue_and_localize_script' ) );

	}

	/**
	 * Register the field approval script and style
	 * @since TODO
	 * @return void
	 */
	function register_scripts_and_styles() {
		$script_debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_script( 'gravityview-field-approval', GRAVITYVIEW_URL . 'assets/js/field-approval'.$script_debug.'.js', array( 'jquery' ), GravityView_Plugin::version, true );

		/**
		 * Override CSS file by placing in your theme's /gravityview/css/ sub-directory.
		 */
		$style_path = GravityView_View::getInstance()->locate_template( 'css/field-approval.css', false );

		$style_url = plugins_url( 'css/field-approval.css', trailingslashit( dirname( $style_path ) )  );

		/**
		 * @filter `gravityview/field/approval/css_url` URL to the Approval field CSS file.
		 * @since TODO
		 * @param string $style_path Override to use your own CSS file, or return empty string to disable loading.
		 */
		$style_path = apply_filters( 'gravityview/field/approval/css_url', $style_url );

		if( ! empty( $style_path ) ) {
			wp_register_style( 'gravityview-field-approval', $style_path, array( 'dashicons' ), GravityView_Plugin::version, 'screen' );
		}

		unset( $style_path, $style_url );
	}

	/**
	 * Get the strings used in the field approval field
	 * @since TODO
	 * @return array
	 */
	static public function get_strings() {

		/**
		 * @filter `gravityview/field/approval/text` Modify the text values used in field approval
		 * @param array $field_approval_text Array with `label_approve`, `label_disapprove`, `approve_title`, and `unapprove_title` keys.
		 * @since TODO
		 */
		$field_approval_text = apply_filters( 'gravityview/field/approval/text', array(
			'label_approve' => __( 'Approve', 'gravityview' ) ,
			'label_disapprove' => __( 'Disapprove', 'gravityview' ),
			'approve_title' => __( 'Entry not approved for directory viewing. Click to approve this entry.', 'gravityview'),
			'unapprove_title' => __( 'Entry approved for directory viewing. Click to disapprove this entry.', 'gravityview'),
		) );

		return $field_approval_text;
	}

	/**
	 * Register the field approval script and output the localized text JS variables
	 * @since TODO
	 * @return void
	 */
	public function enqueue_and_localize_script() {

		// The script is already registered and enqueued
		if( wp_script_is( 'gravityview-field-approval', 'enqueued' ) ) {
			return;
		}

		wp_enqueue_style( 'gravityview-field-approval' );

		wp_enqueue_script( 'gravityview-field-approval' );

		$field_approval_text = self::get_strings();

		wp_localize_script( 'gravityview-field-approval', 'gvApproval', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'gravityview_ajaxgfentries'),
			'text' => array_map( 'esc_js', $field_approval_text ),
		));

	}

	/**
	 * Add Fields to the field list
	 *
	 * @param array $entry_default_fields Array of fields shown by default
	 * @param string|array $form form_ID or form object
	 * @param string $context  Either 'single', 'directory', 'header', 'footer'
	 *
	 * @return array
	 */
	public function filter_gravityview_entry_default_field( $entry_default_fields, $form, $context ) {

		if ( ! isset( $entry_default_fields["{$this->name}"] ) ) {
			$entry_default_fields["{$this->name}"] = array(
				'label' => $this->label,
				'desc'  => $this->description,
				'type'  => $this->name,
			);
		}

		return $entry_default_fields;
	}

}

new GravityView_Field_Approval;
