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
	 * {@inheritDoc}
	 */
	protected static $defaults = [
		'id'       => 0,
		'entry_id' => 0,
		'view_id'  => 0,
		'edit'     => 0,
		'secret'   => '',
	];

	/**
	 * Process and output the [gventry] shortcode.
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
			return apply_filters( 'gravityview/shortcodes/gventry/output', '', null, null, $atts );
		}

		$atts = wp_parse_args(
            $atts,
            self::$defaults
        );

		$atts = gv_map_deep( $atts, array( 'GravityView_Merge_Tags', 'replace_get_variables' ) );

		/**
		 * Filter the [gventry] shortcode attributes.
		 *
		 * @param array $atts The initial attributes.
		 * @since 2.0
		 */
		$atts = apply_filters( 'gravityview/shortcodes/gventry/atts', $atts );

		$view = $this->get_view_by_atts( $atts );

		if ( is_wp_error( $view ) ) {
			return $this->handle_error( $view );
		}

		if ( ! $view ) {
			gravityview()->log->error( 'View does not exist #{view_id}', array( 'view_id' => $atts['view_id'] ) );
			return apply_filters( 'gravityview/shortcodes/gventry/output', '', null, null, $atts );
		}

		$entry_id = ! empty( $atts['entry_id'] ) ? $atts['entry_id'] : $atts['id'];

		switch ( $entry_id ) :
			case 'last':
				if ( class_exists( '\GF_Query' ) ) {
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

		if ( ! $view->form ) {
			gravityview()->log->notice( 'View #{id} has no form attached to it.', array( 'id' => $view->ID ) );

			/**
			 * This View has no data source. There's nothing to show really.
			 * ...apart from a nice message if the user can do anything about it.
			 */
			if ( \GVCommon::has_cap( array( 'edit_gravityviews', 'edit_gravityview' ), $view->ID ) ) {
				$return = sprintf( __( 'This View is not configured properly. Start by <a href="%s">selecting a form</a>.', 'gk-gravityview' ), esc_url( get_edit_post_link( $view->ID, false ) ) );
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
		if ( 'active' != $entry['status'] ) {
			gravityview()->log->notice( 'Entry ID #{entry_id} is not active', array( 'entry_id' => $entry->ID ) );
			return apply_filters( 'gravityview/shortcodes/gventry/output', '', $view, $entry, $atts );
		}

		$is_admin_and_can_view = $view->settings->get( 'admin_show_all_statuses' ) && \GVCommon::has_cap( 'gravityview_moderate_entries', $view->ID );

		if ( $view->settings->get( 'show_only_approved' ) && ! $is_admin_and_can_view ) {
			if ( ! \GravityView_Entry_Approval_Status::is_approved( gform_get_meta( $entry->ID, \GravityView_Entry_Approval::meta_key ) ) ) {
				gravityview()->log->error( 'Entry ID #{entry_id} is not approved for viewing', array( 'entry_id' => $entry->ID ) );
				return apply_filters( 'gravityview/shortcodes/gventry/output', '', $view, $entry, $atts );
			}
		}

		if ( \GV\Utils::get( $_GET, 'edit' ) && \GV\Utils::get( $_GET, 'gvid' ) ) {
			$atts['edit'] = 1;
		}

		if ( $atts['edit'] ) {
			/**
			 * Based on code in our unit-tests.
			 * Mocks old context, etc.
			 */
			$loader = \GravityView_Edit_Entry::getInstance();
			/** @var \GravityView_Edit_Entry_Render $render */
			$render = $loader->instances['render'];

			// Override the \GV\Request::is_entry() check for the query var.
			$_entry_query_var_backup = get_query_var( \GV\Entry::get_endpoint_name() );
			set_query_var( \GV\Entry::get_endpoint_name(), $entry['id'] );
			add_filter(
                'gravityview_is_edit_entry',
                $use_entry = function () use ( $entry ) {
					return $entry;
				}
            );

			add_filter( 'gravityview/is_single_entry', '__return_true' );

			$form = \GVCommon::get_form( $entry['form_id'] );

			$data     = \GravityView_View_Data::getInstance( $view );
			$template = \GravityView_View::getInstance(
                array(
					'form'    => $form,
					'form_id' => $form['id'],
					'view_id' => $view->ID,
					'entries' => array( $entry ),
					'atts'    => \GVCommon::get_template_settings( $view->ID ),
                )
            );

			$_GET['edit'] = wp_create_nonce(
				\GravityView_Edit_Entry::get_nonce_key( $view->ID, $form['id'], $entry['id'] )
			);

			add_filter(
                'gravityview/edit_entry/success',
                $callback = function ( $message, $_view_id, $_entry, $back_link, $redirect_url ) use ( $view, $entry, $atts ) {
					/**
					 * Modify the edit entry success message in [gventry].
					 *
					 * @since  develop
					 *
					 * @param string      $message      The message.
					 * @param \GV\View    $view         The View.
					 * @param \GV\Entry   $entry        The entry.
					 * @param array       $atts         The attributes.
					 * @param string      $back_link    URL to return to the original entry. @since 2.14.6
					 * @param string|null $redirect_url URL to return to after the update. @since 2.14.6
					 */
					return apply_filters( 'gravityview/shortcodes/gventry/edit/success', $message, $view, $entry, $atts, $back_link, $redirect_url );
				},
                10,
                5
            );

			ob_start() && $render->init( $data, \GV\GF_Entry::by_id( $entry['id'] ), $view );
			$output = ob_get_clean(); // Render :)

			// Restore the \GV\Request::is_entry() check for the query var.
			set_query_var( \GV\Entry::get_endpoint_name(), $_entry_query_var_backup );
			remove_filter( 'gravityview_is_edit_entry', $use_entry );
			remove_filter( 'gravityview/is_single_entry', '__return_true' );
			remove_filter( 'gravityview/edit_entry/success', $callback );
		} else {
			/** Remove the back link. */
			add_filter( 'gravityview/template/links/back/url', '__return_false' );

			$renderer = new \GV\Entry_Renderer();

			$request                      = new \GV\Mock_Request();
			$request->returns['is_entry'] = $entry;

			$output = $renderer->render( $entry, $view, $request );

			remove_filter( 'gravityview/template/links/back/url', '__return_false' );
		}

		/**
		 * Filter the [gventry] output.
		 *
		 * @param string $output The output.
		 * @param \GV\View|null $view The View detected or null.
		 * @param \GV\Entry|null $entry The Entry or null.
		 *
		 * @since 2.0
		 */
		return apply_filters( 'gravityview/shortcodes/gventry/output', $output, $view, $entry, $atts );
	}
}
