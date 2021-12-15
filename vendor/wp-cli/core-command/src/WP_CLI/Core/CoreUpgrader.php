<?php

namespace WP_CLI\Core;

use Exception;
use Requests_Response;
use WP_CLI;
use WP_CLI\Utils;
use WP_Error;
use Core_Upgrader as DefaultCoreUpgrader;
use WP_Filesystem_Base;

/**
 * A Core Upgrader class that caches the download, and uses cached if available.
 *
 * @package wp-cli
 */
class CoreUpgrader extends DefaultCoreUpgrader {

	/**
	 * Whether to retry without certificate validation on TLS handshake failure.
	 *
	 * @var bool
	 */
	private $insecure;

	/**
	 * CoreUpgrader constructor.
	 *
	 * @param WP_Upgrader_Skin|null $skin
	 */
	public function __construct( $skin = null, $insecure = false ) {
		$this->insecure = $insecure;
		parent::__construct( $skin );
	}

	/**
	 * Caches the download, and uses cached if available.
	 *
	 * @access public
	 *
	 * @param string $package          The URI of the package. If this is the full path to an
	 *                                 existing local file, it will be returned untouched.
	 * @param bool   $check_signatures Whether to validate file signatures. Default true.
	 * @param array  $hook_extra       Extra arguments to pass to the filter hooks. Default empty array.
	 * @return string|WP_Error The full path to the downloaded package file, or a WP_Error object.
	 */
	public function download_package( $package, $check_signatures = true, $hook_extra = [] ) {

		/**
		 * Filter whether to return the package.
		 *
		 * @since 3.7.0
		 *
		 * @param bool    $reply   Whether to bail without returning the package. Default is false.
		 * @param string  $package The package file name.
		 * @param object  $this    The WP_Upgrader instance.
		 */
		$reply = apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Override existing hook from Core.
			'upgrader_pre_download',
			false,
			$package,
			$this,
			$hook_extra
		);
		if ( false !== $reply ) {
			return $reply;
		}

		// Check if package is a local or remote file. Bail if it's local.
		if ( ! preg_match( '!^(http|https|ftp)://!i', $package ) && file_exists( $package ) ) {
			return $package;
		}

		if ( empty( $package ) ) {
			return new WP_Error( 'no_package', $this->strings['no_package'] );
		}

		$filename  = pathinfo( $package, PATHINFO_FILENAME );
		$extension = pathinfo( $package, PATHINFO_EXTENSION );

		$temp = Utils\get_temp_dir() . uniqid( 'wp_' ) . ".{$extension}";
		register_shutdown_function(
			function () use ( $temp ) {
				if ( file_exists( $temp ) ) {
					unlink( $temp );
				}
			}
		);

		$cache      = WP_CLI::get_cache();
		$update     = $GLOBALS['wpcli_core_update_obj'];
		$cache_key  = "core/{$filename}-{$update->locale}.{$extension}";
		$cache_file = $cache->has( $cache_key );

		if ( $cache_file && false === stripos( $package, 'https://wordpress.org/nightly-builds/' )
			&& false === stripos( $package, 'http://wordpress.org/nightly-builds/' ) ) {
			WP_CLI::log( "Using cached file '{$cache_file}'..." );
			copy( $cache_file, $temp );
			return $temp;
		}

		/*
		 * Download to a temporary file because piping from cURL to tar is flaky
		 * on MinGW (and probably in other environments too).
		 */
		$headers = [ 'Accept' => 'application/json' ];
		$options = [
			'timeout'       => 600,  // 10 minutes ought to be enough for everybody.
			'filename'      => $temp,
			'halt_on_error' => false,
			'insecure'      => $this->insecure,
		];

		$this->skin->feedback( 'downloading_package', $package );

		/** @var Requests_Response|null $req */
		try {
			$response = Utils\http_request( 'GET', $package, null, $headers, $options );
		} catch ( Exception $e ) {
			return new WP_Error( 'download_failed', $e->getMessage() );
		}

		if ( ! is_null( $response ) && 200 !== (int) $response->status_code ) {
			return new WP_Error( 'download_failed', $this->strings['download_failed'] );
		}

		if ( false === stripos( $package, 'https://wordpress.org/nightly-builds/' ) ) {
			$cache->import( $cache_key, $temp );
		}

		return $temp;
	}

	/**
	 * Upgrade WordPress core.
	 *
	 * @access public
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Subclass
	 * @global callable           $_wp_filesystem_direct_method
	 *
	 * @param object $current Response object for whether WordPress is current.
	 * @param array  $args {
	 *        Optional. Arguments for upgrading WordPress core. Default empty array.
	 *
	 *        @type bool $pre_check_md5    Whether to check the file checksums before
	 *                                     attempting the upgrade. Default true.
	 *        @type bool $attempt_rollback Whether to attempt to rollback the chances if
	 *                                     there is a problem. Default false.
	 *        @type bool $do_rollback      Whether to perform this "upgrade" as a rollback.
	 *                                     Default false.
	 * }
	 * @return null|false|WP_Error False or WP_Error on failure, null on success.
	 */
	public function upgrade( $current, $args = [] ) {
		set_error_handler( [ __CLASS__, 'error_handler' ], E_USER_WARNING | E_USER_NOTICE );

		$result = parent::upgrade( $current, $args );

		restore_error_handler();

		return $result;
	}

	/**
	 * Error handler to ignore failures on accessing SSL "https://api.wordpress.org/core/checksums/1.0/" in `get_core_checksums()` which seem to occur intermittently.
	 */
	public static function error_handler( $errno, $errstr, $errfile, $errline, $errcontext = null ) {
		// If ignoring E_USER_WARNING | E_USER_NOTICE, default.
		if ( ! ( error_reporting() & $errno ) ) {
			return false;
		}
		// If not in "wp-admin/includes/update.php", default.
		$update_php = 'wp-admin/includes/update.php';
		if ( 0 !== substr_compare( $errfile, $update_php, -strlen( $update_php ) ) ) {
			return false;
		}
		// Else assume it's in `get_core_checksums()` and just ignore it.
		return true;
	}
}
