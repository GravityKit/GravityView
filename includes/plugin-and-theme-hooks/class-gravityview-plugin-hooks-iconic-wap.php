<?php
/**
 * Add WooCommerce Account Pages fixes to display Views in the My Account pages.
 *
 * @file      class-gravityview-plugin-hooks-iconic-wap.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      https://www.gravitykit.com
 * @copyright Copyright 2023, Katz Web Services, Inc.
 *
 * @since 2.18.7
 */

/**
 * @inheritDoc
 */
class GravityView_Plugin_Hooks_Iconic_WAP extends GravityView_Plugin_and_Theme_Hooks {

	protected $class_name = 'Iconic_Woo_Account_Pages';

	protected function add_hooks() {
		parent::add_hooks();

		$subpages = Iconic_WAP_Helpers::get_account_subpages();

		if ( empty( $subpages ) ) {
			return;
		}

		add_filter( 'gravityview_directory_link', array( $this, 'modify_directory_permalink' ) );

		add_filter( 'gravityview/entry/permalink', array( $this, 'modify_entry_permalink' ), 10, 4 );

		add_filter( 'iconic_wap_content_kses_allowed_tags', array( $this, 'modify_allowed_tags' ) );
	}

	/**
	 * Add tags required to output GravityView to the list of allowed tags when Views are in the content.
	 *
	 * @param array $allowed_tags Previously-allowed tags.
	 *
	 * @return array Modified allowed tags.
	 */
	public function modify_allowed_tags( $allowed_tags ) {

		$endpoint_views = self::get_current_endpoint_views();

		if ( is_null( $endpoint_views ) || 0 === \sizeof( $endpoint_views->all() ) ) {
			return $allowed_tags;
		}

		// Open up the allowed tags to default Post items.
		$allowed_tags = array_merge( wp_kses_allowed_html( 'post' ), $allowed_tags );

		$attributes = array(
			'multiple'       => true,
			'value'          => true,
			'selected'       => true,
			'id'             => true,
			'name'           => true,
			'aria-invalid'   => true,
			'aria-required'  => true,
			'class'          => true,
			'for'            => true,
			'data-js-reload' => true,
			'onclick'        => true,
			'type'           => true,
			'src'            => true,
			'style'          => true,
			'placeholder'    => true,
			'title'          => true,
		);

		$allowed_tags['div'][] = array(
			'data-js-reload' => true,
		);

		// And GravityView needs a few more.
		$allowed_tags['style']        = array(
			'nonce'    => true,
			'title'    => true,
			'media'    => true,
			'blocking' => true,
			'type'     => true,
		);
		$allowed_tags['script']       = array(
			'src'            => true,
			'async'          => true,
			'charset'        => true,
			'crossorigin'    => true,
			'defer'          => true,
			'fetchpriority'  => true,
			'integrity'      => true,
			'nomodule'       => true,
			'nonce'          => true,
			'referrerpolicy' => true,
			'language'       => true,
			'type'           => true,
		);
		$allowed_tags['fieldset']     = $attributes;
		$allowed_tags['textarea']     = $attributes;
		$allowed_tags['button']       = $attributes;
		$allowed_tags['label']        = $attributes;
		$allowed_tags['legend']       = $attributes;
		$allowed_tags['iframe']       = $attributes;
		$allowed_tags['input']        = $attributes;
		$allowed_tags['select']       = $attributes;
		$allowed_tags['option']       = $attributes;
		$allowed_tags['a']['onclick'] = true;
		$allowed_tags['a']['title']   = true;

		return $allowed_tags;
	}

	/**
	 * WooCommerce Account Pages doesn't parse the entry endpoint correctly. Adding the entry ID to the URL fixes it.
	 * There _may_ be a way to do this via {@see add_rewrite_endpoint()}, but it wasn't working.
	 * For now, adding the endpoint via query arg works.
	 *
	 * @param string        $permalink The permalink.
	 * @param \GV\Entry     $entry The entry we're retrieving it for.
	 * @param \GV\View|null $view The view context.
	 * @param \GV\Request   $request The request context.
	 *
	 * @return string
	 */
	public function modify_entry_permalink( $permalink, $entry, $view = null, $request = null ) {

		$entry_query_arg = array(
			GV\Entry::get_endpoint_name() => $entry->get_slug( true, $view, $request ),
		);

		return add_query_arg( $entry_query_arg, $permalink );
	}

	/**
	 * Get any Views being displayed on the current Woo Account Pages content.
	 *
	 * @return \GV\View_Collection|null
	 */
	protected static function get_current_endpoint_views() {
		$endpoint_post = self::get_current_endpoint_page_object();

		if ( ! $endpoint_post ) {
			return null;
		}

		return \GV\View_Collection::from_content( $endpoint_post->post_content );
	}

	/**
	 * Get current endpoint page object.
	 *
	 * @see Iconic_WAP_Pages::get_current_endpoint_page_object() for the original method.
	 *
	 * @return bool|WP_Post
	 */
	protected static function get_current_endpoint_page_object() {
		$endpoint_id = \Iconic_WAP_Pages::is_endpoint();

		if ( ! $endpoint_id ) {
			return false;
		}

		$endpoint_post = get_post( $endpoint_id );

		if ( is_null( $endpoint_post ) || is_wp_error( $endpoint_post ) ) {
			return false;
		}

		return $endpoint_post;
	}

	/**
	 * Update directory links when embedded inside the WAP endpoint, since it's always nested under the My Account page.
	 *
	 * @param string $link URL to the View's "directory" context (Multiple Entries screen)
	 *
	 * @return string The updated URL with a link to the WooCommerce Account Pages endpoint instead of My Account.
	 */
	public function modify_directory_permalink( $link ) {

		$endpoint = Iconic_WAP_Pages::is_endpoint();

		if ( ! $endpoint ) {
			return $link;
		}

		// my-account/my-custom-account-page
		$page_uri = get_page_uri( $endpoint );

		// https://example.com/my-account/my-custom-account-page
		$site_url = site_url( $page_uri );

		// https://example.com/my-account/my-custom-account-page/
		$site_url = user_trailingslashit( $site_url );

		/**
		 * Now re-build the query string from the original link and add it to the new URL (for example: `?pagenum=3`).
		 */
		$url = wp_parse_url( $link );

		$query = array();
		if ( isset( $url['query'] ) ) {
			parse_str( $url['query'], $query );
		}

		return add_query_arg( $query, $site_url );
	}
}

new GravityView_Plugin_Hooks_Iconic_WAP();
