<?php

/**
 * @inheritDoc
 * @since 2.16
 */
class GravityView_Plugin_Hooks_GravityMaps extends GravityView_Plugin_and_Theme_Hooks {
	public function __construct() {
		if ( defined( 'GRAVITYVIEW_MAPS_VERSION' ) && version_compare( GRAVITYVIEW_MAPS_VERSION, '1.8', '<' ) ) {
			add_filter( 'gravityview/admin/notices', function ( $notices ) {
				$message = esc_html_x( 'You are using [plugin] [version] that is incompatible with the current version of GravityView. Please [link]update[/link] [plugin] to the latest version.', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' );

				$message = strtr( $message, array(
					'[plugin]'  => 'GravityView Maps',
					'[version]' => GRAVITYVIEW_MAPS_VERSION,
					'[link]'    => '<a href="' . GravityKitFoundation::licenses()->get_link_to_product_search( 27 ) . '">',
					'[/link]'   => '</a>',
				) );

				$notices[] = [
					'class'   => 'error',
					'message' => $message,
					'dismiss' => false,
				];

				return $notices;
			} );
		}
	}
}

new GravityView_Plugin_Hooks_GravityMaps;