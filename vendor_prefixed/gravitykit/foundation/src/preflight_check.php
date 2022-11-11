<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 11-November-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation;

require_once __DIR__ . '/Helpers/Core.php';

/**
 * Determines if a plugin should load.
 * Adding ?gk_prevent_loading to the URL will prevent loading of all plugins that include Foundation.
 * Adding ?gk_prevent_loading=plugin-text-domain to the URL will prevent loading of a specific plugin that includes Foundation (multiple text domains can be comma-separated).gi
 *
 * @since 1.0.3
 *
 * @param string $plugin_file Absolute path to the main plugin file.
 *
 * @return bool
 */
function should_load( $plugin_file ) {
	// Limit check to admin & login pages.
	if ( ! is_admin() && $GLOBALS['pagenow'] !== 'wp-login.php' ) {
		return true;
	}

	$prevent_loading = isset( $_GET['gk_prevent_loading'] ) ? $_GET['gk_prevent_loading'] : null;

	if ( is_null( $prevent_loading ) ) {
		return true;
	}

	if ( '' === $prevent_loading ) {
		return false;
	}

	$plugin_data = Helpers\Core::get_plugin_data( $plugin_file );

	foreach ( explode( ',', $prevent_loading ) as $text_domain ) {
		if ( $plugin_data['TextDomain'] === $text_domain ) {
			return false;
		}
	}

	return true;
}