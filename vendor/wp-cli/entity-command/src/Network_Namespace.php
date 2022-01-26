<?php

use WP_CLI\Dispatcher\CommandNamespace;

/**
 * Perform network-wide operations.
 *
 * ## EXAMPLES
 *
 *     # Get a list of super-admins
 *     $ wp network meta get 1 site_admins
 *     array (
 *       0 => 'supervisor',
 *     )
 */
class Network_Namespace extends CommandNamespace {

}
