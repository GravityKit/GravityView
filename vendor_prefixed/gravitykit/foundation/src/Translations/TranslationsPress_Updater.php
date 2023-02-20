<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\Translations;

use Exception;
use WP_Filesystem;
use GravityKit\GravityView\Foundation\ThirdParty\Gettext\Translation;
use GravityKit\GravityView\Foundation\ThirdParty\Gettext\Translations;
use GravityKit\GravityView\Foundation\Helpers\Arr;
use GravityKit\GravityView\Foundation\Logger\Framework as LoggerFramework;

/**
 * Allows downloading and installing translations from TranslationsPress.
 *
 * This is a modified version of the Gravity Forms' TranslationsPress_Updater class, which is based on the T15S library.
 *
 * @since 1.0.0
 *
 * @see   https://github.com/WP-Translations/t15s-registry
 * @see   gravityforms/includes/class-translationspress-updater.php
 */
class TranslationsPress_Updater {
	const T15S_TRANSIENT_KEY = 't15s-registry-gravitykit';

	const T15S_TRANSIENT_EXPIRY = 12 * HOUR_IN_SECONDS;

	const T15S_API_PACKAGES_URL = 'https://packages.translationspress.com/gravitykit/packages.json';

	const T15S_API_EXPORT_URL = 'https://translationspress.com/app/gravitykit/{plugin_slug}/{language_slug}/default/export-translations';

	/**
	 * The plugin slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $_slug = '';

	/**
	 * Translations storage path.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $_translations_path = WP_LANG_DIR . '/plugins/';

	/**
	 * Cached TranslationsPress data for all GravityKit plugins.
	 *
	 * @var null|object
	 */
	private $_all_translations;

	/**
	 * The current instances of this class keyed by plugin slugs.
	 *
	 * @var TranslationsPress_Updater[]
	 */
	private static $_instances = [];

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug              The plugin slug.
	 * @param string $translations_path Translations storage path.
	 *
	 */
	private function __construct( $slug, $translations_path = '' ) {
		$this->_slug = $slug;

		if ( $translations_path ) {
			$this->_translations_path = $translations_path;
		}

		add_action( 'upgrader_process_complete', [ $this, 'on_upgrader_process_complete' ], 10, 2 );

		add_filter( 'translations_api', [ $this, 'translations_api' ], 10, 3 );

		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'site_transient_update_plugins' ] );
	}

	/**
	 * Returns an instance of this class for the given slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug              The plugin slug.
	 * @param string $translations_path Translations storage path.
	 *
	 * @return TranslationsPress_Updater
	 */
	public static function get_instance( $slug, $translations_path = '' ) {
		if ( empty( self::$_instances[ $slug ] ) ) {
			self::$_instances[ $slug ] = new self( $slug, $translations_path );
		}

		return self::$_instances[ $slug ];
	}

	/**
	 * Short-circuits translations API requests for private projects.
	 *
	 * @since 1.0.0
	 *
	 * @param bool|array $result         The result object (default: false).
	 * @param string     $requested_type The type of translations being requested.
	 * @param object     $args           Translation API arguments.
	 *
	 * @throws Exception
	 *
	 * @return bool|array
	 */
	public function translations_api( $result, $requested_type, $args ) {
		if ( 'plugins' !== $requested_type || $this->_slug !== $args['slug'] ) {
			return $result;
		}

		return $this->get_plugin_translations();
	}

	/**
	 * Gets the TranslationsPress data for the current plugin.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception
	 *
	 * @return array
	 */
	public function get_plugin_translations() {
		$this->set_all_translations();

		return isset( $this->_all_translations->projects[ $this->_slug ] ) ? $this->_all_translations->projects[ $this->_slug ] : [];
	}

	/**
	 * Filters the translations transients to include the current plugin.
	 *
	 * @since 1.0.0
	 *
	 * @see   wp_get_translation_updates()
	 *
	 * @param mixed $value The transient value.
	 *
	 * @throws Exception
	 *
	 * @return object
	 */
	public function site_transient_update_plugins( $value ) {
		if ( ! $value ) {
			$value = new \stdClass();
		}

		if ( ! isset( $value->translations ) ) {
			$value->translations = [];
		}

		$translations = $this->get_plugin_translations();

		if ( empty( $translations['translations'] ) ) {
			return $value;
		}

		foreach ( $translations['translations'] as $translation ) {
			if ( ! $this->should_install( $translation ) ) {
				continue;
			}

			$translation['type'] = 'plugin';
			$translation['slug'] = $this->_slug;

			$value->translations[] = $translation;
		}

		return $value;
	}

	/**
	 * Refreshes the cached TranslationsPress data, if expired.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function refresh_all_translations() {
		static $done;

		if ( $done ) {
			return;
		}

		$this->_all_translations = null;

		$this->set_all_translations();

		$done = true;
	}

	/**
	 * Determines if the cached TranslationsPress data needs refreshing.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_transient_expired() {
		$cache_lifespan = self::T15S_TRANSIENT_EXPIRY;

		return ! isset( $this->_all_translations->_last_checked ) || ( time() - $this->_all_translations->_last_checked ) > $cache_lifespan;
	}

	/**
	 * Gets the translation data from the TranslationsPress API.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception
	 *
	 * @return array
	 */
	public function get_remote_translations_data() {
		$request = wp_remote_get( self::T15S_API_PACKAGES_URL, [
			'timeout' => 3
		] );

		if ( is_wp_error( $request ) ) {
			throw new Exception(
				$this->get_exception( 'Unable to reach TranslationsPress API: %s.', __METHOD__, $request->get_error_message() )
			);
		}

		if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
			throw new Exception(
				$this->get_exception( 'TranslationsPress API returned an invalid response: %s.', __METHOD__, $request['response']['message'] )
			);
		}

		try {
			$result = json_decode( wp_remote_retrieve_body( $request ), true );

			if ( ! is_array( $result ) ) {
				throw new Exception();
			}
		} catch ( Exception $e ) {
			throw new Exception(
				$this->get_exception( 'Could not decode the response received from TranslationsPress API: $s.', __METHOD__, wp_remote_retrieve_body( $request ) )
			);
		}

		return $result;
	}

	/**
	 * Caches the TranslationsPress data, if not already cached.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function set_all_translations() {
		if ( is_object( $this->_all_translations ) ) {
			return;
		}

		$this->_all_translations = get_site_transient( self::T15S_TRANSIENT_KEY );

		if ( is_object( $this->_all_translations ) && ! $this->is_transient_expired() ) {
			return;
		}

		$this->_all_translations                = new \stdClass();
		$this->_all_translations->projects      = $this->get_remote_translations_data();
		$this->_all_translations->_last_checked = time();

		set_site_transient( self::T15S_TRANSIENT_KEY, $this->_all_translations );
	}

	/**
	 * Downloads and installs the translations package for the specified plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug   The plugin slug.
	 * @param string $locale The locale when the site locale is changed or an empty string to install all the user available locales.
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function download_package( $slug, $locale = '' ) {
		self::get_instance( $slug )->install( $locale );
	}

	/**
	 * Installs translations for a given locale.
	 *
	 * @since 1.0.0
	 *
	 * @param string $locale Locale for which to install the translation.
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function install( $locale = '' ) {
		$translations = $this->get_plugin_translations();

		if ( empty( $translations['translations'] ) ) {
			throw new Exception(
				$this->get_exception( 'No translations found for %s.', __METHOD__, $this->_slug )
			);
		}

		foreach ( $translations['translations'] as $translation ) {
			if ( ! $this->should_install( $translation, $locale ) ) {
				continue;
			}

			$this->install_translation( $translation );

			if ( $locale ) {
				return;
			}
		}
	}

	/**
	 * Downloads and installs the given translation.
	 *
	 * @since 1.0.0
	 * @since 1.0.1 Download an extra file from the T15S API.
	 *
	 * @param array $translation The translation data.
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function install_translation( $translation ) {
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			require_once ABSPATH . '/wp-admin/includes/admin.php';

			if ( ! WP_Filesystem() ) {
				throw new Exception(
					$this->get_exception( 'Aborting translation package installation; unable to init WP_Filesystem.', __METHOD__ )
				);
			}
		}

		if ( ! $wp_filesystem->is_dir( $this->_translations_path ) ) {
			$wp_filesystem->mkdir( $this->_translations_path, FS_CHMOD_DIR );
		}

		$temp_package_file = download_url( $translation['package'] );

		if ( is_wp_error( $temp_package_file ) ) {
			throw new Exception(
				$this->get_exception( 'Error downloading translation package. Code: %s; Message %s.', __METHOD__, $temp_package_file->get_error_code(), $temp_package_file->get_error_message() )
			);
		}

		$_get_file_name = function ( $extension, $language ) {
			return sprintf(
				'%s/%s-%s.%s',
				untrailingslashit( $this->_translations_path ),
				$this->_slug,
				$language,
				$extension
			);
		};

		$zip_file = $_get_file_name( 'zip', $translation['language'] );

		$copy_result = $wp_filesystem->copy( $temp_package_file, $zip_file, true, FS_CHMOD_FILE );

		$wp_filesystem->delete( $temp_package_file );

		if ( ! $copy_result ) {
			throw new Exception(
				$this->get_exception( 'Unable to move translation package to %s.', __METHOD__, $this->_translations_path )
			);
		}

		$result = unzip_file( $zip_file, $this->_translations_path );

		$wp_filesystem->delete( $zip_file );

		if ( is_wp_error( $result ) ) {
			throw new Exception(
				$this->get_exception( 'Error extracting translation package. Code: %s; Message %s.', __METHOD__, $temp_package_file->get_error_code(), $temp_package_file->get_error_message() )
			);
		}

		// Follows is a workaround for T15S purging JS translations from the .po file included in the translation package.
		// This is typically what happens when WP CLI's `i18n make-json` command is used to generate JS translation files (.json).
		// The filenames contain a hash of the source JS file, which in many of our cases is not the actual file that we end up loading since we tend to bundle UI assets.
		// As a result, WP is unable to automatically load JS translations and what we do instead is manually create (and later load) a single .json file with all JS translations by extracting them from a .po file.
		// Since the .po file in the translation package is missing some JS translations, we need to download an unprocessed .po file that's provided by T15S via an API endpoint.
		$T15S_language_slug = $this->get_slug_from_locale( $translation['language'] );

		if ( ! $T15S_language_slug ) {
			LoggerFramework::get_instance()->warn( 'Unable to get the T15S language slug for ' . $translation['language'] . ' locale.' );

			return;
		}

		$temp_po_file = download_url( strtr(
			self::T15S_API_EXPORT_URL,
			[
				'{plugin_slug}'   => $this->_slug,
				'{language_slug}' => $T15S_language_slug
			]
		) );

		if ( is_wp_error( $temp_po_file ) ) {
			throw new Exception(
				$this->get_exception( 'Error downloading translation PO file. Code: %s; Message %s.', __METHOD__, $temp_package_file->get_error_code(), $temp_package_file->get_error_message() )
			);
		}

		$json_file = $_get_file_name( 'json', $translation['language'] );

		$this->convert_po_to_json( $temp_po_file, $json_file );

		$wp_filesystem->delete( $temp_po_file );
	}

	/**
	 * Converts the PO file to JSON file by extracting only JavaScript translations.
	 *
	 * @since 1.0.0
	 * @since 1.0.1 New $json_file parameter and Exception when the PO file cannot be read/JSON file cannot be saved.
	 *
	 * @param string $po_file   File with path to the PO file.
	 * @param string $json_file File with path where the JSON file will be saved.
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	function convert_po_to_json( $po_file, $json_file ) {
		if ( ! file_exists( $po_file ) ) {
			throw new Exception(
				$this->get_exception( 'PO file %s does not exist. Code: %s; Message %s.', __METHOD__, $po_file )
			);
		}

		$original_translations = Translations::fromPoFile( $po_file );

		$filtered_translations = [];

		foreach ( $original_translations as $original_translation ) {
			// Get translations only for files with .js (or .js.php) extensions and only where translated data exists.
			if ( strpos( json_encode( $original_translation->getReferences() ), '.js' ) === false || ! $original_translation->getTranslation() ) {
				continue;
			}

			// We need to re-create the translation without context or else it will be joined with the source string (see https://github.com/php-gettext/Gettext/blob/3f7bc5ef23302a9059e64934f3d59e454516bec0/src/Generators/Jed.php#L51).
			$filtered_translation = new Translation( '', $original_translation->getOriginal(), $original_translation->getPlural() );
			$filtered_translation->setTranslation( $original_translation->getTranslation() );

			$filtered_translations[] = $filtered_translation;
		}

		if ( empty( $filtered_translations ) ) {
			return;
		}

		$converted_translations = new Translations( $filtered_translations );

		foreach ( $original_translations->getHeaders() as $key => $header ) {
			$converted_translations->setHeader( $key, $header );
		}

		$converted_translations = [
			'translation-revision-date' => $converted_translations->getHeader( 'PO-Revision-Date' ),
			'generator'                 => 'GravityKit Translations',
			'locale_data'               => json_decode( $converted_translations->toJedString(), true )
		];

		if ( ! file_put_contents( $json_file, json_encode( $converted_translations ) ) ) {
			throw new Exception(
				$this->get_exception( 'Unable to save JSON file %s.', __METHOD__, $json_file )
			);
		}
	}

	/**
	 * Determines if a translation should be installed.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $translation The translation data.
	 * @param string $locale      The locale when the site locale is changed or an empty string to check all the user available locales.
	 *
	 * @return bool
	 */
	public function should_install( $translation, $locale = '' ) {
		if ( ( $locale !== $translation['language'] ) || ! in_array( $translation['language'], $this->get_available_languages(), true ) ) {
			return false;
		}

		if ( empty( $translation['updated'] ) ) {
			return true;
		}

		$installed = $this->get_installed_translations_data();

		if ( ! isset( $installed[ $translation['language'] ] ) ) {
			return true;
		}

		$local  = date_create( $installed[ $translation['language'] ]['PO-Revision-Date'] );
		$remote = date_create( $translation['updated'] );

		return $remote > $local;
	}

	/**
	 * Returns an array of locales the site has installed.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_available_languages() {
		static $languages = [];

		if ( empty( $languages ) ) {
			$languages = get_available_languages();
		}

		return $languages;
	}

	/**
	 * Returns header data from the installed translations for the current plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_installed_translations_data() {
		static $data = [];

		if ( isset( $data[ $this->_slug ] ) ) {
			return $data[ $this->_slug ];
		}

		$data[ $this->_slug ] = [];
		$translations         = $this->get_installed_translations( true );

		foreach ( $translations as $locale => $mo_file ) {
			$po_file = str_replace( '.mo', '.po', $mo_file );
			if ( ! file_exists( $po_file ) ) {
				continue;
			}

			$data[ $this->_slug ][ $locale ] = wp_get_pomo_file_data( $po_file );
		}

		return $data[ $this->_slug ];
	}

	/**
	 * Returns an array of locales or .mo translation files found in the translations folder.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $return_files Indicates if the object should be keyed by locale (e.g., 'en_EN').
	 *
	 * @return array
	 */
	public function get_installed_translations( $return_files = false ) {
		$files = glob( sprintf(
			'%s/%s-*.mo',
			$this->_translations_path,
			$this->_slug
		) );

		if ( empty( $files ) ) {
			return [];
		}

		$translations = [];

		foreach ( $files as $file ) {
			$translations[ str_replace( $this->_slug . '-', '', basename( $file, '.mo' ) ) ] = $file;
		}

		return $return_files ? $translations : array_keys( $translations );
	}

	/**
	 * Formats and returns an exception message.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message  Exception message with placeholders for sprintf() replacement.
	 * @param string $method   Method throwing the exception.
	 * @param mixed  $args,... Variable-length argument lists for sprintf() replacement.
	 *
	 * @return string
	 */
	public function get_exception( $message, $method, ...$args ) {
		$message = "%s(): ${message}";

		return sprintf(
			$message,
			$method,
			...$args
		);
	}

	/**
	 * Returns T15S slug for the language based on the WP locale.
	 * This is used to access the export URL (e.g., https://translationspress.com/app/gravitykit/gk-gravitycalendar/<slug>/default/export-translations/)
	 *
	 * @since 1.0.1
	 *
	 * @param string $locale WP language locale.
	 *
	 * @return string|null T15S slug.
	 */
	public function get_slug_from_locale( $locale ) {
		// Taken from GlotPress that powers T15S: https://github.com/GlotPress/GlotPress/blob/a4436a6169d9f6cba5bc0ed62abe31e4f3ef15b4/locales/locales.php
		$slug_to_locale_map = [
			'az'          => 'az',
			'azb'         => 'azb',
			'az_TR'       => 'az-tr',
			'ba'          => 'ba',
			'bal'         => 'bal',
			'bcc'         => 'bcc',
			'bel'         => 'bel',
			'bg_BG'       => 'bg',
			'bgn'         => 'bgn',
			'bho'         => 'bho',
			'bn_BD'       => 'bn',
			'bn_IN'       => 'bn-in',
			'bo'          => 'bo',
			'bre'         => 'br',
			'brx'         => 'brx',
			'bs_BA'       => 'bs',
			'ca'          => 'ca',
			'ca_valencia' => 'ca-valencia',
			'ceb'         => 'ceb',
			'ckb'         => 'ckb',
			'co'          => 'co',
			'cor'         => 'cor',
			'cs_CZ'       => 'cs',
			'cy'          => 'cy',
			'da_DK'       => 'da',
			'de_DE'       => 'de',
			'de_AT'       => 'de-at',
			'de_CH'       => 'de-ch',
			'dsb'         => 'dsb',
			'dv'          => 'dv',
			'dzo'         => 'dzo',
			'ewe'         => 'ee',
			'el'          => 'el',
			'art_xemoji'  => 'art-xemoji',
			'en_US'       => 'en',
			'en_AU'       => 'en-au',
			'en_CA'       => 'en-ca',
			'en_GB'       => 'en-gb',
			'en_NZ'       => 'en-nz',
			'en_ZA'       => 'en-za',
			'eo'          => 'eo',
			'es_ES'       => 'es',
			'es_AR'       => 'es-ar',
			'es_CL'       => 'es-cl',
			'es_CO'       => 'es-co',
			'es_CR'       => 'es-cr',
			'es_DO'       => 'es-do',
			'es_EC'       => 'es-ec',
			'es_GT'       => 'es-gt',
			'es_HN'       => 'es-hn',
			'es_MX'       => 'es-mx',
			'es_PE'       => 'es-pe',
			'es_PR'       => 'es-pr',
			'es_UY'       => 'es-uy',
			'es_VE'       => 'es-ve',
			'et'          => 'et',
			'eu'          => 'eu',
			'fa_IR'       => 'fa',
			'fa_AF'       => 'fa-af',
			'fuc'         => 'fuc',
			'fi'          => 'fi',
			'fo'          => 'fo',
			'fon'         => 'fon',
			'fr_FR'       => 'fr',
			'fr_BE'       => 'fr-be',
			'fr_CA'       => 'fr-ca',
			'frp'         => 'frp',
			'fur'         => 'fur',
			'fy'          => 'fy',
			'ga'          => 'ga',
			'gax'         => 'gax',
			'gd'          => 'gd',
			'gl_ES'       => 'gl',
			'gu'          => 'gu',
			'hat'         => 'hat',
			'hau'         => 'hau',
			'haw_US'      => 'haw',
			'haz'         => 'haz',
			'he_IL'       => 'he',
			'hi_IN'       => 'hi',
			'hr'          => 'hr',
			'hsb'         => 'hsb',
			'hu_HU'       => 'hu',
			'hy'          => 'hy',
			'ibo'         => 'ibo',
			'id_ID'       => 'id',
			'ido'         => 'ido',
			'is_IS'       => 'is',
			'it_IT'       => 'it',
			'ja'          => 'ja',
			'jv_ID'       => 'jv',
			'ka_GE'       => 'ka',
			'kaa'         => 'kaa',
			'kab'         => 'kab',
			'kal'         => 'kal',
			'kin'         => 'kin',
			'kk'          => 'kk',
			'km'          => 'km',
			'kmr'         => 'kmr',
			'kn'          => 'kn',
			'ko_KR'       => 'ko',
			'kir'         => 'kir',
			'lb_LU'       => 'lb',
			'li'          => 'li',
			'lij'         => 'lij',
			'lin'         => 'lin',
			'lmo'         => 'lmo',
			'lo'          => 'lo',
			'lt_LT'       => 'lt',
			'lug'         => 'lug',
			'lv'          => 'lv',
			'mai'         => 'mai',
			'me_ME'       => 'me',
			'mfe'         => 'mfe',
			'mg_MG'       => 'mg',
			'mk_MK'       => 'mk',
			'ml_IN'       => 'ml',
			'mlt'         => 'mlt',
			'mn'          => 'mn',
			'mr'          => 'mr',
			'mri'         => 'mri',
			'ms_MY'       => 'ms',
			'my_MM'       => 'mya',
			'ne_NP'       => 'ne',
			'nb_NO'       => 'nb',
			'nl_NL'       => 'nl',
			'nl_BE'       => 'nl-be',
			'nn_NO'       => 'nn',
			'nqo'         => 'nqo',
			'oci'         => 'oci',
			'ory'         => 'ory',
			'os'          => 'os',
			'pa_IN'       => 'pa',
			'pa_PK'       => 'pa-pk',
			'pap_CW'      => 'pap-cw',
			'pap_AW'      => 'pap-aw',
			'pcd'         => 'pcd',
			'pcm'         => 'pcm',
			'art_xpirate' => 'pirate',
			'pl_PL'       => 'pl',
			'pt_PT'       => 'pt',
			'pt_PT_ao90'  => 'pt-ao90',
			'pt_AO'       => 'pt-ao',
			'pt_BR'       => 'pt-br',
			'ps'          => 'ps',
			'rhg'         => 'rhg',
			'ro_RO'       => 'ro',
			'roh'         => 'roh',
			'ru_RU'       => 'ru',
			'sah'         => 'sah',
			'sa_IN'       => 'sa-in',
			'scn'         => 'scn',
			'si_LK'       => 'si',
			'sk_SK'       => 'sk',
			'skr'         => 'skr',
			'sl_SI'       => 'sl',
			'sna'         => 'sna',
			'snd'         => 'snd',
			'so_SO'       => 'so',
			'sq'          => 'sq',
			'sq_XK'       => 'sq-xk',
			'sr_RS'       => 'sr',
			'srd'         => 'srd',
			'ssw'         => 'ssw',
			'su_ID'       => 'su',
			'sv_SE'       => 'sv',
			'sw'          => 'sw',
			'syr'         => 'syr',
			'szl'         => 'szl',
			'ta_IN'       => 'ta',
			'ta_LK'       => 'ta-lk',
			'tah'         => 'tah',
			'te'          => 'te',
			'tg'          => 'tg',
			'th'          => 'th',
			'tir'         => 'tir',
			'tl'          => 'tl',
			'tr_TR'       => 'tr',
			'tt_RU'       => 'tt',
			'tuk'         => 'tuk',
			'twd'         => 'twd',
			'tzm'         => 'tzm',
			'ug_CN'       => 'ug',
			'uk'          => 'uk',
			'ur'          => 'ur',
			'uz_UZ'       => 'uz',
			'vec'         => 'vec',
			'vi'          => 'vi',
			'wol'         => 'wol',
			'xho'         => 'xho',
			'yor'         => 'yor',
			'zgh'         => 'zgh',
			'zh_CN'       => 'zh-cn',
			'zh_HK'       => 'zh-hk',
			'zh_SG'       => 'zh-sg',
			'zh_TW'       => 'zh-tw',
			'zul'         => 'zul'
		];

		return Arr::get( $slug_to_locale_map, $locale, null );
	}
}
