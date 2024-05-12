<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The Gravity Forms Form class implementation.
 *
 * Accessible as an array for back-compatibility.
 */
class GF_Form extends Form implements \ArrayAccess {

	/**
	 * @var string The identifier of the backend used for this form.
	 * @api
	 * @since 2.0
	 */
	public static $backend = self::BACKEND_GRAVITYFORMS;

	/**
	 * The form object.
	 *
	 * @var array
	 */
	public $form;

	/**
	 * Initialization.
	 */
	private function __construct() {
		if ( ! class_exists( 'GFAPI' ) ) {
			gravityview()->log->error( 'Gravity Forms plugin is not active.' );
		}
	}

	/**
	 * Construct a \GV\GF_Form instance by ID.
	 *
	 * @param int|string $form_id The internal form ID.
	 *
	 * @api
	 * @since 2.0
	 * @return \GV\GF_Form|null An instance of this form or null if not found.
	 */
	public static function by_id( $form_id ) {

		$form = \GVCommon::get_form( $form_id );

		if ( ! $form ) {
			return null;
		}

		$self       = new self();
		$self->form = $form;

		$self->ID = intval( $self->form['id'] );

		return $self;
	}

	/**
	 * Construct a \GV\Form instance from a Gravity Forms form array.
	 *
	 * @since 2.0.7
	 *
	 * @param array $form The form array
	 *
	 * @return \GV\GF_Form|null An instance of this form or null if not found.
	 */
	public static function from_form( $form ) {
		if ( empty( $form['id'] ) ) {
			return null;
		}

		$self       = new self();
		$self->form = $form;
		$self->ID   = $self->form['id'];

		return $self;
	}

	/**
	 * Get all entries for this form.
	 *
	 * @api
	 * @since 2.0
	 *
	 * @return \GV\Entry_Collection The \GV\Entry_Collection
	 */
	public function get_entries() {
		$entries = new \GV\Entry_Collection();

		$form = &$this;

		/** Add the fetcher lazy callback. */
		$entries->add_fetch_callback(
			function ( $filters, $sorts, $offset ) use ( $form ) {
				$entries = new \GV\Entry_Collection();

				$search_criteria = array();
				$sorting         = array();
				$paging          = array();

				/** Apply the filters */
				foreach ( $filters as $filter ) {
						$search_criteria = $filter::merge_search_criteria( $search_criteria, $filter->as_search_criteria() );
				}

				/** Apply the sorts */
				foreach ( $sorts as $sort ) {
					/** Gravity Forms does not have multi-sorting, so just overwrite. */
					$sorting = array(
						'key'        => $sort->field->ID,
						'direction'  => $sort->direction,
						'is_numeric' => Entry_Sort::NUMERIC == $sort->mode,
					);
				}

				/** The offset and limit */
				if ( ! empty( $offset->limit ) ) {
					$paging['page_size'] = $offset->limit;
				}

				if ( ! empty( $offset->offset ) ) {
					$paging['offset'] = $offset->offset;
				}

				foreach ( \GFAPI::get_entries( $form->ID, $search_criteria, $sorting, $paging ) as $entry ) {
					$entries->add( \GV\GF_Entry::from_entry( $entry ) );
				}

				return $entries;
			}
		);

		/** Add the counter lazy callback. */
		$entries->add_count_callback(
			function ( $filters ) use ( $form ) {
				$search_criteria = array();
				$sorting         = array();

				/** Apply the filters */
				/** @type \GV\GF_Entry_Filter|\GV\Entry_Filter $filter */
				foreach ( $filters as $filter ) {
						$search_criteria = $filter::merge_search_criteria( $search_criteria, $filter->as_search_criteria() );
				}

				return \GFAPI::count_entries( $form->ID, $search_criteria );
			}
		);

		return $entries;
	}

	/**
	 * Get a \GV\Field by Form and Field ID for this data source.
	 *
	 * @param \GV\GF_Form $form The Gravity Form form ID.
	 * @param int         $field_id The Gravity Form field ID for the $form_id.
	 *
	 * @return \GV\Field|null The requested field or null if not found.
	 */
	public static function get_field( /** varargs */ ) {
		$args = func_get_args();

		if ( ! is_array( $args ) || 2 != count( $args ) ) {
			gravityview()->log->error( '{source} expects 2 arguments for ::get_field ($form, $field_id)', array( 'source' => __CLASS__ ) );
			return null;
		}

		/** Unwrap the arguments. */
		list( $form, $field_id ) = $args;

		/** Wrap it up into a \GV\Field. */
		return GF_Field::by_id( $form, $field_id );
	}

	/**
	 * Get an array of GV Fields for this data source
	 *
	 * @return \GV\Field[]|array Empty array if no fields
	 */
	public function get_fields() {
		$fields = array();
		foreach ( $this['fields'] as $field ) {
			foreach ( empty( $field['inputs'] ) ? array( $field['id'] ) : wp_list_pluck( $field['inputs'], 'id' ) as $id ) {
				if ( is_numeric( $id ) ) {
					$fields[ $id ] = self::get_field( $this, $id );
				} else {
					$fields[ $id ] = Internal_Field::by_id( $id );
				}
			}
		}

		return array_filter( $fields );
	}

	/**
	 * Proxies.
	 *
	 * @param string $key The property to get.
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		switch ( $key ) {
			case 'fields':
				return $this->get_fields();
			default:
				return parent::__get( $key );
		}
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms form array.
	 *
	 * @internal
	 * @deprecated
	 * @since 2.0
	 * @return bool Whether the offset exists or not.
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		return isset( $this->form[ $offset ] );
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms form array.
	 *
	 * Maps the old keys to the new data;
	 *
	 * @internal
	 * @deprecated
	 * @since 2.0
	 *
	 * @return mixed The value of the requested form data.
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		return $this->form[ $offset ];
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms form array.
	 *
	 * @internal
	 * @deprecated
	 * @since 2.0
	 *
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		gravityview()->log->error( 'The underlying Gravity Forms form is immutable. This is a \GV\Form object and should not be accessed as an array.' );
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms form array.
	 *
	 * @internal
	 * @deprecated
	 * @since 2.0
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		gravityview()->log->error( 'The underlying Gravity Forms form is immutable. This is a \GV\Form object and should not be accessed as an array.' );
	}
}
