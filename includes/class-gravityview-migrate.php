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

		add_action( 'admin_menu', array( $this, 'redirect_old_admin_pages' ) );
	}

	public function update_settings() {

		$this->maybe_migrate_search_widget();

		$this->migrate_redux_settings();

		$this->maybe_migrate_approved_meta();
	}

	/**
	 * Redirects old GravityView admin pages to the new ones.
	 *
	 * @since 2.16
	 *
	 * @return void
	 */
	public function redirect_old_admin_pages() {
		global $pagenow;

		if ( ! $pagenow || ! is_admin() ) {
			return;
		}

		// Provide redirect for old GravityView settings page.
		if ( 'edit.php' !== $pagenow ) {
			return;
		}

		switch ( \GV\Utils::_GET( 'page' ) ) {
			case 'gravityview_settings':
				wp_safe_redirect( admin_url( 'admin.php?page=gk_settings&p=gravityview&s=0' ) );
				die();
			case 'grant-gravityview-access':
				wp_safe_redirect( admin_url( 'admin.php?page=gk_foundation_trustedlogin' ) );
				die();
			case 'gv-admin-installer':
				wp_safe_redirect( admin_url( 'admin.php?page=gk_licenses' ) );
				die();
		}
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
			gravityview()->log->error( 'GFFormsModel does not exist.' );
			return;
		}

		if ( version_compare( GFFormsModel::get_database_version(), '2.3-dev-1', '>=' ) ) {
			$table_name = GFFormsModel::get_entry_meta_table_name();
		} else {
			$table_name = GFFormsModel::get_lead_meta_table_name();
		}

		$sql = "UPDATE {$table_name} SET `meta_value` = %s WHERE `meta_key` = 'is_approved' AND `meta_value` = %s";

		$approved_result = $wpdb->query( $wpdb->prepare( $sql, GravityView_Entry_Approval_Status::APPROVED, 'Approved' ) );

		$disapproved_result = $wpdb->query( $wpdb->prepare( $sql, GravityView_Entry_Approval_Status::DISAPPROVED, '0' ) );

		if ( false === $approved_result || false === $disapproved_result ) {
			gravityview()->log->error( 'There was an error processing the query. {error}', array( 'error' => $wpdb->last_error ) );
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
		if ( false === $redux_settings ) {
			return;
		}

		// Get the current app settings (just defaults)
		$current = gravityview()->plugin->settings->all();

		// Merge the redux settings with the defaults
		$updated_settings = wp_parse_args( $redux_settings, $current );

		// Update the defaults to the new merged
		gravityview()->plugin->settings->update( $updated_settings );

		// And now remove the previous option, so this is a one-time thing.
		delete_option( 'gravityview_settings' );
		delete_option( 'gravityview_settings-transients' );
	}

	/**
	 * Get Redux settings, if they exist
	 *
	 * @since 1.7.4
	 * @return array|bool
	 */
	function get_redux_settings() {

		// Previous settings set by Redux
		$redux_option = get_option( 'gravityview_settings' );

		// No Redux settings? Don't proceed.
		if ( false === $redux_option ) {
			return false;
		}

		$redux_settings = array(
			'support-email'    => \GV\Utils::get( $redux_option, 'support-email' ),
			'no-conflict-mode' => \GV\Utils::get( $redux_option, 'no-conflict-mode' ) ? '1' : '0',
		);

		return $redux_settings;
	}


	/** ----  Migrate from old search widget to new search widget  ---- */
	function update_search_on_views() {

		if ( ! class_exists( 'GravityView_Widget_Search' ) ) {
			include_once GRAVITYVIEW_DIR . 'includes/extensions/search-widget/class-search-widget.php';
		}

		// Loop through all the views
		$query_args = array(
			'post_type'      => 'gravityview',
			'post_status'    => 'any',
			'posts_per_page' => -1,
		);

		$views = get_posts( $query_args );

		foreach ( $views as $view ) {

			$widgets       = gravityview_get_directory_widgets( $view->ID );
			$search_fields = null;

			if ( empty( $widgets ) || ! is_array( $widgets ) ) {
				continue; }

			gravityview()->log->debug( '[GravityView_Migrate/update_search_on_views] Loading View ID: {view_id}', array( 'view_id' => $view->ID ) );

			foreach ( $widgets as $area => $ws ) {
				foreach ( $ws as $k => $widget ) {
					if ( 'search_bar' !== $widget['id'] ) {
						continue; }

					if ( is_null( $search_fields ) ) {
						$search_fields = $this->get_search_fields( $view->ID );
					}

					// check widget settings:
					// [search_free] => 1
					// [search_date] => 1
					$search_generic = array();
					if ( ! empty( $widget['search_free'] ) ) {
						$search_generic[] = array(
							'field' => 'search_all',
							'input' => 'input_text',
						);
					}
					if ( ! empty( $widget['search_date'] ) ) {
						$search_generic[] = array(
							'field' => 'entry_date',
							'input' => 'date',
						);
					}

					$search_config = array_merge( $search_generic, $search_fields );

					// don't throw '[]' when json_encode an empty array
					if ( empty( $search_config ) ) {
						$search_config = '';
					} else {
						$search_config = json_encode( $search_config );
					}

					$widgets[ $area ][ $k ]['search_fields'] = $search_config;
					$widgets[ $area ][ $k ]['search_layout'] = 'horizontal';

					gravityview()->log->debug( '[GravityView_Migrate/update_search_on_views] Updated Widget: ', array( 'data' => $widgets[ $area ][ $k ] ) );
				}
			}

			// update widgets view
			gravityview_set_directory_widgets( $view->ID, $widgets );

		} // foreach Views

		// all done! enjoy the new Search Widget!
		update_option( 'gv_migrate_searchwidget', true );

		gravityview()->log->debug( '[GravityView_Migrate/update_search_on_views] All done! enjoy the new Search Widget!' );
	}


	function get_search_fields( $view_id ) {

		$form_id = gravityview_get_form_id( $view_id );
		$form    = gravityview_get_form( $form_id );

		$search_fields = array();

		// check view fields' settings
		$fields = gravityview_get_directory_fields( $view_id, false );

		if ( ! empty( $fields ) && is_array( $fields ) ) {

			foreach ( $fields as $t => $fs ) {

				foreach ( $fs as $k => $field ) {
					// is field a search_filter ?
					if ( empty( $field['search_filter'] ) ) {
						continue; }

					// get field type & calculate the input type (by default)
					$form_field = gravityview_get_field( $form, $field['id'] );

					if ( empty( $form_field['type'] ) ) {
						continue;
					}

					// depending on the field type assign a group of possible search field types
					$type = GravityView_Widget_Search::get_search_input_types( $field['id'], $form_field['type'] );

					// add field to config
					$search_fields[] = array(
						'field' => $field['id'],
						'input' => $type,
					);

				}
			}
		}

		return $search_fields;
	}
} // end class

new GravityView_Migrate();
