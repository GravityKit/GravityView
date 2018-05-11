<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The internal \GF_Field field object wrapper.
 *
 * Used for custom content fields, etc.
 */
class Internal_Field extends Field {
	/**
	 * @var \GravityView_Field The backing GravityView field (old).
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
	 * @return \GV\internal_Field|null The field implementation or null on error.
	 */
	public static function from_configuration( $configuration ) {
		if ( empty( $configuration['id'] ) || ! is_string( $configuration['id'] ) ) {
			gravityview()->log->error( 'Invalid configuration[id] supplied.' );
			return null;
		}

		$field = new self();
		$field->ID = $configuration['id'];
		$field->type = $configuration['id'];
		$field->update_configuration( $configuration );

		return $field;
	}

	/**
	 * Get a \GV\GF_Field from an internal Gravity Forms field ID.
	 *
	 * @param int $field_id The internal Gravity Forms field ID.
	 *
	 * @return \GV\Field|null The requested field or null if not found.
	 */
	public static function by_id( $field_id ) {
		$field = new self();
		$field->ID = $field_id;
		$field->type = $field->ID;

		/**
		 * Retrieve the internal backing field (old for now)
		 * @todo switch to future subclasses
		 */
		$field->field = \GravityView_Fields::get_instance( $field_id );

		return $field;
	}

	/**
	 * Retrieve the label for this field.
	 *
	 * @param \GV\View $view The view for this context if applicable.
	 * @param \GV\Source $source The source (form) for this context if applicable.
	 * @param \GV\Entry $entry The entry for this context if applicable.
	 * @param \GV\Request $request The request for this context if applicable.
	 *
	 * @return string The label for this field. Nothing here.
	 */
	public function get_label( View $view = null, Source $source = null, Entry $entry = null, Request $request = null ) {
		if ( $label = parent::get_label( $view, $source, $entry, $request ) ) {
			return $label;
		}

		if ( ! $this->show_label ) {
			return '';
		}

		return $this->field ? $this->field->label : $this->label;
	}

	/**
	 * Retrieve the value for this field.
	 *
	 * Requires the \GV\Entry in this implementation.
	 *
	 * @param \GV\View $view The view for this context if applicable.
	 * @param \GV\Source $source The source (form) for this context if applicable.
	 * @param \GV\Entry $entry The entry for this context if applicable.
	 * @param \GV\Request $request The request for this context if applicable.
	 *
	 * @return mixed The value for this field.
	 */
	public function get_value( View $view = null, Source $source = null, Entry $entry = null, Request $request = null ) {
		if ( ! $entry || ! is_a( $entry, '\GV\Entry' ) ) {
			gravityview()->log->error( '$entry is not a valid \GV\Entry instance' );
			return null;
		}

		/**
		 * @todo Implement in subclasses, once available.
		 *
		 * For example the "content" field will be empty here. It's
		 *  value is actually currently retrieved inside ...
		 *
		 * *drumroll*
		 *
		 * A TEMPLATE :)
		 */
		$value = Utils::get( $entry->as_entry(), $this->ID );
		
		/** Apply parent filters. */
		return $this->get_value_filters( $value, $view, $source, $entry, $request );
	}
}
