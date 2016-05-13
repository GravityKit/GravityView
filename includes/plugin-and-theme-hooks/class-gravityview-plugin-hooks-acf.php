<?php
/**
 * Add Advanced Custom Fields customizations
 *
 * @file      class-gravityview-plugin-hooks-acf.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since TODO
 */

/**
 * @inheritDoc
 * @since TODO
 */
class GravityView_Plugin_Hooks_ACF extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since TODO
	 */
	protected $function_name = 'acf';

	/**
	 * @since TODO
	 */
	protected function add_hooks() {
		parent::add_hooks();

		$this->fix_posted_fields();
	}

	/**
	 * ACF needs $_POST['fields'] to be an array. GV supports both serialized array and array, so we just process earlier.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	private function fix_posted_fields() {
		if( is_admin() && isset( $_POST['action'] ) && isset( $_POST['post_type'] ) ) {
			if( 'editpost' === $_POST['action'] && 'gravityview' === $_POST['post_type'] ) {
				$_POST['fields'] = _gravityview_process_posted_fields();
			}
		}
	}
}

new GravityView_Plugin_Hooks_ACF;