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
		add_action( 'admin_init', array( $this, 'update_settings' ) );
	}



	function update_settings() {

		// check if search migration is already performed
		$is_updated = get_option( 'gv_migrate_searchwidget' );
		if ( $is_updated ) {
			return;
		} else {
			$this->update_search_on_views();
		}

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

			$widgets = get_post_meta( $view->ID, '_gravityview_directory_widgets', true );
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
			update_post_meta( $view->ID, '_gravityview_directory_widgets', $widgets );

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
		$fields = get_post_meta( $view_id, '_gravityview_directory_fields', true );

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
