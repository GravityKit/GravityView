<?php

namespace GravityKit\GravityView\Gutenberg;

use GravityKit\GravityView\Foundation\Helpers\Arr;
use GVCommon;

class Blocks {
	const MIN_WP_VERSION = '6.0.0';

	const SLUG = 'gk-gravityview-blocks';

	private $blocks_build_path;

	public function __construct() {
		global $wp_version;

		$this->blocks_build_path = str_replace( GRAVITYVIEW_DIR, '', __DIR__ ) . '/build';

		if ( version_compare( $wp_version, self::MIN_WP_VERSION, '<' ) ) {
			return;
		}

		add_filter( 'block_categories_all', [ $this, 'add_block_category' ] );

		add_filter( 'enqueue_block_assets', [ $this, 'localize_block_assets' ] );

		add_action( 'init', [ $this, 'register_blocks' ] );
	}

	/**
	 * Registers block renderers.
	 *
	 * @since $ver$
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

					if ( is_callable( [ $block_class, 'modify_block_meta' ] ) ) {
						$block_meta = array_merge( $block_meta, $block_class->modify_block_meta( $block_meta ) );
					}
				}

				$localization_data = Arr::get( $block_meta, 'localization' );

				if ( $localization_data ) {
					add_filter( 'gk/gravityview/gutenberg/blocks/localization', function ( $localization ) use ( $block_name, $localization_data ) {
						$localization[ $block_name ] = $localization_data;

						return $localization;
					} );
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
					[ 'wp-editor', 'wp-element' ],
					filemtime( GRAVITYVIEW_DIR . $editor_script ),
					true
				);

				add_action( 'enqueue_block_editor_assets', function () use ( $editor_script_handle ) {
					wp_enqueue_script( $editor_script_handle );

					wp_set_script_translations( $editor_script_handle, 'gk-gravityview' );
				} );
			}

			if ( file_exists( GRAVITYVIEW_DIR . $editor_style ) ) {
				wp_register_style(
					$editor_style_handle,
					plugins_url( $editor_style, GRAVITYVIEW_FILE ),
					[],
					filemtime( GRAVITYVIEW_DIR . $editor_style )
				);

				add_action( 'enqueue_block_editor_assets', function () use ( $editor_style_handle ) {
					wp_enqueue_style( $editor_style_handle );
				} );
			}

			if ( file_exists( GRAVITYVIEW_DIR . $global_style ) ) {
				wp_register_style(
					$global_style_handle,
					plugins_url( $global_style, GRAVITYVIEW_FILE ),
					[],
					filemtime( GRAVITYVIEW_DIR . $global_style )
				);

				add_action( 'enqueue_block_editor_assets', function () use ( $global_style_handle) {
					wp_enqueue_style( $global_style_handle );
				} );
			}

			register_block_type_from_metadata( $block_meta_file, $block_meta );
		}
	}

	/**
	 * Adds GravityView category to Gutenberg editor.
	 *
	 * @since $ver$
	 *
	 * @param array $categories
	 *
	 * @return array
	 */
	public function add_block_category( $categories ) {
		return array_merge(
			$categories,
			[
				[ 'slug' => self::SLUG, 'title' => __( 'GravityView', 'gk-gravityview' ) ],
			]
		);
	}

	/**
	 * Localizes shared block assets that's made available to all blocks via the global window.gkGravityKitBlocks object.
	 *
	 * @since $ver$
	 *
	 * @return void
	 */
	public function localize_block_assets() {
		/**
		 * @filter `gk/gravityview/gutenberg/blocks/localization` Modifies the global blocks localization data.
		 *
		 * @since  $ver$
		 *
		 * @param array $block_localization_data
		 */
		$block_localization_data = apply_filters( 'gk/gravityview/gutenberg/blocks/localization', [
			'home_page'           => home_url(),
			'ajax_url'            => admin_url( 'admin-ajax.php' ),
			'create_new_view_url' => gravityview()->plugin->get_link_to_new_view(),
			'edit_view_url'       => add_query_arg(
				[ 'action' => 'edit', 'post' => '%s' ],
				admin_url( 'post.php' )
			),
			'views'               => $this->get_views()
		] );

		wp_register_script( self::SLUG, false, [] );

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
	 * @since $ver$
	 *
	 * @return array|array[]
	 */
	public function get_views() {
		$views = GVCommon::get_all_views( [
			'orderby' => 'post_title',
			'order'   => 'ASC',
		] );

		$formatted_views = array_map( function ( $view ) {
			return [
				'value' => (string) $view->ID,
				'label' => sprintf(
					'%s (#%s)',
					$view->post_title ?: esc_html__( 'View', 'gk-gravityview' ),
					$view->ID
				)
			];
		}, $views );

		/**
		 * @filter `gk/gravityview/gutenberg/blocks/views` Modifies the Views object used in the UI.
		 *
		 * @since  $ver$
		 *
		 * @param array $formatted_views
		 */
		$formatted_views = apply_filters( 'gk/gravityview/gutenberg/blocks/views', $formatted_views );

		return $formatted_views;
	}

	/**
	 * Renders shortcode and returns rendered content along with newly enqueued scripts and styles.
	 *
	 * @since $ver$
	 *
	 * @param string $shortcode
	 *
	 * @return array{content: string, scripts: array, styles: array}
	 */
	static function render_shortcode( $shortcode ) {
		global $wp_scripts, $wp_styles;

		$scripts_before_shortcode = array_keys( $wp_scripts->registered );
		$styles_before_shortcode  = array_keys( $wp_styles->registered );

		$rendered_shortcode = do_shortcode( $shortcode );

		do_action( 'wp_enqueue_scripts' );

		$gravityview_frontend = \GravityView_frontend::getInstance();
		$gravityview_frontend->setGvOutputData( \GravityView_View_Data::getInstance( $shortcode ) );
		$gravityview_frontend->add_scripts_and_styles();

		$scripts_after_shortcode = array_keys( $wp_scripts->registered );
		$styles_after_shortcode  = array_keys( $wp_styles->registered );

		$newly_registered_scripts = array_diff( $scripts_after_shortcode, $scripts_before_shortcode );
		$newly_registered_styles  = array_diff( $styles_after_shortcode, $styles_before_shortcode );

		// This will return an array of all dependencies sorted in the order they should be loaded.
		$get_dependencies = function ( $handle, $source, $dependencies = [] ) use ( &$get_dependencies ) {
			if ( empty( $source->registered[ $handle ] ) ) {
				return $dependencies;
			}

			if ( $source->registered[ $handle ]->extra && ! empty( $source->registered[ $handle ]->extra['data'] ) ) {
				array_unshift( $dependencies, array_filter( [ 'src' => $source->registered[ $handle ]->src, 'data' => $source->registered[ $handle ]->extra['data'] ] ) );
			} else if ( $source->registered[ $handle ]->src ) {
				array_unshift( $dependencies, $source->registered[ $handle ]->src );
			}

			if ( ! $source->registered[ $handle ]->deps ) {
				return $dependencies;
			}

			foreach ( $source->registered[ $handle ]->deps as $dependency ) {
				array_unshift( $dependencies, $get_dependencies( $dependency, $source ) );
			}

			return array_flatten( $dependencies );
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

new Blocks();
