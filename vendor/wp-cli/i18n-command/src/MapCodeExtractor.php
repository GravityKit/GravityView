<?php

namespace WP_CLI\I18n;

use Gettext\Extractors\JsCode;
use Gettext\Translations;
use Peast\Syntax\Exception as PeastException;
use WP_CLI;

final class MapCodeExtractor extends JsCode {
	use IterableCodeExtractor;

	public static $options = [
		'extractComments' => [ 'translators', 'Translators' ],
		'constants'       => [],
		'functions'       => [
			'__'  => 'text_domain',
			'_x'  => 'text_context_domain',
			'_n'  => 'single_plural_number_domain',
			'_nx' => 'single_plural_number_context_domain',
		],
	];

	/**
	 * {@inheritdoc}
	 */
	public static function fromString( $string, Translations $translations, array $options = [] ) {
		if ( ! array_key_exists( 'file', $options ) || substr( $options['file'], -7 ) !== '.js.map' ) {
			return;
		}
		$options['file'] = substr( $options['file'], 0, -7 ) . '.js';

		try {
			$options += static::$options;

			$map_object = json_decode( $string );

			if ( ! isset( $map_object->sourcesContent ) || ! is_array( $map_object->sourcesContent ) ) {
				return;
			}

			$string = implode( "\n", $map_object->sourcesContent );

			WP_CLI::debug( "Parsing file {$options['file']}", 'make-pot' );

			$functions = new JsFunctionsScanner( $string );

			$functions->enableCommentsExtraction( $options['extractComments'] );
			$functions->saveGettextFunctions( $translations, $options );
		} catch ( PeastException $e ) {
			WP_CLI::debug(
				sprintf(
					'Could not parse file %1$s.map: %2$s (line %3$d, column %4$d in the concatenated sourcesContent)',
					$options['file'],
					$e->getMessage(),
					$e->getPosition()->getLine(),
					$e->getPosition()->getColumn()
				),
				'make-pot'
			);
		}
	}
}
