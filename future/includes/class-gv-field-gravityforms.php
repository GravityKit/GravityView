<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The Gravity Forms {@see \GF_Field} field object wrapper.
 */
class GF_Field extends Field {

	/**
	 * @var \GF_Field The backing Gravity Forms field.
	 */
	public $field;

	/**
	 * Create self from a configuration array.
	 *
	 * @param array $configuration The configuration array.
	 * @see \GV\Field::as_configuration()
	 * @internal
	 * @since 2.0
	 *
	 * @return \GV\GF_Field|null The field implementation or null on error.
	 */
	public static function from_configuration( $configuration ) {
		if ( empty( $configuration['id'] ) || ! is_numeric( $configuration['id'] ) ) {
			gravityview()->log->error(
				'Invalid configuration[id] supplied: {id}',
				array(
					'data' => $configuration,
					'id'   => \GV\Utils::get( $configuration, 'id' ),
				)
			);
			return null;
		}

		if ( empty( $configuration['form_id'] ) || ! $form = \GV\GF_Form::by_id( $configuration['form_id'] ) ) {
			gravityview()->log->error(
				'Invalid configuration[form_id] supplied: {form_id}',
				array(
					'data'    => $configuration,
					'form_id' => \GV\Utils::get( $configuration, 'form_id' ),
				)
			);
			return null;
		}

		$field = self::by_id( $form, $configuration['id'] );

		if ( ! $field ) {
			gravityview()->log->error(
				'Invalid configuration: Field not found by [id] supplied: {id}',
				array(
					'data' => $configuration,
					'id'   => \GV\Utils::get( $configuration, 'id' ),
				)
			);
			return null;
		}

		$field->update_configuration( $configuration );
		return $field;
	}

	/**
	 * Get a \GV\GF_Field by \GV\GF_Form and Field ID.
	 *
	 * @param \GV\GF_Form $form The Gravity Form form.
	 * @param int         $field_id The Gravity Form field ID for the $form.
	 *
	 * @return \GV\Field|null The requested field or null if not found.
	 */
	public static function by_id( $form, $field_id ) {

		if ( ! $form || ! is_object( $form ) || ! is_a( $form, '\GV\GF_Form' ) ) {
			gravityview()->log->error( '$form is not a \GV\GF_Form instance', array( 'data' => $form ) );
			return null;
		}

		if ( empty( $form->form ) ) {
			gravityview()->log->error( '$form is not initialized with a backing Gravity Forms form' );
			return null;
		}

		$gv_field = \GFFormsModel::get_field( $form->form, $field_id );

		if ( ! $gv_field ) {
			gravityview()->log->error(
				'Invalid $field_id #{field_id} for current source',
				array(
					'data'     => $form,
					'field_id' => $field_id,
				)
			);
			return null;
		}

		$field        = new self();
		$field->ID    = $field_id;
		$field->field = $gv_field;

		return $field;
	}

	/**
	 * Retrieve the label for this field.
	 *
	 * Requires a \GV\GF_Form in this implementation.
	 *
	 * @param \GV\View    $view The view for this context if applicable.
	 * @param \GV\Source  $source The source (form) for this context if applicable.
	 * @param \GV\Entry   $entry The entry for this context if applicable.
	 * @param \GV\Request $request The request for this context if applicable.
	 *
	 * @return string The label for this Gravity Forms field.
	 */
	public function get_label( View $view = null, Source $source = null, Entry $entry = null, Request $request = null ) {

		if ( ! $this->show_label ) {
			return '';
		}

		if ( $label = parent::get_label( $view, $source, $entry, $request ) ) {
			return $label;
		}

		if ( ! $source || ! is_object( $source ) || ! is_a( $source, '\GV\GF_Form' ) ) {
			gravityview()->log->error( '$source is not a valid \GV\GF_Form instance', array( 'data' => $source ) );
			return null;
		}

		if ( $this->label ) {
			return $this->label;
		}

		/** This is a complex Gravity Forms input. */
		if ( $input = \GFFormsModel::get_input( $this->field, $this->ID ) ) {
			$label = ! empty( $input['customLabel'] ) ? $input['customLabel'] : $input['label'];
		} else {
			/** This is a field with one label. */
			$label = $this->field->get_field_label( true, $this->label );
		}

		return $label;
	}

	/**
	 * Retrieve the value for this field.
	 *
	 * Requires a \GV\GF_Entry in this implementation.
	 *
	 * @param \GV\View    $view The view for this context if applicable.
	 * @param \GV\Source  $source The source (form) for this context if applicable.
	 * @param \GV\Entry   $entry The entry for this context if applicable.
	 * @param \GV\Request $request The request for this context if applicable.
	 *
	 * @return mixed The value for this field.
	 */
	public function get_value( View $view = null, Source $source = null, Entry $entry = null, Request $request = null ) {
		if ( ! $entry || ! is_object( $entry ) || ! is_a( $entry, '\GV\GF_Entry' ) ) {
			gravityview()->log->error( '$entry is not a valid \GV\GF_Entry instance', array( 'data' => $entry ) );
			return null;
		}

		$value = \RGFormsModel::get_lead_field_value( $entry->as_entry(), $this->field );

		/** Apply parent filters. */
		return $this->get_value_filters( $value, $view, $source, $entry, $request );
	}

	/**
	 * A proxy getter for the backing GravityView field.
	 *
	 * The view field configuration is checked first, though.
	 *
	 * @param string $key The property to get.
	 *
	 * @return mixed The value of the Gravity View field property, or null if not exists.
	 */
	public function __get( $key ) {
		if ( $value = parent::__get( $key ) ) {
			return $value;
		}

		if ( $this->field ) {
			return $this->field->$key;
		}
	}
}
