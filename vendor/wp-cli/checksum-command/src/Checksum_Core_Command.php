<?php

use WP_CLI\Utils;
use WP_CLI\WpOrgApi;

/**
 * Verifies core file integrity by comparing to published checksums.
 *
 * @package wp-cli
 */
class Checksum_Core_Command extends Checksum_Base_Command {

	/**
	 * Verifies WordPress files against WordPress.org's checksums.
	 *
	 * Downloads md5 checksums for the current version from WordPress.org, and
	 * compares those checksums against the currently installed files.
	 *
	 * For security, avoids loading WordPress when verifying checksums.
	 *
	 * If you experience issues verifying from this command, ensure you are
	 * passing the relevant `--locale` and `--version` arguments according to
	 * the values from the `Dashboard->Updates` menu in the admin area of the
	 * site.
	 *
	 * ## OPTIONS
	 *
	 * [--version=<version>]
	 * : Verify checksums against a specific version of WordPress.
	 *
	 * [--locale=<locale>]
	 * : Verify checksums against a specific locale of WordPress.
	 *
	 * [--insecure]
	 * : Retry downloads without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.
	 *
	 * ## EXAMPLES
	 *
	 *     # Verify checksums
	 *     $ wp core verify-checksums
	 *     Success: WordPress installation verifies against checksums.
	 *
	 *     # Verify checksums for given WordPress version
	 *     $ wp core verify-checksums --version=4.0
	 *     Success: WordPress installation verifies against checksums.
	 *
	 *     # Verify checksums for given locale
	 *     $ wp core verify-checksums --locale=en_US
	 *     Success: WordPress installation verifies against checksums.
	 *
	 *     # Verify checksums for given locale
	 *     $ wp core verify-checksums --locale=ja
	 *     Warning: File doesn't verify against checksum: wp-includes/version.php
	 *     Warning: File doesn't verify against checksum: readme.html
	 *     Warning: File doesn't verify against checksum: wp-config-sample.php
	 *     Error: WordPress installation doesn't verify against checksums.
	 *
	 * @when before_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {
		$wp_version = '';
		$locale     = '';

		if ( ! empty( $assoc_args['version'] ) ) {
			$wp_version = $assoc_args['version'];
		}

		if ( ! empty( $assoc_args['locale'] ) ) {
			$locale = $assoc_args['locale'];
		}

		if ( empty( $wp_version ) ) {
			$details    = self::get_wp_details();
			$wp_version = $details['wp_version'];

			if ( empty( $locale ) ) {
				$locale = $details['wp_local_package'];
			}
		}

		$insecure   = (bool) Utils\get_flag_value( $assoc_args, 'insecure', false );
		$wp_org_api = new WpOrgApi( [ 'insecure' => $insecure ] );

		try {
			$checksums = $wp_org_api->get_core_checksums( $wp_version, empty( $locale ) ? 'en_US' : $locale );
		} catch ( Exception $exception ) {
			WP_CLI::error( $exception );
		}

		if ( ! is_array( $checksums ) ) {
			WP_CLI::error( "Couldn't get checksums from WordPress.org." );
		}

		$has_errors = false;
		foreach ( $checksums as $file => $checksum ) {
			// Skip files which get updated
			if ( 'wp-content' === substr( $file, 0, 10 ) ) {
				continue;
			}

			if ( ! file_exists( ABSPATH . $file ) ) {
				WP_CLI::warning( "File doesn't exist: {$file}" );
				$has_errors = true;
				continue;
			}

			$md5_file = md5_file( ABSPATH . $file );
			if ( $md5_file !== $checksum ) {
				WP_CLI::warning( "File doesn't verify against checksum: {$file}" );
				$has_errors = true;
			}
		}

		$core_checksums_files = array_filter( array_keys( $checksums ), [ $this, 'filter_file' ] );
		$core_files           = $this->get_files( ABSPATH );
		$additional_files     = array_diff( $core_files, $core_checksums_files );

		if ( ! empty( $additional_files ) ) {
			foreach ( $additional_files as $additional_file ) {
				WP_CLI::warning( "File should not exist: {$additional_file}" );
			}
		}

		if ( ! $has_errors ) {
			WP_CLI::success( 'WordPress installation verifies against checksums.' );
		} else {
			WP_CLI::error( "WordPress installation doesn't verify against checksums." );
		}
	}

	/**
	 * Whether to include the file in the verification or not.
	 *
	 * @param string $filepath Path to a file.
	 *
	 * @return bool
	 */
	protected function filter_file( $filepath ) {
		return ( 0 === strpos( $filepath, 'wp-admin/' )
			|| 0 === strpos( $filepath, 'wp-includes/' )
			|| 1 === preg_match( '/^wp-(?!config\.php)([^\/]*)$/', $filepath )
		);
	}

	/**
	 * Gets version information from `wp-includes/version.php`.
	 *
	 * @return array {
	 *     @type string $wp_version The WordPress version.
	 *     @type int $wp_db_version The WordPress DB revision.
	 *     @type string $tinymce_version The TinyMCE version.
	 *     @type string $wp_local_package The TinyMCE version.
	 * }
	 */
	private static function get_wp_details() {
		$versions_path = ABSPATH . 'wp-includes/version.php';

		if ( ! is_readable( $versions_path ) ) {
			WP_CLI::error(
				"This does not seem to be a WordPress install.\n" .
				'Pass --path=`path/to/wordpress` or run `wp core download`.'
			);
		}

		$version_content = file_get_contents( $versions_path, null, null, 6, 2048 );

		$vars   = [ 'wp_version', 'wp_db_version', 'tinymce_version', 'wp_local_package' ];
		$result = [];

		foreach ( $vars as $var_name ) {
			$result[ $var_name ] = self::find_var( $var_name, $version_content );
		}

		return $result;
	}

	/**
	 * Searches for the value assigned to variable `$var_name` in PHP code `$code`.
	 *
	 * This is equivalent to matching the `\$VAR_NAME = ([^;]+)` regular expression and returning
	 * the first match either as a `string` or as an `integer` (depending if it's surrounded by
	 * quotes or not).
	 *
	 * @param string $var_name Variable name to search for.
	 * @param string $code PHP code to search in.
	 *
	 * @return int|string|null
	 */
	private static function find_var( $var_name, $code ) {
		$start = strpos( $code, '$' . $var_name . ' = ' );

		if ( ! $start ) {
			return null;
		}

		$start = $start + strlen( $var_name ) + 3;
		$end   = strpos( $code, ';', $start );

		$value = substr( $code, $start, $end - $start );

		return trim( $value, "'" );
	}
}
