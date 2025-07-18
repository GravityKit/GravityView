<?php
/**
 * Add Jetpack CRM Client Portal Pro plugin compatibility to GravityView
 *
 * @file      class
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2025, Katz Web Services, Inc.
 *
 * @since TODO
 */

/**
 * Add support for the Jetpack CRM Client Portal Pro plugin
 *
 * @since TODO
 */
class GravityView_Theme_Hooks_Jetpack_CRM_Client_Portal_Pro extends GravityView_Plugin_and_Theme_Hooks {

	use GravityView_Permalink_Override_Trait;

	/**
	 * @inheritDoc
	 *
	 * @since TODO
	 *
	 * @var string
	 */
	protected $constant_name = 'ZBS_CLIENTPORTALPRO_ROOTFILE';

	/**
	 * In addition to Client Portal Pro, we need to make sure that Jetpack CRM is loaded.
	 *
	 * @since TODO
	 *
	 * @var string
	 */
	protected $class_name = 'ZeroBSCRM';

	/**
	 * Remove the permalink structure for Jetpack CRM Client Portal endpoints.
	 *
	 * @since TODO
	 *
	 * @return bool Whether to remove the permalink structure from View rendered links.
	 */
	protected function should_disable_permalink_structure() {

		if ( ! is_callable( 'ZeroBSCRM::instance' ) ) {
			return false;
		}

		$zbs = ZeroBSCRM::instance();

		if ( ! isset( $zbs->modules->portal ) ) {
			return false;
		}

		return $zbs->modules->portal->is_a_client_portal_endpoint();
	}
}

new GravityView_Theme_Hooks_Jetpack_CRM_Client_Portal_Pro();
