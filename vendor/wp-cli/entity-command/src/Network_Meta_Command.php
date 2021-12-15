<?php

use WP_CLI\CommandWithMeta;

/**
 * Gets, adds, updates, deletes, and lists network custom fields.
 *
 * ## EXAMPLES
 *
 *     # Get a list of super-admins
 *     $ wp network meta get 1 site_admins
 *     array (
 *       0 => 'supervisor',
 *     )
 */
class Network_Meta_Command extends CommandWithMeta {
	protected $meta_type = 'site';
}
