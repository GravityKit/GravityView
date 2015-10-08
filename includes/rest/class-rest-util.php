<?php

/**
 * Utility functions for the GV REST API
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Josh Pollock <josh@joshpress.net>
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.14.4
 */

class GravityView_REST_Util {

	/**
	 * Get namespace for GravityView REST API endpoints
	 *
	 * @since 1.14.4
	 * @return string
	 */
	public static function get_namespace() {
		return 'gravityview/v1';

	}

	/**
	 * Get root URL for GravityView REST API
	 *
	 * @since 1.14.4
	 * @return string
	 */
	public static function get_url() {
		return rest_url( self::get_namespace() );

	}

}

