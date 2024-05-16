<?php
namespace GV\REST;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The default REST Request class.
 */
class Request extends \GV\Request {
	private $request;

	/**
	 * @param \WP_REST_Request $request The WordPress REST request object.
	 */
	public function __construct( \WP_REST_Request $request ) {
		$this->request = $request;
	}

	public function is_view( $return_view = true ) {
		return parent::is_view( $return_view );
	}

	public function is_entry( $form_id = 0 ) {

		//
		//
		// TODO: This is a temporary fix!
		//
		//
		if ( isset( $_GET['lightbox'] ) ) {
			return true;
		}

		return parent::is_entry( $form_id );
	}

	/**
	 * Retrieve paging parameters if any.
	 *
	 * @return array
	 */
	public function get_paging() {
		return array(
			'paging' => array(
				'page_size'    => $this->request->get_param( 'limit' ),
				'current_page' => $this->request->get_param( 'page' ),
			),
		);
	}
}
