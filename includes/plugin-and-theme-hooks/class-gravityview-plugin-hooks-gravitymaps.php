<?php

/**
 * @inheritDoc
 * @since 2.16
 */
class GravityView_Plugin_Hooks_GravityMaps extends GravityView_Plugin_and_Theme_Hooks {

	protected $class_name = 'GravityView_Plugin_and_Theme_Hooks'; // Always true!

	public function __construct() {
		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	public function add_hooks() {
		if ( defined( 'GRAVITYVIEW_MAPS_VERSION' ) ) {
			$this->add_hooks_maps_enabled();
		} else {
			$this->add_hooks_maps_disabled();
		}
	}

	/**
	 * Add hooks for when Maps is disabled.
	 */
	private function add_hooks_maps_disabled() {
		add_action( 'add_meta_boxes', [ $this, 'register_metabox' ] );
	}

	/**
	 * Register the Maps placeholder metabox.
	 *
	 * @since TODO
	 */
	function register_metabox() {

		$m = [
			'id'            => 'maps_settings',
			'title'         => __( 'Maps', 'gk-gravitymaps' ),
			'callback'      => array( $this, 'render_metabox_placeholder' ),
			'icon-class'    => 'dashicons-location-alt',
			'file'          => '',
			'callback_args' => '',
			'screen'        => 'gravityview',
			'context'       => 'side',
			'priority'      => 'default',
		];

		$metabox = new GravityView_Metabox_Tab( $m['id'], $m['title'], $m['file'], $m['icon-class'], $m['callback'], $m['callback_args'] );

		GravityView_Metabox_Tabs::add( $metabox );
	}

	/**
	 * Render placeholder HTML.
	 *
	 * @access public
	 * @param WP_Post $post
	 * @return void
	 */
	function render_metabox_placeholder( $post ) {
		?>
		<style>
			#gk-placeholder-maps .gk-placeholder-container {
				margin: 15px auto;
				box-sizing: border-box;
				padding: 30px 15px;
				background: white;
				border-radius: 7px;
				max-width: 80%;
				border: 1px #DDDDE5 solid;
			}

			#gk-placeholder-maps .gk-placeholder-content {
				min-height: 48px;
				border-radius: 4px;
				text-align: center;
			}
			#gk-placeholder-maps svg {
				margin-bottom: 30px;
			}
			#gk-placeholder-maps .button {
				display: block;
				padding: 0 16px;
				text-align: center;
				margin: 1.5em auto 1em;
			}

			#gk-placeholder-maps .gk-placeholder-summary {
				max-width: 800px;
				margin: 0 auto;
			}

			#gk-placeholder-maps .gk-placeholder-summary .gk-h3 {
				display: block;
				position: relative;
				font-weight: 500;
				line-height: 24px;
				vertical-align: middle;
				color: #23282D;
				font-size: 18px;
				margin: 0;
				padding: 0;
			}

			#gk-placeholder-maps .gk-placeholder-summary .howto p {
				font-size: 1.3em;
				line-height: 1.7;
			}
			#gk-placeholder-maps .gk-placeholder-learn-more {
				display: block;
				text-align: center;
				margin: 1.5em auto 0;
				font-size: 1.1em;
			}
		</style>
		<div id='gk-placeholder-maps'>
			<div class='gk-placeholder-container'>
				<div class='gk-placeholder-content'>
					<svg width='80' height='80' viewBox='0 0 80 80' fill='none' xmlns='http://www.w3.org/2000/svg'>
						<rect x='1.5' y='1.5' width='77' height='77' rx='6.5' fill='white'/>
						<rect x='1.5' y='1.5' width='77' height='77' rx='6.5' stroke='#FF1B67' stroke-width='3'/>
						<path d='M40.5 54.5V50.5' stroke='#FF1B67' stroke-width='3' stroke-miterlimit='10'
						      stroke-linecap='round' stroke-linejoin='round'/>
						<path
							d='M56.5 24.5H58.5C59.5609 24.5 60.5783 24.9214 61.3284 25.6716C62.0786 26.4217 62.5 27.4391 62.5 28.5V56.5C62.5 57.5609 62.0786 58.5783 61.3284 59.3284C60.5783 60.0786 59.5609 60.5 58.5 60.5H22.5C21.4391 60.5 20.4217 60.0786 19.6716 59.3284C18.9214 58.5783 18.5 57.5609 18.5 56.5V28.5C18.5 27.4391 18.9214 26.4217 19.6716 25.6716C20.4217 24.9214 21.4391 24.5 22.5 24.5H25.5'
							stroke='#FF1B67' stroke-width='3' stroke-miterlimit='10' stroke-linecap='round'
							stroke-linejoin='round'/>
						<path d='M56.5 31.5V54.5H24.5V31.5' stroke='#FF1B67' stroke-width='3' stroke-miterlimit='10'
						      stroke-linecap='round' stroke-linejoin='round'/>
						<path d='M24.5 54.5L36.593 40.333' stroke='#FF1B67' stroke-width='3' stroke-miterlimit='10'
						      stroke-linecap='round' stroke-linejoin='round'/>
						<path d='M56.5 54.5L44.407 40.333' stroke='#FF1B67' stroke-width='3' stroke-miterlimit='10'
						      stroke-linecap='round' stroke-linejoin='round'/>
						<path
							d='M50.5 28.5C50.5 34.672 40.5 44.75 40.5 44.75C40.5 44.75 30.5 34.672 30.5 28.5C30.5 25.8478 31.5536 23.3043 33.4289 21.4289C35.3043 19.5536 37.8478 18.5 40.5 18.5C43.1522 18.5 45.6957 19.5536 47.5711 21.4289C49.4464 23.3043 50.5 25.8478 50.5 28.5V28.5Z'
							stroke='#FF1B67' stroke-width='3' stroke-miterlimit='10' stroke-linecap='round'
							stroke-linejoin='round'/>
						<path
							d='M40.5 30.5C41.6046 30.5 42.5 29.6046 42.5 28.5C42.5 27.3954 41.6046 26.5 40.5 26.5C39.3954 26.5 38.5 27.3954 38.5 28.5C38.5 29.6046 39.3954 30.5 40.5 30.5Z'
							fill='#FF1B67'/>
					</svg>

					<div class='gk-placeholder-summary'>
						<h3 class='gk-h3'><?php esc_html_e( 'Maps Layout', 'gk-gravityview' ); ?></h3>
						<div class="howto">
							<p><?php esc_html_e( 'Display entries in a Map View, where entries are displayed as “pins” on a map, like on Yelp.com. Also, add map widgets and fields to all GravityView layouts.', 'gk-gravityview' ); ?></p>
						</div>
					</div>
				</div>
				<button class="gk-placeholder-button button button-primary button-hero"><?php

					esc_html_e( 'Activate Now', 'gk-gravityview' );

					// TODO: Enable logic to check if the plugin is installed/activated.
					# esc_html_e( 'Buy Now', 'gk-gravityview' );
					#esc_html_e( 'Install & Activate', 'gk-gravityview' );

					?></button>
				<p><a class="gk-placeholder-learn-more" href="https://www.gravitykit.com/products/maps/" rel="external noopener noreferrer" target="_blank"><?php
					echo esc_html( sprintf( __( 'Learn more about %s…', 'gk-gravityview' ), __( 'Maps Layout', 'gk-gravityview' ) ) );
					?><span class="screen-reader-text"> <?php esc_html_e( 'This link opens in a new window.', 'gk-gravityview' );?></span></a></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Add hooks for when Maps is enabled.
	 *
	 * @since TODO
	 */
	private function add_hooks_maps_enabled() {
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
