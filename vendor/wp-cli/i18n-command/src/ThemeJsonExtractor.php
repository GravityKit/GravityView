<?php

namespace WP_CLI\I18n;

use Gettext\Extractors\Extractor;
use Gettext\Extractors\ExtractorInterface;
use Gettext\Translations;
use WP_CLI;
use WP_CLI\Utils;

final class ThemeJsonExtractor extends Extractor implements ExtractorInterface {
	use IterableCodeExtractor;

	/**
	 * Source URL from which to download the latest theme-i18n.json file.
	 *
	 * @var string
	 */
	const THEME_JSON_SOURCE = 'https://develop.svn.wordpress.org/trunk/src/wp-includes/theme-i18n.json';

	/**
	 * @inheritdoc
	 */
	public static function fromString( $string, Translations $translations, array $options = [] ) {
		$file = $options['file'];
		WP_CLI::debug( "Parsing file {$file}", 'make-pot' );

		$theme_json = json_decode( $string, true );

		if ( null === $theme_json ) {
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

		$fields = self::get_fields_to_translate();
		foreach ( $fields as $field ) {
			$path    = $field['path'];
			$key     = $field['key'];
			$context = $field['context'];

			/*
			 * We need to process the paths that include '*' separately.
			 * One example of such a path would be:
			 * [ 'settings', 'blocks', '*', 'color', 'palette' ]
			 */
			$nodes_to_iterate = array_keys( $path, '*', true );
			if ( ! empty( $nodes_to_iterate ) ) {
				/*
				 * At the moment, we only need to support one '*' in the path, so take it directly.
				 * - base will be [ 'settings', 'blocks' ]
				 * - data will be [ 'color', 'palette' ]
				 */
				$base_path = array_slice( $path, 0, $nodes_to_iterate[0] );
				$data_path = array_slice( $path, $nodes_to_iterate[0] + 1 );
				$base_tree = self::array_get( $theme_json, $base_path, [] );
				foreach ( $base_tree as $node_data ) {
					$array_to_translate = self::array_get( $node_data, $data_path );
					if ( null === $array_to_translate ) {
						continue;
					}

					foreach ( $array_to_translate as $item_to_translate ) {
						if ( empty( $item_to_translate[ $key ] ) ) {
							continue;
						}

						$translation = $translations->insert( $context, $item_to_translate[ $key ] );
						$translation->addReference( $file );
					}
				}
			} else {
				$array_to_translate = self::array_get( $theme_json, $path );
				if ( null === $array_to_translate ) {
					continue;
				}

				foreach ( $array_to_translate as $item_to_translate ) {
					if ( empty( $item_to_translate[ $key ] ) ) {
						continue;
					}

					$translation = $translations->insert( $context, $item_to_translate[ $key ] );
					$translation->addReference( $file );
				}
			}
		}
	}

	/**
	 * Given a remote URL, fetches it remotely and returns its content.
	 *
	 * Returns an empty string in case of error.
	 *
	 * @param string $url URL of the file to fetch.
	 *
	 * @return string Contents of the file.
	 */
	private static function remote_get( $url ) {
		if ( ! $url ) {
			return '';
		}

		$headers  = [ 'Content-type: application/json' ];
		$options  = [ 'halt_on_error' => false ];
		$response = Utils\http_request( 'GET', $url, null, $headers, $options );

		if (
			! $response->success
			|| 200 > (int) $response->status_code
			|| 300 <= $response->status_code
		) {
			WP_CLI::debug( "Failed to download from URL {$url}", 'make-pot' );
			return '';
		}

		return trim( $response->body );
	}

	/**
	 * Returns a data structure to help setting up translations for theme.json data.
	 *
	 * [
	 *     [
	 *         'path'    => [ 'settings', 'color', 'palette' ],
	 *         'key'     => 'key-that-stores-the-string-to-translate',
	 *         'context' => 'translation-context',
	 *     ],
	 *     [
	 *         'path'    => 'etc',
	 *         'key'     => 'etc',
	 *         'context' => 'etc',
	 *     ],
	 * ]
	 *
	 * Ported from the core class `WP_Theme_JSON_Resolver`.
	 *
	 * @return array An array of theme.json fields that are translatable and the keys that are translatable.
	 */
	private static function get_fields_to_translate() {
		$json = self::remote_get( self::THEME_JSON_SOURCE );

		if ( empty( $json ) ) {
			WP_CLI::debug( 'Remote file could not be accessed, will use local file as fallback', 'make-pot' );
			$json = file_get_contents( __DIR__ . '/../assets/theme-i18n.json' );
		}

		$file_structure = json_decode( $json, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			WP_CLI::debug( 'Error when decoding theme-i18n.json file', 'make-pot' );
			return [];
		}

		if ( ! is_array( $file_structure ) ) {
			return [];
		}

		return self::extract_paths_to_translate( $file_structure );
	}

	/**
	 * Converts a tree as in theme-i18.json file into a linear array
	 * containing metadata to translate a theme.json file.
	 *
	 * For example, given this input:
	 *
	 *     {
	 *       "settings": {
	 *         "*": {
	 *           "typography": {
	 *             "fontSizes": [ { "name": "Font size name" } ],
	 *             "fontStyles": [ { "name": "Font size name" } ]
	 *           }
	 *         }
	 *       }
	 *     }
	 *
	 * will return this output:
	 *
	 *     [
	 *       0 => [
	 *         'path'    => [ 'settings', '*', 'typography', 'fontSizes' ],
	 *         'key'     => 'name',
	 *         'context' => 'Font size name'
	 *       ],
	 *       1 => [
	 *         'path'    => [ 'settings', '*', 'typography', 'fontStyles' ],
	 *         'key'     => 'name',
	 *         'context' => 'Font style name'
	 *       ]
	 *     ]
	 *
	 * Ported from the core class `WP_Theme_JSON_Resolver`.
	 *
	 * @param array $i18n_partial A tree that follows the format of theme-i18n.json.
	 * @param array $current_path Keeps track of the path as we walk down the given tree.
	 * @return array A linear array containing the paths to translate.
	 */
	private static function extract_paths_to_translate( $i18n_partial, $current_path = [] ) {
		$result = [];
		foreach ( $i18n_partial as $property => $partial_child ) {
			if ( is_numeric( $property ) ) {
				foreach ( $partial_child as $key => $context ) {
					$result[] = [
						'path'    => $current_path,
						'key'     => $key,
						'context' => $context,
					];
				}
				return $result;
			}
			$result = array_merge(
				$result,
				self::extract_paths_to_translate( $partial_child, array_merge( $current_path, [ $property ] ) )
			);
		}
		return $result;
	}

	/**
	 * Accesses an array in depth based on a path of keys.
	 *
	 * It is the PHP equivalent of JavaScript's `lodash.get()` and mirroring it may help other components
	 * retain some symmetry between client and server implementations.
	 *
	 * Example usage:
	 *
	 *     $array = [
	 *         'a' => [
	 *             'b' => [
	 *                 'c' => 1,
	 *             ],
	 *         ],
	 *     ];
	 *     array_get( $array, [ 'a', 'b', 'c' ] );
	 *
	 * @param array $array   An array from which we want to retrieve some information.
	 * @param array $path    An array of keys describing the path with which to retrieve information.
	 * @param mixed $default The return value if the path does not exist within the array,
	 *                       or if `$array` or `$path` are not arrays.
	 * @return mixed The value from the path specified.
	 */
	private static function array_get( $array, $path, $default = null ) {
		// Confirm $path is valid.
		if ( ! is_array( $path ) || 0 === count( $path ) ) {
			return $default;
		}

		foreach ( $path as $path_element ) {
			if (
				! is_array( $array ) ||
				( ! is_string( $path_element ) && ! is_integer( $path_element ) && null !== $path_element ) ||
				! array_key_exists( $path_element, $array )
			) {
				return $default;
			}
			$array = $array[ $path_element ];
		}

		return $array;
	}

}
