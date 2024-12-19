<?php

use GV\Entry_Layout_Builder_Template;
use GV\Field_Collection;
use GV\Grid;
use GV\View_Layout_Builder_Template;

/**
 * Registers the Layout Builder Layout.
 *
 * @since $ver$
 */
final class GravityView_Layout_Builder extends GravityView_Template {
	/**
	 * The Layout ID.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	public const ID = 'gravityview-layout-builder';

	/**
	 * Creates the layout.
	 *
	 * @since $ver$
	 */
	public function __construct() {
		$areas = Grid::prefixed(
			self::ID,
			static fn() => [ Grid::get_row_by_type( '100' ) ],
		);

		parent::__construct(
			self::ID,
			[
				'slug'        => self::ID,
				'type'        => 'custom',
				'label'       => __( 'Layout Builder', 'gk-gravityview' ),
				'description' => __(
					'Display items in customizable rows and columns.',
					'gk-gravityview',
				),
				'css_source'  => null,
				'logo'        => plugins_url( 'includes/presets/layout-builder/logo-layout-builder.svg', GRAVITYVIEW_FILE ),
			],
			[
				'show_as_link' => [
					'type'     => 'checkbox',
					'label'    => esc_html__( 'Link to single entry', 'gk-gravityview' ),
					'value'    => false,
					'context'  => 'directory',
					'priority' => 1190,
					'group'    => 'display',
				],
			],
			$areas,
		);

		add_filter( 'gravityview/template/view/class', [ __CLASS__, 'get_view_class' ] );
		add_filter( 'gravityview/template/entry/class', [ __CLASS__, 'get_view_class' ] );

		add_filter( 'gk/gravityview/admin-views/view/is-dynamic', [ __CLASS__, 'make_dynamic' ], 5, 3 );
		add_filter( 'gk/gravityview/admin-views/view/template/active-areas', [ __CLASS__, 'replace_active_areas' ], 5, 4 );

		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_style_assets' ] );
	}

	/**
	 * Returns {@see ViewTemplate} class name to be used as the View template class.
	 *
	 * @used-by `gravityview/template/view/class` filter.
	 *
	 * @since   TBD
	 *
	 * @param string $template_class View template class.
	 *
	 * @return string The template class to use.
	 */
	public static function get_view_class( string $template_class ): string {
		// GravityView expects the class to be in the "GV\View_<name>_Template" format.
		$is_layout_builder_template = false !== stripos( $template_class, '_' . self::ID . '_' );
		if ( ! $is_layout_builder_template ) {
			return $template_class;
		}

		return stripos( $template_class, 'view_' ) !== false
			? View_Layout_Builder_Template::class
			: Entry_Layout_Builder_Template::class;
	}

	/**
	 * Returns the dynamic areas, stored in the fields array.
	 *
	 * @since $ver$
	 *
	 * @param array  $areas       The current areas.
	 * @param string $template_id The template ID.
	 * @param string $context     The context / zone.
	 * @param array  $fields      The fields to render.
	 *
	 * @return array The rows with the active dynamic areas.
	 */
	public static function replace_active_areas(
		array $areas,
		string $template_id,
		string $context,
		array $fields
	): array {
		if ( self::ID !== $template_id ) {
			return $areas;
		}

		$collection = Field_Collection::from_configuration( $fields );
		$rows       = Grid::prefixed(
			self::ID,
			static fn() => Grid::get_rows_from_collection( $collection, $context ),
		);

		return $rows ?: $areas;
	}

	/**
	 * Makes the field sections for the Layout Builder template sortable.
	 *
	 * @since $ver$
	 *
	 * @param bool   $is_dynamic  Whether it is dynamic.
	 * @param string $template_id The template ID.
	 * @param string $type        The object type.
	 *
	 * @return bool Whether it is sortable
	 */
	public static function make_dynamic( bool $is_dynamic, string $template_id, string $type ): bool {
		if (
			self::ID !== $template_id
			|| 'field' !== $type
		) {
			return $is_dynamic;
		}

		return true;
	}

	/**
	 * Registers the style assets for the View layout.
	 *
	 * @since $ver$
	 */
	public static function register_style_assets(): void {
		$style = 'gravityview_style_' . self::ID;
		wp_register_style(
			$style,
			plugin_dir_url( GRAVITYVIEW_FILE ) . 'templates/css/layout-builder.css',
			[],
			GV_PLUGIN_VERSION
		);
	}
}

// new GravityView_Layout_Builder();
