<?php
namespace GV\Shortcodes;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The [gvfield] shortcode.
 */
class gvfield extends \GV\Shortcode {
	/**
	 * {@inheritDoc}
	 */
	public $name = 'gvfield';

	/**
	 * {@inheritDoc}
	 */
	protected static $defaults = [
		'view_id'  => null,
		'entry_id' => null,
		'field_id' => null,
		'secret'   => '',
	];

	/**
	 * Process and output the [gvfield] shortcode.
	 *
	 * @param array  $passed_atts The attributes passed.
	 * @param string $content The content inside the shortcode.
	 * @param string $tag The shortcode tag.
	 *
	 * @return string|null The output.
	 */
	public function callback( $atts, $content = '', $tag = '' ) {
		$request = gravityview()->request;

		if ( $request->is_admin() ) {
			return apply_filters( 'gravityview/shortcodes/gvfield/output', '', null, null, $atts );
		}

		$atts = wp_parse_args(
			$atts,
			self::$defaults
		);

		$atts = gv_map_deep( $atts, array( 'GravityView_Merge_Tags', 'replace_get_variables' ) );

		/**
		 * Filter the [gvfield] shortcode attributes.
		 *
		 * @param array $atts The initial attributes.
		 * @since 2.0
		 */
		$atts = apply_filters( 'gravityview/shortcodes/gvfield/atts', $atts );

		$view = $this->get_view_by_atts( $atts );

		if ( is_wp_error( $view ) ) {
			return $this->handle_error( $view );
		}

		if ( ! $view ) {
			gravityview()->log->error( 'View #{view_id} not found', array( 'view_id' => $atts['view_id'] ) );
			return apply_filters( 'gravityview/shortcodes/gvfield/output', '', $view, null, null, $atts );
		}

		switch ( $atts['entry_id'] ) :
			case 'last':
				if ( gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
					/**
					 * @todo Remove once we refactor the use of get_view_entries_parameters.
					 *
					 * Since we're using \GF_Query shorthand initialization we have to reverse the order parameters here.
					 */
					add_filter(
						'gravityview_get_entries',
						$filter = function ( $parameters, $args, $form_id ) {
							if ( ! empty( $parameters['sorting'] ) ) {
								/**
								 * Reverse existing sorts.
								 */
								$sort              = &$parameters['sorting'];
								$sort['direction'] = 'RAND' == $sort['direction'] ? : ( 'ASC' == $sort['direction'] ? 'DESC' : 'ASC' );
							} else {
								/**
								 * Otherwise, sort by date_created.
								 */
								$parameters['sorting'] = array(
									'key'        => 'id',
									'direction'  => 'ASC',
									'is_numeric' => true,
								);
							}
							return $parameters;
						},
						10,
						3
					);
					$entries = $view->get_entries( null );
					remove_filter( 'gravityview_get_entries', $filter );
				} else {
					$entries = $view->get_entries( null );

					/** If a sort already exists, reverse it. */
					if ( $sort = end( $entries->sorts ) ) {
						$entries = $entries->sort( new \GV\Entry_Sort( $sort->field, \GV\Entry_Sort::RAND == $sort->direction ? : ( \GV\Entry_Sort::ASC == $sort->direction ? \GV\Entry_Sort::DESC : \GV\Entry_Sort::ASC ) ), $sort->mode );
					} else {
						/** Otherwise, sort by date_created */
						$entries = $entries->sort( new \GV\Entry_Sort( \GV\Internal_Field::by_id( 'id' ), \GV\Entry_Sort::ASC ), \GV\Entry_Sort::NUMERIC );
					}
				}

				if ( ! $entry = $entries->first() ) {
					return apply_filters( 'gravityview/shortcodes/gvfield/output', '', $view, $entry, null, $atts );
				}
				break;
			case 'first':
				if ( ! $entry = $view->get_entries( null )->first() ) {
					return apply_filters( 'gravityview/shortcodes/gvfield/output', '', $view, $entry, null, $atts );
				}
				break;
			default:
				if ( ! $entry = \GV\GF_Entry::by_id( $atts['entry_id'] ) ) {
					gravityview()->log->error( 'Entry #{entry_id} not found', array( 'view_id' => $atts['view_id'] ) );
					return apply_filters( 'gravityview/shortcodes/gvfield/output', '', $view, $entry, null, $atts );
				}
		endswitch;

		$field = is_numeric( $atts['field_id'] ) ? \GV\GF_Field::by_id( $view->form, $atts['field_id'] ) : \GV\Internal_Field::by_id( $atts['field_id'] );

		if ( ! $field ) {
			gravityview()->log->error( 'Field #{field_id} not found', array( 'view_id' => $atts['field_id'] ) );
			return apply_filters( 'gravityview/shortcodes/gvfield/output', '', $view, $entry, $field, $atts );
		}

		if ( $view->form->ID != $entry['form_id'] ) {
			gravityview()->log->error( 'Entry does not belong to view (form mismatch)' );
			return apply_filters( 'gravityview/shortcodes/gvfield/output', '', $view, $entry, $atts );
		}

		if ( post_password_required( $view->ID ) ) {
			gravityview()->log->notice( 'Post password is required for View #{view_id}', array( 'view_id' => $view->ID ) );
			return apply_filters( 'gravityview/shortcodes/gvfield/output', get_the_password_form( $view->ID ), $view, $entry, $atts );
		}

		if ( ! $view->form ) {
			gravityview()->log->notice( 'View #{id} has no form attached to it.', array( 'id' => $view->ID ) );

			/**
			 * This View has no data source. There's nothing to show really.
			 * ...apart from a nice message if the user can do anything about it.
			 */
			if ( \GVCommon::has_cap( array( 'edit_gravityviews', 'edit_gravityview' ), $view->ID ) ) {
				$return = sprintf( __( 'This View is not configured properly. Start by <a href="%s">selecting a form</a>.', 'gk-gravityview' ), esc_url( get_edit_post_link( $view->ID, false ) ) );
				return apply_filters( 'gravityview/shortcodes/gvfield/output', $return, $view, $entry, $atts );
			}

			return apply_filters( 'gravityview/shortcodes/gvfield/output', '', $view, $entry, $atts );
		}

		/** Private, pending, draft, etc. */
		$public_states = get_post_stati( array( 'public' => true ) );
		if ( ! in_array( $view->post_status, $public_states ) && ! \GVCommon::has_cap( 'read_gravityview', $view->ID ) ) {
			gravityview()->log->notice( 'The current user cannot access this View #{view_id}', array( 'view_id' => $view->ID ) );
			return apply_filters( 'gravityview/shortcodes/gvfield/output', '', $view, $entry, $atts );
		}

		/** Unapproved entries. */
		if ( 'active' != $entry['status'] ) {
			gravityview()->log->notice( 'Entry ID #{entry_id} is not active', array( 'entry_id' => $entry->ID ) );
			return apply_filters( 'gravityview/shortcodes/gvfield/output', '', $view, $entry, $atts );
		}

		if ( $view->settings->get( 'show_only_approved' ) ) {
			if ( ! \GravityView_Entry_Approval_Status::is_approved( gform_get_meta( $entry->ID, \GravityView_Entry_Approval::meta_key ) ) ) {
				gravityview()->log->error( 'Entry ID #{entry_id} is not approved for viewing', array( 'entry_id' => $entry->ID ) );
				return apply_filters( 'gravityview/shortcodes/gvfield/output', '', $view, $entry, $atts );
			}
		}

		$field->update_configuration( $atts );

		$renderer = new \GV\Field_Renderer();
		$output   = $renderer->render( $field, $view, is_numeric( $field->ID ) ? $view->form : new \GV\Internal_Source(), $entry, gravityview()->request );

		/**
		 * Filter the [gvfield] output.
		 *
		 * @param string $output The output.
		 * @param \GV\View|null $view The View detected or null.
		 * @param \GV\Entry|null $entry The Entry or null.
		 * @param \GV\Field|null $field The Field or null.
		 *
		 * @since 2.0
		 */
		return apply_filters( 'gravityview/shortcodes/gvfield/output', $output, $view, $entry, $field, $atts );
	}
}
