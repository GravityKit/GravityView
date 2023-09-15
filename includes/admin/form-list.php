<?php

/**
 * Actions to be performed on the Gravity Forms forms list screen
 */
class GravityView_GF_Forms_List {

	public function __construct() {

		// Add Edit link to the entry actions
		add_filter( 'views_toplevel_page_gf_edit_forms', [ $this, 'add_views_filters' ] );

		add_filter( 'gform_form_list_forms', [ $this, 'filter_forms' ], 10, 6 );
	}

	/**
	 * @param array $views The format is an associative array: `'id' => 'link'`
	 *
	 * @return array
	 */
	public function add_views_filters( $views ) {

		$views['has_gravityview_views'] = '<a href="'. esc_url( add_query_arg( ['filter' => 'views' ], add_query_arg( [] ) ) ) .'" title="'. esc_attr__( 'Forms that are connected to GravityView Views', 'gk-gravityview' ) .'">' . esc_html__( 'Has GravityView', 'gk-gravityview' ) . '</a>';

		return $views;
	}

	/**
	 * Allow form list filtering.
	 *
	 * @since 2.3-beta-3
	 *
	 * @param array  $forms          The complete list of forms.
	 */
	public function filter_forms( $forms ) {

		if ( 'views' !== \GV\Utils::_GET( 'filter' ) ) {
			return $forms;
		}

		static $forms_filtered = [];

		if ( ! empty( $forms_filtered ) ) {
			return $forms_filtered;
		}

		foreach ( $forms as $key => $form ) {

			$connected_views = gravityview_get_connected_views( $form->id, ['posts_per_page' => 1 ], false );

			if ( empty( $connected_views ) ) {
				unset( $forms[ $key ] );
			}
		}

		$forms_filtered = $forms;

		return $forms;
	}
}

new GravityView_GF_Forms_List();
