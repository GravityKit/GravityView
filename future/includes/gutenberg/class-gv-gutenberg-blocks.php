<?php

namespace GravityKit\GravityView\Gutenberg;

use GravityKit\GravityView\Foundation\Helpers\Arr;
use GVCommon;

class Blocks {
	const MIN_WP_VERSION = '6.0.0';

	const SLUG = 'gk-gravityview-blocks';

	public function __construct() {
		global $wp_version;

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

				$block_name        = Arr::get( $block_meta, 'name' );
				$localization_data = Arr::get( $block_meta, 'localization' );

				if ( $localization_data ) {
					add_filter( 'gk/gravityview/gutenberg/blocks/localization', function ( $localization ) use ( $block_name, $localization_data ) {
						$localization[ $block_name ] = $localization_data;

						return $localization;
					} );
				}
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
		 * @since  1.0.0
		 *
		 * @param array $block_localization_data
		 */
		$block_localization_data = apply_filters( 'gk/gravityview/gutenberg/blocks/localization', [
			'home_page' => home_url(),
			'ajax_url'  => admin_url( 'admin-ajax.php' ),
			'views'     => $this->get_views()
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

		return array_map( function ( $view ) {
			return [
				'value' => (string) $view->ID,
				'label' => $view->post_title,
			];
		}, $views );
	}
}

new Blocks();