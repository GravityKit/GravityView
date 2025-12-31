<?php
/**
 * GravityView Basic Module for Divi Builder
 *
 * @package GravityKit\GravityView\Extensions\Divi
 * @since TODO
 */

namespace GravityKit\GravityView\Extensions\Divi;

use GravityKit\GravityView\Shortcodes\ShortcodeRenderer;
use GVCommon;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * GravityView Basic Module for Divi Builder.
 *
 * Provides basic functionality for embedding GravityView Views in Divi Builder.
 *
 * @since TODO
 */
class Basic_Module extends \ET_Builder_Module {

	/**
	 * Module slug.
	 *
	 * @since TODO
	 *
	 * @var string
	 */
	public $slug = 'gk_gravityview';

	/**
	 * Visual Builder support.
	 *
	 * @since TODO
	 *
	 * @var string
	 */
	public $vb_support = 'on';

	/**
	 * Module credits.
	 *
	 * @since TODO
	 *
	 * @var array
	 */
	protected $module_credits = [
		'module_uri' => 'https://www.gravitykit.com/products/gravityview/',
		'author'     => 'GravityKit',
		'author_uri' => 'https://www.gravitykit.com',
	];

	/**
	 * Module icon path (SVG file).
	 *
	 * Uses the same icon as the GravityView Gutenberg block.
	 *
	 * @since TODO
	 *
	 * @var string
	 */
	public $icon_path;

	/**
	 * Initialize the module.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	public function init() {
		$this->name             = esc_html__( 'GravityView', 'gk-gravityview' );
		$this->icon_path        = __DIR__ . '/assets/icon.svg';
		$this->main_css_element = '%%order_class%%';

		$this->settings_modal_toggles = [
			'general' => [
				'toggles' => [
					'main_content' => esc_html__( 'View Selection', 'gk-gravityview' ),
					'settings'     => esc_html__( 'View Settings', 'gk-gravityview' ),
				],
			],
		];

		$this->advanced_fields = [
			'background'     => [
				'css' => [
					'main'      => '%%order_class%%',
					'important' => true,
				],
			],
			'borders'        => [
				'default' => [
					'css' => [
						'main' => [
							'border_radii'       => '%%order_class%%',
							'border_radii_hover' => '%%order_class%%:hover',
							'border_styles'      => '%%order_class%%',
						],
						'important' => true,
					],
				],
			],
			'box_shadow'     => [
				'default' => [
					'css' => [
						'main'      => '%%order_class%%',
						'important' => true,
					],
				],
			],
			'margin_padding' => [
				'css' => [
					'important' => 'all',
				],
			],
			'fonts'          => false,
			'text'           => false,
			'link_options'   => false,
		];
	}

	/**
	 * Get module fields.
	 *
	 * @since TODO
	 *
	 * @return array Module fields configuration.
	 */
	public function get_fields() {
		$views_list = $this->get_views_list();

		$fields = [
			'viewId'        => [
				'label'           => esc_html__( 'Select View', 'gk-gravityview' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => $views_list,
				'default'         => '0',
				'searchable'      => true,
				'description'     => esc_html__( 'Choose an existing View to display on this page.', 'gk-gravityview' ),
				'toggle_slug'     => 'main_content',
				'computed_affects' => [
					'__view_content',
				],
			],
			'pageSize'      => [
				'label'           => esc_html__( 'Number of Entries', 'gk-gravityview' ),
				'type'            => 'range',
				'option_category' => 'basic_option',
				'default'         => '',
				'default_unit'    => '',
				'range_settings'  => [
					'min'  => '-1',
					'max'  => '',
					'step' => '1',
				],
				'unitless'        => true,
				'description'     => esc_html__( 'Number of entries to display per page. Leave empty to use View settings. Use -1 to display all entries.', 'gk-gravityview' ),
				'toggle_slug'     => 'settings',
				'computed_affects' => [
					'__view_content',
				],
			],
			'sortField'     => [
				'label'           => esc_html__( 'Sort Field', 'gk-gravityview' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'description'     => esc_html__( 'Field ID to sort by. Leave empty to use View settings.', 'gk-gravityview' ),
				'toggle_slug'     => 'settings',
				'computed_affects' => [
					'__view_content',
				],
			],
			'sortDirection' => [
				'label'           => esc_html__( 'Sort Direction', 'gk-gravityview' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => [
					''     => esc_html__( 'Default', 'gk-gravityview' ),
					'ASC'  => esc_html__( 'Ascending', 'gk-gravityview' ),
					'DESC' => esc_html__( 'Descending', 'gk-gravityview' ),
				],
				'default'         => '',
				'description'     => esc_html__( 'Sort direction for entries.', 'gk-gravityview' ),
				'toggle_slug'     => 'settings',
				'computed_affects' => [
					'__view_content',
				],
			],
			'__view_content' => [
				'type'                => 'computed',
				'computed_callback'   => [ self::class, 'render_view_content' ],
				'computed_depends_on' => [
					'viewId',
					'pageSize',
					'sortField',
					'sortDirection',
				],
			],
		];

		return $fields;
	}

	/**
	 * Render the module output.
	 *
	 * @since TODO
	 *
	 * @param array  $attrs       Module attributes.
	 * @param string $content     Module content.
	 * @param string $render_slug Module render slug.
	 *
	 * @return string Module HTML output.
	 */
	public function render( $attrs, $content, $render_slug ) {
		$view_id = ! empty( $this->props['viewId'] ) ? (int) $this->props['viewId'] : 0;

		if ( ! $view_id ) {
			if ( $this->is_builder_context() ) {
				return ShortcodeRenderer::render_placeholder( __( 'Please select a View from the module settings.', 'gk-gravityview' ) );
			}
			return '';
		}

		$view = \GV\View::by_id( $view_id );

		if ( ! $view ) {
			if ( $this->is_builder_context() ) {
				return ShortcodeRenderer::render_placeholder( __( 'View not found.', 'gk-gravityview' ) );
			}
			return '';
		}

		// Build shortcode using the shared ShortcodeRenderer.
		$shortcode = ShortcodeRenderer::build_from_block_atts(
			$this->props,
			$view->get_validation_secret()
		);

		// Render using the shared ShortcodeRenderer.
		$rendered = ShortcodeRenderer::render( $shortcode );

		$output = $rendered['content'] ?? '';

		if ( empty( $output ) ) {
			return '';
		}

		return sprintf(
			'<div class="gk-gravityview-divi-module">%s</div>',
			$output
		);
	}

	/**
	 * Render View content for Visual Builder computed callback.
	 *
	 * Returns JSON with content and styles for the React component to handle.
	 * Following the same pattern as Gutenberg blocks.
	 *
	 * @since TODO
	 *
	 * @param array $props Module properties.
	 *
	 * @return string JSON-encoded content and styles.
	 */
	public static function render_view_content( $props ) {
		if ( ! class_exists( '\GV\View' ) ) {
			return '';
		}

		$view_id = ! empty( $props['viewId'] ) ? (int) $props['viewId'] : 0;

		if ( ! $view_id ) {
			return '';
		}

		$view = \GV\View::by_id( $view_id );

		if ( ! $view ) {
			return '';
		}

		// Build shortcode using the shared ShortcodeRenderer.
		$shortcode = ShortcodeRenderer::build_from_block_atts(
			$props,
			$view->get_validation_secret()
		);

		// Render using ShortcodeRenderer with GravityView-only style filtering.
		// This uses allowlist patterns to filter styles by handle (slug)
		// BEFORE dependency resolution, ensuring only GravityView-related
		// styles and their dependencies are included.
		$rendered = ShortcodeRenderer::render( $shortcode, [
			'allowed_style_patterns' => ShortcodeRenderer::ALLOWLIST_HANDLE_PATTERNS,
		] );

		$content = $rendered['content'] ?? '';
		$styles  = $rendered['styles'] ?? [];

		// If no content, return empty.
		if ( empty( $content ) ) {
			return '';
		}

		// Return JSON with content and styles for the React component to handle.
		// The React component will dynamically load styles as external stylesheets,
		// following the same pattern as the Gutenberg block implementation.
		// Use array_values() to ensure sequential array keys for proper JSON array encoding.
		return wp_json_encode( [
			'content' => $content,
			'styles'  => array_values( $styles ),
		] );
	}

	/**
	 * Get list of published Views for select control.
	 *
	 * @since TODO
	 *
	 * @return array List of Views with ID as key and title as value.
	 */
	private function get_views_list() {
		$views_data = GVCommon::get_views_list();

		$views_list = [ '0' => esc_html__( 'Select a View', 'gk-gravityview' ) ];

		foreach ( $views_data as $id => $view ) {
			// translators: %1$s is the View title, %2$d is the View ID.
			$views_list[ $id ] = esc_html( sprintf( __( '%1$s (View #%2$d)', 'gk-gravityview' ), $view['title'], $id ) );
		}

		return $views_list;
	}

	/**
	 * Check if we're in a builder context (Visual Builder or Backend Builder).
	 *
	 * @since TODO
	 *
	 * @return bool Whether we're in builder context.
	 */
	private function is_builder_context() {
		// Check for Visual Builder.
		if ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) {
			return true;
		}

		// Check for Backend Builder.
		if ( isset( $_GET['et_fb'] ) || isset( $_GET['et_pb_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		// Check if we're in an AJAX request for the builder.
		if ( wp_doing_ajax() && isset( $_POST['action'] ) && 0 === strpos( $_POST['action'], 'et_' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return true;
		}

		return false;
	}

}
