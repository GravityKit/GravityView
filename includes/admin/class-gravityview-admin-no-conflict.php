<?php
/**
 * GravityView's No-Conflict mode: disable scripts that interfere with the plugin.
 *
 * @since 1.17
 * @file class-gravityview-admin-no-conflict.php
 * @package GravityView
 * @subpackage includes\admin
 */

/**
 * @since 1.17
 */
class GravityView_Admin_No_Conflict {

	/**
	 * @since 1.17
	 */
	public function __construct() {

		if( ! is_admin() ) { return; }
		
		$this->add_hooks();
	}

	/**
	 * Add the hooks to fix script and style conflicts
	 *
	 * @since 1.17
	 *
	 * @return void
	 */
	private function add_hooks() {
		//Hooks for no-conflict functionality
		add_action( 'wp_print_scripts', array( $this, 'no_conflict_scripts' ), 1000);
		add_action( 'admin_print_footer_scripts', array( $this, 'no_conflict_scripts' ), 9);

		add_action( 'wp_print_styles', array( $this, 'no_conflict_styles' ), 1000);
		add_action( 'admin_print_styles', array( $this, 'no_conflict_styles' ), 11);
		add_action( 'admin_print_footer_scripts', array( $this, 'no_conflict_styles' ), 1);
		add_action( 'admin_footer', array( $this, 'no_conflict_styles' ), 1);
	}

	/**
	 * Callback to eliminate any non-registered script
	 *
	 * @since 1.17 Moved to GravityView_Admin_No_Conflict class
	 *
	 * @return void
	 */
	function no_conflict_scripts() {
		global $wp_scripts;

		if( ! gravityview_is_admin_page() ) {
			return;
		}

		$no_conflict_mode = GravityView_Settings::getSetting('no-conflict-mode');

		if( empty( $no_conflict_mode ) ) {
			return;
		}

		$wp_allowed_scripts = array(
			'common',
			'admin-bar',
			'autosave',
			'post',
			'inline-edit-post',
			'utils',
			'svg-painter',
			'wp-auth-check',
			'heartbeat',
			'media-editor',
			'media-upload',
			'thickbox',
			'wp-color-picker',

			// Settings
			'gv-admin-edd-license',

			// Common
			'select2-js',
			'qtip-js',

			// jQuery
			'jquery',
			'jquery-ui-core',
			'jquery-ui-sortable',
			'jquery-ui-datepicker',
			'jquery-ui-dialog',
			'jquery-ui-slider',
			'jquery-ui-dialog',
			'jquery-ui-tabs',
			'jquery-ui-draggable',
			'jquery-ui-droppable',
			'jquery-ui-accordion',
		);

		$this->remove_conflicts( $wp_scripts, $wp_allowed_scripts, 'scripts' );
	}

	/**
	 * Callback to eliminate any non-registered style
	 *
	 * @since 1.17 Moved to GravityView_Admin_No_Conflict class
	 *
	 * @return void
	 */
	function no_conflict_styles() {
		global $wp_styles;

		if( ! gravityview_is_admin_page() ) {
			return;
		}

		// Dequeue other jQuery styles even if no-conflict is off.
		// Terrible-looking tabs help no one.
		if( !empty( $wp_styles->registered ) )  {
			foreach ($wp_styles->registered as $key => $style) {
				if( preg_match( '/^(?:wp\-)?jquery/ism', $key ) ) {
					wp_dequeue_style( $key );
				}
			}
		}

		$no_conflict_mode = GravityView_Settings::getSetting('no-conflict-mode');

		// If no conflict is off, jQuery will suffice.
		if( empty( $no_conflict_mode ) ) {
			return;
		}

		$wp_allowed_styles = array(
			'admin-bar',
			'colors',
			'ie',
			'wp-auth-check',
			'media-views',
			'thickbox',
			'dashicons',
			'wp-jquery-ui-dialog',
			'jquery-ui-sortable',

			// Settings
			'gravityview_settings',

			// @todo qTip styles not loading for some reason!
			'jquery-qtip.js',
		);

		$this->remove_conflicts( $wp_styles, $wp_allowed_styles, 'styles' );

		/**
		 * @action `gravityview_remove_conflicts_after` Runs after no-conflict styles are removed. You can re-add styles here.
		 */
		do_action('gravityview_remove_conflicts_after');
	}

	/**
	 * Remove any style or script non-registered in the no conflict mode
	 *
	 * @since 1.17 Moved to GravityView_Admin_No_Conflict class
	 *
	 * @param  WP_Dependencies $wp_objects        Object of WP_Styles or WP_Scripts
	 * @param  string[] $required_objects   List of registered script/style handles
	 * @param  string $type              Either 'styles' or 'scripts'
	 * @return void
	 */
	private function remove_conflicts( &$wp_objects, $required_objects, $type = 'scripts' ) {

		/**
		 * @filter `gravityview_noconflict_{$type}` Modify the list of no conflict scripts or styles\n
		 * Filter is `gravityview_noconflict_scripts` or `gravityview_noconflict_styles`
		 * @param array $required_objects
		 */
		$required_objects = apply_filters( "gravityview_noconflict_{$type}", $required_objects );

		//reset queue
		$queue = array();
		foreach( $wp_objects->queue as $object ) {
			if( in_array( $object, $required_objects ) || preg_match('/gravityview|gf_|gravityforms/ism', $object ) ) {
				$queue[] = $object;
			}
		}
		$wp_objects->queue = $queue;

		$required_objects = $this->add_script_dependencies( $wp_objects->registered, $required_objects );

		//unregistering scripts
		$registered = array();
		foreach( $wp_objects->registered as $handle => $script_registration ){
			if( in_array( $handle, $required_objects ) ){
				$registered[ $handle ] = $script_registration;
			}
		}
		$wp_objects->registered = $registered;
	}

	/**
	 * Add dependencies
	 *
	 * @since 1.17 Moved to GravityView_Admin_No_Conflict class
	 *
	 * @param array $registered [description]
	 * @param array $scripts    [description]
	 */
	private function add_script_dependencies($registered, $scripts) {

		//gets all dependent scripts linked to the $scripts array passed
		do {
			$dependents = array();
			foreach ( $scripts as $script ) {
				$deps = isset( $registered[ $script ] ) && is_array( $registered[ $script ]->deps ) ? $registered[ $script ]->deps : array();
				foreach ( $deps as $dep ) {
					if ( ! in_array( $dep, $scripts ) && ! in_array( $dep, $dependents ) ) {
						$dependents[] = $dep;
					}
				}
			}
			$scripts = array_merge( $scripts, $dependents );
		} while ( ! empty( $dependents ) );

		return $scripts;
	}
}

new GravityView_Admin_No_Conflict;