<?php
/**
 * GravityView Migrate Class - where awesome features become even better, seamlessly!
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.2
 */


class GravityView_Migrate {

	function __construct() {
		add_action( 'admin_init', array( $this, 'update_settings' ), 1 );
	}

	public function update_settings() {

		$this->maybe_migrate_search_widget();

		$this->migrate_redux_settings();

		$this->maybe_migrate_approved_meta();

	}

	/**
	 * Convert approval meta values to enumerated values
	 *
	 * @since 1.18
	 */
	private function maybe_migrate_approved_meta() {

		// check if search migration is already performed
		$is_updated = get_option( 'gv_migrated_approved_meta' );

		if ( $is_updated ) {
			return;
		}

		$this->update_approved_meta();
	}

	/**
	 * Convert "Approved" approval status to "1"
	 *
	 * @since 1.18
	 *
	 * @return void
	 */
	private function update_approved_meta() {
		global $wpdb;

		if ( ! class_exists( 'GFFormsModel' ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . ': GFFormsModel does not exist.' );
			return;
		}

		$table_name = GFFormsModel::get_lead_meta_table_name();

		$sql = "UPDATE {$table_name} SET `meta_value` = %s WHERE `meta_key` = 'is_approved' AND `meta_value` = %s";

		$approved_result = $wpdb->query( $wpdb->prepare( $sql, GravityView_Entry_Approval_Status::APPROVED, 'Approved' ) );

		$disapproved_result = $wpdb->query( $wpdb->prepare( $sql, GravityView_Entry_Approval_Status::DISAPPROVED, '0' ) );

		if( false === $approved_result || false === $disapproved_result ) {
			do_action( 'gravityview_log_error', __METHOD__ . ': There was an error processing the query.', $wpdb->last_error );
		} else {
			// All done: Meta values are migrated
			update_option( 'gv_migrated_approved_meta', true );
		}
	}

	/**
	 * @since 1.7.4
	 */
	private function maybe_migrate_search_widget() {

		// check if search migration is already performed
		$is_updated = get_option( 'gv_migrate_searchwidget' );
		if ( $is_updated ) {
			return;
		} else {
			$this->update_search_on_views();
		}
	}

	/**
	 * Set app settings from prior Redux settings, if exists
	 *
	 * @since 1.7.4
	 * @return mixed|void
	 */
	private function migrate_redux_settings() {

		$redux_settings = $this->get_redux_settings();

		// No need to process
		if( false === $redux_settings ) {
			return;
		}

		if( empty(  $redux_settings['license_key_status'] ) ) {
			$redux_settings = $this->get_redux_license_status( $redux_settings );
		}

		// Get the current app settings (just defaults)
		$current = GravityView_Settings::get_instance()->get_app_settings();

		// Merge the redux settings with the defaults
		$updated_settings = wp_parse_args( $redux_settings, $current );

		// Update the defaults to the new merged
		GravityView_Settings::get_instance()->update_app_settings( $updated_settings );

		// And now remove the previous option, so this is a one-time thing.
		delete_option('gravityview_settings');
		delete_option('gravityview_settings-transients');
	}

	/**
	 * If the settings transient wasn't set, we need to set the default status for the license
	 *
	 * @since 1.7.4
	 *
	 * @param array $redux_settings
	 *
	 * @return array
	 */
	function get_redux_license_status( $redux_settings = array() ) {

		$data = array(
			'edd_action' => 'check_license',
			'license' => rgget('license_key', $redux_settings ),
			'update' => false,
			'format' => 'object',
		);

		$license_call = GravityView_Settings::get_instance()->get_license_handler()->license_call( $data );

		if( is_object( $license_call ) && isset( $license_call->license ) ) {
			$redux_settings['license_key_status'] = $license_call->license;
			$redux_settings['license_key_response'] = json_encode( $license_call );
		}

		return $redux_settings;
	}

	/**
	 * Get Redux settings, if they exist
	 * @since 1.7.4
	 * @return array|bool
	 */
	function get_redux_settings() {

		// Previous settings set by Redux
		$redux_option = get_option('gravityview_settings');

		// No Redux settings? Don't proceed.
		if( false === $redux_option ) {
			return false;
		}


		$redux_settings = array(
			'support-email' => rgget( 'support-email', $redux_option ),
			'no-conflict-mode' => ( rgget( 'no-conflict-mode', $redux_option ) ? '1' : '0' ),
		);

		if( $license_array = rgget( 'license', $redux_option ) ) {

			$redux_settings['license_key'] = $license_key = rgget( 'license', $license_array );

			$redux_last_changed_values = get_option('gravityview_settings-transients');

			// This contains the last response for license validation
			if( !empty( $redux_last_changed_values ) && $saved_values = rgget( 'changed_values', $redux_last_changed_values ) ) {

				$saved_license = rgget('license', $saved_values );

				// Only use the last-saved values if they are for the same license
				if( $saved_license && rgget( 'license', $saved_license ) === $license_key ) {
					$redux_settings['license_key_status'] = rgget( 'status', $saved_license );
					$redux_settings['license_key_response'] = rgget( 'response', $saved_license );
				}
			}
		}

		return $redux_settings;
	}


	/** ----  Migrate from old search widget to new search widget  ---- */
	function update_search_on_views() {

		if( !class_exists('GravityView_Widget_Search') ) {
			include_once( GRAVITYVIEW_DIR .'includes/extensions/search-widget/class-search-widget.php' );
		}

		// Loop through all the views
		$query_args = array(
			'post_type' => 'gravityview',
			'post_status' => 'any',
			'posts_per_page' => -1,
		);

		$views = get_posts( $query_args );

		foreach( $views as $view ) {

			$widgets = gravityview_get_directory_widgets( $view->ID );
			$search_fields = null;

			if( empty( $widgets ) || !is_array( $widgets ) ) { continue; }

			do_action( 'gravityview_log_debug', '[GravityView_Migrate/update_search_on_views] Loading View ID: ', $view->ID );

			foreach( $widgets as $area => $ws ) {
				foreach( $ws as $k => $widget ) {
					if( $widget['id'] !== 'search_bar' ) { continue; }

					if( is_null( $search_fields ) ) {
						$search_fields = $this->get_search_fields( $view->ID );
					}

					// check widget settings:
					//  [search_free] => 1
			        //  [search_date] => 1
			        $search_generic = array();
					if( !empty( $widget['search_free'] ) ) {
						$search_generic[] = array( 'field' => 'search_all', 'input' => 'input_text' );
					}
					if( !empty( $widget['search_date'] ) ) {
						$search_generic[] = array( 'field' => 'entry_date', 'input' => 'date' );
					}

					$search_config = array_merge( $search_generic, $search_fields );

					// don't throw '[]' when json_encode an empty array
					if( empty( $search_config ) ) {
						$search_config = '';
					} else {
						$search_config = json_encode( $search_config );
					}

					$widgets[ $area ][ $k ]['search_fields'] = $search_config;
					$widgets[ $area ][ $k ]['search_layout'] = 'horizontal';

					do_action( 'gravityview_log_debug', '[GravityView_Migrate/update_search_on_views] Updated Widget: ', $widgets[ $area ][ $k ] );
				}
			}

			// update widgets view
			gravityview_set_directory_widgets( $view->ID, $widgets );

		} // foreach Views

		// all done! enjoy the new Search Widget!
		update_option( 'gv_migrate_searchwidget', true );

		do_action( 'gravityview_log_debug', '[GravityView_Migrate/update_search_on_views] All done! enjoy the new Search Widget!' );
	}


	function get_search_fields( $view_id ) {

		$form_id = gravityview_get_form_id( $view_id );
		$form = gravityview_get_form( $form_id );

		$search_fields = array();

		// check view fields' settings
		$fields = gravityview_get_directory_fields( $view_id, false );

		if( !empty( $fields ) && is_array( $fields ) ) {

			foreach( $fields as $t => $fs ) {

				foreach( $fs as $k => $field ) {
					// is field a search_filter ?
					if( empty( $field['search_filter'] ) ) { continue; }

					// get field type & calculate the input type (by default)
					$form_field = gravityview_get_field( $form, $field['id'] );

					if( empty( $form_field['type'] ) ) {
						continue;
					}

					// depending on the field type assign a group of possible search field types
					$type = GravityView_Widget_Search::get_search_input_types( $field['id'], $form_field['type'] );

					// add field to config
					$search_fields[] = array( 'field' => $field['id'], 'input' => $type );

				}
			}
		}

		return $search_fields;
	}



} // end class

new GravityView_Migrate;
