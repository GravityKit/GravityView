<?php

namespace WP_CLI\Fetchers;

use WP_CLI\Utils;

/**
 * Fetch a WordPress theme based on one of its attributes.
 */
class Theme extends Base {

	/**
	 * @var string $msg Error message to use when invalid data is provided
	 */
	protected $msg = "The '%s' theme could not be found.";

	/**
	 * Get a theme object by name
	 *
	 * @param string $name
	 * @return object|false
	 */
	public function get( $name ) {
		// Workaround to equalize folder naming conventions across Win/Mac/Linux.
		// Returns false if theme stylesheet doesn't exactly match existing themes.
		$existing_themes      = wp_get_themes( array( 'errors' => null ) );
		$existing_stylesheets = array_keys( $existing_themes );
		if ( ! in_array( $name, $existing_stylesheets, true ) ) {
			$inexact_match = $this->find_inexact_match( $name, $existing_themes );
			if ( false !== $inexact_match ) {
				$this->msg .= sprintf( " Did you mean '%s'?", $inexact_match );
			}
			return false;
		}

		$theme = $existing_themes[ $name ];

		return $theme;
	}

	/**
	 * Find and return the key in $existing_themes that matches $name with
	 * a case insensitive string comparison.
	 *
	 * @param string $name Name of theme received by command.
	 * @param array  $existing_themes Key/value pair of existing themes, key is
	 *                                a case sensitive name.
	 * @return string|boolean Case sensitive name if match found, otherwise false.
	 */
	private function find_inexact_match( $name, $existing_themes ) {
		$target = strtolower( $name );
		$themes = array_map( 'strtolower', array_keys( $existing_themes ) );

		if ( in_array( $target, $themes, true ) ) {
			return $target;
		}

		$suggestion = Utils\get_suggestion( $target, $themes );

		if ( '' !== $suggestion ) {
			return $suggestion;
		}

		return false;
	}
}

