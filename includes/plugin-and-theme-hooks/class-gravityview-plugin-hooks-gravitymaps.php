<?php

/**
 * @inheritDoc
 * @since 2.16
 */
class GravityView_Plugin_Hooks_GravityMaps extends GravityView_Plugin_and_Theme_Hooks {
	public function __construct() {

		if ( ! defined( 'GRAVITYVIEW_MAPS_VERSION' ) || version_compare( GRAVITYVIEW_MAPS_VERSION, '1.8', '>=' ) ) {
			return;
		}

		/**
		 * Temporarily keep maps working on the front-end when running new GV and old Maps.
		 *
		 * @since 2.16
		 */
		add_filter(
			'gravityview/maps/render/google_api_key',
			function ( $api_key ) {

				if ( ! empty( $api_key ) ) {
					return $api_key;
				}

				$legacy_options = (array) get_option( 'gravityformsaddon_gravityview_app_settings' );

				return \GV\Utils::get( $legacy_options, 'googlemaps-api-key', '' );
			}
		);

		/**
		 * @since 2.16
		 * @param array $notices
		 * @return array $notices, with a new notice about Maps compatibility added.
		 */
		add_filter(
			'gravityview/admin/notices',
			function ( $notices ) {

				$message  = '<h3>' . esc_html__( 'Plugin update required.', 'gk-gravityview' ) . '</h3>';
				$message .= esc_html_x( 'You are using [plugin] [version] that is incompatible with the current version of GravityView. Please [link]update [plugin][/link] to the latest version.', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' );

				$message = strtr(
					$message,
					array(
						'[version]' => GRAVITYVIEW_MAPS_VERSION,
						'[link]'    => '<a href="' . esc_url( GravityKitFoundation::licenses()->get_link_to_product_search( 27 ) ) . '">',
						'[plugin]'  => 'GravityView Maps',
						'[/link]'   => '</a>',
					)
				);

				$notices[] = array(
					'class'   => 'error',
					'message' => $message,
					'dismiss' => false,
				);

				return $notices;
			}
		);
	}
}

new GravityView_Plugin_Hooks_GravityMaps();
