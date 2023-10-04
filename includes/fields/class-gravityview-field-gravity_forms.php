<?php
/**
 * @file class-gravityview-field-gravity_forms.php
 * @since 2.19
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Widget to display a Gravity Forms form
 */
class GravityView_Field_Gravity_Forms extends GravityView_Field {

	var $name = 'gravity_forms';

	var $contexts = array( 'single', 'multiple' );

	var $group = 'gravityview';

	var $is_searchable = false;

	var $is_sortable = false;

	public $icon = 'data:image/svg+xml,%3Csvg%20enable-background%3D%22new%200%200%20391.6%20431.1%22%20viewBox%3D%220%200%20391.6%20431.1%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22m391.6%20292.8c0%2019.7-14%2043.9-31%2053.7l-133.8%2077.2c-17.1%209.9-45%209.9-62%200l-133.8-77.2c-17.1-9.9-31-34-31-53.7v-154.5c0-19.7%2013.9-43.9%2031-53.7l133.8-77.2c17.1-9.9%2045-9.9%2062%200l133.7%2077.2c17.1%209.8%2031%2034%2031%2053.7z%22%20fill%3D%22%2340464D%22%2F%3E%3Cpath%20d%3D%22m157.8%20179.8h177.2v-49.8h-176.8c-25.3%200-46.3%208.7-62.3%2025.7-38.6%2041.1-39.6%20144.6-39.6%20144.6h277.4v-93.6h-49.8v43.8h-174.4c1.1-16.3%208.6-45.5%2022.8-60.6%206.4-6.9%2014.5-10.1%2025.5-10.1z%22%20fill%3D%22%23fff%22%2F%3E%3C%2Fsvg%3E';

	function __construct() {

		$this->label = __( 'Gravity Forms', 'gk-gravityview' );
		$this->description = __('Display a Gravity Forms form.', 'gk-gravityview' );

		parent::__construct();
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		unset ( $field_options['search_filter'], $field_options['show_as_link'], $field_options['new_window'] );

		$new_fields = array(
			'field_form_id' => array(
				'type' => 'select',
				'label' => __( 'Form to display', 'gk-gravityview' ),
				'value' => '',
				'options' => GVCommon::get_forms_as_options(),
			),
			'title' => array(
				'type' => 'checkbox',
				'label' => __( 'Show form title?', 'gk-gravityview' ),
				'value' => 1,
			),
			'description' => array(
				'type' => 'checkbox',
				'label' => __( 'Show form description?', 'gk-gravityview' ),
				'value' => 1,
			),
			'ajax' => array(
				'type' => 'checkbox',
				'label' => __( 'Enable AJAX', 'gk-gravityview' ),
				'desc' => '',
				'value' => 1,
			),
			'field_values' => array(
				'type' => 'text',
				'class' => 'code widefat',
				'label' => __( 'Field value parameters', 'gk-gravityview' ),
				'desc' => '<a href="https://docs.gravityforms.com/using-dynamic-population/" rel="external">' . esc_html__( 'Learn how to dynamically populate a field.', 'gk-gravityview' ) . '</a>',
				'value' => '',
				'merge_tags' => 'force',
			),
		);

		return $new_fields + $field_options;
	}


	/**
	 * @param array $widget_args
	 * @param string|\GV\Template_Context $content
	 * @param string $context
	 */
	static public function render_frontend( $field_settings, $form, $entry ) {

		$form_id = \GV\Utils::get( $field_settings, 'field_form_id' );

		if ( empty( $form_id ) ) {
			return;
		}

		$title       = \GV\Utils::get( $field_settings, 'title' );
		$description = \GV\Utils::get( $field_settings, 'description' );
		$field_values = \GV\Utils::get( $field_settings, 'field_values' );
		$ajax = \GV\Utils::get( $field_settings, 'ajax' );

		// Prepare field values.
		$field_values_array = [];
		if ( ! empty( $field_values ) ) {
			parse_str( \GV\Utils::get( $field_settings, 'field_values' ), $field_values_array );

			foreach( $field_values_array as & $field_value ) {
				$field_value = GFCommon::replace_variables( $field_value, $form, $entry );
			}

			$field_values_array = array_map( 'esc_attr', $field_values_array );
		}

		gravity_form( $form_id, ! empty( $title ), ! empty( $description ), false, $field_values_array, $ajax );
	}

}

new GravityView_Field_Gravity_Forms;
