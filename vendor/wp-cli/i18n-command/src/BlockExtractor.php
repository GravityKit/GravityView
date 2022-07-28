<?php

namespace WP_CLI\I18n;

use Gettext\Translations;
use WP_CLI;

final class BlockExtractor extends JsonSchemaExtractor {
	/**
	 * @inheritdoc
	 */
	public static function fromString( $string, Translations $translations, array $options = [] ) {
		$file = $options['file'];
		WP_CLI::debug( "Parsing file $file", 'make-pot' );

		$json = json_decode( $string, true );

		if ( null === $json ) {
			WP_CLI::debug(
				sprintf(
					'Could not parse file %1$s: error code %2$s',
					$file,
					json_last_error()
				),
				'make-pot'
			);

			return;
		}

		$domain = isset( $json['textdomain'] ) ? $json['textdomain'] : null;

		// Always allow missing domain or when --ignore-domain is used, but skip if domains don't match.
		if ( null !== $translations->getDomain() && null !== $domain && $domain !== $translations->getDomain() ) {
			return;
		}

		parent::fromString( $string, $translations, $options );
	}
}
