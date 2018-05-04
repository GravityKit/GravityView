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
			return '';
		}

		$atts = wp_parse_args( $atts, array(
			'id'      => 0,
			'view_id' => 0,
		) );

		$view = \GV\View::by_id( $atts['view_id'] );

		if ( ! $view ) {
			gravityview()->log->error( 'View does not exist #{view_id}', array( 'view_id' => $atts['view_id'] ) );
			return '';
		}

		$entry = \GV\GF_Entry::by_id( $atts['id'] );

		if ( ! $entry ) {
			gravityview()->log->error( 'Entry does not exist #{entry_id}', array( 'entry_id' => $atts['entry_id'] ) );
			return '';
		}

		if ( $view->form->ID != $entry['form_id'] ) {
			gravityview()->log->error( 'Entry does not belong to view (form mismatch)' );
			return '';
		}

		if ( post_password_required( $view->ID ) ) {
			gravityview()->log->notice( 'Post password is required for View #{view_id}', array( 'view_id' => $view->ID ) );
			return get_the_password_form( $view->ID );
		}

		if ( ! $view->form  ) {
			gravityview()->log->notice( 'View #{id} has no form attached to it.', array( 'id' => $view->ID ) );

			/**
			 * This View has no data source. There's nothing to show really.
			 * ...apart from a nice message if the user can do anything about it.
			 */
			if ( \GVCommon::has_cap( array( 'edit_gravityviews', 'edit_gravityview' ), $view->ID ) ) {
				return __( sprintf( 'This View is not configured properly. Start by <a href="%s">selecting a form</a>.', esc_url( get_edit_post_link( $view->ID, false ) ) ), 'gravityview' );
			}

			return $content;
		}

		/** Private, pending, draft, etc. */
		$public_states = get_post_stati( array( 'public' => true ) );
		if ( ! in_array( $view->post_status, $public_states ) && ! \GVCommon::has_cap( 'read_gravityview', $view->ID ) ) {
			gravityview()->log->notice( 'The current user cannot access this View #{view_id}', array( 'view_id' => $view->ID ) );
			return '';
		}

		/** Unapproved entries. */
		if ( $entry['status'] != 'active' ) {
			gravityview()->log->notice( 'Entry ID #{entry_id} is not active', array( 'entry_id' => $entry->ID ) );
			return '';
		}

		if ( $view->settings->get( 'show_only_approved' ) ) {
			if ( ! \GravityView_Entry_Approval_Status::is_approved( gform_get_meta( $entry->ID, \GravityView_Entry_Approval::meta_key ) )  ) {
				gravityview()->log->error( 'Entry ID #{entry_id} is not approved for viewing', array( 'entry_id' => $entry->ID ) );
				return '';
			}
		}

		$renderer = new \GV\Entry_Renderer();

		$request = new \GV\Mock_Request();
		$request->returns['is_entry'] = $entry;

		return  $renderer->render( $entry, $view, $request );
	}
}
