<?php
/**
 * @file       class-gravityview-field-gravity_forms.php
 * @since      2.19
 * @package    GravityView
 * @subpackage includes\fields
 */

use GV\Utils;

/**
 * View field to embed a Gravity Forms form.
 */
class GravityView_Field_Gravity_Forms extends GravityView_Field {
	var $name = 'gravity_forms';

	var $contexts = array( 'single', 'multiple' );

	var $group = 'gravityview';

	var $is_searchable = false;

	var $is_sortable = false;

	public $icon = 'data:image/svg+xml,%3Csvg%20enable-background%3D%22new%200%200%20391.6%20431.1%22%20viewBox%3D%220%200%20391.6%20431.1%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22m391.6%20292.8c0%2019.7-14%2043.9-31%2053.7l-133.8%2077.2c-17.1%209.9-45%209.9-62%200l-133.8-77.2c-17.1-9.9-31-34-31-53.7v-154.5c0-19.7%2013.9-43.9%2031-53.7l133.8-77.2c17.1-9.9%2045-9.9%2062%200l133.7%2077.2c17.1%209.8%2031%2034%2031%2053.7z%22%20fill%3D%22%2340464D%22%2F%3E%3Cpath%20d%3D%22m157.8%20179.8h177.2v-49.8h-176.8c-25.3%200-46.3%208.7-62.3%2025.7-38.6%2041.1-39.6%20144.6-39.6%20144.6h277.4v-93.6h-49.8v43.8h-174.4c1.1-16.3%208.6-45.5%2022.8-60.6%206.4-6.9%2014.5-10.1%2025.5-10.1z%22%20fill%3D%22%23fff%22%2F%3E%3C%2Fsvg%3E';

	/**
	 * {@inheritdoc}
	 *
	 * @since 2.19
	 */
	function __construct() {
		require_once GFCommon::get_base_path() . '/form_display.php';

		$this->label       = __( 'Gravity Forms', 'gk-gravityview' );
		$this->description = __( 'Display a Gravity Forms form.', 'gk-gravityview' );

		add_action( 'gform_after_submission', array( $this, 'add_new_entry_meta' ), 10, 2 );
		add_action( 'gform_ajax_iframe_content', array( $this, 'modify_form_ajax_postback_content' ) );

		parent::__construct();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 2.19
	 */
	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {
		unset( $field_options['search_filter'], $field_options['show_as_link'], $field_options['new_window'] );

		$new_fields = array(
			'field_form_id' => array(
				'type'    => 'select',
				'label'   => __( 'Form to display', 'gk-gravityview' ),
				'value'   => '',
				'options' => GVCommon::get_forms_as_options(),
			),
			'title'         => array(
				'type'  => 'checkbox',
				'label' => __( 'Show form title?', 'gk-gravityview' ),
				'value' => 1,
			),
			'description'   => array(
				'type'  => 'checkbox',
				'label' => __( 'Show form description?', 'gk-gravityview' ),
				'value' => 1,
			),
			'ajax'          => array(
				'type'  => 'checkbox',
				'label' => __( 'Enable AJAX', 'gk-gravityview' ),
				'desc'  => '',
				'value' => 1,
			),
			'field_values'  => array(
				'type'       => 'text',
				'class'      => 'code widefat',
				'label'      => __( 'Field value parameters', 'gk-gravityview' ),
				'desc'       => '<a href="https://docs.gravityforms.com/using-dynamic-population/" rel="external">' . esc_html__( 'Learn how to dynamically populate a field.', 'gk-gravityview' ) . '</a>',
				'value'      => '',
				'merge_tags' => 'force',
			),
		);

		return $new_fields + $field_options;
	}

	/**
	 * Get the highest-number form ID.
	 *
	 * @since 2.19
	 *
	 * @return int Form ID.
	 */
	private static function get_latest_form_id() {
		global $wpdb;

		$form_table_name = GFFormsModel::get_form_table_name();

		return $wpdb->get_var( "SELECT id FROM {$form_table_name} ORDER BY id DESC LIMIT 1" );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 2.19
	 */
	public static function render_frontend( $field_settings, $view_form, $view_entry ) {
		$embed_form_id = Utils::get( $field_settings, 'field_form_id' );

		if ( empty( $embed_form_id ) ) {
			return;
		}

		$title              = Utils::get( $field_settings, 'title' );
		$description        = Utils::get( $field_settings, 'description' );
		$field_values       = Utils::get( $field_settings, 'field_values' );
		$ajax               = Utils::get( $field_settings, 'ajax' );
		$field_values_array = array();

		// Prepare field values.
		if ( ! empty( $field_values ) ) {
			parse_str( Utils::get( $field_settings, 'field_values' ), $field_values_array );

			foreach ( $field_values_array as & $field_value ) {
				$field_value = GFCommon::replace_variables( $field_value, $view_form, $view_entry );
			}

			$field_values_array = array_map( 'esc_attr', $field_values_array );
		}

		$_submission = GFFormDisplay::$submission;
		$_post       = $_POST;

		if ( rgpost( 'gform_submit' ) && rgpost( 'gk_parent_entry_id' ) ) {
			// GF sets validation errors on the form object and caches it.
			// As a result, subsequent form retrievals by gravity_form() will get the processed/validated form rather than a fresh object and will display errors for the wrong form instance.
			GFFormsModel::flush_current_form( GFFormsModel::get_form_cache_key( $embed_form_id ) );

			if ( rgpost( 'gk_parent_entry_id' ) !== $view_entry['id'] ) {
				GFFormDisplay::$submission = array(); // Prevent GF from thinking the form was submitted.
				$_POST                     = array(); // Prevent GF from populating fields with $_POST data when displaying the form.
			}
		}

		$rendered_form = gravity_form( $embed_form_id, ! empty( $title ), ! empty( $description ), false, $field_values_array, $ajax, 0, false );

		GFFormDisplay::$submission = $_submission;
		$_POST                     = $_post;

		$rendered_form = self::modify_form_content( $rendered_form, (int) $embed_form_id, (int) $view_form['id'], (int) $view_entry['id'] );

		echo $rendered_form;
	}

	/**
	 * Modifies form postback content sent via an iframe submission.
	 * Since GF recreates the form object, we need to ensure that the same IDs are used as set in {@see self::render_frontend()} so that errors and other messages are scoped to the correct form in the View.
	 *
	 * @since 2.19
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function modify_form_ajax_postback_content( $content ) {
		$required_post_data = array( 'gk_parent_entry_id', 'gk_parent_form_id', 'gk_unique_id' );

		foreach ( $required_post_data as $key ) {
			if ( ! rgpost( $key ) ) {
				return $content;
			}
		}

		$content = self::modify_form_content(
			$content,
			(int) rgpost( 'gform_submit' ),
			(int) rgpost( 'gk_parent_form_id' ),
			(int) rgpost( 'gk_parent_entry_id' ),
			(int) rgpost( 'gk_unique_id' )
		);

		return $content;
	}

	/**
	 * Updates the form content with extra data and also prevents collisions when multiple forms are embedded on the same page.
	 *
	 * @param string   $content
	 * @param int      $form_id
	 * @param int      $view_form_id
	 * @param init     $view_entry_id
	 * @param null|int $form_count (optional) Sequential number for each form instance in the View. Used to prevent collisions when multiple forms are embedded on the same page. Default: null and will be set to the highest-number form ID in the database.
	 *
	 * @return string
	 */
	public static function modify_form_content( $content, $form_id, $view_form_id, $view_entry_id, $form_count = null ) {
		static $unique_id;

		// Start the form count at the highest-number form ID to prevent collisions.
		$unique_id = ( $form_count ?? $unique_id ) ?? self::get_latest_form_id();

		if ( $view_form_id && $view_entry_id ) {
			// Add hidden fields that let us later identify the parent (View) form and entry.
			$content = preg_replace(
				'/(<input[^>]*name=\'gform_field_values\'[^>]*?>)(?=[^<]*<)/',
				<<<HTML
					$1
					<input type="hidden" name="gk_parent_entry_id" value="{$view_entry_id}">
					<input type="hidden" name="gk_parent_form_id" value="{$view_form_id}">
					<input type="hidden" name="gk_unique_id" value="{$unique_id}">
HTML
				,
				$content
			);
		}

		// Set unique ID for iframe that handles GF's form Ajax logic, which allows us to have multiple forms on the same page.
		$strings_to_replace = array(
			"gform_ajax_frame_{$form_id}"               => "gform_ajax_frame_{$unique_id}",
			"gform_wrapper_{$form_id}"                  => "gform_wrapper_{$unique_id}",
			"gform_confirmation_wrapper_{$form_id}"     => "gform_confirmation_wrapper_{$unique_id}",
			"gforms_confirmation_message_{$form_id}"    => "gforms_confirmation_message_{$unique_id}",
			"gform_confirmation_message_{$form_id}"     => "gform_confirmation_message_{$unique_id}",
			"gformInitSpinner( {$form_id},"             => "gformInitSpinner( {$unique_id},",
			"trigger('gform_page_loaded', [{$form_id}"  => "trigger('gform_page_loaded', [{$unique_id}",
			"'gform_confirmation_loaded', [{$form_id}]" => "'gform_confirmation_loaded', [{$unique_id}]",
			"gform_submit_button_{$form_id}"            => "gform_submit_button_{$unique_id}",
			"gf_submitting_{$form_id}"                  => "gf_submitting_{$unique_id}",
			"gform_{$form_id}"                          => "gform_{$unique_id}",
			"gform_{$form_id}_validation_container"     => "gform_{$unique_id}_validation_container",
			"validation_message_{$form_id}"             => "validation_message_{$unique_id}",
		);

		$content = str_replace( array_keys( $strings_to_replace ), array_values( $strings_to_replace ), $content );

		++$unique_id;

		return $content;
	}

	/**
	 * Adds the parent entry ID and form ID to the new entry's meta.
	 *
	 * @since 2.19
	 *
	 * @param $entry The entry object.
	 * @param $form  The form object.
	 *
	 * @return void
	 */
	public function add_new_entry_meta( $entry, $form ) {
		if ( ! rgpost( 'gk_parent_entry_id' ) && ! rgpost( 'gk_parent_form_id' ) ) {
			return;
		}

		gform_update_meta( $entry['id'], 'gk_parent_entry_id', rgpost( 'gk_parent_entry_id' ), $form['id'] );
		gform_update_meta( $entry['id'], 'gk_parent_form_id', rgpost( 'gk_parent_form_id' ), $form['id'] );
	}
}

new GravityView_Field_Gravity_Forms();
