<?php

/**
 * @inheritDoc
 *
 * @since 2.16
 */
class GravityView_Plugin_Hooks_GravityMaps extends GravityView_Plugin_and_Theme_Hooks {
	const MAPS_PRODUCT_ID = 27;

	protected $constant_name = 'GRAVITYVIEW_MAPS_VERSION';

	/**
	 * @inheritDoc
	 */
	public function add_hooks() {
		$google_maps_api_key_filter = defined( 'GRAVITYVIEW_MAPS_VERSION' ) && version_compare( GRAVITYVIEW_MAPS_VERSION, '3.1.0', '<' )
			? 'gravityview/maps/render/google_api_key'
			: 'gk/gravitymaps/map-services/google-maps/api_key';

		/**
		 * Temporarily keep maps working on the front-end when running new GV and old Maps.
		 *
		 * @since 2.16
		 */
		add_filter(
			$google_maps_api_key_filter,
			function ( $api_key ) {
				if ( ! empty( $api_key ) ) {
					return $api_key;
				}

				$legacy_options = (array) get_option( 'gravityformsaddon_gravityview_app_settings' );

				return \GV\Utils::get( $legacy_options, 'googlemaps-api-key', '' );
			}
		);

		if ( defined( 'GRAVITYVIEW_MAPS_VERSION' ) &&
		     version_compare( GRAVITYVIEW_MAPS_VERSION, '1.8', '<' ) &&
		     class_exists( 'GravityKitFoundation' )
		) {
			$notice_manager = GravityKitFoundation::notices();

			if ( $notice_manager ) {
				$messages = [
					esc_html__( 'Plugin update required.', 'gk-gravityview' ),
					// Translators: [plugin], [version], [link], [/link] are placeholders. Do not translate the words inside [].
					esc_html_x( 'You are using [plugin] [version] that is incompatible with the current version of GravityView. Please [link]update [plugin][/link] to the latest version.', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
				];

				$message = strtr(
					join( ' ', $messages ),
					[
						'[version]' => GRAVITYVIEW_MAPS_VERSION,
						'[link]'    => '<a href="' . esc_url( GravityKitFoundation::licenses()->get_link_to_product_search( self::MAPS_PRODUCT_ID ) ) . '">',
						'[plugin]'  => 'GravityView Maps',
						'[/link]'   => '</a>',
					]
				);

				try {
					$notice_manager->add_runtime( [
						'namespace'    => 'gk-gravitymaps',
						'slug'         => 'gravitymaps-version-conflict',
						'message'      => $message,
						'severity'     => 'error',
						'capabilities' => [ 'manage_options' ],
						'dismissible'  => false,
						'screens'      => [ 'dashboard', 'plugins', 'dashboard-network', 'plugins-network' ],
						'context'      => 'all',
					] );
				} catch ( Exception $e ) {
					gravityview()->log->debug( 'Failed to register GravityMaps compatibility notice with Foundation: ' . $e->getMessage() );
				}
			}
		}

		parent::add_hooks();
	}

	/**
	 * @inheritDoc
	 *
	 * @since 2.26
	 */
	protected function add_inactive_hooks(): void {
		add_action( 'add_meta_boxes', [ $this, 'register_metabox_placeholder' ] );
	}

	/**
	 * Returns the icon for the GravityView Maps layout.
	 *
	 * @since 2.26
	 *
	 * @return string The SVG icon.
	 */
	private function get_placeholder_icon(): string {
		return <<<ICON
	<svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
		<rect x="1.5" y="1.5" width="77" height="77" rx="6.5" fill="white"/>
		<rect x="1.5" y="1.5" width="77" height="77" rx="6.5" stroke="#FF1B67" stroke-width="3"/>
		<path d="M40.5 54.5V50.5" stroke="#FF1B67" stroke-width="3" stroke-miterlimit="10"
			  stroke-linecap="round" stroke-linejoin="round"/>
		<path
			d="M56.5 24.5H58.5C59.5609 24.5 60.5783 24.9214 61.3284 25.6716C62.0786 26.4217 62.5 27.4391 62.5 28.5V56.5C62.5 57.5609 62.0786 58.5783 61.3284 59.3284C60.5783 60.0786 59.5609 60.5 58.5 60.5H22.5C21.4391 60.5 20.4217 60.0786 19.6716 59.3284C18.9214 58.5783 18.5 57.5609 18.5 56.5V28.5C18.5 27.4391 18.9214 26.4217 19.6716 25.6716C20.4217 24.9214 21.4391 24.5 22.5 24.5H25.5"
			stroke="#FF1B67" stroke-width="3" stroke-miterlimit="10" stroke-linecap="round"
			stroke-linejoin="round"/>
		<path d="M56.5 31.5V54.5H24.5V31.5" stroke="#FF1B67" stroke-width="3" stroke-miterlimit="10"
			  stroke-linecap="round" stroke-linejoin="round"/>
		<path d="M24.5 54.5L36.593 40.333" stroke="#FF1B67" stroke-width="3" stroke-miterlimit="10"
			  stroke-linecap="round" stroke-linejoin="round"/>
		<path d="M56.5 54.5L44.407 40.333" stroke="#FF1B67" stroke-width="3" stroke-miterlimit="10"
			  stroke-linecap="round" stroke-linejoin="round"/>
		<path
			d="M50.5 28.5C50.5 34.672 40.5 44.75 40.5 44.75C40.5 44.75 30.5 34.672 30.5 28.5C30.5 25.8478 31.5536 23.3043 33.4289 21.4289C35.3043 19.5536 37.8478 18.5 40.5 18.5C43.1522 18.5 45.6957 19.5536 47.5711 21.4289C49.4464 23.3043 50.5 25.8478 50.5 28.5V28.5Z"
			stroke="#FF1B67" stroke-width="3" stroke-miterlimit="10" stroke-linecap="round"
			stroke-linejoin="round"/>
		<path
			d="M40.5 30.5C41.6046 30.5 42.5 29.6046 42.5 28.5C42.5 27.3954 41.6046 26.5 40.5 26.5C39.3954 26.5 38.5 27.3954 38.5 28.5C38.5 29.6046 39.3954 30.5 40.5 30.5Z"
			fill="#FF1B67"/>
	</svg>
ICON;
	}

	/**
	 * Returns the placeholder value object.
	 *
	 * @since 2.26
	 *
	 * @return GravityView_Object_Placeholder The placeholder.
	 */
	private function get_placeholder(): GravityView_Object_Placeholder {
		return
			GravityView_Object_Placeholder::card(
				__( 'Maps Layout', 'gk-gravityview' ),
				__( 'Display entries in a Map View, where entries are displayed as “pins” on a map, like on Yelp.com. Also, add map widgets and fields to all GravityView layouts.', 'gk-gravityview' ),
				$this->get_placeholder_icon(),
				'gk-gravitymaps',
				'https://www.gravitykit.com/products/maps/'
			);
	}

	/**
	 * Register the Maps placeholder metabox.
	 *
	 * @since 2.26
	 */
	public function register_metabox_placeholder(): void {
		$disabled = apply_filters( 'gk/gravityview/feature/upgrade/disabled', false );

		if ( $disabled ) {
			return;
		}

		$metabox = new GravityView_Metabox_Tab(
			'maps_settings',
			__( 'Maps', 'gk-gravitymaps', 'gk-gravityview' ),
			'',
			'dashicons-location-alt',
			function () {
				$this->get_placeholder()->render();
			}
		);

		$metabox->extra_nav_class = 'gravityview-upgrade';

		GravityView_Metabox_Tabs::add( $metabox );
	}
}

new GravityView_Plugin_Hooks_GravityMaps();
