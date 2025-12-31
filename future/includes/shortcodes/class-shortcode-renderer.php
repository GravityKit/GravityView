<?php
/**
 * Shortcode Renderer - Universal shortcode building and rendering utilities.
 *
 * This class centralizes shortcode generation and rendering for all page builder
 * integrations (Gutenberg, Divi, Elementor, Beaver Builder) to ensure consistency.
 *
 * @package GravityKit\GravityView\Shortcodes
 * @since TODO
 */

namespace GravityKit\GravityView\Shortcodes;

use GravityKit\GravityView\Foundation\Helpers\Arr;
use GV\View;
use GV\View_Settings;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * Universal shortcode building and rendering utilities.
 *
 * Provides two approaches to shortcode building:
 * - Simple: `build_from_block_atts()` - For basic page builders with limited settings
 * - Complete: `build_from_view_settings()` - For full-featured integrations reading from View_Settings
 *
 * @since TODO
 */
class ShortcodeRenderer {

	/**
	 * Scripts and styles to ignore when filtering assets.
	 *
	 * These patterns identify assets from other plugins that may cause conflicts
	 * when rendering Views in page builder contexts.
	 *
	 * @since TODO
	 *
	 * @var array
	 */
	const IGNORE_SCRIPTS_AND_STYLES = [ 'jetpack', 'elementor', 'yoast' ];

	/**
	 * Default allowlist patterns for GravityView assets.
	 *
	 * These patterns identify GravityView core and extension stylesheets/scripts
	 * by their registered handle names.
	 *
	 * @since TODO
	 *
	 * @var array
	 */
	const ALLOWLIST_HANDLE_PATTERNS = [
		'gravityview',
		'gv-',
		'gv_',
		'gk-',
		'gk_',
	];

	/**
	 * Map of block attribute names (camelCase) to shortcode attribute names (snake_case).
	 *
	 * @since TODO
	 *
	 * @var array
	 */
	const BLOCK_TO_SHORTCODE_MAP = [
		'viewId'         => 'id',
		'postId'         => 'post_id',
		'secret'         => 'secret',
		'pageSize'       => 'page_size',
		'sortField'      => 'sort_field',
		'sortDirection'  => 'sort_direction',
		'searchField'    => 'search_field',
		'searchValue'    => 'search_value',
		'searchOperator' => 'search_operator',
		'startDate'      => 'start_date',
		'endDate'        => 'end_date',
		'classValue'     => 'class',
		'offset'         => 'offset',
		'singleTitle'    => 'single_title',
		'backLinkLabel'  => 'back_link_label',
	];

	/**
	 * Formats an attributes array into a shortcode attributes string.
	 *
	 * Handles both scalar and array values. Array values are formatted with
	 * numbered suffixes (e.g., sort_direction, sort_direction_2, sort_direction_3).
	 *
	 * @since TODO
	 *
	 * @param array $atts Attributes to format (key => value pairs).
	 *
	 * @return string Formatted attributes string (e.g., 'key1="value1" key2="value2"').
	 */
	public static function format_atts_string( $atts ) {
		$parts = [];

		foreach ( $atts as $key => $value ) {
			if ( is_array( $value ) ) {
				// Handle array values (e.g., multi-sort: sort_direction, sort_direction_2).
				foreach ( $value as $index => $item ) {
					$suffix  = 0 === $index ? '' : '_' . ( $index + 1 );
					$escaped = str_replace( '"', '\"', sanitize_text_field( $item ) );
					$parts[] = sprintf( '%s%s="%s"', $key, $suffix, $escaped );
				}
			} else {
				$escaped = str_replace( '"', '\"', sanitize_text_field( $value ) );
				$parts[] = sprintf( '%s="%s"', $key, $escaped );
			}
		}

		return implode( ' ', $parts );
	}

	/**
	 * Converts block attributes array to shortcode attributes array.
	 *
	 * This is useful for page builders that use camelCase attribute names
	 * (like Gutenberg blocks) and need to convert to snake_case shortcode attributes.
	 *
	 * @since TODO
	 *
	 * @param array $block_attributes Block attributes array (camelCase keys).
	 *
	 * @return array Shortcode attributes array (snake_case keys).
	 */
	public static function map_block_atts_to_shortcode_atts( $block_attributes = [] ) {
		$shortcode_attributes = [];

		// Remove searchOperator if searchValue is empty.
		if ( isset( $block_attributes['searchOperator'] ) && isset( $block_attributes['searchValue'] ) && '' === trim( $block_attributes['searchValue'] ) ) {
			unset( $block_attributes['searchOperator'] );
		}

		foreach ( $block_attributes as $attribute => $value ) {
			if ( ! isset( self::BLOCK_TO_SHORTCODE_MAP[ $attribute ] ) ) {
				continue;
			}

			if ( '' === $value ) {
				continue;
			}

			$shortcode_attributes[ self::BLOCK_TO_SHORTCODE_MAP[ $attribute ] ] = $value;
		}

		return $shortcode_attributes;
	}

	/**
	 * Build a [gravityview] shortcode string from block-style attributes.
	 *
	 * This is the SIMPLE approach for page builders with limited UI controls.
	 * For full-featured integrations, use `build_from_view_settings()` instead.
	 *
	 * @since TODO
	 *
	 * @param array       $block_atts Block-style attributes (camelCase: viewId, pageSize, etc.).
	 * @param string|null $secret     Optional. View validation secret. If provided, added to shortcode.
	 *
	 * @return string The formatted [gravityview ...] shortcode string.
	 */
	public static function build_from_block_atts( $block_atts, $secret = null ) {
		$shortcode_atts = self::map_block_atts_to_shortcode_atts( $block_atts );

		if ( $secret ) {
			$shortcode_atts['secret'] = $secret;
		}

		return sprintf( '[gravityview %s]', self::format_atts_string( $shortcode_atts ) );
	}

	/**
	 * Build a [gravityview] shortcode string from View settings.
	 *
	 * This is the COMPLETE approach that reads from View_Settings::defaults()
	 * and includes ALL settings with `show_in_shortcode=true`. This approach
	 * is used by Advanced Elementor Widget and other full-featured integrations.
	 *
	 * Supports array values for multi-sort (e.g., sort_direction[0], sort_direction_2).
	 *
	 * @since TODO
	 *
	 * @param array  $settings Widget/module settings array (snake_case keys matching View_Settings).
	 * @param View   $view     View object.
	 * @param string $secret   Optional. View validation secret. Auto-retrieved from View if not provided.
	 *
	 * @return string The formatted [gravityview ...] shortcode string.
	 */
	public static function build_from_view_settings( $settings, $view, $secret = null ) {
		$atts = self::convert_settings_to_shortcode_atts( $settings );

		// Add secret if provided or get from view.
		$secret = $secret ?? $view->get_validation_secret();
		if ( $secret ) {
			$atts['secret'] = $secret;
		}

		// Add view ID.
		$atts['id'] = $view->ID;

		// Reverse to put `id` first, `secret` second for readability.
		$atts = array_reverse( $atts, true );

		return sprintf( '[gravityview %s]', self::format_atts_string( $atts ) );
	}

	/**
	 * Convert widget/module settings to shortcode attributes.
	 *
	 * Reads from View_Settings::defaults() and only includes settings
	 * where `show_in_shortcode=true` and value differs from default.
	 *
	 * @since TODO
	 *
	 * @param array $settings Widget/module settings.
	 *
	 * @return array Shortcode attributes.
	 */
	public static function convert_settings_to_shortcode_atts( $settings ) {
		$defaults = View_Settings::defaults( true );
		$atts     = [];

		foreach ( $defaults as $key => $view_setting ) {
			// Only render settings that are shown in the shortcode.
			if ( empty( $view_setting['show_in_shortcode'] ) ) {
				continue;
			}

			// Get the passed value, falling back to default if empty.
			$passed_value = ( ! isset( $settings[ $key ] ) || null === $settings[ $key ] || '' === $settings[ $key ] )
				? $view_setting['value']
				: $settings[ $key ];

			// Convert based on type.
			switch ( $view_setting['type'] ) {
				case 'number':
					$converted_value = (int) $passed_value;
					break;

				case 'checkbox':
					// Handle various truthy representations (Elementor uses 'yes').
					if ( 'yes' === $passed_value || '1' === $passed_value || 1 === $passed_value || true === $passed_value ) {
						$converted_value = 1;
					} else {
						$converted_value = 0;
					}
					break;

				default:
					$converted_value = $passed_value;
					break;
			}

			// Only add if different from default value.
			if ( $converted_value === $view_setting['value'] ) {
				continue;
			}

			$atts[ $key ] = $converted_value;
		}

		return $atts;
	}

	/**
	 * Filters asset handles based on patterns.
	 *
	 * This method filters WordPress script/style handles using pattern matching.
	 * It can operate in two modes:
	 * - 'blocklist': Remove handles matching any pattern (default for backward compatibility)
	 * - 'allowlist': Keep only handles matching at least one pattern
	 *
	 * Filtering happens on handles BEFORE dependency resolution, which prevents
	 * unrelated dependencies from being included in the final output.
	 *
	 * @since TODO
	 *
	 * @param array  $handles  Array of asset handles (slugs) to filter.
	 * @param array  $patterns Array of patterns to match against handles.
	 * @param string $mode     Filter mode: 'allowlist' or 'blocklist'. Default 'blocklist'.
	 *
	 * @return array Filtered array of handles.
	 */
	public static function filter_asset_handles( $handles, $patterns = [], $mode = 'blocklist' ) {
		if ( empty( $handles ) || empty( $patterns ) ) {
			return $handles;
		}

		// Pass the delimiter '/' to preg_quote to properly escape it within patterns.
		$pattern_regex = '/(' . implode( '|', array_map( function( $p ) { return preg_quote( $p, '/' ); }, $patterns ) ) . ')/i';

		if ( 'allowlist' === $mode ) {
			// Keep only handles that match at least one pattern.
			return array_values( preg_grep( $pattern_regex, $handles ) );
		}

		// Blocklist mode: remove handles that match any pattern.
		return array_values( array_diff( $handles, preg_grep( $pattern_regex, $handles ) ) );
	}

	/**
	 * Renders shortcode and returns rendered content along with newly enqueued scripts and styles.
	 *
	 * This is the universal rendering method used by all page builder integrations.
	 *
	 * @since TODO
	 *
	 * @param string $shortcode The shortcode to render.
	 * @param array  $options   {
	 *     Optional. Configuration options.
	 *
	 *     @type array $allowed_style_patterns  Patterns to allowlist for styles. If provided, only
	 *                                          styles with handles matching these patterns are returned.
	 *     @type array $allowed_script_patterns Patterns to allowlist for scripts. If provided, only
	 *                                          scripts with handles matching these patterns are returned.
	 * }
	 *
	 * @return array{content: string, scripts: array, styles: array}
	 */
	public static function render( $shortcode, $options = [] ) {
		global $wp_scripts, $wp_styles;

		// Ensure WordPress script/style systems are initialized.
		if ( ! $wp_scripts instanceof \WP_Scripts ) {
			$wp_scripts = wp_scripts();
		}
		if ( ! $wp_styles instanceof \WP_Styles ) {
			$wp_styles = wp_styles();
		}

		$scripts_before_shortcode = array_keys( $wp_scripts->registered );
		$styles_before_shortcode  = array_keys( $wp_styles->registered );

		// Use output buffering only for the shortcode rendering to capture any stray output.
		// Keep do_action() calls outside the buffer to avoid conflicts with other plugins' buffering.
		ob_start();
		$rendered_shortcode = do_shortcode( $shortcode );
		ob_end_clean();

		// Trigger script/style registration outside of output buffering.
		// This follows WordPress best practice: no hooks should be called within an output buffer scope.
		do_action( 'wp_enqueue_scripts' );

		$gravityview_frontend = \GravityView_frontend::getInstance();
		$gravityview_frontend->setGvOutputData( \GravityView_View_Data::getInstance( $shortcode ) );
		$gravityview_frontend->add_scripts_and_styles();

		$scripts_after_shortcode = array_keys( $wp_scripts->registered );
		$styles_after_shortcode  = array_keys( $wp_styles->registered );

		$newly_registered_scripts = array_diff( $scripts_after_shortcode, $scripts_before_shortcode );
		$newly_registered_styles  = array_diff( $styles_after_shortcode, $styles_before_shortcode );

		// First, apply blocklist to remove scripts/styles that may cause conflicts.
		$newly_registered_scripts = self::filter_asset_handles(
			$newly_registered_scripts,
			self::IGNORE_SCRIPTS_AND_STYLES,
			'blocklist'
		);
		$newly_registered_styles = self::filter_asset_handles(
			$newly_registered_styles,
			self::IGNORE_SCRIPTS_AND_STYLES,
			'blocklist'
		);

		// Then, apply allowlist if patterns are provided to further filter assets.
		if ( ! empty( $options['allowed_script_patterns'] ) ) {
			$newly_registered_scripts = self::filter_asset_handles(
				$newly_registered_scripts,
				$options['allowed_script_patterns'],
				'allowlist'
			);
		}

		if ( ! empty( $options['allowed_style_patterns'] ) ) {
			$newly_registered_styles = self::filter_asset_handles(
				$newly_registered_styles,
				$options['allowed_style_patterns'],
				'allowlist'
			);
		}

		// This will return an array of all dependencies sorted in the order they should be loaded.
		$get_dependencies = function ( $handle, $source, $dependencies = [] ) use ( &$get_dependencies ) {
			if ( empty( $source->registered[ $handle ] ) ) {
				return $dependencies;
			}

			if ( $source->registered[ $handle ]->extra && ! empty( $source->registered[ $handle ]->extra['data'] ) ) {
				array_unshift(
					$dependencies,
					array_filter(
						[
							'src'  => $source->registered[ $handle ]->src,
							'data' => $source->registered[ $handle ]->extra['data'],
						]
					)
				);
			} elseif ( $source->registered[ $handle ]->src ) {
				array_unshift( $dependencies, $source->registered[ $handle ]->src );
			}

			if ( ! $source->registered[ $handle ]->deps ) {
				return $dependencies;
			}

			foreach ( $source->registered[ $handle ]->deps as $dependency ) {
				array_unshift( $dependencies, $get_dependencies( $dependency, $source ) );
			}

			return Arr::flatten( $dependencies );
		};

		$script_dependencies = [];
		foreach ( $newly_registered_scripts as $script ) {
			$script_dependencies = array_merge( $script_dependencies, $get_dependencies( $script, $wp_scripts ) );
		}

		$style_dependencies = [];
		foreach ( $newly_registered_styles as $style ) {
			$style_dependencies = array_merge( $style_dependencies, $get_dependencies( $style, $wp_styles ) );
		}

		return [
			'scripts' => array_unique( $script_dependencies, SORT_REGULAR ),
			'styles'  => array_unique( $style_dependencies, SORT_REGULAR ),
			'content' => $rendered_shortcode,
		];
	}
}
