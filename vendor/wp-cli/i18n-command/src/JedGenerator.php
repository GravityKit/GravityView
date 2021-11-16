<?php

namespace WP_CLI\I18n;

use Gettext\Generators\Jed;
use Gettext\Translation;
use Gettext\Translations;

/**
 * Jed file generator.
 *
 * Adds some more meta data to JED translation files than the default generator.
 */
class JedGenerator extends Jed {
	/**
	 * {@parentDoc}.
	 */
	public static function toString( Translations $translations, array $options = [] ) {
		$options += static::$options;
		$domain   = $translations->getDomain() ?: 'messages';
		$messages = static::buildMessages( $translations );

		$configuration = [
			'' => [
				'domain'       => $domain,
				'lang'         => $translations->getLanguage() ?: 'en',
				'plural-forms' => $translations->getHeader( 'Plural-Forms' ) ?: 'nplurals=2; plural=(n != 1);',
			],
		];

		$data = [
			'translation-revision-date' => $translations->getHeader( 'PO-Revision-Date' ),
			'generator'                 => 'WP-CLI/' . WP_CLI_VERSION,
			'source'                    => $options['source'],
			'domain'                    => $domain,
			'locale_data'               => [
				$domain => $configuration + $messages,
			],
		];

		return json_encode( $data, $options['json'] );
	}

	/**
	 * Generates an array with all translations.
	 *
	 * @param Translations $translations
	 *
	 * @return array
	 */
	public static function buildMessages( Translations $translations ) {
		$plural_forms      = $translations->getPluralForms();
		$number_of_plurals = is_array( $plural_forms ) ? ( $plural_forms[0] - 1 ) : null;
		$messages          = [];
		$context_glue      = chr( 4 );

		foreach ( $translations as $translation ) {
			/** @var Translation $translation */

			if ( $translation->isDisabled() ) {
				continue;
			}

			$key = $translation->getOriginal();

			if ( $translation->hasContext() ) {
				$key = $translation->getContext() . $context_glue . $key;
			}

			if ( $translation->hasPluralTranslations( true ) ) {
				$message = $translation->getPluralTranslations( $number_of_plurals );
				array_unshift( $message, $translation->getTranslation() );
			} else {
				$message = [ $translation->getTranslation() ];
			}

			$messages[ $key ] = $message;
		}

		return $messages;
	}
}
