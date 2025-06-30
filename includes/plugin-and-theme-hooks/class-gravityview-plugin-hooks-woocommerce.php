<?php
/**
 * Add WooCommerce scripts and styles to GravityView no-conflict list
 *
 * @file      class-gravityview-plugin-hooks-woocommerce.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15.2
 */

/**
 * @inheritDoc
 */
class GravityView_Plugin_Hooks_WooCommerce extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @var string
	 */
	protected $function_name = 'wc_get_page_id';

	/**
	 * @inheritDoc
	 * @var array
	 */
	protected $style_handles = array(
		'woocommerce_admin_menu_styles',
		'woocommerce_admin_styles',
	);

	/**
	 * @inheritDoc
	 *
	 * @since TODO
	 */
	public function add_hooks() {
		parent::add_hooks();

		// Add on template_redirect to allow for $post to be set.
		add_action(
			'template_redirect',
			function () {
				$this->add_permalink_hooks();
			},
			1
		);
	}

	/**
	 * Add permalink hooks to disable permalinks when the My Account page is loaded.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	private function add_permalink_hooks() {
		$page_id = wc_get_page_id( 'myaccount' );

		if ( get_the_ID() !== $page_id ) {
			return;
		}

		/**
		 * Adds entry query arg to the permalink to make sure that GravityView "sees" that we're in an entry.
		 *
		 * @since TODO
		 * @param string $permalink The permalink.
		 * @param \GV\Entry $entry The entry we're retrieving it for.
		 * @param \GV\View|null $view The view context. Optional.
		 * @param \GV\Request $request The request context. Optional.
		 */
		add_filter(
			'gravityview/entry/permalink',
			function ( $permalink, $entry ) {
				$endpoint = \GV\Entry::get_endpoint_name();
				return add_query_arg( $endpoint, $entry->get_slug( true ), $permalink );
			},
			10,
			2
		);

		/**
		 * Make sure that GravityView "sees" that we're in an entry.
		 *
		 * @see \GV\Request::is_entry()
		 */
		add_filter(
			'gravityview/edit/link',
			function ( $url, $entry ) {
				$entry    = \GV\GF_Entry::by_id( $entry['id'] );
				$endpoint = \GV\Entry::get_endpoint_name();
				return add_query_arg( $endpoint, $entry->get_slug( true ), $url );
			},
			10,
			2
		);
	}
}

new GravityView_Plugin_Hooks_WooCommerce();
