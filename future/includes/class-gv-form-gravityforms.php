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
	 * @since future
	 */
	public static $backend = self::BACKEND_GRAVITYFORMS;

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
	 * @since future
	 * @return \GV\GF_Form|null An instance of this form or null if not found.
	 */
	public static function by_id( $form_id ) {
		$form = \GFAPI::get_form( $form_id );
		if ( !$form ) {
			return null;
		}

		$self = new self();
		$self->form = $form;

		$self->ID = $self->form['id'];

		return $self;
	}

	/**
	 * Get all entries for this form.
	 *
	 * @api
	 * @since future
	 *
	 * @return \GV\Entry_Collection The \GV\Entry_Collection
	 */
	public function get_entries() {
		$entries = new \GV\Entry_Collection();

		$form = &$this;

		/** Add the fetcher lazy callback. */
		$entries->add_fetch_callback( function( $filters, $sorts, $offset ) use ( $form ) {
			$entries = new \GV\Entry_Collection();

			$search_criteria = array();
			$sorting = array();
			$paging = array();

			/** Apply the filters */
			foreach ( $filters as $filter ) {
				$search_criteria = $filter::merge_search_criteria( $search_criteria, $filter->as_search_criteria() );
			}

			/** Apply the sorts */
			foreach ( $sorts as $sort ) {
				/** Gravity Forms does not have multi-sorting, so just overwrite. */
				$sorting = array(
					'key' => $sort->field->ID,
					'direction' => $sort->direction,
					'is_numeric' => $sort->mode == Entry_Sort::NUMERIC,
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
		} );

		/** Add the counter lazy callback. */
		$entries->add_count_callback( function( $filters ) use ( $form ) {

			$search_criteria = array();
			$sorting = array();

			/** Apply the filters */
			foreach ( $filters as $filter ) {
				$search_criteria = $filter::merge_search_criteria( $search_criteria, $filter->as_search_criteria() );
			}

			return \GFAPI::count_entries( $form->ID, $search_criteria );
		} );

		return $entries;
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms form array.
	 *
	 * @internal
	 * @deprecated
	 * @since future
	 * @return bool Whether the offset exists or not.
	 */
	public function offsetExists( $offset ) {
		return isset( $this->form[$offset] );
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms form array.
	 *
	 * Maps the old keys to the new data;
	 *
	 * @internal
	 * @deprecated
	 * @since future
	 *
	 * @return mixed The value of the requested form data.
	 */
	public function offsetGet( $offset ) {
		return $this->form[$offset];
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms form array.
	 *
	 * @internal
	 * @deprecated
	 * @since future
	 *
	 * @return void
	 */
	public function offsetSet( $offset, $value ) {
		gravityview()->log->error( 'The underlying Gravity Forms form is immutable. This is a \GV\Form object and should not be accessed as an array.' );
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms form array.
	 *
	 * @internal
	 * @deprecated
	 * @since future
	 * @return void
	 */
	public function offsetUnset( $offset ) {
		gravityview()->log->error( 'The underlying Gravity Forms form is immutable. This is a \GV\Form object and should not be accessed as an array.' );
	}
}
