<?php
/**
 * GravityView Defining Post Types and Rewrite rules
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 * @deprecated
 *
 * @since 1.0.9
 */

class GravityView_Post_Types {

	function __construct() {
	}

	/**
	 * Init plugin components such as register own custom post types
	 *
	 * @deprecated
	 * @see \GV\View::register_post_type
	 * @return void
	 */
	public static function init_post_types() {
		\GV\View::register_post_type();
	}

	/**
	 * Register rewrite rules to capture the single entry view
	 *
	 * @deprecated
	 * @see \GV\Entry::add_rewrite_endpoint
	 * @return void
	 */
	public static function init_rewrite() {
		\GV\Entry::add_rewrite_endpoint();
	}

	/**
	 * Return the query var / end point name for the entry
	 *
	 * @deprecated
	 * @see \GV\Entry::get_endpoint_name
	 * @return string Default: "entry"
	 */
	public static function get_entry_var_name() {
		return \GV\Entry::get_endpoint_name();
	}
}

new GravityView_Post_Types();
