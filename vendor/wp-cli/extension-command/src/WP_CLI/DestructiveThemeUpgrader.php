<?php

namespace WP_CLI;

/**
 * A theme upgrader class that clears the destination directory.
 */
class DestructiveThemeUpgrader extends \Theme_Upgrader {

	public function install_package( $args = array() ) {
		parent::upgrade_strings(); // Needed for the 'remove_old' string.

		$args['clear_destination']           = true;
		$args['abort_if_destination_exists'] = false;
		return parent::install_package( $args );
	}
}

