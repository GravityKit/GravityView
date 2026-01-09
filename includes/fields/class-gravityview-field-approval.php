<?php

use GV\Multi_Entry;

/**
 * Add custom options for address fields
 *
 * @since 1.19
 */
class GravityView_Field_Entry_Approval extends GravityView_Field {

	var $name = 'entry_approval';

	var $is_searchable = true;

	public $search_operators = array( 'is', 'isnot' );

	var $is_sortable = true;

	var $is_numeric = true;

	var $group = 'gravityview';

	var $contexts = array( 'single', 'multiple', 'edit' );

	var $icon = 'dashicons-yes-alt';

	public function __construct() {

		$this->label = esc_attr__( 'Approve Entries', 'gk-gravityview' );

		$this->description = esc_attr__( 'Approve and reject entries from the View.', 'gk-gravityview' );

		$this->add_hooks();

		parent::__construct();
	}

	/**
	 * Remove unused settings for the approval field
	 *
	 * @since 1.19
	 *
	 * @param array  $field_options
	 * @param string $template_id
	 * @param string $field_id
	 * @param string $context
	 * @param string $input_type
	 *
	 * @return array
	 */
	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		unset( $field_options['only_loggedin'] );

		unset( $field_options['new_window'] );

		unset( $field_options['show_as_link'] );

		return $field_options;
	}

	/**
	 * Add filters and actions for the field
	 *
	 * @since 1.19
	 *
	 * @return void
	 */
	private function add_hooks() {

		add_filter( 'gravityview_entry_default_fields', array( $this, 'filter_gravityview_entry_default_field' ), 10, 3 );

		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts_and_styles' ) );

		// Make sure scripts are registered for FSE themes
		add_action( 'gravityview/template/before', array( $this, 'register_scripts_and_styles' ) );

		add_action( 'gravityview/field/approval/load_scripts', array( $this, 'enqueue_and_localize_script' ) );

		add_action( 'gravityview_datatables_scripts_styles', array( $this, 'enqueue_and_localize_script' ) );

		add_filter( 'gravityview_get_entries', array( $this, 'modify_search_parameters' ), 1000 );

		add_filter( 'gravityview/field_output/html', array( $this, 'maybe_prevent_field_render' ), 10, 2 );

		add_filter( 'gravityview/field/is_visible', array( $this, 'maybe_not_visible' ), 10, 2 );

		add_filter( 'gravityview/edit_entry/form_fields', array( $this, 'show_field_in_edit_entry' ), 10, 3 );

		add_action( 'gravityview/edit_entry/after_update', array( $this, 'update_edit_entry' ), 10, 4 );

		add_filter( 'gravityview/edit_entry/field_value', [ $this, 'override_field_value' ], 10, 3 );
	}

	/**
	 * Updates approval field after edit entry save.
	 *
	 * @since 2.26
	 *
	 * @param array                         $form              Gravity Forms form array.
	 * @param int                           $entry_id          Gravity Forms Entry ID.
	 * @param GravityView_Edit_Entry_Render $edit_entry_render The Edit Entry renderer.
	 * @param GravityView_View_Data         $gv_data           The GravityView View data.
	 *
	 * @return void
	 */
	public function update_edit_entry( $form = [], $entry_id = 0, $edit_entry_render = null, $gv_data = null ) {
		if ( ! $form || ! $entry_id ) {
			return;
		}

		if ( ! $gv_data ) {
			// We can't be sure the user can edit the approval field, since we can't check the view configuration.
			return;
		}

		$unique_id = crc32( 'is_approved' );

		// There is no input to process.
		if ( ! isset( $_POST[ 'input_' . $unique_id ] ) ) {
			return;
		}

		$can_edit = false;
		// Make sure we can edit the approval field.
		foreach ( $gv_data->views->all() as $view ) {
			$properties  = $view->fields ? $view->fields->as_configuration() : [];
			$edit_fields = $properties['edit_edit-fields'] ?? null;
			foreach ( $edit_fields as $edit_field ) {
				if (
					(int) ( $edit_field['form_id'] ?? 0 ) !== (int) $form['id']
					|| ( 'entry_approval' !== $edit_field['id'] )
				) {
					continue;
				}

				if ( $this->check_user_can_edit_approval_field( $edit_field ) ) {
					$can_edit = true;
					break 2;
				}
			}
		}

		if ( ! $can_edit ) {
			return;
		}

		$approval_status = \GV\Utils::_POST( 'input_' . $unique_id );

		if ( ! GravityView_Entry_Approval_Status::is_valid( $approval_status ) ) {
			$approval_status = GravityView_Entry_Approval_Status::UNAPPROVED;
		}

		GravityView_Entry_Approval::update_approved( $entry_id, $approval_status, $form['id'] );
	}

	/**
	 * Overwrites the field value with the current approval status.
	 *
	 * @since $ver$
	 *
	 * @param mixed                         $field_value       Field value used to populate the input.
	 * @param GF_Field                      $field             Gravity Forms field object.
	 * @param GravityView_Edit_Entry_Render $edit_entry_render The Edit Entry renderer.
	 *
	 * @return mixed The (possibly updated) value.
	 */
	public function override_field_value( $field_value, $field, GravityView_Edit_Entry_Render $edit_entry_render ) {
		if ( crc32( 'is_approved' ) !== ( $field->id ?? null ) ) {
			return $field_value;
		}

		return $edit_entry_render->entry['is_approved'] ?? $field_value;
	}

	/**
	 * Modify field label output.
	 *
	 * @since 1.19
	 *
	 * @param string $html Existing HTML output
	 * @param array  $args Arguments passed to the function
	 *
	 * @return string Empty string if user doesn't have the `gravityview_moderate_entries` cap; field HTML otherwise
	 */
	public function maybe_prevent_field_render( $html, $args ) {

		$field_id = \GV\Utils::get( $args['field'], 'id' );

		// If the field is `entry_approval` type but the user doesn't have the moderate entries cap, don't render.
		if ( $this->name === $field_id && ! GVCommon::has_cap( 'gravityview_moderate_entries' ) ) {
			return '';
		}

		return $html;
	}

	/**
	 * Do not show this field if `gravityview_moderate_entries` capability is absent.
	 *
	 * @return boolean Whether this field is visible or not.
	 */
	public function maybe_not_visible( $visible, $field ) {
		if ( $this->name !== $field->ID ) {
			return $visible;
		}

		return GVCommon::has_cap( 'gravityview_moderate_entries' );
	}

	/**
	 * Modify search to use `is_approved` meta key to sort, instead of `entry_approval`
	 *
	 * @param array $parameters Search parameters used to generate GF search
	 *
	 * @return array Same parameters, but if sorting by `entry_approval`, changed to `is_approved`
	 */
	public function modify_search_parameters( $parameters ) {

		if ( $this->name === \GV\Utils::get( $parameters, 'sorting/key' ) ) {
			$parameters['sorting']['key'] = 'is_approved';
		}

		return $parameters;
	}

	/**
	 * Register the field approval script and style
	 *
	 * @since 1.19
	 *
	 * @return void
	 */
	function register_scripts_and_styles() {

		if ( wp_script_is( 'gravityview-field-approval' ) ) {
			return;
		}

		$script_debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_script( 'gravityview-field-approval', GRAVITYVIEW_URL . 'assets/js/field-approval' . $script_debug . '.js', array( 'jquery' ), GV_PLUGIN_VERSION, true );

		wp_register_script( 'gravityview-field-approval-popper', GRAVITYVIEW_URL . 'assets/lib/tippy/popper.min.js', array(), GV_PLUGIN_VERSION, true );
		wp_register_script( 'gravityview-field-approval-tippy', GRAVITYVIEW_URL . 'assets/lib/tippy/tippy.min.js', array(), GV_PLUGIN_VERSION, true );
		wp_register_style( 'gravityview-field-approval-tippy', GRAVITYVIEW_URL . 'assets/lib/tippy/tippy.css', array(), GV_PLUGIN_VERSION, 'screen' );

		$style_path = GRAVITYVIEW_DIR . 'templates/css/field-approval.css';

		if ( class_exists( 'GravityView_View' ) ) {
			/**
			 * Override CSS file by placing in your theme's /gravityview/css/ sub-directory.
			 */
			$style_path = GravityView_View::getInstance()->locate_template( 'css/field-approval.css', false );
		}

		$style_url = plugins_url( 'css/field-approval.css', trailingslashit( dirname( $style_path ) ) );

		/**
		 * URL to the Approval field CSS file.
		 *
		 * @since 1.19
		 *
		 * @param string $style_url Override to use your own CSS file, or return empty string to disable loading.
		 */
		$style_url = apply_filters( 'gravityview/field/approval/css_url', $style_url );

		if ( ! empty( $style_url ) ) {
			wp_register_style( 'gravityview-field-approval', $style_url, array( 'dashicons' ), GV_PLUGIN_VERSION, 'screen' );
		}

		unset( $style_path, $style_url );
	}

	/**
	 * Register the field approval script and output the localized text JS variables
	 *
	 * @since 1.19
	 * @return void
	 */
	public function enqueue_and_localize_script() {

		// The script is already registered and enqueued
		if ( wp_script_is( 'gravityview-field-approval', 'enqueued' ) ) {
			return;
		}

		wp_enqueue_style( 'gravityview-field-approval' );

		wp_enqueue_script( 'gravityview-field-approval' );
		wp_enqueue_script( 'gravityview-field-approval-tippy' );
		wp_enqueue_script( 'gravityview-field-approval-popper' );
		wp_enqueue_style( 'gravityview-field-approval-tippy' );

		wp_localize_script(
			'gravityview-field-approval',
			'gvApproval',
			array(
				'ajaxurl'                  => admin_url( 'admin-ajax.php' ),
				'nonce'                    => wp_create_nonce( 'gravityview_entry_approval' ),
				'status'                   => GravityView_Entry_Approval_Status::get_all(),
				'status_popover_template'  => GravityView_Entry_Approval::get_popover_template(),
				'status_popover_placement' => GravityView_Entry_Approval::get_popover_placement(),
			)
		);
	}

	/**
	 * Add Fields to the field list
	 *
	 * @since 1.19
	 *
	 * @param array        $entry_default_fields Array of fields shown by default
	 * @param string|array $form form_ID or form object
	 * @param string       $context  Either 'single', 'directory', 'header', 'footer'
	 *
	 * @return array
	 */
	public function filter_gravityview_entry_default_field( $entry_default_fields, $form, $context ) {

		if ( ! isset( $entry_default_fields[ "{$this->name}" ] ) && 'edit' === $context ) {
			$entry_default_fields[ "{$this->name}" ] = array(
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
	 * @since 1.19
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
	 * @since 1.19
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

	/**
	 * Adds the GravityView Approval Entries field to the Edit Entry form
	 *
	 * @since 2.26
	 *
	 * @param GF_Field[] $fields        Gravity Forms form fields
	 * @param array|null $edit_fields   Fields for the Edit Entry tab configured in the View Configuration
	 * @param array      $form          GF Form array (`fields` key modified to have only fields configured to show in Edit Entry)
	 *
	 * @return GF_Field[]               If Custom Content field exists, returns fields array with the fields inserted. Otherwise, returns unmodified fields array.
	 */
	public function show_field_in_edit_entry( $fields, $edit_fields = null, $form = array() ) {
		// Not configured; show all fields.
		if ( is_null( $edit_fields ) ) {
			return $fields;
		}

		$new_fields = array();
		$i          = 0;

		foreach ( (array) $edit_fields as $id => $edit_field ) {
			if ( 'entry_approval' !== $edit_field['id'] ) {
				if ( isset( $fields[ $i ] ) ) {
					$new_fields[] = $fields[ $i ];
				}
				++$i;
				continue;
			}

			// Check if the user has permission to edit this field using the same logic as regular fields.
			if ( ! $this->check_user_can_edit_approval_field( (array) $edit_field ) ) {
				// User doesn't have permission - skip this field.
				continue;
			}

			$label = ( $edit_field['custom_label'] ? $edit_field['custom_label'] : __( 'Approve Entries', 'gk-gravityview' ) );

			if ( ! $edit_field['show_label'] ) {
				$label = '';
			}

			$unique_id = crc32( 'is_approved' );

			$approval_value = GravityView_Entry_Approval_Status::UNAPPROVED;

			$entry = gravityview()->request->is_entry();

			if ( $entry ) {
				$entry = $entry instanceof Multi_Entry ? reset( $entry->entries ) : $entry;

				$entry = $entry->as_entry();

				$approval_value = $entry['is_approved'] ?? $approval_value;
			}

			$approval_value = $_POST[ "input_{$unique_id}" ] ?? $approval_value;

			$field_data = [
				'id'           => $unique_id,
				'custom_id'    => $id,
				'label'        => $label,
				'choices'      => [
					[
						'text'  => __( 'Approve', 'gk-gravityview' ),
						'value' => GravityView_Entry_Approval_Status::APPROVED,
					],
					[
						'text'  => __( 'Disapprove', 'gk-gravityview' ),
						'value' => GravityView_Entry_Approval_Status::DISAPPROVED,
					],
					[
						'text'  => __( 'Reset Approval', 'gk-gravityview' ),
						'value' => GravityView_Entry_Approval_Status::UNAPPROVED,
					],
				],
				'defaultValue' => (int) $approval_value,
				'cssClass'     => $edit_field['custom_class'],
			];

			$new_fields[] = new GF_Field_Radio( $field_data );
		}

		return $new_fields;
	}

	/**
	 * Check if the current user has permission to edit the approval field using the same logic as regular fields.
	 *
	 * @since TBD
	 *
	 * @param array $edit_field Field configuration from the View.
	 *
	 * @return bool Whether the user can edit the field.
	 */
	private function check_user_can_edit_approval_field( array $edit_field ): bool {
		// If the user has full entry editing capabilities, they can edit all fields.
		if ( GVCommon::has_cap( [ 'gravityforms_edit_entries', 'gravityview_edit_others_entries' ] ) ) {
			return true;
		}

		// Check the allow_edit_cap setting (matches the pattern used for regular fields).
		$field_cap = $edit_field['allow_edit_cap'] ?? false;

		if ( $field_cap ) {
			return GVCommon::has_cap( $field_cap );
		}

		return false;
	}
}

new GravityView_Field_Entry_Approval();
