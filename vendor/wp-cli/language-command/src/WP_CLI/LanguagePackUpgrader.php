<?php

namespace WP_CLI;

use WP_CLI;

/**
 * A Language Pack Upgrader class that caches the download, and uses cached if available
 *
 * @package wp-cli
 */
class LanguagePackUpgrader extends \Language_Pack_Upgrader {
	/**
	 * Initialize the upgrade strings.
	 *
	 * Makes sure that the strings are always in English.
	 */
	public function upgrade_strings() {
		$switched_locale = function_exists( 'switch_to_locale' ) && switch_to_locale( 'en_US' );

		parent::upgrade_strings();

		if ( $switched_locale ) {
			restore_previous_locale();
		}
	}

	/**
	 * Initialize the generic strings.
	 *
	 * Makes sure that the strings are always in English.
	 */
	public function generic_strings() {
		$switched_locale = function_exists( 'switch_to_locale' ) && switch_to_locale( 'en_US' );

		parent::generic_strings();

		if ( $switched_locale ) {
			restore_previous_locale();
		}
	}

	/**
	 * Caches the download, and uses cached if available.
	 *
	 * @param string $package          The URI of the package. If this is the full path to an
	 *                                 existing local file, it will be returned untouched.
	 * @param bool   $check_signatures Whether to validate file signatures. Default false.
	 * @param array  $hook_extra       Extra arguments to pass to the filter hooks. Default empty array.
	 * @return string|\WP_Error The full path to the downloaded package file, or a WP_Error object.
	 */
	public function download_package( $package, $check_signatures = false, $hook_extra = [] ) {

		/**
		 * Filter whether to return the package.
		 *
		 * @since 3.7.0
		 * @since 5.5.0 Added the `$hook_extra` parameter.
		 *
		 * @param bool          $reply      Whether to bail without returning the package. Default is false.
		 * @param string        $package    The package file name.
		 * @param \WP_Upgrader  $this       The WP_Upgrader instance.
		 * @param array         $hook_extra Extra arguments passed to hooked filters.
		 *
		 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Using WP native hook.
		 */
		$reply = apply_filters( 'upgrader_pre_download', false, $package, $this, $hook_extra );
		// phpcs:enable

		if ( false !== $reply ) {
			return $reply;
		}

		// Check if package is a local or remote file. Bail if it's local.
		if ( ! preg_match( '!^(http|https|ftp)://!i', $package ) && file_exists( $package ) ) {
			return $package;
		}

		if ( empty( $package ) ) {
			return new \WP_Error( 'no_package', $this->strings['no_package'] );
		}

		$language_update = $this->skin->language_update;
		$type            = $language_update->type;
		$slug            = empty( $language_update->slug ) ? 'default' : $language_update->slug;
		$updated         = strtotime( $language_update->updated );
		$version         = $language_update->version;
		$language        = $language_update->language;
		$ext             = pathinfo( $package, PATHINFO_EXTENSION );

		$temp = \WP_CLI\Utils\get_temp_dir() . uniqid( 'wp_' ) . '.' . $ext;

		$cache      = WP_CLI::get_cache();
		$cache_key  = "translation/{$type}-{$slug}-{$version}-{$language}-{$updated}.{$ext}";
		$cache_file = $cache->has( $cache_key );

		if ( $cache_file ) {
			WP_CLI::log( "Using cached file '$cache_file'..." );
			copy( $cache_file, $temp );
			return $temp;
		}

		$this->skin->feedback( 'downloading_package', $package );

		$temp = download_url( $package, 600 ); // 10 minutes ought to be enough for everybody.

		if ( is_wp_error( $temp ) ) {
			return $temp;
		}

		$cache->import( $cache_key, $temp );

		return $temp;
	}
}
