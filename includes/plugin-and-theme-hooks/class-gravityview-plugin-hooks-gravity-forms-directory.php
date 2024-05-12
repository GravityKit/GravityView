<?php
/**
 * Customization for the Gravity Forms Directory plugin
 *
 * @file      class-gravityview-plugin-hooks-gravity-forms-directory.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2016, Katz Web Services, Inc.
 *
 * @since 2.1.2
 */

/**
 * @inheritDoc
 * @since 2.1.2
 */
class GravityView_Plugin_Hooks_Gravity_Forms_Directory extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @type string Class that should be exist in a plugin or theme. Used to check whether plugin is active.
	 * @since 2.1.2
	 */
	protected $class_name = 'KWS_GF_Change_Lead_Creator';

	protected function add_hooks() {

		$KWS_GF_Change_Lead_Creator = new KWS_GF_Change_Lead_Creator();

		// Now, no validation is required in the methods; let's hook in.
		remove_action( 'admin_init', array( $KWS_GF_Change_Lead_Creator, 'set_screen_mode' ) );

		remove_action( 'gform_entry_info', array( $KWS_GF_Change_Lead_Creator, 'add_select' ), 10 );

		remove_action( 'gform_after_update_entry', array( $KWS_GF_Change_Lead_Creator, 'update_entry_creator' ), 10 );
	}
}

new GravityView_Plugin_Hooks_Gravity_Forms_Directory();
