<?php

namespace WP_CLI\I18n;

use Exception;
use Gettext\Extractors\JsCode;
use Gettext\Translations;
use Peast\Syntax\Exception as PeastException;
use WP_CLI;

final class JsCodeExtractor extends JsCode {
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

	protected static $functionsScannerClass = 'WP_CLI\I18n\JsFunctionsScanner';

	/**
	 * @inheritdoc
	 */
	public static function fromString( $string, Translations $translations, array $options = [] ) {
		WP_CLI::debug( "Parsing file {$options['file']}", 'make-pot' );

		try {
			static::fromStringMultiple( $string, [ $translations ], $options );
		} catch ( PeastException $exception ) {
			WP_CLI::debug(
				sprintf(
					'Could not parse file %1$s: %2$s (line %3$d, column %4$d)',
					$options['file'],
					$exception->getMessage(),
					$exception->getPosition()->getLine(),
					$exception->getPosition()->getColumn()
				),
				'make-pot'
			);
		} catch ( Exception $exception ) {
			WP_CLI::debug(
				sprintf(
					'Could not parse file %1$s: %2$s',
					$options['file'],
					$exception->getMessage()
				),
				'make-pot'
			);
		}
	}

	/**
	 * @inheritDoc
	 */
	public static function fromStringMultiple( $string, array $translations, array $options = [] ) {
		$options += static::$options;

		/** @var JsFunctionsScanner $functions */
		$functions = new static::$functionsScannerClass( $string );
		$functions->enableCommentsExtraction( $options['extractComments'] );
		$functions->saveGettextFunctions( $translations, $options );
	}
}
