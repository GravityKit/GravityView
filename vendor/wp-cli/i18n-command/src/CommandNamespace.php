<?php

namespace WP_CLI\I18n;

use WP_CLI\Dispatcher\CommandNamespace as BaseCommandNamespace;

/**
 * Provides internationalization tools for WordPress projects.
 *
 * ## EXAMPLES
 *
 *     # Create a POT file for the WordPress plugin/theme in the current directory
 *     $ wp i18n make-pot . languages/my-plugin.pot
 *
 * @when before_wp_load
 */
class CommandNamespace extends BaseCommandNamespace {
}
