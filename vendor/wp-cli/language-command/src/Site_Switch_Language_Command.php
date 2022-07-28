<?php

class Site_Switch_Language_Command extends WP_CLI\CommandWithTranslation {
	protected $obj_type = 'core';

	/**
	 * Activates a given language.
	 *
	 * ## OPTIONS
	 *
	 * <language>
	 * : Language code to activate.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site switch-language ja
	 *     Success: Language activated.
	 *
	 * @throws WP_CLI\ExitException
	 */
	public function __invoke( $args, $assoc_args ) {
		list( $language_code ) = $args;

		$available = $this->get_installed_languages();

		if ( ! in_array( $language_code, $available, true ) ) {
			WP_CLI::error( 'Language not installed.' );
		}

		if ( 'en_US' === $language_code ) {
			$language_code = '';
		}

		if ( get_locale() === $language_code ) {
			WP_CLI::warning( "Language '{$language_code}' already active." );

			return;
		}

		update_option( 'WPLANG', $language_code );

		WP_CLI::success( 'Language activated.' );
	}
}
