<?php
/**
 * GravityView Basic Module for Beaver Builder
 *
 * @package GravityKit\GravityView\Extensions\BeaverBuilder
 * @since TODO
 */

namespace GravityKit\GravityView\Extensions\BeaverBuilder;

use FLBuilderModule;
use FLBuilder;
use GravityKit\GravityView\Shortcodes\ShortcodeRenderer;
use GVCommon;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * GravityView Basic Module for Beaver Builder.
 *
 * Provides basic functionality for embedding GravityView Views in Beaver Builder.
 *
 * @since TODO
 */
class Basic_Module extends FLBuilderModule {

	/**
	 * Module constructor.
	 *
	 * @since TODO
	 */
	public function __construct() {
		parent::__construct(
			[
				'name'            => esc_html__( 'GravityView', 'gk-gravityview' ),
				'description'     => esc_html__( 'Display a GravityView View.', 'gk-gravityview' ),
				'category'        => esc_html__( 'Basic', 'gk-gravityview' ),
				'group'           => esc_html__( 'GravityKit', 'gk-gravityview' ),
				'dir'             => __DIR__,
				'url'             => plugins_url( '', __FILE__ ),
				'icon'            => 'format-aside.svg',
				'editor_export'   => true,
				'enabled'         => true,
				'partial_refresh' => true,
			]
		);
	}

	/**
	 * Get list of published Views for select control.
	 *
	 * @since TODO
	 *
	 * @return array List of Views with ID as key and title as value.
	 */
	public static function get_views_list() {
		if ( ! class_exists( 'GVCommon' ) ) {
			return [ '' => esc_html__( '-- No Views Found --', 'gk-gravityview' ) ];
		}

		$views_data = GVCommon::get_views_list();

		if ( empty( $views_data ) ) {
			return [ '' => esc_html__( '-- No Views Found --', 'gk-gravityview' ) ];
		}

		$views_list = [ '' => esc_html__( '-- Select a View --', 'gk-gravityview' ) ];

		foreach ( $views_data as $id => $view ) {
			// translators: %1$s is the View title, %2$d is the View ID.
			$views_list[ $id ] = esc_html( sprintf( __( '%1$s (View #%2$d)', 'gk-gravityview' ), $view['title'], $id ) );
		}

		return $views_list;
	}

	/**
	 * Render the module frontend output.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	public function frontend() {
		if ( ! class_exists( '\GV\View' ) ) {
			return;
		}

		$view_id = isset( $this->settings->viewId ) ? (int) $this->settings->viewId : 0;

		if ( 0 === $view_id ) {
			if ( FLBuilder::is_active() ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in render_placeholder.
				echo ShortcodeRenderer::render_placeholder( __( 'Please select a View from the module settings.', 'gk-gravityview' ) );
			}
			return;
		}

		$view = \GV\View::by_id( $view_id );

		if ( ! $view ) {
			if ( FLBuilder::is_active() ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in render_placeholder.
				echo ShortcodeRenderer::render_placeholder( __( 'View not found.', 'gk-gravityview' ) );
			}
			return;
		}

		// Build shortcode using the same pattern as Gutenberg blocks.
		$shortcode = self::build_shortcode( $this->settings, $view );

		// Render using the shared ShortcodeRenderer.
		$rendered = ShortcodeRenderer::render( $shortcode );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $rendered['content'];
	}

	/**
	 * Build a shortcode string from module settings.
	 *
	 * Uses the shared ShortcodeRenderer for consistency across
	 * all page builder integrations.
	 *
	 * @since TODO
	 *
	 * @param object   $settings Module settings object.
	 * @param \GV\View $view     View object for getting the secret.
	 *
	 * @return string The formatted shortcode string.
	 */
	private static function build_shortcode( $settings, $view ) {
		// Convert settings object to array with camelCase keys matching Gutenberg.
		$props = [
			'viewId'        => isset( $settings->viewId ) ? $settings->viewId : '',
			'pageSize'      => isset( $settings->pageSize ) ? $settings->pageSize : '',
			'sortField'     => isset( $settings->sortField ) ? $settings->sortField : '',
			'sortDirection' => isset( $settings->sortDirection ) ? $settings->sortDirection : '',
		];

		// Use the shared shortcode builder.
		return ShortcodeRenderer::build_from_block_atts(
			$props,
			$view->get_validation_secret()
		);
	}
}

/**
 * Register the module with Beaver Builder.
 */
FLBuilder::register_module(
	__NAMESPACE__ . '\Basic_Module',
	[
		'general' => [
			'title'    => esc_html__( 'General', 'gk-gravityview' ),
			'sections' => [
				'view_selection' => [
					'title'  => esc_html__( 'View Selection', 'gk-gravityview' ),
					'fields' => [
						'viewId' => [
							'type'    => 'select',
							'label'   => esc_html__( 'Select View', 'gk-gravityview' ),
							'options' => Basic_Module::get_views_list(),
							'preview' => [
								'type' => 'refresh',
							],
						],
					],
				],
				'view_settings'  => [
					'title'  => esc_html__( 'View Settings', 'gk-gravityview' ),
					'fields' => [
						'pageSize'      => [
							'type'        => 'text',
							'label'       => esc_html__( 'Number of Entries', 'gk-gravityview' ),
							'placeholder' => esc_html__( 'Leave empty to use View settings', 'gk-gravityview' ),
							'preview'     => [
								'type' => 'refresh',
							],
						],
						'sortField'     => [
							'type'        => 'text',
							'label'       => esc_html__( 'Sort Field', 'gk-gravityview' ),
							'placeholder' => esc_html__( 'Field ID to sort by', 'gk-gravityview' ),
							'preview'     => [
								'type' => 'refresh',
							],
						],
						'sortDirection' => [
							'type'    => 'select',
							'label'   => esc_html__( 'Sort Direction', 'gk-gravityview' ),
							'default' => '',
							'options' => [
								''     => esc_html__( 'Default', 'gk-gravityview' ),
								'ASC'  => esc_html__( 'Ascending', 'gk-gravityview' ),
								'DESC' => esc_html__( 'Descending', 'gk-gravityview' ),
							],
							'preview' => [
								'type' => 'refresh',
							],
						],
					],
				],
			],
		],
	]
);
