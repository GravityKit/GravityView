<?php
/**
 * GravityView Basic Module for Divi Builder
 *
 * @package GravityKit\GravityView\Extensions\Divi
 * @since TODO
 */

namespace GravityKit\GravityView\Extensions\Divi;

use GravityKit\GravityView\Gutenberg\Blocks;
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
		'module_uri' => 'https://www.gravitykit.com',
		'author'     => 'GravityKit',
		'author_uri' => 'https://www.gravitykit.com',
	];

	/**
	 * Initialize the module.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	public function init() {
		$this->name = esc_html__( 'GravityView', 'gk-gravityview' );
		$this->icon = $this->get_icon_svg();

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
					'main' => '%%order_class%%',
				],
			],
			'borders'        => [
				'default' => [
					'css' => [
						'main' => [
							'border_radii'  => '%%order_class%%',
							'border_styles' => '%%order_class%%',
						],
					],
				],
			],
			'box_shadow'     => [
				'default' => [
					'css' => [
						'main' => '%%order_class%%',
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
			'view_id'        => [
				'label'           => esc_html__( 'Select View', 'gk-gravityview' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => $views_list,
				'default'         => '0',
				'description'     => esc_html__( 'Choose an existing View to display on this page.', 'gk-gravityview' ),
				'toggle_slug'     => 'main_content',
				'computed_affects' => [
					'__view_content',
				],
			],
			'page_size'      => [
				'label'           => esc_html__( 'Number of Entries', 'gk-gravityview' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'description'     => esc_html__( 'Number of entries to display per page. Leave empty to use View settings.', 'gk-gravityview' ),
				'toggle_slug'     => 'settings',
				'computed_affects' => [
					'__view_content',
				],
			],
			'sort_field'     => [
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
			'sort_direction' => [
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
					'view_id',
					'page_size',
					'sort_field',
					'sort_direction',
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
		$view_id = isset( $this->props['view_id'] ) ? (int) $this->props['view_id'] : 0;

		if ( 0 === $view_id ) {
			if ( $this->is_builder_context() ) {
				return $this->render_placeholder_message(
					esc_html__( 'Please select a View from the module settings.', 'gk-gravityview' )
				);
			}
			return '';
		}

		$output = self::render_view_content( $this->props );

		if ( empty( $output ) ) {
			if ( $this->is_builder_context() ) {
				return $this->render_placeholder_message(
					esc_html__( 'View not found.', 'gk-gravityview' )
				);
			}
			return '';
		}

		return sprintf(
			'<div class="gk-gravityview-divi-module">%s</div>',
			$output
		);
	}

	/**
	 * Render View content (used for both frontend and Visual Builder).
	 *
	 * @since TODO
	 *
	 * @param array $props Module properties.
	 *
	 * @return string Rendered View content.
	 */
	public static function render_view_content( $props ) {
		if ( ! class_exists( '\GV\View' ) ) {
			return '';
		}

		$view_id = isset( $props['view_id'] ) ? (int) $props['view_id'] : 0;

		if ( 0 === $view_id ) {
			return '';
		}

		$view = \GV\View::by_id( $view_id );

		if ( ! $view ) {
			return '';
		}

		// Build shortcode attributes.
		$atts = [ 'id' => $view_id ];

		// Add optional settings if provided.
		$optional_settings = [ 'page_size', 'sort_field', 'sort_direction' ];
		foreach ( $optional_settings as $key ) {
			if ( ! empty( $props[ $key ] ) ) {
				$atts[ $key ] = $props[ $key ];
			}
		}

		// Generate shortcode.
		$shortcode_atts = [];
		foreach ( $atts as $key => $value ) {
			$shortcode_atts[] = sprintf( '%s="%s"', $key, esc_attr( $value ) );
		}

		$secret = $view->get_validation_secret();

		if ( $secret ) {
			$shortcode_atts[] = sprintf( 'secret="%s"', $secret );
		}

		$shortcode = sprintf( '[gravityview %s]', implode( ' ', $shortcode_atts ) );

		// Render using existing GravityView renderer.
		$rendered = Blocks::render_shortcode( $shortcode );

		return $rendered['content'] ?? '';
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

		$views_list = [ '0' => esc_html__( '-- Select a View --', 'gk-gravityview' ) ];

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

	/**
	 * Render a placeholder message for the builder.
	 *
	 * @since TODO
	 *
	 * @param string $message The message to display.
	 *
	 * @return string HTML placeholder.
	 */
	private function render_placeholder_message( $message ) {
		return sprintf(
			'<div style="text-align:center; padding:20px; border:1px dashed #ccc; background:#f9f9f9;">%s</div>',
			esc_html( $message )
		);
	}

	/**
	 * Get custom SVG icon for the module.
	 *
	 * @since TODO
	 *
	 * @return string SVG icon data URI.
	 */
	private function get_icon_svg() {
		$svg = '<svg width="30.5" height="28" viewBox="0 0 48 44" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M42.4105 36.6666H36.8824V38.4999C36.8824 41.5375 34.3902 43.9999 31.3544 43.9999H16.6134C13.5375 43.9999 11.0853 41.5375 11.0853 38.4999V11H5.55724C4.51437 11 3.71481 11.8207 3.71481 12.8333V31.1666C3.71481 32.1792 4.51437 33 5.55724 33H6.47884C6.96291 33 7.39967 33.4104 7.39967 33.9167V35.75C7.39967 36.2562 6.96291 36.6666 6.47884 36.6666H5.55724C2.47856 36.6666 0.0291748 34.2042 0.0291748 31.1666V12.8333C0.0291748 9.79573 2.47856 7.33326 5.55724 7.33326H11.0853V5.5C11.0853 2.4624 13.5375 0 16.6134 0H31.3544C34.3902 0 36.8824 2.4624 36.8824 5.5V33H42.4105C43.4132 33 44.2529 32.1792 44.2529 31.1666V12.8333C44.2529 11.8207 43.4132 11 42.4105 11H41.4889C40.9647 11 40.5673 10.5896 40.5673 10.0833V8.24992C40.5673 7.74369 40.9647 7.33326 41.4889 7.33326H42.4105C45.449 7.33326 47.9378 9.79573 47.9378 12.8333V31.1666C47.9378 34.2042 45.449 36.6666 42.4105 36.6666ZM33.1968 5.5C33.1968 4.48739 32.3544 3.66667 31.3544 3.66667H16.6134C15.5733 3.66667 14.7709 4.48739 14.7709 5.5V38.4999C14.7709 39.5125 15.5733 40.3333 16.6134 40.3333H31.3544C32.3544 40.3333 33.1968 39.5125 33.1968 38.4999V5.5Z" fill="#000000"/></svg>';

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}
}
