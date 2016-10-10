<?php

/**
 * Add custom options for address fields
 * @since 1.18
 */
class GravityView_Field_Entry_Approval extends GravityView_Field {

	var $name = 'entry_approval';

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
	 * @since 1.18
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
		 * @since 1.18
		 * @param string $style_path Override to use your own CSS file, or return empty string to disable loading.
		 */
		$style_path = apply_filters( 'gravityview/field/approval/css_url', $style_url );

		if( ! empty( $style_path ) ) {
			wp_register_style( 'gravityview-field-approval', $style_path, array( 'dashicons' ), GravityView_Plugin::version, 'screen' );
		}

		unset( $style_path, $style_url );
	}

	/**
	 * Register the field approval script and output the localized text JS variables
	 * @since 1.18
	 * @return void
	 */
	public function enqueue_and_localize_script() {

		// The script is already registered and enqueued
		if( wp_script_is( 'gravityview-field-approval', 'enqueued' ) ) {
			return;
		}

		wp_enqueue_style( 'gravityview-field-approval' );

		wp_enqueue_script( 'gravityview-field-approval' );

		wp_localize_script( 'gravityview-field-approval', 'gvApproval', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce('gravityview_entry_approval'),
			'status' => GravityView_Entry_Approval_Status::get_all(),
		));

	}

	/**
	 * Add Fields to the field list
	 *
	 * @since 1.18
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

	/**
	 * Get the anchor text for a link, based on the current status
	 *
	 * @since 1.18
	 * @uses GravityView_Entry_Approval_Status::get_string()
	 *
	 * @param string $approved_status Status string or key
	 *
	 * @return false|string False if string doesn't exist, otherwise the "label" for the status
	 */
	public static function get_anchor_text( $approved_status = '' ) {
		return GravityView_Entry_Approval_Status::get_string( $approved_status, 'label' );
	}

	/**
	 * Get the title attribute for a link, based on the current status
	 *
	 * @since 1.18
	 * @uses GravityView_Entry_Approval_Status::get_string()
	 *
	 * @param int|string $approved_status Status string or key
	 *
	 * @return false|string
	 */
	public static function get_title_attr( $approved_status ) {
		return GravityView_Entry_Approval_Status::get_string( $approved_status, 'title' );
	}

	/**
	 * Get the CSS class for a link, based on the current status
	 *
	 * @param int|string $approved_status Status string or key
	 *
	 * @return string CSS class, sanitized using esc_attr()
	 */
	public static function get_css_class( $approved_status ) {

		$approved_key = GravityView_Entry_Approval_Status::get_key( $approved_status );

		return esc_attr( "gv-approval-{$approved_key}" );
	}
}

new GravityView_Field_Entry_Approval;
