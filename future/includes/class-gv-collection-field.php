<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * A collection of \GV\Field objects.
 *
 * @implements Collection<Field>
 */
class Field_Collection extends Collection {

	/**
	 * Returns all the objects in this collection as an an array. Here for docBlock purposes only.
	 *
	 * @since 2.0.13.1
	 *
	 * @return \GV\Field[]
	 */
	public function all() {
		return parent::all();
	}

	/**
	 * Add a \GV\Field to this collection.
	 *
	 * @param \GV\Field $field The field to add to the internal array.
	 *
	 * @api
	 * @since 2.0
	 * @return void
	 */
	public function add( $field ) {
		if ( ! $field instanceof Field ) {
			gravityview()->log->error( 'Field_Collections can only contain objects of type \GV\Field.' );
			return;
		}
		parent::add( $field );
	}

	/**
	 * Get a \GV\Field from this list by UID.
	 *
	 * @param int $field_uid The UID of the field in the field to get.
	 *
	 * @api
	 * @since 2.0
	 *
	 * @return \GV\Field|null The \GV\Field with the $field_uid as the UID, or null if not found.
	 */
	public function get( $field_uid ) {
		foreach ( $this->all() as $field ) {
			if ( $field->UID == $field_uid ) {
				return $field;
			}
		}
		return null;
	}

	/**
	 * Get a copy of this \GV\Field_Collection filtered by position.
	 *
	 * @param string $position The position to get the fields for.
	 *  Can be a wildcard *
	 *
	 * @api
	 * @since
	 *
	 * @return \GV\Field_Collection A filtered collection of \GV\Fields, filtered by position.
	 */
	public function by_position( $position ) {
		$fields = new self();

		$search = implode( '.*', array_map( 'preg_quote', explode( '*', $position ) ) );

		foreach ( $this->all() as $field ) {
			if ( preg_match( "#^{$search}$#", $field->position ) ) {
				$fields->add( $field );
			}
		}
		return $fields;
	}

	/**
	 * Get a copy of this \GV\Field_Collection filtered by visibility to current user context.
	 *
	 * @api
	 * @since
	 *
	 * @param $view \GV\View The view!
	 *
	 * @return \GV\Field_Collection A filtered collection of \GV\Fields, filtered by visibility.
	 */
	public function by_visible( $view = null ) {
		$fields = new self();

		/** @type \GV\Field $field */
		foreach ( $this->all() as $field ) {
			if ( $field->is_visible( $view ) ) {
				$fields->add( $field );
			}
		}
		return $fields;
	}

	/**
	 * Parse a configuration array into a Field_Collection.
	 *
	 * @param array $configuration The configuration, structured like so:
	 *
	 * array(
	 *
	 *  [other zones]
	 *
	 *  'directory_list-title' => array(
	 *
	 *      [other fields]
	 *
	 *      '5372653f25d44' => array(
	 *          @see \GV\Field::as_configuration() for structure
	 *      )
	 *
	 *      [other fields]
	 *  )
	 *
	 *  [other zones]
	 * )
	 *
	 * @return \GV\Field_Collection A collection of fields.
	 */
	public static function from_configuration( $configuration ) {
		$fields = new self();
		foreach ( $configuration as $position => $_fields ) {

			if ( empty( $_fields ) || ! is_array( $_fields ) ) {
				continue;
			}

			foreach ( $_fields as $uid => $_configuration ) {
				$field           = Field::from_configuration( $_configuration );
				$field->UID      = $uid;
				$field->position = $position;

				$fields->add( $field );
			}
		}
		return $fields;
	}

	/**
	 * Return a configuration array for this field collection.
	 *
	 * @return array See \GV\Field_Collection::from_configuration() for structure.
	 */
	public function as_configuration() {
		$configuration = array();

		/**
		 * @var \GV\Field $field
		 */
		foreach ( $this->all() as $field ) {
			if ( empty( $configuration[ $field->position ] ) ) {
				$configuration[ $field->position ] = array();
			}

			$configuration[ $field->position ][ $field->UID ] = $field->as_configuration();
		}
		return $configuration;
	}
}
