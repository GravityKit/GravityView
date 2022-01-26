<?php

namespace WP_CLI\I18n;

use Gettext\Utils\PhpFunctionsScanner as GettextPhpFunctionsScanner;

class PhpFunctionsScanner extends GettextPhpFunctionsScanner {

	/**
	 * {@inheritdoc}
	 */
	public function saveGettextFunctions( $translations, array $options ) {
		// Ignore multiple translations for now.
		// @todo Add proper support for multiple translations.
		if ( is_array( $translations ) ) {
			$translations = $translations[0];
		}

		$functions     = $options['functions'];
		$file          = $options['file'];
		$add_reference = ! empty( $options['addReferences'] );

		foreach ( $this->getFunctions( $options['constants'] ) as $function ) {
			list( $name, $line, $args ) = $function;

			if ( ! isset( $functions[ $name ] ) ) {
				continue;
			}

			$original = null;
			$domain   = null;
			$context  = null;
			$plural   = null;

			switch ( $functions[ $name ] ) {
				case 'text_domain':
				case 'gettext':
					list( $original, $domain ) = array_pad( $args, 2, null );
					break;

				case 'text_context_domain':
					list( $original, $context, $domain ) = array_pad( $args, 3, null );
					break;

				case 'single_plural_number_domain':
					list( $original, $plural, $number, $domain ) = array_pad( $args, 4, null );
					break;

				case 'single_plural_number_context_domain':
					list( $original, $plural, $number, $context, $domain ) = array_pad( $args, 5, null );
					break;

				case 'single_plural_domain':
					list( $original, $plural, $domain ) = array_pad( $args, 3, null );
					break;

				case 'single_plural_context_domain':
					list( $original, $plural, $context, $domain ) = array_pad( $args, 4, null );
					break;

				default:
					// Should never happen.
					\WP_CLI::error( sprintf( "Internal error: unknown function map '%s' for '%s'.", $functions[ $name ], $name ) );
			}

			if ( '' === (string) $original ) {
				continue;
			}

			if ( $domain !== $translations->getDomain() && null !== $translations->getDomain() ) {
				continue;
			}

			$translation = $translations->insert( $context, $original, $plural );
			if ( $add_reference ) {
				$translation = $translation->addReference( $file, $line );
			}

			if ( isset( $function[3] ) ) {
				foreach ( $function[3] as $extracted_comment ) {
					$translation = $translation->addExtractedComment( $extracted_comment );
				}
			}
		}
	}
}
