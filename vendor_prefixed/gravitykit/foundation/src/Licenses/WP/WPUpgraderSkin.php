<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\Licenses\WP;

use WP_Upgrader_Skin;
use Exception;

/**
 * This is class is used to catch errors and suppress output during product installation/update.
 *
 *
 * @since 1.0.0
 *
 * @see   WP_Upgrader_Skin
 */
class WPUpgraderSkin extends WP_Upgrader_Skin {
	/**
	 * @inheritDoc Silences header display.
	 *
	 * @since      1.0.0
	 *
	 * @return void
	 */
	public function header() {
	}

	/**
	 * @inheritDoc Silences footer display.
	 *
	 *
	 * @since      1.0.0
	 *
	 * @return void
	 */
	public function footer() {
	}

	/**
	 * @inheritDoc Silences results.
	 *
	 * @since      1.0.0
	 *
	 * @return void
	 */
	public function feedback( $feedback, ...$args ) {
	}

	/**
	 * Throws an error when one (or multiple) is encountered.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function error( $errors ) {
		$output = $errors;

		if ( is_wp_error( $errors ) && $errors->has_errors() ) {
			// One error is enough to get a sense of why the installation failed.
			$output = $errors->get_error_messages()[0];
		}

		throw new Exception( $output );
	}
}
