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

	public static function get_namespace() {
		return 'gravity-view/v1';

	}

	public static function get_url() {
		return rest_url( self::get_namespace() );

	}

}
