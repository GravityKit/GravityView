<?php

namespace WP_CLI\I18n;

use Gettext\Translation;
use Gettext\Translations;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use WP_CLI;
use WP_CLI\Utils;

trait IterableCodeExtractor {

	private static $dir = '';

	/**
	 * Extract the translations from a file.
	 *
	 * @param array|string $file_or_files A path of a file or files
	 * @param Translations $translations  The translations instance to append the new translations.
	 * @param array        $options      {
	 *     Optional. An array of options passed down to static::fromString()
	 *
	 *     @type bool  $wpExtractTemplates Extract 'Template Name' headers in theme files. Default 'false'.
	 *     @type array $restrictFileNames  Skip all files which are not included in this array.
	 * }
	 * @return null
	 */
	public static function fromFile( $file_or_files, Translations $translations, array $options = [] ) {
		foreach ( static::getFiles( $file_or_files ) as $file ) {
			if ( ! empty( $options['restrictFileNames'] ) ) {
				$basename = Utils\basename( $file );
				if ( ! in_array( $basename, $options['restrictFileNames'], true ) ) {
					continue;
				}
			}

			// Make sure a relative file path is added as a comment.
			$options['file'] = ltrim( str_replace( static::$dir, '', Utils\normalize_path( $file ) ), '/' );

			$string = file_get_contents( $file );

			if ( ! $string ) {
				WP_CLI::debug(
					sprintf(
						'Could not load file %1s',
						$file
					),
					'make-pot'
				);

				continue;
			}

			if ( ! empty( $options['wpExtractTemplates'] ) ) {
				$headers = MakePotCommand::get_file_data_from_string( $string, [ 'Template Name' => 'Template Name' ] );

				if ( ! empty( $headers['Template Name'] ) ) {
					$translation = new Translation( '', $headers['Template Name'] );
					$translation->addExtractedComment( 'Template Name of the theme' );

					$translations[] = $translation;
				}
			}

			static::fromString( $string, $translations, $options );
		}
	}

	/**
	 * Extract the translations from a file.
	 *
	 * @param string $dir                Root path to start the recursive traversal in.
	 * @param Translations $translations The translations instance to append the new translations.
	 * @param array        $options      {
	 *     Optional. An array of options passed down to static::fromString()
	 *
	 *     @type bool $wpExtractTemplates Extract 'Template Name' headers in theme files. Default 'false'.
	 *     @type array $exclude           A list of path to exclude. Default [].
	 *     @type array $extensions        A list of extensions to process. Default [].
	 * }
	 * @return null
	 */
	public static function fromDirectory( $dir, Translations $translations, array $options = [] ) {
		$dir = Utils\normalize_path( $dir );

		static::$dir = $dir;

		$include = isset( $options['include'] ) ? $options['include'] : [];
		$exclude = isset( $options['exclude'] ) ? $options['exclude'] : [];

		$files = static::getFilesFromDirectory( $dir, $include, $exclude, $options['extensions'] );

		if ( ! empty( $files ) ) {
			static::fromFile( $files, $translations, $options );
		}

		static::$dir = '';
	}

	/**
	 * Determines whether a file is valid based on given matchers.
	 *
	 * @param SplFileInfo $file     File or directory.
	 * @param array       $matchers List of files and directories to match.
	 * @return int How strongly the file is matched.
	 */
	protected static function calculateMatchScore( SplFileInfo $file, array $matchers = [] ) {
		if ( empty( $matchers ) ) {
			return 0;
		}

		if ( in_array( $file->getBasename(), $matchers, true ) ) {
			return 10;
		}

		// Check for more complex paths, e.g. /some/sub/folder.
		$root_relative_path = str_replace( static::$dir, '', $file->getPathname() );

		foreach ( $matchers as $path_or_file ) {
			$pattern = preg_quote( str_replace( '*', '__wildcard__', $path_or_file ), '/' );
			$pattern = '(^|/)' . str_replace( '__wildcard__', '(.+)', $pattern );

			// Base score is the amount of nested directories, discounting wildcards.
			$base_score = count(
				array_filter(
					explode( '/', $path_or_file ),
					static function ( $component ) {
						return '*' !== $component;
					}
				)
			);
			if ( 0 === $base_score ) {
				// If the matcher is simply * it gets a score above the implicit score but below 1.
				$base_score = 0.2;
			}

			// If the matcher contains no wildcards and matches the end of the path.
			if (
				false === strpos( $path_or_file, '*' ) &&
				false !== mb_ereg( $pattern . '$', $root_relative_path )
			) {
				return $base_score * 10;
			}

			// If the matcher matches the end of the path or a full directory contained.
			if ( false !== mb_ereg( $pattern . '(/|$)', $root_relative_path ) ) {
				return $base_score;
			}
		}

		return 0;
	}

	/**
	 * Determines whether or not a directory has children that may be matched.
	 *
	 * @param SplFileInfo $dir      Directory.
	 * @param array       $matchers List of files and directories to match.
	 * @return bool Whether or not there are any matchers for children of this directory.
	 */
	protected static function containsMatchingChildren( SplFileInfo $dir, array $matchers = [] ) {
		if ( empty( $matchers ) ) {
			return false;
		}

		/** @var string $root_relative_path */
		$root_relative_path = str_replace( static::$dir, '', $dir->getPathname() );

		foreach ( $matchers as $path_or_file ) {
			// If the matcher contains no wildcards and the path matches the start of the matcher.
			if (
				'' !== $root_relative_path &&
				false === strpos( $path_or_file, '*' ) &&
				0 === strpos( $path_or_file . '/', $root_relative_path )
			) {
				return true;
			}

			$base = current( explode( '*', $path_or_file ) );

			// If start of the path matches the start of the matcher until the first wildcard.
			// Or the start of the matcher until the first wildcard matches the start of the path.
			if (
				( '' !== $root_relative_path && 0 === strpos( $base, $root_relative_path ) ) ||
				( '' !== $base && 0 === strpos( $root_relative_path, $base ) )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Recursively gets all PHP files within a directory.
	 *
	 * @param string $dir A path of a directory.
	 * @param array $include List of files and directories to include.
	 * @param array $exclude List of files and directories to skip.
	 * @param array $extensions List of filename extensions to process.
	 *
	 * @return array File list.
	 */
	public static function getFilesFromDirectory( $dir, array $include = [], array $exclude = [], $extensions = [] ) {
		$filtered_files = [];

		$files = new RecursiveIteratorIterator(
			new RecursiveCallbackFilterIterator(
				new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::UNIX_PATHS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS ),
				static function ( $file, $key, $iterator ) use ( $include, $exclude, $extensions ) {
					/** @var RecursiveCallbackFilterIterator $iterator */
					/** @var SplFileInfo $file */

					// Normalize include and exclude paths.
					$include = array_map( 'static::trim_leading_slash', $include );
					$exclude = array_map( 'static::trim_leading_slash', $exclude );

					// If no $include is passed everything gets the weakest possible matching score.
					$inclusion_score = empty( $include ) ? 0.1 : static::calculateMatchScore( $file, $include );
					$exclusion_score = static::calculateMatchScore( $file, $exclude );

					// Always include directories that aren't excluded.
					if ( 0 === $exclusion_score && $iterator->hasChildren() ) {
						return true;
					}

					if ( ( 0 === $inclusion_score || $exclusion_score > $inclusion_score ) && $iterator->hasChildren() ) {
						// Always include directories that may have matching children even if they are excluded.
						return static::containsMatchingChildren( $file, $include );
					}

					if ( ! $file->isFile() || ! in_array( $file->getExtension(), $extensions, true ) ) {
						return false;
					}

					return $inclusion_score > $exclusion_score;
				}
			),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $files as $file ) {
			/** @var SplFileInfo $file */
			if ( ! $file->isFile() || ! in_array( $file->getExtension(), $extensions, true ) ) {
				continue;
			}

			$filtered_files[] = Utils\normalize_path( $file->getPathname() );
		}

		sort( $filtered_files, SORT_NATURAL | SORT_FLAG_CASE );

		return $filtered_files;
	}

	/**
	 * Trim leading slash from a path.
	 *
	 * @param string $path Path to trim.
	 * @return string Trimmed path.
	 */
	private static function trim_leading_slash( $path ) {
		return ltrim( $path, '/' );
	}
}
