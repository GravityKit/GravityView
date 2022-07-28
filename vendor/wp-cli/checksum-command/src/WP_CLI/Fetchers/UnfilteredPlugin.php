<?php

namespace WP_CLI\Fetchers;

/**
 * Fetch a WordPress plugin based on one of its attributes.
 *
 * This is a special version of the plugin fetcher. It doesn't use the
 * `all_plugins` filter, so that plugins cannot hide themselves from the
 * checks.
 *
 */
class UnfilteredPlugin extends Base {

	/**
	 * @var string $msg Error message to use when invalid data is provided
	 */
	protected $msg = "The '%s' plugin could not be found.";

	/**
	 * Get a plugin object by name.
	 *
	 * @param string $name
	 *
	 * @return object|false
	 */
	public function get( $name ) {
		foreach ( get_plugins() as $file => $_ ) {
			if ( "{$name}.php" === $file ||
				( $name && $file === $name ) ||
				( dirname( $file ) === $name && '.' !== $name ) ) {
				return (object) compact( 'name', 'file' );
			}
		}

		return false;
	}
}

