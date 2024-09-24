<?php

namespace GV;

use GravityKitFoundation;
use GravityKit\GravityView\Foundation\Settings\Framework as SettingsFramework;
use GravityKit\GravityView\Foundation\Core as FoundationCore;
use GravityKit\GravityView\Foundation\Helpers\Arr;

/**
 * Ex-GF Addon Settings class that's been stripped of GF functionality.
 * Serves as a wrapper for Foundation Settings while also providing old getter/setter methods for backward
 * compatibility.
 */
class Plugin_Settings {
	const SETTINGS_PLUGIN_ID = 'gravityview';

	/**
	 * Foundation Settings framework instance.
	 *
	 * @since 2.16
	 *
	 * @var SettingsFramework|GravityKitFoundation::settings
	 */
	private $_foundation_settings;

	/**
	 * Cached settings.
	 *
	 * @since 2.16.2
	 *
	 * @var array
	 */
	private $_plugin_settings = [];

	public function __construct() {
		// GravityKitFoundation may not yet be available when this class is instantiated, so let's temporarily use the Settings framework from Foundation that's included with GravityView and then possibly replace it with the latest version.
		$this->_foundation_settings = SettingsFramework::get_instance();

		add_action(
			'gk/foundation/initialized',
			function () {
				$this->_foundation_settings = GravityKitFoundation::settings();
			}
		);

		add_filter( 'gk/foundation/settings/data/plugins', [ $this, 'add_settings' ] );
	}

	/**
	 * Returns a setting.
	 *
	 * @param string $setting_name The setting key.
	 *
	 * @return mixed The setting or null
	 * @deprecated Use \GV\Plugin_Settings::get()
	 */
	public function get_app_setting( $setting_name ) {
		return $this->get( $setting_name );
	}

	/**
	 * Returns setting by its name.
	 *
	 * @param string $key     The setting key.
	 * @param string $default (optional) A default if not found.
	 *
	 * @return mixed The setting value.
	 */
	public function get( $key, $default = null ) {
		return Arr::get( $this->all(), $key, $default );
	}

	/**
	 * Returns setting by its name.
	 *
	 * @param string $key Option key to fetch
	 *
	 * @return mixed
	 * @deprecated Use gravityview()->plugin->settings->get()
	 */
	public static function getSetting( $key ) {
		if ( gravityview()->plugin->settings instanceof Plugin_Settings ) {
			return gravityview()->plugin->settings->get( $key );
		}
	}

	/**
	 * Returns all settings.
	 *
	 * @return array The settings.
	 * @deprecated Use \GV\Plugin_Settings::all() or \GV\Plugin_Settings::get()
	 */
	public function get_app_settings() {
		return $this->all();
	}

	/**
	 * Returns all settings.
	 *
	 * @return array The settings.
	 */
	public function all() {
		if ( ! empty( $this->_plugin_settings ) ) {
			return $this->_plugin_settings;
		}

		$this->_plugin_settings = $this->_foundation_settings->get_plugin_settings( self::SETTINGS_PLUGIN_ID );

		// Migrate legacy settings
		if ( empty( $this->_plugin_settings ) ) {
			$this->_plugin_settings = $this->migrate_legacy_settings();
		}

		$this->_plugin_settings = wp_parse_args( $this->_plugin_settings, $this->defaults() );

		return $this->_plugin_settings;
	}

	/**
	 * Migrates GravityView <2.16 settings to the new settings framework.
	 * Some of those settings are now part of the GravityKit general settings, and some are part of the GravityView
	 * plugin settings.
	 *
	 * @return array The settings.
	 */
	public function migrate_legacy_settings() {
		$option_name = 'gravityformsaddon_' . self::SETTINGS_PLUGIN_ID . '_app_settings';

		// Legacy check (@see https://github.com/gravityview/GravityView/blob/3719151f3752ebca56b5ec70bd4064effcb7094a/future/includes/class-gv-settings-addon.php#L939)
		$site_has_settings = ( ! is_multisite() ) || is_main_site() || ( ! gravityview()->plugin->is_network_activated() ) || ( is_network_admin() && gravityview()->plugin->is_network_activated() );

		if ( $site_has_settings ) {
			$legacy_settings = get_option( $option_name, [] );
		} else {
			$legacy_settings = get_blog_option( get_main_site_id(), $option_name );
		}

		if ( empty( $legacy_settings ) ) {
			return [];
		}

		// Migrate legacy GravityView settings that are still part of GravityView.
		$plugin_settings = [
			'rest_api' => (int) Arr::get( $legacy_settings, 'rest_api' ),
		];

		$this->_foundation_settings->save_plugin_settings( self::SETTINGS_PLUGIN_ID, $plugin_settings );

		// Migrate legacy GravityView settings that are now part of the GravityKit general settings.
		$gk_settings_id = class_exists( 'GravityKitFoundation' ) ? GravityKitFoundation::ID : FoundationCore::ID;

		// If there are no settings configured, this would typically return an array of default settings.
		// However, because GV\Plugin_Settings is used before Foundation initializes and configures default settings, this will return an empty array indicating that migration should proceed.
		$gk_settings = $this->_foundation_settings->get_plugin_settings( $gk_settings_id );

		if ( empty( $gk_settings ) ) {
			$gk_settings = [
				'support_email'    => Arr::get( $legacy_settings, 'support-email' ),
				'support_port'     => Arr::get( $legacy_settings, 'support_port' ),
				'powered_by'       => (int) Arr::get( $legacy_settings, 'powered_by' ),
				'affiliate_id'     => Arr::get( $legacy_settings, 'affiliate_id' ),
				'beta'             => (int) Arr::get( $legacy_settings, 'beta' ),
				'support_email'    => Arr::get( $legacy_settings, 'support-email' ),
				'support_port'     => (int) Arr::get( $legacy_settings, 'support_port' ),
				'no_conflict_mode' => (int) Arr::get( $legacy_settings, 'no-conflict-mode' ),
			];

			$this->_foundation_settings->save_plugin_settings( $gk_settings_id, $gk_settings );
		}

		return $plugin_settings;
	}

	/**
	 * Returns a GravityKit general setting.
	 *
	 * @param string     $setting Setting value.
	 * @param mixed|null $default (optional) A default value if setting is not set. Defaults to null.
	 *
	 * @return mixed
	 */
	public function get_gravitykit_setting( $setting, $default = null ) {
		$gk_settings_id = class_exists( 'GravityKitFoundation' ) ? GravityKitFoundation::ID : FoundationCore::ID;

		return $this->_foundation_settings->get_plugin_setting( $gk_settings_id, $setting, $default );
	}

	/**
	 * Returns default settings.
	 *
	 * @return array The defaults.
	 */
	public function defaults() {
		$defaults = [
			'rest_api'                => 0,
			'public_entry_moderation' => 0,
			'caching'                 => 1,
			'caching_entries'         => DAY_IN_SECONDS,
		];

		/**
		 * Filter default global settings.
		 *
		 * @param  [in,out] array The defaults.
		 */
		return apply_filters( 'gravityview/settings/defaults', $defaults );
	}

	/**
	 * Adds GravityView settings to Foundation.
	 *
	 * @since 2.16
	 *
	 * @param array $plugins_data Plugins data
	 *
	 * @return array $plugins_data
	 */
	public function add_settings( $plugins_data ) {
		/**
		 * Override whether to show GravityView settings.
		 *
		 * @since 1.7.6
		 *
		 * @param bool $show_settings Default: true
		 */
		$show_settings = apply_filters( 'gravityview/show-settings-menu', true );

		if ( ! $show_settings ) {
			return $plugins_data;
		}

		$default_settings = $this->defaults();

		$cache_filters_in_use = [];

		if ( has_filter( 'gravityview_use_cache' ) ) {
			$cache_filters_in_use[] = 'gravityview_use_cache';
		}

		if ( has_filter( 'gravityview_cache_time_entries' ) ) {
			$cache_filters_in_use[] = 'gravityview_cache_time_entries';
		}

		$cache_settings = [];

		if ( ! empty( $cache_filters_in_use ) ) {
			$notice = 1 === count( $cache_filters_in_use )
				? esc_html_x( 'The [filter] active filter could be overriding cache settings.', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' )
				: esc_html_x( 'The following active filters could be overriding cache settings: [filters].', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' );

			$notice = strtr(
				$notice,
				[
					'[filter]'  => '<code>' . implode( '</code>, <code>', $cache_filters_in_use ) . '</code>',
					'[filters]' => '<code>' . implode( '</code>, <code>', $cache_filters_in_use ) . '</code>',
				]
			);

			$notice = <<<HTML
<div class="bg-yellow-50 p-4 rounded-md">
	<div class="flex">
		<div class="flex-shrink-0">
			<svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
				<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
			</svg>
		</div>
		<div class="ml-3">
			<p class="text-sm">
				{$notice}
			</p>
		</div>
	</div>
</div>
HTML;

			$cache_settings[] = [
				'id'              => 'caching_filters_notice',
				'html'            => $notice,
				'markdown'        => false,
				'excludeFromSave' => true,
			];
		}

		$cache_settings = array_merge( $cache_settings, [
				[
					'id'          => 'caching',
					'type'        => 'checkbox',
					'title'       => esc_html__( 'Enable Caching', 'gk-gravityview' ),
					'description' => strtr(
						esc_html_x( '[url]Enabling caching[/url] improves performance by reducing the number of queries during page loads. When enabled, you can also specify cache duration for entries.', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
						[
							'[url]'  => '<a class="underline" href="https://docs.gravitykit.com/article/58-about-gravityview-caching" rel="noopener noreferrer" target="_blank">',
							'[/url]' => '<span class="screen-reader-text"> ' . esc_html__( '(This link opens in a new window.)', 'gk-gravityview' ) . '</span></a>',
						]
					),
					'value'       => $this->get( 'caching', $default_settings['caching'] ),
				],
				[
					'id'          => 'caching_entries',
					'type'        => 'number',
					'requires'    => [
						'id'       => 'caching',
						'operator' => '==',
						'value'    => 1,
					],
					'validation'  => [
						[
							'rule'    => 'min:1',
							'message' => esc_html__( 'The cache duration must be at least 1 second.', 'gk-gravityview' ),
						],
					],
					'title'       => esc_html__( 'Entry Cache Duration', 'gk-gravityview' ),
					'description' => esc_html__( 'Specify the duration in seconds that entry data should remain cached before being refreshed. A shorter duration ensures more up-to-date data, while a longer duration improves performance.', 'gk-gravityview' ),
					'value'       => $this->get( 'caching_entries', $default_settings['caching_entries'] ),
				],
			]
		);

		$settings = [
			'id'       => self::SETTINGS_PLUGIN_ID,
			'title'    => 'GravityView',
			'defaults' => $default_settings,
			'assets'   => [
				'scripts' => [
					[ 'file' => dirname( GRAVITYVIEW_FILE ) . '/assets/js/foundation-settings.js'],
				],
			],
			'icon'     => 'data:image/svg+xml,%3Csvg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg"%3E%3Crect width="80" height="80" rx="8" fill="%23FF1B67"/%3E%3Cg clip-path="url(%23clip0_16_27)"%3E%3Cpath fill-rule="evenodd" clip-rule="evenodd" d="M58.4105 54.6666H52.8824V56.4999C52.8824 59.5375 50.3902 61.9999 47.3544 61.9999H32.6134C29.5375 61.9999 27.0853 59.5375 27.0853 56.4999V29H21.5572C20.5144 29 19.7148 29.8207 19.7148 30.8333V49.1666C19.7148 50.1792 20.5144 51 21.5572 51H22.4788C22.9629 51 23.3997 51.4104 23.3997 51.9167V53.75C23.3997 54.2562 22.9629 54.6666 22.4788 54.6666H21.5572C18.4786 54.6666 16.0292 52.2042 16.0292 49.1666V30.8333C16.0292 27.7957 18.4786 25.3333 21.5572 25.3333H27.0853V23.5C27.0853 20.4624 29.5375 18 32.6134 18H47.3544C50.3902 18 52.8824 20.4624 52.8824 23.5V51H58.4105C59.4132 51 60.2529 50.1792 60.2529 49.1666V30.8333C60.2529 29.8207 59.4132 29 58.4105 29H57.4889C56.9647 29 56.5673 28.5896 56.5673 28.0833V26.2499C56.5673 25.7437 56.9647 25.3333 57.4889 25.3333H58.4105C61.449 25.3333 63.9378 27.7957 63.9378 30.8333V49.1666C63.9378 52.2042 61.449 54.6666 58.4105 54.6666ZM49.1968 23.5C49.1968 22.4874 48.3544 21.6667 47.3544 21.6667H32.6134C31.5733 21.6667 30.7709 22.4874 30.7709 23.5V56.4999C30.7709 57.5125 31.5733 58.3333 32.6134 58.3333H47.3544C48.3544 58.3333 49.1968 57.5125 49.1968 56.4999V23.5Z" fill="white"/%3E%3C/g%3E%3Cdefs%3E%3CclipPath id="clip0_16_27"%3E%3Crect width="48" height="44" fill="white" transform="translate(16 18)"/%3E%3C/clipPath%3E%3C/defs%3E%3C/svg%3E%0A',
			'sections' => [
				[
					'title'    => esc_html__( 'General', 'gk-gravityview' ),
					'settings' => [
						[
							'id'          => 'rest_api',
							'type'        => 'checkbox',
							'title'       => esc_html__( 'REST API', 'gk-gravityview' ),
							'description' => esc_html__( 'Enable View and Entry access via the REST API? Regular per-View restrictions apply (private, password protected, etc.).', 'gk-gravityview' ) . ' ' . esc_html__( 'If you are unsure, disable this setting.', 'gk-gravityview' ),
							'value'       => $this->get( 'rest_api', $default_settings['rest_api'] ),
						],
					],
				],
				[
					'title'    => esc_html__( 'Caching', 'gk-gravityview' ),
					'settings' => $cache_settings,
				],
				[
					'title'    => esc_html__( 'Permissions', 'gk-gravityview' ),
					'settings' => [
						[
							'id'          => 'public_entry_moderation',
							'type'        => 'checkbox',
							'title'       => esc_html__( 'Enable Public Entry Moderation', 'gk-gravityview' ),
							'description' => strtr(
							// translators: Do not translate the words inside the {} curly brackets; they are replaced.
								__( 'If enabled, adding {public} to {link}entry moderation merge tags{/link} will allow logged-out users to approve or reject entries. If disabled, all entry moderation actions require the user to be logged-in and have the ability to edit the entry.', 'gk-gravityview' ),
								[
									'{public}' => '<code style="font-size: .9em">:public</code>',
									'{link}'   => '<a href="https://docs.gravitykit.com/article/904-entry-moderation-merge-tags" target="_blank" rel="noopener noreferrer">',
									'{/link}'  => '<span class="screen-reader-text"> ' . esc_html__( '(This link opens in a new window.)', 'gk-gravityview' ) . '</span></a>',
								]
							),
							'value'       => $this->get( 'public_entry_moderation', $default_settings['public_entry_moderation'] ),
						],
					],
				],
			],
		];

		return array_merge( (array) $plugins_data, [ self::SETTINGS_PLUGIN_ID => $settings ] );
	}

	/**
	 * Updates app settings with the provided settings.
	 *
	 * Same as the GFAddon, except it returns the value from update_option()
	 *
	 * @param array $settings App settings to be saved
	 *
	 * @return boolean False if value was not updated and true if value was updated.
	 * @deprecated Use \GV\Settings::set() or \GV\Settings::update()
	 */
	public function update_app_settings( $settings ) {
		return $this->update( $settings );
	}

	/**
	 * Sets a subset of settings.
	 *
	 * @param array|string $settings An array of settings to update, or string (key) and $value to update one setting.
	 * @param mixed        $value    A value if $settings is string (key). Default: null.
	 */
	public function set( $settings, $value = null ) {
		if ( is_string( $settings ) ) {
			$settings = [ $settings => $value ];
		}

		$settings = wp_parse_args( $settings, $this->all() );

		return $this->_foundation_settings->save_plugin_settings( self::SETTINGS_PLUGIN_ID, $settings );
	}

	/**
	 * Updates settings.
	 *
	 * @param array $settings The settings array.
	 *
	 * @return boolean False if value was not updated and true if value was updated.
	 */
	public function update( $settings ) {
		$result = $this->_foundation_settings->save_plugin_settings( self::SETTINGS_PLUGIN_ID, $settings );

		if ( ! $result ) {
			return false;
		}

		$this->_plugin_settings = $this->_foundation_settings->get_plugin_settings( self::SETTINGS_PLUGIN_ID );

		return true;
	}

	/**
	 * Check for the `gravityview_edit_settings` capability before saving plugin settings.
	 * Gravity Forms says you're able to edit if you're able to view settings. GravityView allows two different
	 * permissions.
	 *
	 * @since 1.15
	 * @return void
	 */
	public function maybe_save_app_settings() {
		if ( ! $this->is_save_postback() ) {
			return;
		}

		if ( \GVCommon::has_cap( 'gravityview_edit_settings' ) ) {
			return;
		}

		$_POST = []; // If you don't reset the $_POST array, it *looks* like the settings were changed, but they weren't
		\GFCommon::add_error_message( __( 'You don\'t have the ability to edit plugin settings.', 'gk-gravityview' ) );
	}

	/**
	 * @TODO Reimplement elsewhere. Keeping the strings from the form here, for now, so that translations are not lost.
	 * @internal
	 */
	private function _uninstall_form_strings() {
		__( 'Uninstall GravityView', 'gk-gravityview' );
		__( 'There was an error sharing your feedback. Sorry! Please email us at support@gravitykit.com', 'gk-gravityview' );
		__( 'Please share your thoughts about GravityView', 'gk-gravityview' );
		__( 'Please follow up with me about my feedback', 'gk-gravityview' );
		__( 'How likely are you to recommend GravityView?', 'gk-gravityview' );
		__( 'Send Us Your Feedback', 'gk-gravityview' );
		__( 'Thank you for using GravityView!', 'gk-gravityview' );
		__( 'Your feedback helps us improve GravityView. If you have any questions or comments, email us: support@gravitykit.com', 'gk-gravityview' );
		__( 'If you delete then re-install GravityView, it will be like installing GravityView for the first time.', 'gk-gravityview' );
		__( 'Delete all Views, GravityView entry approval status, GravityView-generated entry notes (including approval and entry creator changes), and GravityView plugin settings.', 'gk-gravityview' );
		__( 'I am going to continue using GravityView', 'gk-gravityview' );
		__( 'I no longer need GravityView', 'gk-gravityview' );
		__( 'The plugin doesn\'t work', 'gk-gravityview' );
		__( 'I found a better plugin', 'gk-gravityview' );
		__( 'What plugin you are using, and why?', 'gk-gravityview' );
		__( 'Other', 'gk-gravityview' );
		_x( '%1$s ("Not at all likely") to %2$s ("Extremely likely")', 'A scale from 0 (bad) to 10 (good)', 'gk-gravityview' );
	}
}
