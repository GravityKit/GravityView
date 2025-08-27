<?php
/**
 * Add compatibility notices for Formidable Views and GravityView.
 *
 * @file      class-gravityview-plugin-hooks-formidable-views.php
 * @since     2.41
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2025, Katz Web Services, Inc.
 *
 * @package   GravityView
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

		if ( class_exists( 'GravityKitFoundation' ) ) {
			$this->register_compatibility_notice();
		}
	}

	/**
	 * Register compatibility notice with Foundation.
	 *
	 * @since 2.41
	 */
	public function register_compatibility_notice() {
		$entry_endpoint = gravityview()->plugin->settings->get( 'entry_endpoint' );

		// If the entry endpoint is not "entry", we don't need to show the notice.
		if ( ! empty( $entry_endpoint ) && 'entry' !== $entry_endpoint ) {
			return;
		}

		$messages = [
			esc_html__( 'Plugin conflict detected.', 'gk-gravityview' ),
			esc_html__( 'Formidable Views and GravityView share the same "entry" endpoint for viewing single entries. This may cause conflicts.', 'gk-gravityview' ),
			sprintf(
			// Translators: Do not translate placeholders %1$s (opening <a>) and %2$s (closing </a>).
				esc_html__( 'To resolve this, you can %1$schange the entry endpoint%2$s in GravityKit Settings from "entry" to something else.', 'gk-gravityview' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=gk_settings&p=gravityview&s=3#entry_endpoint-label' ) ) . '">', '</a>'
			),
		];

		if ( ! class_exists( 'GravityKitFoundation' ) ) {
			return;
		}

		$notice_manager = GravityKitFoundation::notices();

		if ( ! $notice_manager ) {
			return;
		}

		try {
			$notice_manager->add_runtime( [
				'namespace'    => 'gk-gravityview',
				'slug'         => 'formidable-views-conflict',
				'message'      => join( ' ', $messages ),
				'severity'     => 'error',
				'capabilities' => [ 'manage_options' ],
				'dismissible'  => false,
				'screens'      => [ 'dashboard', 'plugins' ],
			] );
		} catch ( Exception $e ) {
			gravityview()->log->debug( 'Failed to register Formidable Views conflict notice with Foundation: ' . $e->getMessage() );
		}
	}
}

new GravityView_Theme_Hooks_Formidable_Views();
