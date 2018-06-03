<?php
namespace GV\Shortcodes;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The [gventry] shortcode.
 */
class gventry extends \GV\Shortcode {
	/**
	 * {@inheritDoc}
	 */
	public $name = 'gventry';

	/**
	 * Process and output the [gventry] shortcode.
	 *
	 * @param array $atts The attributes passed.
	 * @param string $content The content inside the shortcode.
	 *
	 * @return string|null The output.
	 */
	public function callback( $atts, $content = null ) {

		$request = gravityview()->request;

		if ( $request->is_admin() ) {
			return apply_filters( 'gravityview/shortcodes/gventry/output', '', null, null, $atts );
		}

		$atts = wp_parse_args( $atts, array(
			'id'        => 0,
			'entry_id'  => 0,
			'view_id'   => 0,
		) );

		$atts = gv_map_deep( $atts, array( 'GravityView_Merge_Tags', 'replace_get_variables' ) );

		/**
		 * @filter `gravityview/shortcodes/gventry/atts` Filter the [gventry] shortcode attributes.
		 * @param array $atts The initial attributes.
		 * @since 2.0
		 */
		$atts = apply_filters( 'gravityview/shortcodes/gventry/atts', $atts );

		$view = \GV\View::by_id( $atts['view_id'] );

		if ( ! $view ) {
			gravityview()->log->error( 'View does not exist #{view_id}', array( 'view_id' => $atts['view_id'] ) );
			return apply_filters( 'gravityview/shortcodes/gventry/output', '', null, null, $atts );
		}

		$entry_id = ! empty( $atts['entry_id'] ) ? $atts['entry_id'] : $atts['id'];

		switch( $entry_id ):
			case 'last':
				if ( class_exists( '\GF_Query' ) ) {
					/**
					 * @todo Remove once we refactor the use of get_view_entries_parameters.
					 *
					 * Since we're using \GF_Query shorthand initialization we have to reverse the order parameters here.
					 */
					add_filter( 'gravityview_get_entries', $filter = function( $parameters, $args, $form_id ) {
						if ( ! empty( $parameters['sorting'] ) ) {
							/**
							 * Reverse existing sorts.
							 */
							$sort = &$parameters['sorting'];
							$sort['direction'] = $sort['direction'] == 'RAND' ? : ( $sort['direction'] == 'ASC' ? 'DESC' : 'ASC' );
						} else {
							/**
							 * Otherwise, sort by date_created.
							 */
							$parameters['sorting'] = array(
								'key' => 'id',
								'direction' => 'ASC',
								'is_numeric' => true
							);
						}
						return $parameters;
					}, 10, 3 );
					$entries = $view->get_entries( null );
					remove_filter( 'gravityview_get_entries', $filter );
				} else {
					$entries = $view->get_entries( null );

					/** If a sort already exists, reverse it. */
					if ( $sort = end( $entries->sorts ) ) {
						$entries = $entries->sort( new \GV\Entry_Sort( $sort->field, $sort->direction == \GV\Entry_Sort::RAND ? : ( $sort->direction == \GV\Entry_Sort::ASC ? \GV\Entry_Sort::DESC : \GV\Entry_Sort::ASC ) ), $sort->mode );
					} else {
						/** Otherwise, sort by date_created */
						$entries = $entries->sort( new \GV\Entry_Sort( \GV\Internal_Field::by_id( 'id' ), \GV\Entry_Sort::ASC ), \GV\Entry_Sort::NUMERIC );
					}
				}

				if ( ! $entry = $entries->first() ) {
					return apply_filters( 'gravityview/shortcodes/gventry/output', '', $view, null, $atts );
				}
				break;
			case 'first':
				if ( ! $entry = $view->get_entries( null )->first() ) {
					return apply_filters( 'gravityview/shortcodes/gventry/output', '', $view, null, $atts );
				}
				break;
			default:
				if ( ! $entry = \GV\GF_Entry::by_id( $entry_id ) ) {
					gravityview()->log->error( 'Entry #{entry_id} not found', array( 'view_id' => $atts['view_id'] ) );
					return apply_filters( 'gravityview/shortcodes/gventry/output', '', $view, null, $atts );
				}
		endswitch;

		if ( $view->form->ID != $entry['form_id'] ) {
			gravityview()->log->error( 'Entry does not belong to view (form mismatch)' );
			return apply_filters( 'gravityview/shortcodes/gventry/output', '', $view, $entry, $atts );
		}

		if ( post_password_required( $view->ID ) ) {
			gravityview()->log->notice( 'Post password is required for View #{view_id}', array( 'view_id' => $view->ID ) );
			return apply_filters( 'gravityview/shortcodes/gventry/output', get_the_password_form( $view->ID ), $view, $entry, $atts );
		}

		if ( ! $view->form  ) {
			gravityview()->log->notice( 'View #{id} has no form attached to it.', array( 'id' => $view->ID ) );

			/**
			 * This View has no data source. There's nothing to show really.
			 * ...apart from a nice message if the user can do anything about it.
			 */
			if ( \GVCommon::has_cap( array( 'edit_gravityviews', 'edit_gravityview' ), $view->ID ) ) {
				$return = __( sprintf( 'This View is not configured properly. Start by <a href="%s">selecting a form</a>.', esc_url( get_edit_post_link( $view->ID, false ) ) ), 'gravityview' );
				return apply_filters( 'gravityview/shortcodes/gventry/output', $return, $view, $entry, $atts );
			}

			return apply_filters( 'gravityview/shortcodes/gventry/output', '', $view, $entry, $atts );
		}

		/** Private, pending, draft, etc. */
		$public_states = get_post_stati( array( 'public' => true ) );
		if ( ! in_array( $view->post_status, $public_states ) && ! \GVCommon::has_cap( 'read_gravityview', $view->ID ) ) {
			gravityview()->log->notice( 'The current user cannot access this View #{view_id}', array( 'view_id' => $view->ID ) );
			return apply_filters( 'gravityview/shortcodes/gventry/output', '', $view, $entry, $atts );
		}

		/** Unapproved entries. */
		if ( $entry['status'] != 'active' ) {
			gravityview()->log->notice( 'Entry ID #{entry_id} is not active', array( 'entry_id' => $entry->ID ) );
			return apply_filters( 'gravityview/shortcodes/gventry/output', '', $view, $entry, $atts );
		}

		$is_admin_and_can_view = $view->settings->get( 'admin_show_all_statuses' ) && \GVCommon::has_cap('gravityview_moderate_entries', $view->ID );

		if ( $view->settings->get( 'show_only_approved' ) && ! $is_admin_and_can_view ) {
			if ( ! \GravityView_Entry_Approval_Status::is_approved( gform_get_meta( $entry->ID, \GravityView_Entry_Approval::meta_key ) )  ) {
				gravityview()->log->error( 'Entry ID #{entry_id} is not approved for viewing', array( 'entry_id' => $entry->ID ) );
				return apply_filters( 'gravityview/shortcodes/gventry/output', '', $view, $entry, $atts );
			}
		}

		/** Remove the back link. */
		add_filter( 'gravityview/template/links/back/url', '__return_false' );

		$renderer = new \GV\Entry_Renderer();

		$request = new \GV\Mock_Request();
		$request->returns['is_entry'] = $entry;

		$output = $renderer->render( $entry, $view, $request );

		remove_filter( 'gravityview/template/links/back/url', '__return_false' );

		/**
		 * @filter `gravityview/shortcodes/gventry/output` Filter the [gventry] output.
		 * @param string $output The output.
		 * @param \GV\View|null $view The View detected or null.
		 * @param \GV\Entry|null $entry The Entry or null.
		 *
		 * @since 2.0
		 */
		return apply_filters( 'gravityview/shortcodes/gventry/output', $output, $view, $entry, $atts );
	}
}
