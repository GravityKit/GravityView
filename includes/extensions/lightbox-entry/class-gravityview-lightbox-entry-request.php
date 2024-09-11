<?php

use GV\Frontend_Request;
use GV\GF_Entry;
use GV\View;

class GravityView_Lightbox_Entry_Request extends Frontend_Request {
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
	 * @since TBD
	 */
	public function is_entry( $form_id = 0 ) {
		return $this->entry;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 */
	public function is_view( $return_view = true ) {
		return $return_view ? $this->view : true;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 */
	public function is_renderable(): bool {
		return true;
	}
}
