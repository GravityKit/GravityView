<?php

use GV\Frontend_Request;
use GV\GF_Entry;
use GV\View;

class GravityView_Lightbox_Entry_Request extends Frontend_Request {
	/**
	 * Gravity Forms entry object.
	 *
	 * @since 2.29.0
	 *
	 * @var GF_Entry
	 */
	private $entry;

	/**
	 * GravityView View object.
	 *
	 * @since 2.29.0
	 *
	 * @var View
	 */
	private $view;

	/**
	 * Class constructor.
	 *
	 * @param View     $view
	 * @param GF_Entry $entry
	 */
	public function __construct( View $view, GF_Entry $entry ) {
		$this->entry = $entry;
		$this->view  = $view;

		parent::__construct();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 2.29.0
	 */
	public function is_entry( $form_id = 0 ) {
		return $this->entry;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 2.29.0
	 */
	public function is_view( $return_view = true ) {
		return $return_view ? $this->view : true;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 2.29.0
	 */
	public function is_renderable(): bool {
		return true;
	}
}
