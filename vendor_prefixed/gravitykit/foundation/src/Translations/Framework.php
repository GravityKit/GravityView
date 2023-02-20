<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\Translations;

use GravityKit\GravityView\Foundation\Core as FoundationCore;
use GravityKit\GravityView\Foundation\Helpers\Core as CoreHelpers;
use GravityKit\GravityView\Foundation\Logger\Framework as LoggerFramework;

class Framework {
	const ID = 'gk-translations';

	const WP_LANG_DIR = WP_LANG_DIR . '/plugins';

	/**
	 * Class instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Framework
	 */
	private static $_instance;

	/**
	 * Logger class instance.
	 *
	 * @since 1.0.0
	 *
	 * @var LoggerFramework
	 */
	private $_logger;

	/**
	 * @since 1.0.0
	 *
	 * @var array Text domain for which translations are fetched.
	 */
	private $_text_domains = [];

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
	 * Returns TranslationsPress updater instance.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text_domain Text domain.
	 *
	 * @return TranslationsPress_Updater
	 */
	public function get_T15s_updater( $text_domain ) {
		return TranslationsPress_Updater::get_instance( $text_domain );
	}

	/**
	 * Initializes Translations framework.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		if ( did_action( 'gk/foundation/translations/initialized' ) ) {
			return;
		}

		foreach ( FoundationCore::get_instance()->get_registered_plugins() as $plugin_file ) {
			$plugin_data = CoreHelpers::get_plugin_data( $plugin_file );

			if ( isset( $plugin_data['TextDomain'] ) ) {
				$this->_text_domains[] = $plugin_data['TextDomain'];
			}
		}

		if ( empty( $this->_text_domains ) ) {
			return;
		}

		add_action( 'update_option_WPLANG', [ $this, 'on_site_language_change' ], 10, 2 );
		add_action( 'gk/foundation/plugin_activated', [ $this, 'on_plugin_activation' ] );
		add_action( 'gk/foundation/plugin_deactivated', [ $this, 'on_plugin_deactivation' ] );

		$this->_logger = LoggerFramework::get_instance();

		/**
		 * @action `gk/foundation/translations/initialized` Fires when the class has finished initializing.
		 *
		 * @since  1.0.0
		 *
		 * @param $this
		 */
		do_action( 'gk/foundation/translations/initialized', $this );
	}

	/**
	 * Checks of user has permissions to install languages.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function can_install_languages() {
		/**
		 * @filter `gk/foundation/translations/permissions/can_install_languages` Sets permission to install languages.
		 *
		 * @since  1.0.0
		 *
		 * @param bool $can_install_languages Default: 'install_languages' capability.
		 */
		return apply_filters( 'gk/foundation/translations/permissions/can-install-languages', current_user_can( 'install_languages' ) );
	}

	/**
	 * Downloads and installs translations from TranslationsPress.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text_domain  Text domain.
	 * @param string $new_language The new site language, only set if user is updating their language settings.
	 *
	 * @return void
	 */
	public function install( $text_domain, $new_language ) {
		$current_user = wp_get_current_user();

		if ( ! $this->can_install_languages() ) {
			$this->_logger->addError(
				sprintf(
					'User "%s" does not have permissions to install languages.',
					$current_user->user_login
				)
			);
		}

		try {
			$T15s_updater = $this->get_T15s_updater( $text_domain );

			$T15s_updater->install( $new_language );

			$translations = $T15s_updater->get_installed_translations( true );

			if ( isset( $translations[ $new_language ] ) ) {
				$this->load_backend_translations( $text_domain, $new_language );
			}
		} catch ( \Exception $e ) {
			$this->_logger->addError( $e->getMessage() );
		}
	}

	/**
	 * Loads and sets frontend and backend translations.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text_domain Text domain.
	 * @param string $language    (optional) Language to load. Default is site locale.
	 *
	 * @return void
	 */
	public function load_all_translations( $text_domain, $language = '' ) {
		$this->load_backend_translations( $text_domain, $language );
		$this->load_frontend_translations( $text_domain, $language );
	}

	/**
	 * Loads and sets backend translations.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text_domain Text domain.
	 * @param string $language    (optional) Language to load. Default is site locale.
	 *
	 * @return void
	 */
	function load_backend_translations( $text_domain, $language = '' ) {
		if ( ! $language ) {
			$language = get_locale();
		}

		$mo_translations = $this->get_translation_filename( $text_domain, $language );

		if ( ! $mo_translations ) {
			$this->_logger->notice(
				sprintf(
					'No "%s" .mo translations found for "%s".',
					$text_domain,
					$language
				)
			);

			return;
		}

		load_textdomain( $text_domain, $mo_translations );
	}

	/**
	 * Loads and sets frontend translations.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text_domain          Text domain.
	 * @param string $language             (optional) Language to load. Default is site locale.
	 * @param string $frontend_text_domain (optional) Frontend text domain if different from the backend text domain (e.g., plugin uses 'foo', but JS uses 'bar' for the same translations).
	 *
	 * @return void
	 */
	function load_frontend_translations( $text_domain, $language = '', $frontend_text_domain = '' ) {
		if ( ! $language ) {
			$language = get_locale();
		}

		if ( $this->is_en_locale( $language ) ) {
			return;
		}

		$json_translations = $this->get_translation_filename( $text_domain, $language, 'json' );

		if ( ! $json_translations ) {
			$this->_logger->notice(
				sprintf(
					'No %s.json translations file found for "%s" text domain.',
					$text_domain ?: $frontend_text_domain,
					$language
				)
			);

			return;
		}

		$json_translations = file_get_contents( $json_translations );

		// Optionally override text domain if UI expects a different one.
		$text_domain = $frontend_text_domain ?: $text_domain;

		add_filter( 'gk/foundation/inline-scripts', function ( $scripts ) use ( $text_domain, $json_translations ) {
			$js = <<<JS
( function( domain, translations ) {
	var localeData = translations.locale_data[ domain ] || translations.locale_data.messages;
	localeData[""].domain = domain;
	wp.i18n.setLocaleData( localeData, domain );
} )( '${text_domain}', ${json_translations});
JS;

			$scripts[] = [
				'script'       => $js,
				'dependencies' => [ 'wp-i18n' ],
			];

			return $scripts;
		} );
	}

	/**
	 * Returns the translation filename for a given language.
	 *
	 * @param string $text_domain Text domain.
	 * @param string $language    Translation language (e.g. 'en_EN').
	 * @param string $extension   (optional) File extension. Default is 'mo'.
	 *
	 * @return string|null
	 */
	public static function get_translation_filename( $text_domain, $language, $extension = 'mo' ) {
		$filename = sprintf( '%s/%s-%s.%s', self::WP_LANG_DIR, $text_domain, $language, $extension );

		return ( file_exists( $filename ) ) ? $filename : null;
	}

	/**
	 * Installs or updates translations for all plugins when the site's language setting is changed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $from_language The language before the user changed their language setting.
	 * @param string $to_language   The new language after the user changed their language setting.
	 *
	 * @return void
	 */
	public function on_site_language_change( $from_language, $to_language ) {
		if ( empty( $to_language ) || ! $this->can_install_languages() ) {
			return;
		}

		foreach ( $this->_text_domains as $text_domain ) {
			$this->install( $text_domain, $to_language );
		}
	}

	/**
	 * Installs or updates translations for all plugins when a plugin is activated.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_file Plugin file.
	 *
	 * @return void
	 */
	public function on_plugin_activation( $plugin_file ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$plugin_data = get_plugin_data( $plugin_file );

		if ( ! isset( $plugin_data['TextDomain'] ) || $this->is_en_locale() || ! $this->can_install_languages() ) {
			return;
		}

		$this->install( $plugin_data['TextDomain'], get_locale() );
	}

	/**
	 * Deletes translations when the plugin is deactivated.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_file Plugin file.
	 *
	 * @return void
	 */
	public function on_plugin_deactivation( $plugin_file ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$plugin_data = get_plugin_data( $plugin_file );

		if ( ! isset( $plugin_data['TextDomain'] ) || $this->is_en_locale() || ! $this->can_install_languages() ) {
			return;
		}

		$files = glob( sprintf(
			'%s/%s-*',
			self::WP_LANG_DIR,
			$plugin_data['TextDomain']
		) );

		if ( empty( $files ) ) {
			return;
		}

		array_walk( $files, 'wp_delete_file' );
	}

	/**
	 * Checks whether the current locale is set to English language.
	 *
	 * @since 1.0.0
	 *
	 * @param string $locale (optional) Locale to check. Default is site locale.
	 *
	 * @return bool
	 */
	public function is_en_locale( $locale = '' ) {
		if ( ! $locale ) {
			$locale = get_locale();
		}

		// en_EN = en_US; en_GB and en_CA can have their own "translations" due to differences in spelling.
		return in_array( $locale, [ 'en_EN', 'en_US' ], true );
	}
}
