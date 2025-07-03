<?php
/**
 * Add compatibility notices for Formidable Views and GravityView.
 *
 * @file      class-gravityview-plugin-hooks-formidable-views.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2025, Katz Web Services, Inc.
 *
 * @since 2.41
 */

/**
 * @inheritDoc
 * @since 2.41
 */
class GravityView_Theme_Hooks_Formidable_Views extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 2.41
	 */
	protected $function_name = 'load_formidable_views';

	/**
	 * @inheritDoc
	 * @since 2.41
	 */
	protected function add_hooks() {
		parent::add_hooks();

		add_filter( 'gravityview/admin/notices', [ $this, 'add_conflict_notice' ] );
	}

	/**
	 * Add a notice about the conflict between GravityView and Formidable Views.
	 *
	 * @since 2.41
	 *
	 * @param array $notices
	 *
	 * @return array $notices, with a new notice about the conflict added.
	 */
	public function add_conflict_notice( $notices ) {

		// Only show the notice to users who can manage options.
		if( ! current_user_can( 'manage_options' ) ) {
			return $notices;
		}

		$entry_endpoint = gravityview()->plugin->settings->get( 'entry_endpoint' );

		// If the entry endpoint is not "entry", we don't need to show the notice.
		if( ! empty( $entry_endpoint ) && 'entry' !== $entry_endpoint ) {
			return $notices;
		}

		$message = '<h3>' . esc_html__( 'Plugin conflict detected.', 'gk-gravityview' ) . '</h3>';
		$message .= esc_html__( 'Formidable Views and GravityView share the same "entry" endpoint for viewing single entries. This may cause conflicts.', 'gk-gravityview' );
		$message .= ' ' . sprintf(
			esc_html__( 'To resolve this, you can %1$schange the entry endpoint%2$s in GravityKit Settings from "entry" to something else.', 'gk-gravityview' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=gk_settings&p=gravityview&s=3#entry_endpoint-label' ) ) . '">', '</a>'
		);

		$notices[] = [
			'class'   => 'error',
			'message' => $message,
			'dismiss' => false,
		];

		return $notices;
	}
}

new GravityView_Theme_Hooks_Formidable_Views();
