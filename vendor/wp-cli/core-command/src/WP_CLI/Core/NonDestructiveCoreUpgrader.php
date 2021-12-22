<?php

namespace WP_CLI\Core;

/**
 * A Core Upgrader class that leaves packages intact by default.
 *
 * @package wp-cli
 */
class NonDestructiveCoreUpgrader extends CoreUpgrader {
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Provide default value.
	public function unpack_package( $package, $delete_package = false ) {
		return parent::unpack_package( $package, $delete_package );
	}
}

