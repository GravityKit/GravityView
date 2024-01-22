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
