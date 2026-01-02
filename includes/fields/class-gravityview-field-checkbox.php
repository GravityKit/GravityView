<?php
/**
 * @file class-gravityview-field-checkbox.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Checkbox extends GravityView_Field {

	var $name = 'checkbox';

	var $is_searchable = true;

	/**
	 * @see GFCommon::get_field_filter_settings Gravity Forms suggests checkboxes should just be "is"
	 * @var array
	 */
	var $search_operators = array( 'is', 'in', 'not in', 'isnot', 'contains' );

	var $_gf_field_class_name = 'GF_Field_Checkbox';

	var $group = 'standard';

	var $icon = 'dashicons-yes';

	public function __construct() {
		$this->label = esc_html__( 'Checkbox', 'gk-gravityview' );
		parent::__construct();
	}

	/**
	 * Add `choice_display` setting to the field
	 *
	 * @param array  $field_options
	 * @param string $template_id
	 * @param string $field_id
	 * @param string $context
	 * @param string $input_type
	 *
	 * @since 1.17
	 *
	 * @return array
	 */
	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		// Set the $_field_id var.
		$field_options = parent::field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id );

		if ( $this->is_choice_value_enabled() ) {

			$desc    = esc_html__( 'This input has a label and a value. What should be displayed?', 'gk-gravityview' );
			$default = 'value';
			$choices = array(
				'tick'  => __( 'A check mark, if the input is checked', 'gk-gravityview' ),
				'value' => __( 'Value of the input', 'gk-gravityview' ),
				'label' => __( 'Label of the input', 'gk-gravityview' ),
			);
		} else {
			$desc    = '';
			$default = 'tick';
			$choices = array(
				'tick'  => __( 'A check mark, if the input is checked', 'gk-gravityview' ),
				'label' => __( 'Label of the input', 'gk-gravityview' ),
			);
		}

		// It's the parent field, not an input.
		if ( floor( $field_id ) === floatval( $field_id ) ) {
			unset( $choices['tick'] );

			// Add display format setting for parent checkbox fields only.
			$field_options['display_format'] = array(
				'type'     => 'radio',
				'label'    => __( 'Display Format:', 'gk-gravityview' ),
				'value'    => 'default',
				'desc'     => __( 'Choose how multiple checkbox values should be displayed.', 'gk-gravityview' ),
				'choices'  => array(
					'default' => __( 'Bulleted list (default)', 'gk-gravityview' ),
					'csv'     => __( 'Comma-separated values', 'gk-gravityview' ),
				),
				'group'    => 'display',
				'priority' => 110,
			);
		}

		$field_options['choice_display'] = array(
			'type'     => 'radio',
			'class'    => 'vertical',
			'label'    => __( 'What should be displayed:', 'gk-gravityview' ),
			'value'    => $default,
			'desc'     => $desc,
			'choices'  => $choices,
			'group'    => 'display',
			'priority' => 100,
		);

		return $field_options;
	}

	/**
	 * Format checkbox field values as CSV when display_format is set to 'csv'
	 *
	 * @since 2.19
	 *
	 * @param array                $value Array of checkbox values
	 * @param GF_Field             $field Gravity Forms field object
	 * @param bool                 $show_label Whether to show labels (true) or values (false)
	 * @param array                $entry Gravity Forms entry array
	 * @param \GV\Template_Context $gravityview The template context
	 *
	 * @return string CSV-formatted string of checkbox values
	 */
	public static function format_checkbox_csv( $value, $field, $show_label, $entry, $gravityview ) {
		$filtered_values = array_filter(
            (array) $value,
            function ( $item ) {
				return '' !== $item;
			}
        );

		if ( empty( $filtered_values ) ) {
			return '';
		}

		$csv_values = array();
		foreach ( $filtered_values as $item_value ) {
			// If not showing labels, just use the raw value.
			if ( ! $show_label || ! isset( $field->choices ) || ! is_array( $field->choices ) ) {
				$csv_values[] = $item_value;
				continue;
			}

			// Find the label for this value.
			$choice_label = $item_value;
			foreach ( $field->choices as $choice ) {
				if ( ! isset( $choice['value'] ) || $choice['value'] !== $item_value ) {
					continue;
				}

				$choice_label = isset( $choice['text'] ) ? $choice['text'] : $item_value;
				break;
			}
			$csv_values[] = $choice_label;
		}

		/**
		 * Modify the separator used for CSV display of checkbox values.
		 *
		 * @since 2.19
		 *
		 * @param string               $separator   The separator to use between values. Default: ', '.
		 * @param array                $entry       Gravity Forms entry array.
		 * @param GF_Field             $field       Gravity Forms field object.
		 * @param \GV\Template_Context $gravityview The template context.
		 */
		$separator = apply_filters( 'gravityview/field/checkbox/csv_separator', ', ', $entry, $field, $gravityview );

		return implode( $separator, $csv_values );
	}
}

new GravityView_Field_Checkbox();
