<?php

use WP_CLI\Dispatcher\CommandNamespace;

/**
 * Installs, activates, and manages language packs.
 *
 * ## EXAMPLES
 *
 *     # Install the Dutch core language pack.
 *     $ wp language core install nl_NL
 *     Success: Language installed.
 *
 *     # Activate the Dutch core language pack.
 *     $ wp language core activate nl_NL
 *     Success: Language activated.
 *
 *     # Install the Dutch language pack for Twenty Seventeen.
 *     $ wp language theme install twentyseventeen nl_NL
 *     Success: Language installed.
 *
 *     # Install the Dutch language pack for Akismet.
 *     $ wp language plugin install akismet nl_NL
 *     Success: Language installed.
 */
class Language_Namespace extends CommandNamespace {

}
