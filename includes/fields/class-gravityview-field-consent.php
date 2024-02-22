<?php
/**
 * @file class-gravityview-field-consent.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 2.10
 */

/**
 * @since 2.10
 */
class GravityView_Field_Consent extends GravityView_Field {

	var $name = 'consent';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot' );

	var $_gf_field_class_name = 'GF_Field_Consent';

	var $group = 'standard';

	var $icon = 'dashicons-text-page';

	public function __construct() {

		$this->label = esc_html__( 'Consent', 'gk-gravityview' );

		parent::__construct();

		add_filter( 'gravityview/template/field/consent/output', array( $this, 'field_output' ), 10, 2 );
	}

	/**
	 * Returns the value of the consent field based on the field settings.
	 *
	 * @param string               $output Existing default $display_value for the field
	 * @param \GV\Template_Context $context
	 *
	 * @return string
	 */
	public function field_output( $output, $context ) {

		if ( empty( $output ) ) {
			return '';
		}

		$configuration = $context->field->as_configuration();

		/** @var GF_Field_Consent $consent_field */
		$consent_field = $context->field->field;

		switch ( \GV\Utils::get( $configuration, 'choice_display' ) ) {
			case 'tick':
				return $consent_field->checked_indicator_markup;
			case 'label':
				$revision_id = absint( trim( $context->value[ $context->field->ID . '.3' ] ) );

				// Gravity Forms performs a DB query for consent output. Let's reduce queries
				// and cache each version we find.
				static $_consent_field_cache = array();
				$_cache_key                  = "{$consent_field->formId}_{$consent_field->ID}_{$revision_id}";

				// We have a cache hit!
				if ( ! empty( $_consent_field_cache[ $_cache_key ] ) ) {
					return $_consent_field_cache[ $_cache_key ];
				}

				$description = $consent_field->get_field_description_from_revision( $revision_id );

				// There was no "description" value set when submitted. Use the checkbox value instead.
				if ( ! $description ) {
					$description = $consent_field->checkboxLabel;
				}

				$_consent_field_cache[ $_cache_key ] = $description;

				return $description;
		}

		return $output;
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

		// Set the $_field_id var
		$field_options = parent::field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id );

		if ( floor( $field_id ) !== floatval( $field_id ) ) {
			$default = 'tick';
		} else {
			$default = 'both';
		}

		$field_options['choice_display'] = array(
			'type'    => 'radio',
			'class'   => 'vertical',
			'label'   => __( 'What should be displayed:', 'gk-gravityview' ),
			'value'   => $default,
			'desc'    => '',
			'choices' => array(
				'both'  => __( 'Consent image with description', 'gk-gravityview' ),
				'tick'  => __( 'Consent image', 'gk-gravityview' ),
				'label' => __( 'Consent description', 'gk-gravityview' ),
			),
			'priorty' => 100,
			'group'   => 'display',
		);

		return $field_options;
	}
}

new GravityView_Field_Consent();
