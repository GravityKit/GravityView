<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\Settings;

use Exception;
use GravityKit\GravityView\Foundation\Core as FoundationCore;
use GravityKit\GravityView\Foundation\Helpers\Arr;
use GravityKit\GravityView\Foundation\Helpers\Core as CoreHelpers;
use GravityKit\GravityView\Foundation\WP\AdminMenu;
use GravityKit\GravityView\Foundation\Translations\Framework as TranslationsFramework;
use GravityKit\GravityView\Foundation\Logger\Framework as LoggerFramework;

class Framework {
	const ID = 'gk_settings';

	const AJAX_ROUTER = 'settings';

	/**
	 * Class instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Framework
	 */
	private static $_instance;

	/**
	 * @since 1.0.0
	 *
	 * @var string Access capabilities.
	 */
	private $_capability = 'manage_options';

	/**
	 * @since 1.0.0
	 *
	 * @var SettingsValidator Settings validator instance.
	 */
	private $_validator;

	/**
	 * @since 1.0.0
	 *
	 * @var array Cached settings data.
	 */
	private $_settings_data = [];

	private function __construct() {
		/**
		 * @filter `gk/foundation/settings/capability` Modifies capability to access GravityKit Settings.
		 *
		 * @since  1.0.0
		 *
		 * @param string $capability Capability.
		 */
		$this->_capability = apply_filters( 'gk/foundation/settings/capability', $this->_capability );

		$this->_validator = new SettingsValidator();
	}

	/**
	 * Returns class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Framework
	 */
	public static function get_instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Initializes Settings framework.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		if ( did_action( 'gk/foundation/settings/initialized' ) || is_network_admin() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		add_filter( 'gk/foundation/ajax/' . self::AJAX_ROUTER . '/routes', [ $this, 'configure_ajax_routes' ] );

		$this->add_gk_submenu_item();

		/**
		 * @action `gk/foundation/settings/initialized` Fires when the class has finished initializing.
		 *
		 * @since  1.0.0
		 *
		 * @param $this
		 */
		do_action( 'gk/foundation/settings/initialized', $this );
	}

	/**
	 * Configures AJAX routes handled by this class.
	 *
	 * @since 1.0.0
	 *
	 * @see   FoundationCore::process_ajax_request()
	 *
	 * @param array $routes AJAX route to class method map.
	 *
	 * @return array
	 */
	public function configure_ajax_routes( array $routes ) {
		return array_merge( $routes, [
			'save_settings' => [ $this, 'save_ui_settings' ],
		] );
	}

	/**
	 * Gets settings for all GravityKit plugins.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $site_id (optional) Site ID for which to get settings. Default is null (i.e, current site ID).
	 *
	 * @return array
	 */
	public function get_all_settings( $site_id = null ) {
		$site_id = $site_id ?: get_current_blog_id();

		if ( ! isset( $this->_settings_data[ $site_id ] ) ) {
			$this->_settings_data[ $site_id ] = is_multisite() ? get_blog_option( $site_id, self::ID, [] ) : get_option( self::ID, [] );
		}

		if ( doing_action( 'gk/foundation/settings/data/plugins' ) ) {
			// Avoid possible infinite loop if this method is called from within the `gk/foundation/settings/data/plugins` filter.
			return $this->_settings_data[ $site_id ];
		}

		// Update cached settings data with default values for each plugin.
		$plugins_settings = $this->get_plugins_settings_data();

		foreach ( $plugins_settings as $plugin_id => $plugin_settings ) {
			if ( ! isset( $this->_settings_data[ $site_id ][ $plugin_id ] ) ) {
				$this->_settings_data[ $site_id ][ $plugin_id ] = $this->get_default_settings( $plugin_id );
			} else {
				$this->_settings_data[ $site_id ][ $plugin_id ] = wp_parse_args( $this->_settings_data[ $site_id ][ $plugin_id ], $this->get_default_settings( $plugin_id ) );
			}
		}

		return $this->_settings_data[ $site_id ];
	}

	/**
	 * Returns settings data object for all plugins.
	 *
	 * @since 1.0.3
	 *
	 * @return array
	 */
	public function get_plugins_settings_data() {
		$plugins_settings_data = apply_filters( 'gk/foundation/settings/data/plugins', [] );

		if ( ! is_array( $plugins_settings_data ) ) {

			LoggerFramework::get_instance()->error( 'Invalid settings data. Expected array, got ' . print_r( $plugins_settings_data, true ) );

			return [];
		}

		foreach ( Arr::pluck( $plugins_settings_data, 'id' ) as $plugin_id ) {
			$filter = "gk/foundation/settings/${plugin_id}/settings-url";

			if ( has_filter( $filter ) ) {
				continue;
			}

			add_filter( $filter, function () use ( $plugin_id ) {
				return $this->get_plugin_settings_url( $plugin_id );
			} );
		}

		/**
		 * @filter `gk/foundation/settings/data/plugins` Modifies plugins' settings.
		 *
		 * @since  1.0.0
		 *
		 * @param array $plugins_data Plugins data.
		 */
		return array_filter( $plugins_settings_data );
	}

	/**
	 * Returns default settings for a plugin all plugins.
	 * Default settings are defined in the plugin's settings object under the `defaults` key.
	 *
	 * @since 1.0.3
	 *
	 * @param $plugin_id
	 *
	 * @return array|array[]
	 */
	public function get_default_settings( $plugin_id = null ) {
		$plugins_data = $this->get_plugins_settings_data();

		if ( empty( $plugins_data ) ) {
			return [];
		}

		if ( ! $plugin_id ) {
			return array_map( function ( $plugin_data ) {
				return Arr::get( $plugin_data, 'defaults', [] );
			}, $plugins_data );
		}

		return Arr::get( $plugins_data, "{$plugin_id}.defaults", [] );
	}

	/**
	 * Saves settings for all GravityKit plugins.
	 *
	 * @since 1.0.0
	 *
	 * @param array    $settings
	 * @param int|null $site_id (optional) Site ID for which to save settings. Default is null (i.e., current site ID).
	 *
	 * @return bool
	 */
	public function save_all_settings( array $settings, $site_id = null ) {
		$site_id = $site_id ?: get_current_blog_id();

		$this->_settings_data[ $site_id ] = $settings;

		return is_multisite() ? update_blog_option( $site_id, self::ID, $settings ) : update_option( self::ID, $settings );
	}

	/**
	 * Gets a single setting for a GravityKit plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param string           $plugin              Plugin ID as specified in the settings object.
	 * @param string           $plugin_setting_name Setting name as specified in the settings object.
	 * @param null|array|mixed $default             (optional) Default value to return if the setting is not found. Default is null.
	 * @param int|null         $site_id             (optional) Site ID for which to get settings. Default is null (i.e., current site ID).     *
	 *
	 * @return mixed|null
	 */
	public function get_plugin_setting( $plugin, $plugin_setting_name, $default = null, $site_id = null ) {
		$site_id = $site_id ?: get_current_blog_id();

		$plugin_settings = $this->get_plugin_settings( $plugin, $site_id );

		if ( array_key_exists( $plugin_setting_name, $plugin_settings ) ) {
			return $plugin_settings[ $plugin_setting_name ];
		}


		if ( is_array( $default ) && array_key_exists( $plugin_setting_name, $default ) ) {
			return $default[ $plugin_setting_name ];
		}

		return $default;
	}

	/**
	 * Saves a single setting for a GravityKit plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $plugin
	 * @param string   $plugin_setting_name
	 * @param mixed    $plugin_setting_value
	 * @param int|null $site_id (optional) Site ID for which to save settings. Default is null (i.e., current site ID).
	 *
	 * @return bool
	 */
	public function save_plugin_setting( $plugin, $plugin_setting_name, $plugin_setting_value, $site_id = null ) {
		$site_id = $site_id ?: get_current_blog_id();

		$plugin_settings = $this->get_plugin_settings( $plugin, $site_id );

		$plugin_settings[ $plugin_setting_name ] = $plugin_setting_value;

		return $this->save_plugin_settings( $plugin, $plugin_settings, $site_id );
	}

	/**
	 * Get all settings for a GravityKit plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $plugin
	 * @param int|null $site_id (optional) Site ID for which to get settings. Default is null (i.e., current site ID).
	 *
	 * @return array
	 */
	public function get_plugin_settings( $plugin, $site_id = null ) {
		$site_id = $site_id ?: get_current_blog_id();

		$settings = $this->get_all_settings( $site_id );

		return ! empty( $settings[ $plugin ] ) ? $settings[ $plugin ] : [];
	}

	/**
	 * Saves all settings for a GravityKit plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $plugin
	 * @param array    $plugin_settings
	 * @param int|null $site_id (optional) Site ID for which to save settings. Default is null (i.e, current site ID).
	 *
	 * @return bool
	 */
	public function save_plugin_settings( $plugin, array $plugin_settings, $site_id = null ) {
		$site_id = $site_id ?: get_current_blog_id();

		$settings_data = $this->get_all_settings( $site_id );

		$settings_data[ $plugin ] = ! empty( $settings_data[ $plugin ] ) ? $settings_data[ $plugin ] : [];

		$settings_data[ $plugin ] = array_merge( $settings_data[ $plugin ], $plugin_settings );

		/**
		 * @filter `gk/foundation/settings/{plugin}/save/before` Modifies plugin settings object before saving.
		 *
		 * @since  1.0.0
		 *
		 * @param array $settings Plugin settings.
		 */
		$settings_data[ $plugin ] = apply_filters( "gk/foundation/settings/${plugin}/save/before", $settings_data[ $plugin ] );

		return $this->save_all_settings( $settings_data, $site_id );
	}

	/**
	 * Adds Settings submenu to the GravityKit top-level admin menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_gk_submenu_item() {
		$page_title = $menu_title = esc_html__( 'Settings', 'gk-gravityview' );

		AdminMenu::add_submenu_item( [
			'page_title' => $page_title,
			'menu_title' => $menu_title,
			'capability' => $this->_capability,
			'id'         => self::ID,
			'callback'   => '__return_false', // Content will be injected into #wpbody by gk-setting.js (see /UI/Settings/src/main-prod.js)
			'order'      => 2,
		], 'top' );
	}

	/**
	 * Returns link to the plugin settings page.
	 *
	 * @since 1.0.3
	 *
	 * @param string $plugin_id
	 *
	 * @return string
	 */
	public function get_plugin_settings_url( $plugin_id ) {
		return add_query_arg( [
			'page' => self::ID,
			'p'    => $plugin_id,
		], admin_url( 'admin.php' ) );
	}

	/**
	 * Checks if the current page is a Settings page.
	 *
	 * @since 1.0.9
	 *
	 * @return bool
	 */
	public function is_settings_page() {
		return Arr::get( $_REQUEST, 'page' ) === self::ID;
	}

	/**
	 * Enqueues UI assets.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$plugins_data = $this->get_plugins_settings_data();

		ksort( $plugins_data );

		if ( empty( $plugins_data ) ) {
			// Remove the Settings menu items when there are no settings to display.
			AdminMenu::remove_submenu_item( self::ID );

			LoggerFramework::get_instance()->warning( 'There are no plugins with settings to display.' );

			return;
		}

		if ( !$this->is_settings_page() ) {
			return;
		}

		$script = 'settings.js';
		$style  = 'settings.css';

		if ( ! file_exists( CoreHelpers::get_assets_path( $script ) ) || ! file_exists( CoreHelpers::get_assets_path( $style ) ) ) {
			LoggerFramework::get_instance()->warning( 'UI assets not found.' );

			return;
		}

		/**
		 * @filter `gk/foundation/settings/data/config` Modifies global settings configuration.
		 *
		 * @since  1.0.0 Introduced but not (yet) used.
		 *
		 * @param array $config Configuration.
		 */
		$config = apply_filters( 'gk/foundation/settings/data/config', [] );

		$script_data = array_merge(
			[
				'config'  => $config,
				'plugins' => array_values( $plugins_data ),
			],
			FoundationCore::get_ajax_params( self::AJAX_ROUTER )
		);

		wp_enqueue_script(
			self::ID,
			CoreHelpers::get_assets_url( $script ),
			[ 'wp-hooks', 'wp-i18n' ],
			filemtime( CoreHelpers::get_assets_path( $script ) )
		);

		wp_localize_script(
			self::ID,
			'gkSettings',
			[ 'data' => $script_data ]
		);

		wp_enqueue_style(
			self::ID,
			CoreHelpers::get_assets_url( $style ),
			[],
			filemtime( CoreHelpers::get_assets_path( $style ) )
		);

		foreach ( $plugins_data as &$plugin_data ) {
			$styles  = Arr::get( $plugin_data, 'assets.styles', [] );
			$scripts = Arr::get( $plugin_data, 'assets.scripts', [] );

			if ( empty( $styles ) || empty ( $scripts ) ) {
				continue;
			}

			foreach ( $scripts as $script ) {
				if ( ! is_file( Arr::get( $script, 'file' ) ) ) {
					continue;
				}

				wp_enqueue_script(
					self::ID . '-' . md5( Arr::get( $script, 'file' ), '' ),
					plugin_dir_url( $script['file'] ) . basename( $script['file'] ),
					Arr::get( $script, 'deps', [] ),
					filemtime( Arr::get( $script, 'file' ) )
				);
			}
			foreach ( $styles as $style ) {
				if ( ! is_file( Arr::get( $style, 'file' ) ) ) {
					continue;
				}

				wp_enqueue_style(
					self::ID . '-' . md5( Arr::get( $style, 'file' ), '' ),
					plugin_dir_url( $style['file'] ) . basename( $style['file'] ),
					Arr::get( $style, 'deps', [] ),
					filemtime( Arr::get( $style, 'file' ) )
				);
			}

			unset( $plugin_data['assets'] );
		}

		// WP's forms.css interferes with our styles.
		wp_deregister_style( 'forms' );
		wp_register_style( 'forms', false );

		// Load UI translations using the text domain of the plugin that instantiated Foundation.
		$registered_plugins            = FoundationCore::get_instance()->get_registered_plugins();
		$foundation_source_plugin_data = CoreHelpers::get_plugin_data( $registered_plugins['foundation_source'] );
		TranslationsFramework::get_instance()->load_frontend_translations( $foundation_source_plugin_data['TextDomain'], '', 'gk-foundation' );
	}

	/**
	 * Saves UI settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings_data
	 *
	 * @throws Exception
	 *
	 * @return mixed|void Exit with JSON response or return response message.
	 */
	public function save_ui_settings( array $settings_data ) {
		$plugin_id   = ! empty( $settings_data['plugin'] ) ? $settings_data['plugin'] : null;
		$ui_settings = ! empty( $settings_data['settings'] ) ? $settings_data['settings'] : null;

		if ( ! $plugin_id || ! $ui_settings ) {
			throw new Exception( esc_html__( 'Invalid request.', 'gk-gravityview' ) );
		}

		try {
			$plugins_data = $this->get_plugins_settings_data();

			$plugin_data = null;

			foreach ( $plugins_data as $settings_data ) {
				if ( empty( $settings_data ) ) {
					continue;
				}

				if ( $plugin_id === $settings_data['id'] ) {
					$plugin_data = $settings_data;

					break;
				}
			}

			if ( empty( $plugin_data['sections'] ) ) {
				throw new ValidatorException( esc_html__( 'Plugin settings data not found.', 'gk-gravityview' ) );
			}

			foreach ( $ui_settings as $id => $value ) {
				$ui_settings['id'] = sanitize_text_field( $value );
			}

			$plugin_settings = []; // Flattened plugin settings data without sections/etc.; this is the source of truth against which the UI settings are being validated below.

			foreach ( $plugin_data['sections'] as $section ) {
				if ( empty( $section['settings'] ) ) {
					continue;
				}

				foreach ( $section['settings'] as $plugin_setting ) {
					// A setting can be explicitly excluded from being saved via a flag.
					if ( ! empty( $plugin_setting['html'] ) || ! empty( $plugin_setting['excludeFromSave'] ) ) {
						unset( $ui_settings[ $plugin_setting['id'] ] );

						continue;
					}

					// A setting can depend on the value of another setting (i.e., it is conditionally used when the value matches).
					if ( ! empty( $plugin_setting['requires'] ) && is_array( $plugin_setting['requires'] ) && ! $this->are_setting_requirements_met( $plugin_setting, $ui_settings ) ) {
						// If the requirements aren't met and the setting isn't among the ones being saved, exclude it.
						if ( ! isset( $ui_settings[ $plugin_setting['id'] ] ) ) {
							continue;
						}

						throw new Exception(
							strtr(
								esc_html_x( 'Setting [setting] has unmet requirements.', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
								[ '[setting]' => $plugin_setting['title'] ]
							)
						);
					}

					$plugin_settings[ $plugin_setting['id'] ] = $plugin_setting;
				}
			}

			// Remove UI settings that are not among the plugin settings.
			foreach ( $ui_settings as $id => $value ) {
				if ( ! isset( $plugin_settings[ $id ] ) ) {
					unset( $ui_settings[ $id ] );
				}
			}

			/**
			 * @filter `gk/foundation/settings/${plugin}/validation/before` Modifies plugin settings object before validation.
			 *
			 * @since  1.0.0
			 *
			 * @param array $ui_settings Settings.
			 */
			$ui_settings = apply_filters( "gk/foundation/settings/${plugin_id}/validation/before", $ui_settings );

			$this->_validator->validate( $plugin_id, $plugin_settings, $ui_settings );

			/**
			 * @filter `gk/foundation/settings/${plugin}/validation/after` Modifies plugin settings object after validation.
			 *
			 * @since  1.0.0
			 *
			 * @param array $ui_settings Settings.
			 */
			$ui_settings = apply_filters( "gk/foundation/settings/${plugin_id}/validation/after", $ui_settings );

			$this->save_plugin_settings( $plugin_id, $ui_settings );

			return esc_html__( 'Settings were successfully saved.', 'gk-gravityview' );
		} catch ( ValidatorException $e ) {
			throw new Exception( sprintf( '%s %s', esc_html__( 'Error saving settings.', 'gk-gravityview' ), $e->getMessage() ) );
		}
	}

	/**
	 * Determines if a setting's requirements are met based on the value(s) of other setting(s).
	 *
	 * @since 1.0.0
	 *
	 * @param array $plugin_setting Individual plugin setting as configured using the `gk/foundation/settings/data/plugins` filter.
	 * @param array $settings       Setting ID:value pairs.
	 *
	 * @return bool
	 */
	public function are_setting_requirements_met( $plugin_setting, $settings ) {
		$requirements = is_array( array_values( $plugin_setting['requires'] )[0] ) ? $plugin_setting['requires'] : [ $plugin_setting['requires'] ]; // Make requirements an array of arrays.

		foreach ( $requirements as $requirement ) {
			$required_value = $requirement['value'];
			$setting_value  = isset( $settings[ $requirement['id'] ] ) ? $settings[ $requirement['id'] ] : null;
			$operator       = $requirement['operator'];

			if ( ! Helpers::compare_values( $required_value, $setting_value, $operator ) ) {
				return false;
			}
		}

		return true;
	}
}
