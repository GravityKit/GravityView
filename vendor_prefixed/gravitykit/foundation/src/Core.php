<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation;

use GravityKit\GravityView\Foundation\Integrations\GravityForms;
use GravityKit\GravityView\Foundation\Integrations\HelpScout;
use GravityKit\GravityView\Foundation\Integrations\TrustedLogin;
use GravityKit\GravityView\Foundation\WP\AdminMenu;
use GravityKit\GravityView\Foundation\Logger\Framework as LoggerFramework;
use GravityKit\GravityView\Foundation\WP\PluginActivationHandler;
use GravityKit\GravityView\Foundation\Settings\Framework as SettingsFramework;
use GravityKit\GravityView\Foundation\Licenses\Framework as LicensesFramework;
use GravityKit\GravityView\Foundation\Translations\Framework as TranslationsFramework;
use GravityKit\GravityView\Foundation\Encryption\Encryption;
use GravityKit\GravityView\Foundation\Helpers\Core as CoreHelpers;
use GravityKit\GravityView\Foundation\Helpers\Arr;
use Exception;

class Core {
	const VERSION = '1.0.9';

	const ID = 'gk_foundation';

	const WP_AJAX_ACTION = 'gk_foundation_do_ajax';

	const AJAX_ROUTER = 'core';

	const INIT_PRIORITY = 100;

	/**
	 * Class instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Core
	 */
	private static $_instance;

	/**
	 * Instance of plugin activation/deactivation handler class.
	 *
	 * @since 1.0.0
	 *
	 * @var PluginActivationHandler
	 */
	private $_plugin_activation_handler;

	/**
	 * Absolute paths to the plugin files that instantiated this class.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $_registered_plugins = [];

	/**
	 * Instances of various components that make up the Core functionality.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $_components = [];

	/**
	 * Random string generated once for the current request.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private static $_request_unique_string;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 *
	 * @return void
	 */
	private function __construct( $plugin_file ) {
		$this->_plugin_activation_handler = new PluginActivationHandler();

		$this->_plugin_activation_handler->register_hooks( $plugin_file );

		$this->_registered_plugins['foundation_source'] = $plugin_file;

		add_filter(
			'gk/foundation/get-instance',
			function ( $passed_instance ) use ( $plugin_file ) {
				if ( ! $passed_instance || ! defined( get_class( $passed_instance ) . '::VERSION' ) || ! is_callable( [ $passed_instance, 'get_registered_plugins' ] ) ) {
					return $this;
				}

				$instance_to_return = version_compare( $passed_instance::VERSION, self::VERSION, '<' ) ? $this : $passed_instance;

				/**
				 * Controls whether the Foundation standalone plugin instance should always be returned regardless of the version.
				 *
				 * @filter gk/foundation/force-standalone-foundation-instance
				 *
				 * @since  1.0.2
				 *
				 * @param bool $force_standalone_instance Default: true.
				 */
				$force_standalone_instance = apply_filters( 'gk/foundation/force-standalone-foundation-instance', true );

				if ( $force_standalone_instance ) {
					$plugin_data = CoreHelpers::get_plugin_data( $plugin_file );

					if ( 'gk-foundation' === Arr::get( $plugin_data, 'TextDomain' ) ) {
						$instance_to_return = $this;
					}
				}

				// We need to make sure that the returned instance contains a list of all registered plugins that may have come with another passed instance.
				if ( $instance_to_return === $this ) {
					// Reset the other instance's registered plugin keys so that there is only 1 "foundation source" plugin, which is that of the current instance.
					$registered_plugins = array_merge( $this->_registered_plugins, array_values( $passed_instance->get_registered_plugins() ) );
				} else {
					$registered_plugins = array_merge( array_values( $this->_registered_plugins ), $instance_to_return->get_registered_plugins() );
				}

				$instance_to_return->set_registered_plugins( $registered_plugins );

				return $instance_to_return;
			}
		);

		add_action(
			'plugins_loaded',
			function () {
				if ( class_exists( 'GravityKitFoundation' ) ) {
					return;
				}

				$gk_foundation = apply_filters( 'gk/foundation/get-instance', null );

				if ( ! $gk_foundation ) {
					return;
				}

				$gk_foundation->init();
			},
			self::INIT_PRIORITY
		);
	}

	/**
	 * Registers class instance.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 *
	 * @return void
	 */
	public static function register( $plugin_file ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $plugin_file );
		} elseif ( ! in_array( $plugin_file, self::$_instance->_registered_plugins ) ) {
			self::$_instance->_registered_plugins[] = $plugin_file;
		}
	}

	/**
	 * Returns class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Core
	 */
	public static function get_instance() {
		return self::$_instance;
	}

	/**
	 * Returns a list of plugins that have instantiated Foundation.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_registered_plugins() {
		return $this->_registered_plugins;
	}

	/**
	 * Sets a list of plugins that have instantiated Foundation.
	 *
	 * @since 1.0.0
	 *
	 * @param array $plugins Array of absolute paths to the plugin files that instantiated this class.
	 *
	 * @return void
	 */
	public function set_registered_plugins( $plugins ) {
		$this->_registered_plugins = array_unique( $plugins );
	}

	/**
	 * Initializes Foundation.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		if ( did_action( 'gk/foundation/initialized' ) ) {
			return;
		}

		add_action( 'wp_ajax_' . self::WP_AJAX_ACTION, [ $this, 'process_ajax_request' ] );

		$this->_components = [
			'settings'     => SettingsFramework::get_instance(),
			'licenses'     => LicensesFramework::get_instance(),
			'translations' => TranslationsFramework::get_instance(),
			'logger'       => LoggerFramework::get_instance(),
			'admin_menu'   => AdminMenu::get_instance(),
			'encryption'   => Encryption::get_instance(),
			'trustedlogin' => TrustedLogin::get_instance(),
			'helpscout'    => HelpScout::get_instance(),
			'gravityforms' => GravityForms::get_instance(),
		];

		foreach ( $this->_components as $component => $instance ) {
			if ( CoreHelpers::is_callable_class_method( [ $this->_components[ $component ], 'init' ] ) ) {
				$this->_components[ $component ]->init();
			}
		}

		self::$_request_unique_string = $this->encryption()->get_random_nonce();

		if ( is_admin() ) {
			$this->plugin_activation_handler()->fire_activation_hook();

			$this->configure_settings();

			add_action( 'admin_enqueue_scripts', [ $this, 'inline_scripts_and_styles' ], 20 );

			add_action( 'admin_footer', [ $this, 'display_foundation_information' ] );
		}

		class_alias( __CLASS__, 'GravityKitFoundation' );

		/**
		 * Fires when the class has finished initializing.
		 *
		 * @action gk/foundation/initialized
		 *
		 * @since  1.0.0
		 *
		 * @param $this
		 */
		do_action( 'gk/foundation/initialized', $this );
	}

	/**
	 * Configures general GravityKit settings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function configure_settings() {
		add_filter(
			'gk/foundation/settings/data/plugins',
			function ( $plugins ) {
				$gk_settings = $this->settings()->get_plugin_settings( self::ID );

				// If multisite and not the main site, get default settings from the main site.
				// This allows site admins to configure the default settings for all subsites.
				// If no settings are found on the main site, default settings (set below) will be used.
				if ( ! is_main_site() && empty( $gk_settings ) ) {
					$gk_settings = $this->settings()->get_plugin_settings( self::ID, get_main_site_id() );
				}

				$default_settings = [
					'support_email'    => get_bloginfo( 'admin_email' ),
					'support_port'     => 1,
					'no_conflict_mode' => 1,
					'powered_by'       => 0,
					'beta'             => 0,
				];

				$general_settings = [];

				// TODO: This is a temporary notice. To be removed once GravityView is updated to v2.16.
				if ( defined( 'GV_PLUGIN_VERSION' ) && version_compare( GV_PLUGIN_VERSION, '2.16', '<' ) ) {
					$notice_1 = esc_html__( 'You are using a version of GravityView that does not yet support the new GravityKit settings framework.', 'gk-gravityview' );

					$notice_2 = strtr(
						esc_html_x( 'As such, the settings below will not apply to GravityView pages and you will have to continue using the [link]old settings[/link] until an updated version of the plugin is available. We apologize for the inconvenience as we work to update our products in a timely fashion.', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
						[
							'[link]'  => '<a href="' . admin_url( 'edit.php?post_type=gravityview&page=gravityview_settings' ) . '" class="text-blue-gv underline hover:text-gray-900 focus:text-gray-900 focus:no-underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">',
							'[/link]' => '</a>',
						]
					);

					$html = <<<HTML
<div class="bg-yellow-50 p-4 rounded-md">
	<div class="flex">
		<div class="flex-shrink-0">
			<svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
				<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
			</svg>
		</div>
		<div class="ml-3">
			<p class="text-sm">
				{$notice_1}
			</p>
			<br />
			<p class="text-sm">
				{$notice_2}
			</p>
		</div>
	</div>
</div>
HTML;

					$general_settings[] = [
						'id'   => 'legacy_settings_notice',
						'html' => $html,
					];
				}

				$general_settings = array_merge(
					$general_settings,
					[
						[
							'id'          => 'powered_by',
							'type'        => 'checkbox',
							'value'       => Arr::get( $gk_settings, 'powered_by', $default_settings['powered_by'] ),
							'title'       => esc_html__( 'Display "Powered By" Link', 'gk-gravityview' ),
							'description' => esc_html__( 'A "Powered by GravityKit" link will be displayed below some GravityKit products. Help us spread the word!', 'gk-gravityview' ),
						],
						[
							'id'          => 'affiliate_id',
							'type'        => 'number',
							'value'       => Arr::get( $gk_settings, 'affiliate_id' ),
							'title'       => esc_html__( 'Affiliate ID', 'gk-gravityview' ),
							'description' => strtr(
								esc_html_x( 'Earn money when people clicking your links become GravityKit customers. [link]Register as an affiliate[/link]!', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
								[
									'[link]'  => '<a href="https://www.gravitykit.com/account/affiliates/?utm_source=in-plugin&utm_medium=setting&utm_content=Register%20as%20an%20affiliate" class="underline" rel="external">',
									'[/link]' => '</a>',
								]
							),
							'requires'    => [
								'id'       => 'powered_by',
								'operator' => '=',
								'value'    => '1',
							],
						],
						[
							'id'          => 'beta',
							'type'        => 'checkbox',
							'value'       => Arr::get( $gk_settings, 'beta', $default_settings['beta'] ),
							'title'       => esc_html__( 'Become a Beta Tester', 'gk-gravityview' ),
							'description' => esc_html__( 'You will have early access to the latest GravityKit products. There may be bugs! If you encounter an issue, report it to help make GravityKit products better!', 'gk-gravityview' ),
						],
					]
				);

				$support_settings = [
					[
						'id'          => 'support_email',
						'type'        => 'text',
						'required'    => true,
						'value'       => Arr::get( $gk_settings, 'support_email', $default_settings['support_email'] ),
						'title'       => esc_html__( 'Support Email', 'gk-gravityview' ),
						'description' => esc_html__( 'In order to provide responses to your support requests, please provide your email address.', 'gk-gravityview' ),
						'validation'  => [
							[
								'rule'    => 'required',
								'message' => esc_html__( 'Support email is required', 'gk-gravityview' ),
							],
							[
								'rule'    => 'email',
								'message' => esc_html__( 'Please provide a valid email address', 'gk-gravityview' ),
							],
						],
					],
					[
						'id'          => 'support_port',
						'type'        => 'checkbox',
						'value'       => Arr::get( $gk_settings, 'support_port', $default_settings['support_port'] ),
						'title'       => esc_html__( 'Show Support Port', 'gk-gravityview' ),
						'description' => ( esc_html__( 'The Support Port provides quick access to how-to articles and tutorials. For administrators, it also makes it easy to contact support.', 'gk-gravityview' ) .
						                   strtr(
							                   esc_html_x( '[image]Support Port icon[/image]', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
							                   [
								                   '[image]'  => '<div style="margin-top: 1em; width: 7em;">![',
								                   '[/image]' => '](' . CoreHelpers::get_assets_url( 'support-port-icon.jpg' ) . ')</div>',
							                   ]
						                   ) ),
						'markdown'    => true,
					],
				];

				$technical_settings = [
					[
						'id'          => 'no_conflict_mode',
						'type'        => 'checkbox',
						'value'       => Arr::get( $gk_settings, 'no_conflict_mode', $default_settings['no_conflict_mode'] ),
						'title'       => esc_html__( 'Enable No-Conflict Mode', 'gk-gravityview' ),
						'description' => esc_html__( 'No-conflict mode prevents extraneous scripts and styles from being printed on GravityKit admin pages, reducing conflicts with other plugins and themes.', 'gk-gravityview' ),
					],
				];

				$all_settings = [
					self::ID => [
						'id'       => self::ID,
						'title'    => 'GravityKit',
						'defaults' => $default_settings,
						'icon'     => CoreHelpers::get_assets_url( 'gravitykit-icon.png' ),
						'sections' => [
							[
								'title'    => esc_html__( 'General', 'gk-gravityview' ),
								'settings' => $general_settings,
							],
							[
								'title'    => esc_html__( 'Support', 'gk-gravityview' ),
								'settings' => $support_settings,
							],
							[
								'title'    => esc_html__( 'Technical', 'gk-gravityview' ),
								'settings' => $technical_settings,
							],
						],
					],
				];

				/**
				 * Modifies the GravityKit general settings object.
				 *
				 * @filter gk/foundation/settings
				 *
				 * @since  1.0.0
				 *
				 * @param array $all_settings GravityKit general settings.
				 */
				$all_settings = apply_filters( 'gk/foundation/settings', $all_settings );

				return array_merge( $plugins, $all_settings );
			}
		);
	}

	/**
	 * Registers the GravityKit admin menu.
	 *
	 * @since 1.0.0
	 *
	 * @param string $router AJAX router that will be handling the request.
	 *
	 * @return array
	 */
	public static function get_ajax_params( $router ) {
		return [
			'_wpNonce'      => wp_create_nonce( self::ID ),
			'_wpAjaxUrl'    => admin_url( 'admin-ajax.php' ),
			'_wpAjaxAction' => self::WP_AJAX_ACTION,
			'ajaxRouter'    => $router ?: self::AJAX_ROUTER,
		];
	}

	/**
	 * Processes AJAX request and routes it to the appropriate endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception
	 *
	 * @return void|mixed Send JSON response if an AJAX request or return the response as is.
	 */
	public function process_ajax_request() {
		$request = wp_parse_args(
			$_POST, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			[
				'nonce'      => null,
				'payload'    => [],
				'ajaxRouter' => null,
				'ajaxRoute'  => null,
			]
		);

		list ( $nonce, $payload, $router, $route ) = array_values( $request );

		if ( ! is_array( $payload ) ) {
			$payload = json_decode( stripslashes_deep( $payload ), true );
		}

		$is_valid_nonce = wp_verify_nonce( $nonce, self::ID );

		if ( ! wp_doing_ajax() || ! $is_valid_nonce ) {
			wp_die( false, false, [ 'response' => 403 ] );
		}

		/**
		 * Modifies a list of AJAX routes that map to backend functions/class methods. $router groups routes to avoid a name collision (e.g., 'settings', 'licenses').
		 *
		 * @filter gk/foundation/ajax/{$router}/routes
		 *
		 * @since  1.0.0
		 *
		 * @param array[] $routes AJAX route to function/class method map.
		 */
		$ajax_route_to_class_method_map = apply_filters( "gk/foundation/ajax/{$router}/routes", [] );

		$route_callback = Arr::get( $ajax_route_to_class_method_map, $route );

		if ( ! CoreHelpers::is_callable_function( $route_callback ) && ! CoreHelpers::is_callable_class_method( $route_callback ) ) {
			wp_die( false, false, [ 'response' => 404 ] );
		}

		try {
			/**
			 * Modifies AJAX payload before the route is processed.
			 *
			 * @filter gk/foundation/ajax/payload
			 *
			 * @since  1.0.3
			 *
			 * @param array  $payload
			 * @param string $router
			 * @param string $route
			 */
			$payload = apply_filters( 'gk/foundation/ajax/payload', $payload, $router, $route );

			$result = call_user_func( $route_callback, $payload );
		} catch ( Exception $e ) {
			$result = new Exception( $e->getMessage() );
		}

		/**
		 * Modifies AJAX response after the route is processed.
		 *
		 * @filter gk/foundation/ajax/result
		 *
		 * @since  1.0.3
		 *
		 * @param mixed|Exception $result
		 * @param string          $router
		 * @param string          $route
		 * @param array           $payload
		 */
		$result = apply_filters( 'gk/foundation/ajax/result', $result, $router, $route, $payload );

		return CoreHelpers::process_return( $result );
	}

	/**
	 * Inlines scripts/styles.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function inline_scripts_and_styles() {
		/**
		 * Modifies scripts inlined by Foundation.
		 *
		 * @filter gk/foundation/inline-scripts
		 *
		 * @since  1.0.0
		 *
		 * @param array $inline_scripts Scripts inlined by Foundation.
		 */
		$inline_scripts = apply_filters( 'gk/foundation/inline-scripts', [] );

		if ( ! empty( $inline_scripts ) ) {
			$dependencies = [];
			$scripts      = [];

			foreach ( $inline_scripts as $script_data ) {
				if ( isset( $script_data['dependencies'] ) ) {
					$dependencies = array_merge( $dependencies, $script_data['dependencies'] );
				}

				if ( isset( $script_data['script'] ) ) {
					$scripts[] = $script_data['script'];
				}
			}

			wp_register_script( self::ID, false, $dependencies ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter,WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_enqueue_script( self::ID );
			wp_add_inline_script( self::ID, implode( ' ', $scripts ) );
		}

		/**
		 * Modifies styles inlined by Foundation.
		 *
		 * @filter gk/foundation/inline-styles
		 *
		 * @since  1.0.0
		 *
		 * @param array $inline_styles Styles inlined by Foundation.
		 */
		$inline_styles = apply_filters( 'gk/foundation/inline-styles', [] );

		if ( ! empty( $inline_styles ) ) {
			$dependencies = [];
			$styles       = [];

			foreach ( $inline_styles as $style_data ) {
				if ( isset( $style_data['dependencies'] ) ) {
					$dependencies = array_merge( $dependencies, $style_data['dependencies'] );
				}

				if ( isset( $style_data['style'] ) ) {
					$styles[] = $style_data['style'];
				}
			}

			wp_register_style( self::ID, false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_enqueue_style( self::ID );
			wp_add_inline_style( self::ID, implode( ' ', $styles ) );
		}
	}

	/**
	 * Magic method to get private class instances.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name      Component/class name.
	 * @param array  $arguments Optional and not used.
	 *
	 * @return mixed
	 */
	public function __call( $name, array $arguments = [] ) {
		if ( 'plugin_activation_handler' === $name ) {
			return $this->_plugin_activation_handler;
		}

		if ( 'helpers' === $name ) {
			return (object) [
				'core'  => new CoreHelpers(),
				'array' => new Arr(),
			];
		}

		if ( ! isset( $this->_components[ $name ] ) ) {
			return;
		}

		switch ( $name ) {
			case 'logger':
				$logger_name  = isset( $arguments[0] ) ? $arguments[0] : null;
				$logger_title = isset( $arguments[1] ) ? $arguments[1] : null;

				return call_user_func_array( [ $this->_components[ $name ], 'get_instance' ], [ $logger_name, $logger_title ] );
			default:
				return $this->_components[ $name ];
		}
	}

	/**
	 * Magic method to get private class instances as static methods.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name      Component/class name.
	 * @param array  $arguments Optional and not used.
	 *
	 * @return mixed
	 */
	public static function __callStatic( $name, array $arguments = [] ) {
		$instance = apply_filters( 'gk/foundation/get-instance', null );

		return call_user_func_array( [ $instance, $name ], $arguments );
	}

	/**
	 * Returns a unique value that was generated for this request.
	 * This value can be used, among other purposes, as a random initialization vector for encryption operations performed during the request (e.g., encrypting a license key in various places will result in the same encrypted value).
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public static function get_request_unique_string() {
		return self::$_request_unique_string;
	}

	/**
	 * Outputs an HTML comment with the Foundation version and the plugin that loaded it.
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 */
	public function display_foundation_information() {
		/**
		 * Controls the display of HTML comment with Foundation information.
		 *
		 * @filter gk/foundation/display_foundation_information
		 *
		 * @since  1.0.1
		 *
		 * @param bool $display_foundation_information Whether to display the information.
		 */
		$display_foundation_information = apply_filters( 'gk/foundation/display_foundation_information', true );

		if ( ! $display_foundation_information ) {
			return;
		}

		$foundation_version = self::VERSION;
		$foundation_source  = Arr::get( CoreHelpers::get_plugin_data( $this->_registered_plugins['foundation_source'] ), 'Name', '<unknown plugin>' );

		echo "<!-- GravityKit Foundation v{$foundation_version} (loaded by {$foundation_source}) -->"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
