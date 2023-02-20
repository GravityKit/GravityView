<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation;

require_once __DIR__ . '/Helpers/Core.php';

/**
 * Determines if a plugin should load.
 *
 * @since 1.0.3
 * @since 1.0.4 Repurposed as a catch-all method that performs multiple checks
 *
 * @param string $plugin_file Absolute path to the main plugin file.
 *
 * @return bool
 */
function should_load( $plugin_file ) {
	return ! is_disabled_via_url( $plugin_file ) || ! meets_min_php_version_requirement( $plugin_file );
}

/**
 * Checks if loading is disabled via a URL parameter.
 * Adding ?gk_disable_loading to the URL will prevent the loading of all plugins that include Foundation.
 * Adding ?gk_disable_loading=plugin-text-domain to the URL will prevent the loading of specific plugin(s) that includes Foundation (multiple text domains can be comma-separated).
 *
 * @since 1.0.4
 *
 * @param string $plugin_file Absolute path to the main plugin file.
 *
 * @return bool
 */
function is_disabled_via_url( $plugin_file ) {
	// Limit check to admin & login pages.
	if ( ! is_admin() && strpos( $_SERVER['PHP_SELF'], 'wp-login.php' ) === false ) {
		return false;
	}

	$plugin_data = Helpers\Core::get_plugin_data( $plugin_file );

	$cookie             = 'gk_disable_loading';
	$cookie_expiry_time = MINUTE_IN_SECONDS;

	$_is_disabled = function ( $plugin_text_domains ) use ( $plugin_data ) {
		if ( 'all' === $plugin_text_domains ) {
			return true;
		}

		foreach ( explode( ',', $plugin_text_domains ) as $text_domain ) {
			if ( $plugin_data['TextDomain'] === $text_domain ) {
				return true;
			}
		}

		return false;
	};

	if ( isset( $_COOKIE[ $cookie ] ) ) {
		if ( isset( $_GET['gk_enable_loading'] ) ) {
			setcookie( $cookie, false, time() - $cookie_expiry_time );

			return false;
		}

		return $_is_disabled( $_COOKIE[ $cookie ] );
	}

	$disable_loading = isset( $_GET['gk_disable_loading'] ) ? $_GET['gk_disable_loading'] : null;

	if ( is_null( $disable_loading ) ) {
		return false;
	}

	setcookie( $cookie, $disable_loading ?: 'all', time() + $cookie_expiry_time );

	return $_is_disabled( $disable_loading );
}

/**
 * Checks if the minimum PHP version requirement is met.
 *
 * @param string $plugin_file     Absolute path to the main plugin file.
 * @param string $min_php_version (optional) Minimum PHP version. Default: 5.6.4.
 * @param bool   $show_notice     (optional) Display error notice. Default: true.
 *
 * @return bool
 */
function meets_min_php_version_requirement( $plugin_file, $min_php_version = '5.6.4', $show_notice = true ) {
	$plugin_data = Helpers\Core::get_plugin_data( $plugin_file );

	$meets_requirement = (bool) version_compare( phpversion(), $min_php_version, '>=' );

	if ( ! $show_notice ) {
		return $meets_requirement;
	}

	if ( ! $meets_requirement ) {
		$notice = strtr(
			esc_html_x( '[plugin] requires PHP [version] or newer.', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
			[
				'[plugin]'  => $plugin_data['Name'],
				'[version]' => $min_php_version
			]
		);

		if ( 'cli' === php_sapi_name() ) {
			printf( $notice );
		} else {
			add_action( 'admin_notices', function () use ( $notice ) {
				echo "<div class='error' style='padding: 1.25em 0 1.25em 1em;'>$notice</div>";
			} );
		}
	}

	return $meets_requirement;
}
