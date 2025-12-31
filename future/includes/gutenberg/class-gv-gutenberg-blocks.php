<?php

namespace GravityKit\GravityView\Gutenberg;

use GravityKit\GravityView\Foundation\Helpers\Arr;
use GravityKit\GravityView\Shortcodes\ShortcodeRenderer;
use GV\View;
use GVCommon;

class Blocks {
	const MIN_WP_VERSION = '6.3.0';

	const SLUG = 'gk-gravityview-blocks';

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

	private $blocks_build_path;

	public function __construct() {
		global $wp_version;

		$this->blocks_build_path = str_replace( GRAVITYVIEW_DIR, '', __DIR__ ) . '/build';

		if ( version_compare( $wp_version, self::MIN_WP_VERSION, '<' ) ) {
			return;
		}

		add_filter( 'block_categories_all', array( $this, 'add_block_category' ) );

		add_filter( 'enqueue_block_assets', array( $this, 'localize_block_assets' ) );

		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Registers block renderers.
	 *
	 * @since 2.17
	 *
	 * @return void
	 */
	public function register_blocks() {
		foreach ( glob( plugin_dir_path( __FILE__ ) . 'blocks/*' ) as $block_folder ) {
			$block_meta_file = $block_folder . '/block.json';
			$block_file      = $block_folder . '/block.php';

			if ( ! file_exists( $block_meta_file ) ) {
				continue;
			}

			$block_meta = json_decode( file_get_contents( $block_meta_file ), true );

			$block_name = Arr::get( $block_meta, 'name' );

			if ( file_exists( $block_file ) ) {
				$declared_classes = get_declared_classes();

				require_once $block_file;

				$block_class = array_values( array_diff( get_declared_classes(), $declared_classes ) );

				if ( ! empty( $block_class ) ) {
					$block_class = new $block_class[0]();

					if ( is_callable( array( $block_class, 'modify_block_meta' ) ) ) {
						$block_meta = array_merge( $block_meta, $block_class->modify_block_meta( $block_meta ) );
					}
				}

				$localization_data = Arr::get( $block_meta, 'localization' );

				if ( $localization_data ) {
					add_filter(
						'gk/gravityview/gutenberg/blocks/localization',
						function ( $localization ) use ( $block_name, $localization_data ) {
							$localization[ $block_name ] = $localization_data;

							return $localization;
						}
					);
				}
			}

			// Assets can be specified in the block.json file, but their paths must be relative to that file location.
			// We store all build assets in ./build, and while we can use "file:../../build/filename.js' in the block.json,
			// the MD5 hash will not match the translation file from translations.pot. Manually enqueuing assets fixes this.
			$editor_script_handle = generate_block_asset_handle( $block_name, 'editorScript' );
			$editor_script        = sprintf( '%s/%s.js', $this->blocks_build_path, basename( $block_folder ) );

			$editor_style_handle = generate_block_asset_handle( $block_name, 'editorStyle' );
			$editor_style        = sprintf( '%s/%s.css', $this->blocks_build_path, basename( $block_folder ) );

			$global_style_handle = generate_block_asset_handle( $block_name, 'style' );
			$global_style        = sprintf( '%s/style-%s.css', $this->blocks_build_path, basename( $block_folder ) );

			if ( file_exists( GRAVITYVIEW_DIR . $editor_script ) ) {
				wp_register_script(
					$editor_script_handle,
					plugins_url( $editor_script, GRAVITYVIEW_FILE ),
					array( 'wp-editor', 'wp-element' ),
					filemtime( GRAVITYVIEW_DIR . $editor_script ),
					true
				);

				add_action(
					'enqueue_block_editor_assets',
					function () use ( $editor_script_handle ) {
						wp_enqueue_script( $editor_script_handle );

						wp_set_script_translations( $editor_script_handle, 'gk-gravityview' );
					}
				);
			}

			if ( file_exists( GRAVITYVIEW_DIR . $editor_style ) ) {
				wp_register_style(
					$editor_style_handle,
					plugins_url( $editor_style, GRAVITYVIEW_FILE ),
					array(),
					filemtime( GRAVITYVIEW_DIR . $editor_style )
				);

				add_action(
					'enqueue_block_editor_assets',
					function () use ( $editor_style_handle ) {
						wp_enqueue_style( $editor_style_handle );
					}
				);
			}

			if ( file_exists( GRAVITYVIEW_DIR . $global_style ) ) {
				wp_register_style(
					$global_style_handle,
					plugins_url( $global_style, GRAVITYVIEW_FILE ),
					array(),
					filemtime( GRAVITYVIEW_DIR . $global_style )
				);

				add_action(
					'enqueue_block_editor_assets',
					function () use ( $global_style_handle ) {
						wp_enqueue_style( $global_style_handle );
					}
				);
			}

			register_block_type_from_metadata( $block_meta_file, $block_meta );
		}
	}

	/**
	 * Adds GravityView category to Gutenberg editor.
	 *
	 * @since 2.17
	 *
	 * @param array $categories
	 *
	 * @return array
	 */
	public function add_block_category( $categories ) {
		return array_merge(
			$categories,
			array(
				array(
					'slug'  => self::SLUG,
					'title' => __( 'GravityView', 'gk-gravityview' ),
				),
			)
		);
	}

	/**
	 * Localizes shared block assets that's made available to all blocks via the global window.gkGravityKitBlocks object.
	 *
	 * @since 2.17
	 *
	 * @return void
	 */
	public function localize_block_assets() {
		// Prevent leaking information on front-end.
		if ( ! is_admin() ) {
			return;
		}

		/**
		 * Modifies the global blocks localization data.
		 *
		 * @since 2.17
		 *
		 * @param array $block_localization_data Array of data to be localized for Gutenberg blocks.
		 */
		$block_localization_data = apply_filters(
			'gk/gravityview/gutenberg/blocks/localization',
			array(
				'home_page'           => home_url(),
				'ajax_url'            => admin_url( 'admin-ajax.php' ),
				'create_new_view_url' => gravityview()->plugin->get_link_to_new_view(),
				'edit_view_url'       => add_query_arg(
					array(
						'action' => 'edit',
						'post'   => '%s',
					),
					admin_url( 'post.php' )
				),
				'views'               => $this->get_views(),
				'nonce'=>wp_create_nonce( 'gravityview_ajaxaddshortcode' ),
			)
		);

		wp_register_script( self::SLUG, false, array() );

		wp_enqueue_script( self::SLUG );

		wp_localize_script(
			self::SLUG,
			'gkGravityViewBlocks',
			$block_localization_data
		);
	}

	/**
	 * Returns the list of views for the block editor.
	 *
	 * @since 2.17
	 *
	 * @return array[]
	 */
	public function get_views() {
		$views_data = GVCommon::get_views_list();

		$formatted_views = [];

		foreach ( $views_data as $view ) {
			$formatted_views[] = [
				'value'  => (string) $view['id'],
				// translators: %1$s is the View title, %2$d is the View ID.
				'label'  => sprintf( __( '%1$s (#%2$d)', 'gk-gravityview' ), $view['title'], $view['id'] ),
				'secret' => $view['secret'],
			];
		}

		/**
		 * Modifies the Views object used in the UI.
		 *
		 * @since 2.17
		 *
		 * @param array $formatted_views Array of formatted View data for the block editor.
		 */
		return apply_filters( 'gk/gravityview/gutenberg/blocks/views', $formatted_views );
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
	 * @deprecated TODO Use ShortcodeRenderer::filter_asset_handles() instead.
	 *
	 * @param array  $handles  Array of asset handles (slugs) to filter.
	 * @param array  $patterns Array of patterns to match against handles.
	 * @param string $mode     Filter mode: 'allowlist' or 'blocklist'. Default 'blocklist'.
	 *
	 * @return array Filtered array of handles.
	 */
	public static function filter_asset_handles( $handles, $patterns = [], $mode = 'blocklist' ) {
		return ShortcodeRenderer::filter_asset_handles( $handles, $patterns, $mode );
	}

	/**
	 * Renders shortcode and returns rendered content along with newly enqueued scripts and styles.
	 *
	 * @since 2.17
	 * @since TODO Added $options parameter for asset handle filtering.
	 * @deprecated TODO Use ShortcodeRenderer::render() instead.
	 *
	 * @param string $shortcode The shortcode to render.
	 * @param array  $options {
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
	public static function render_shortcode( $shortcode, $options = [] ) {
		return ShortcodeRenderer::render( $shortcode, $options );
	}
}

new Blocks();
