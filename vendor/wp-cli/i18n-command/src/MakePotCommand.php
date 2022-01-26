<?php

namespace WP_CLI\I18n;

use Gettext\Extractors\Po;
use Gettext\Merge;
use Gettext\Translation;
use Gettext\Translations;
use Gettext\Utils\ParsedComment;
use WP_CLI;
use WP_CLI_Command;
use WP_CLI\Utils;
use DirectoryIterator;
use IteratorIterator;

class MakePotCommand extends WP_CLI_Command {
	/**
	 * @var string
	 */
	protected $source;

	/**
	 * @var string
	 */
	protected $destination;

	/**
	 * @var array
	 */
	protected $merge = [];

	/**
	 * @var Translations[]
	 */
	protected $exceptions = [];

	/**
	 * @var bool
	 */
	protected $subtract_and_merge;

	/**
	 * @var array
	 */
	protected $include = [];

	/**
	 * @var array
	 */
	protected $exclude = [ 'node_modules', '.git', '.svn', '.CVS', '.hg', 'vendor', 'Gruntfile.js', 'webpack.config.js', '*.min.js' ];

	/**
	 * @var string
	 */
	protected $slug;

	/**
	 * @var array
	 */
	protected $main_file_data = [];

	/**
	 * @var bool
	 */
	protected $skip_js = false;

	/**
	 * @var bool
	 */
	protected $skip_php = false;

	/**
	 * @var bool
	 */
	protected $skip_block_json = false;

	/**
	 * @var bool
	 */
	protected $skip_theme_json = false;

	/**
	 * @var bool
	 */
	protected $skip_audit = false;

	/**
	 * @var bool
	 */
	protected $location = true;

	/**
	 * @var array
	 */
	protected $headers = [];

	/**
	 * @var string
	 */
	protected $domain;

	/**
	 * @var string
	 */
	protected $package_name;

	/**
	 * @var string
	 */
	protected $file_comment;

	/**
	 * @var string
	 */
	protected $project_type = 'generic';

	/**
	 * These Regexes copied from http://php.net/manual/en/function.sprintf.php#93552
	 * and adjusted for better precision and updated specs.
	 */
	const SPRINTF_PLACEHOLDER_REGEX = '/(?:
		(?<!%)                     # Don\'t match a literal % (%%).
		(
			%                          # Start of placeholder.
			(?:[0-9]+\$)?              # Optional ordering of the placeholders.
			[+-]?                      # Optional sign specifier.
			(?:
				(?:0|\'.)?                 # Optional padding specifier - excluding the space.
				-?                         # Optional alignment specifier.
				[0-9]*                     # Optional width specifier.
				(?:\.(?:[ 0]|\'.)?[0-9]+)? # Optional precision specifier with optional padding character.
				|                      # Only recognize the space as padding in combination with a width specifier.
				(?:[ ])?                   # Optional space padding specifier.
				-?                         # Optional alignment specifier.
				[0-9]+                     # Width specifier.
				(?:\.(?:[ 0]|\'.)?[0-9]+)? # Optional precision specifier with optional padding character.
			)
			[bcdeEfFgGosuxX]           # Type specifier.
		)
	)/x';

	/**
	 * "Unordered" means there's no position specifier: '%s', not '%2$s'.
	 */
	const UNORDERED_SPRINTF_PLACEHOLDER_REGEX = '/(?:
		(?<!%)                     # Don\'t match a literal % (%%).
		%                          # Start of placeholder.
		[+-]?                      # Optional sign specifier.
		(?:
			(?:0|\'.)?                 # Optional padding specifier - excluding the space.
			-?                         # Optional alignment specifier.
			[0-9]*                     # Optional width specifier.
			(?:\.(?:[ 0]|\'.)?[0-9]+)? # Optional precision specifier with optional padding character.
			|                      # Only recognize the space as padding in combination with a width specifier.
			(?:[ ])?                   # Optional space padding specifier.
			-?                         # Optional alignment specifier.
			[0-9]+                     # Width specifier.
			(?:\.(?:[ 0]|\'.)?[0-9]+)? # Optional precision specifier with optional padding character.
		)
		[bcdeEfFgGosuxX]           # Type specifier.
	)/x';

	/**
	 * Create a POT file for a WordPress project.
	 *
	 * Scans PHP and JavaScript files for translatable strings, as well as theme stylesheets and plugin files
	 * if the source directory is detected as either a plugin or theme.
	 *
	 * ## OPTIONS
	 *
	 * <source>
	 * : Directory to scan for string extraction.
	 *
	 * [<destination>]
	 * : Name of the resulting POT file.
	 *
	 * [--slug=<slug>]
	 * : Plugin or theme slug. Defaults to the source directory's basename.
	 *
	 * [--domain=<domain>]
	 * : Text domain to look for in the source code, unless the `--ignore-domain` option is used.
	 * By default, the "Text Domain" header of the plugin or theme is used.
	 * If none is provided, it falls back to the project slug.
	 *
	 * [--ignore-domain]
	 * : Ignore the text domain completely and extract strings with any text domain.
	 *
	 * [--merge[=<paths>]]
	 * : Comma-separated list of POT files whose contents should be merged with the extracted strings.
	 * If left empty, defaults to the destination POT file. POT file headers will be ignored.
	 *
	 * [--subtract=<paths>]
	 * : Comma-separated list of POT files whose contents should act as some sort of denylist for string extraction.
	 * Any string which is found on that denylist will not be extracted.
	 * This can be useful when you want to create multiple POT files from the same source directory with slightly
	 * different content and no duplicate strings between them.
	 *
	 * [--subtract-and-merge]
	 * : Whether source code references and comments from the generated POT file should be instead added to the POT file
	 * used for subtraction. Warning: this modifies the files passed to `--subtract`!
	 *
	 * [--include=<paths>]
	 * : Comma-separated list of files and paths that should be used for string extraction.
	 * If provided, only these files and folders will be taken into account for string extraction.
	 * For example, `--include="src,my-file.php` will ignore anything besides `my-file.php` and files in the `src`
	 * directory. Simple glob patterns can be used, i.e. `--include=foo-*.php` includes any PHP file with the `foo-`
	 * prefix. Leading and trailing slashes are ignored, i.e. `/my/directory/` is the same as `my/directory`.
	 *
	 * [--exclude=<paths>]
	 * : Comma-separated list of files and paths that should be skipped for string extraction.
	 * For example, `--exclude=".github,myfile.php` would ignore any strings found within `myfile.php` or the `.github`
	 * folder. Simple glob patterns can be used, i.e. `--exclude=foo-*.php` excludes any PHP file with the `foo-`
	 * prefix. Leading and trailing slashes are ignored, i.e. `/my/directory/` is the same as `my/directory`. The
	 * following files and folders are always excluded: node_modules, .git, .svn, .CVS, .hg, vendor, *.min.js.
	 *
	 * [--headers=<headers>]
	 * : Array in JSON format of custom headers which will be added to the POT file. Defaults to empty array.
	 *
	 * [--location]
	 * : Whether to write `#: filename:line` lines.
	 * Defaults to true, use `--no-location` to skip the removal.
	 * Note that disabling this option makes it harder for technically skilled translators to understand each message’s context.
	 *
	 * [--skip-js]
	 * : Skips JavaScript string extraction. Useful when this is done in another build step, e.g. through Babel.
	 *
	 * [--skip-php]
	 * : Skips PHP string extraction.
	 *
	 * [--skip-block-json]
	 * : Skips string extraction from block.json files.
	 *
	 * [--skip-theme-json]
	 * : Skips string extraction from theme.json files.
	 *
	 * [--skip-audit]
	 * : Skips string audit where it tries to find possible mistakes in translatable strings. Useful when running in an
	 * automated environment.
	 *
	 * [--file-comment=<file-comment>]
	 * : String that should be added as a comment to the top of the resulting POT file.
	 * By default, a copyright comment is added for WordPress plugins and themes in the following manner:
	 *
	 *      ```
	 *      Copyright (C) 2018 Example Plugin Author
	 *      This file is distributed under the same license as the Example Plugin package.
	 *      ```
	 *
	 *      If a plugin or theme specifies a license in their main plugin file or stylesheet, the comment looks like
	 *      this:
	 *
	 *      ```
	 *      Copyright (C) 2018 Example Plugin Author
	 *      This file is distributed under the GPLv2.
	 *      ```
	 *
	 * [--package-name=<name>]
	 * : Name to use for package name in the resulting POT file's `Project-Id-Version` header.
	 * Overrides plugin or theme name, if applicable.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create a POT file for the WordPress plugin/theme in the current directory
	 *     $ wp i18n make-pot . languages/my-plugin.pot
	 *
	 *     # Create a POT file for the continents and cities list in WordPress core.
	 *     $ wp i18n make-pot . continents-and-cities.pot --include="wp-admin/includes/continents-cities.php"
	 *     --ignore-domain
	 *
	 * @when before_wp_load
	 *
	 * @throws WP_CLI\ExitException
	 */
	public function __invoke( $args, $assoc_args ) {
		$this->handle_arguments( $args, $assoc_args );

		$translations = $this->extract_strings();

		if ( ! $translations ) {
			WP_CLI::warning( 'No strings found' );
		}

		$translations_count = count( $translations );

		if ( 1 === $translations_count ) {
			WP_CLI::debug( sprintf( 'Extracted %d string', $translations_count ), 'make-pot' );
		} else {
			WP_CLI::debug( sprintf( 'Extracted %d strings', $translations_count ), 'make-pot' );
		}

		if ( ! PotGenerator::toFile( $translations, $this->destination ) ) {
			WP_CLI::error( 'Could not generate a POT file!' );
		}

		WP_CLI::success( 'POT file successfully generated!' );
	}

	/**
	 * Process arguments from command-line in a reusable way.
	 *
	 * @throws WP_CLI\ExitException
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function handle_arguments( $args, $assoc_args ) {
		$array_arguments = array( 'headers' );
		$assoc_args      = Utils\parse_shell_arrays( $assoc_args, $array_arguments );

		$this->source          = realpath( $args[0] );
		$this->slug            = Utils\get_flag_value( $assoc_args, 'slug', Utils\basename( $this->source ) );
		$this->skip_js         = Utils\get_flag_value( $assoc_args, 'skip-js', $this->skip_js );
		$this->skip_php        = Utils\get_flag_value( $assoc_args, 'skip-php', $this->skip_php );
		$this->skip_block_json = Utils\get_flag_value( $assoc_args, 'skip-block-json', $this->skip_block_json );
		$this->skip_theme_json = Utils\get_flag_value( $assoc_args, 'skip-theme-json', $this->skip_theme_json );
		$this->skip_audit      = Utils\get_flag_value( $assoc_args, 'skip-audit', $this->skip_audit );
		$this->headers         = Utils\get_flag_value( $assoc_args, 'headers', $this->headers );
		$this->file_comment    = Utils\get_flag_value( $assoc_args, 'file-comment' );
		$this->package_name    = Utils\get_flag_value( $assoc_args, 'package-name' );
		$this->location        = Utils\get_flag_value( $assoc_args, 'location', true );

		$ignore_domain = Utils\get_flag_value( $assoc_args, 'ignore-domain', false );

		if ( ! $this->source || ! is_dir( $this->source ) ) {
			WP_CLI::error( 'Not a valid source directory!' );
		}

		$this->main_file_data = $this->get_main_file_data();

		if ( $ignore_domain ) {
			WP_CLI::debug( 'Extracting all strings regardless of text domain', 'make-pot' );
		}

		if ( ! $ignore_domain ) {
			$this->domain = $this->slug;

			if ( ! empty( $this->main_file_data['Text Domain'] ) ) {
				$this->domain = $this->main_file_data['Text Domain'];
			}

			$this->domain = Utils\get_flag_value( $assoc_args, 'domain', $this->domain );

			WP_CLI::debug( sprintf( 'Extracting all strings with text domain "%s"', $this->domain ), 'make-pot' );
		}

		// Determine destination.
		$this->destination = "{$this->source}/{$this->slug}.pot";

		if ( ! empty( $this->main_file_data['Domain Path'] ) ) {
			// Domain Path inside source folder.
			$this->destination = sprintf(
				'%s/%s/%s.pot',
				$this->source,
				$this->unslashit( $this->main_file_data['Domain Path'] ),
				$this->slug
			);
		}

		if ( isset( $args[1] ) ) {
			$this->destination = $args[1];
		}

		WP_CLI::debug( sprintf( 'Destination: %s', $this->destination ), 'make-pot' );

		// Two is_dir() checks in case of a race condition.
		if ( ! is_dir( dirname( $this->destination ) )
			&& ! mkdir( dirname( $this->destination ), 0777, true )
			&& ! is_dir( dirname( $this->destination ) )
		) {
			WP_CLI::error( 'Could not create destination directory!' );
		}

		if ( isset( $assoc_args['merge'] ) ) {
			if ( true === $assoc_args['merge'] ) {
				$this->merge = [ $this->destination ];
			} elseif ( ! empty( $assoc_args['merge'] ) ) {
				$this->merge = explode( ',', $assoc_args['merge'] );
			}

			$this->merge = array_filter(
				$this->merge,
				function ( $file ) {
					if ( ! file_exists( $file ) ) {
						WP_CLI::warning( sprintf( 'Invalid file provided to --merge: %s', $file ) );

						return false;
					}

					return true;
				}
			);

			if ( ! empty( $this->merge ) ) {
				WP_CLI::debug(
					sprintf(
						'Merging with existing POT %s: %s',
						WP_CLI\Utils\pluralize( 'file', count( $this->merge ) ),
						implode( ',', $this->merge )
					),
					'make-pot'
				);
			}
		}

		if ( isset( $assoc_args['subtract'] ) ) {
			$this->subtract_and_merge = Utils\get_flag_value( $assoc_args, 'subtract-and-merge', false );

			$files = explode( ',', $assoc_args['subtract'] );

			foreach ( $files as $file ) {
				if ( ! file_exists( $file ) ) {
					WP_CLI::warning( sprintf( 'Invalid file provided to --subtract: %s', $file ) );
					continue;
				}

				WP_CLI::debug( sprintf( 'Ignoring any string already existing in: %s', $file ), 'make-pot' );

				$this->exceptions[ $file ] = new Translations();
				Po::fromFile( $file, $this->exceptions[ $file ] );
			}
		}

		if ( isset( $assoc_args['include'] ) ) {
			$this->include = array_filter( explode( ',', $assoc_args['include'] ) );
			$this->include = array_map( [ $this, 'unslashit' ], $this->include );
			$this->include = array_unique( $this->include );

			WP_CLI::debug( sprintf( 'Only including the following files: %s', implode( ',', $this->include ) ), 'make-pot' );
		}

		if ( isset( $assoc_args['exclude'] ) ) {
			$this->exclude = array_filter( array_merge( $this->exclude, explode( ',', $assoc_args['exclude'] ) ) );
			$this->exclude = array_map( [ $this, 'unslashit' ], $this->exclude );
			$this->exclude = array_unique( $this->exclude );
		}

		WP_CLI::debug( sprintf( 'Excluding the following files: %s', implode( ',', $this->exclude ) ), 'make-pot' );
	}

	/**
	 * Removes leading and trailing slashes of a string.
	 *
	 * @param string $string What to add and remove slashes from.
	 * @return string String without leading and trailing slashes.
	 */
	protected function unslashit( $string ) {
		return ltrim( rtrim( trim( $string ), '/\\' ), '/\\' );
	}

	/**
	 * Retrieves the main file data of the plugin or theme.
	 *
	 * @return array
	 */
	protected function get_main_file_data() {
		$files = new IteratorIterator( new DirectoryIterator( $this->source ) );

		/** @var DirectoryIterator $file */
		foreach ( $files as $file ) {
			// wp-content/themes/my-theme/style.css
			if ( $file->isFile() && 'style' === $file->getBasename( '.css' ) && $file->isReadable() ) {
				$theme_data = static::get_file_data( $file->getRealPath(), array_combine( $this->get_file_headers( 'theme' ), $this->get_file_headers( 'theme' ) ) );

				// Stop when it contains a valid Theme Name header.
				if ( ! empty( $theme_data['Theme Name'] ) ) {
					WP_CLI::log( 'Theme stylesheet detected.' );
					WP_CLI::debug( sprintf( 'Theme stylesheet: %s', $file->getRealPath() ), 'make-pot' );

					$this->project_type = 'theme';

					return $theme_data;
				}
			}

			// wp-content/themes/my-themes/theme-a/style.css
			if ( $file->isDir() && ! $file->isDot() && is_readable( $file->getRealPath() . '/style.css' ) ) {
				$theme_data = static::get_file_data( $file->getRealPath() . '/style.css', array_combine( $this->get_file_headers( 'theme' ), $this->get_file_headers( 'theme' ) ) );

				// Stop when it contains a valid Theme Name header.
				if ( ! empty( $theme_data['Theme Name'] ) ) {
					WP_CLI::log( 'Theme stylesheet detected.' );
					WP_CLI::debug( sprintf( 'Theme stylesheet: %s', $file->getRealPath() . '/style.css' ), 'make-pot' );

					$this->project_type = 'theme';

					return $theme_data;
				}
			}

			// wp-content/plugins/my-plugin/my-plugin.php
			if ( $file->isFile() && $file->isReadable() && 'php' === $file->getExtension() ) {
				$plugin_data = static::get_file_data( $file->getRealPath(), array_combine( $this->get_file_headers( 'plugin' ), $this->get_file_headers( 'plugin' ) ) );

				// Stop when we find a file with a valid Plugin Name header.
				if ( ! empty( $plugin_data['Plugin Name'] ) ) {
					WP_CLI::log( 'Plugin file detected.' );
					WP_CLI::debug( sprintf( 'Plugin file: %s', $file->getRealPath() ), 'make-pot' );

					$this->project_type = 'plugin';

					return $plugin_data;
				}
			}
		}

		WP_CLI::debug( 'No valid theme stylesheet or plugin file found, treating as a regular project.', 'make-pot' );

		return [];
	}

	/**
	 * Returns the file headers for themes and plugins.
	 *
	 * @param string $type Source type, either theme or plugin.
	 *
	 * @return array List of file headers.
	 */
	protected function get_file_headers( $type ) {
		switch ( $type ) {
			case 'plugin':
				return [
					'Plugin Name',
					'Plugin URI',
					'Description',
					'Author',
					'Author URI',
					'Version',
					'License',
					'Domain Path',
					'Text Domain',
				];
			case 'theme':
				return [
					'Theme Name',
					'Theme URI',
					'Description',
					'Author',
					'Author URI',
					'Version',
					'License',
					'Domain Path',
					'Text Domain',
				];
			default:
				return [];
		}
	}

	/**
	 * Creates a POT file and stores it on disk.
	 *
	 * @throws WP_CLI\ExitException
	 *
	 * @return Translations A Translation set.
	 */
	protected function extract_strings() {
		$translations = new Translations();

		// Add existing strings first but don't keep headers.
		if ( ! empty( $this->merge ) ) {
			$existing_translations = new Translations();
			Po::fromFile( $this->merge, $existing_translations );
			$translations->mergeWith( $existing_translations, Merge::ADD | Merge::REMOVE );
		}

		PotGenerator::setCommentBeforeHeaders( $this->get_file_comment() );

		$this->set_default_headers( $translations );

		// POT files have no Language header.
		$translations->deleteHeader( Translations::HEADER_LANGUAGE );

		// Only relevant for PO files, not POT files.
		$translations->setHeader( 'PO-Revision-Date', 'YEAR-MO-DA HO:MI+ZONE' );

		if ( $this->domain ) {
			$translations->setDomain( $this->domain );
		}

		unset( $this->main_file_data['Version'], $this->main_file_data['License'], $this->main_file_data['Domain Path'], $this->main_file_data['Text Domain'] );

		// Set entries from main file data.
		foreach ( $this->main_file_data as $header => $data ) {
			if ( empty( $data ) ) {
				continue;
			}

			$translation = new Translation( '', $data );

			if ( isset( $this->main_file_data['Theme Name'] ) ) {
				$translation->addExtractedComment( sprintf( '%s of the theme', $header ) );
			} else {
				$translation->addExtractedComment( sprintf( '%s of the plugin', $header ) );
			}

			$translations[] = $translation;
		}

		try {
			if ( ! $this->skip_php ) {
				$options = [
					// Extract 'Template Name' headers in theme files.
					'wpExtractTemplates' => isset( $this->main_file_data['Theme Name'] ),
					'include'            => $this->include,
					'exclude'            => $this->exclude,
					'extensions'         => [ 'php' ],
					'addReferences'      => $this->location,
				];
				PhpCodeExtractor::fromDirectory( $this->source, $translations, $options );
			}

			if ( ! $this->skip_js ) {
				JsCodeExtractor::fromDirectory(
					$this->source,
					$translations,
					[
						'include'       => $this->include,
						'exclude'       => $this->exclude,
						'extensions'    => [ 'js', 'jsx' ],
						'addReferences' => $this->location,
					]
				);

				MapCodeExtractor::fromDirectory(
					$this->source,
					$translations,
					[
						'include'       => $this->include,
						'exclude'       => $this->exclude,
						'extensions'    => [ 'map' ],
						'addReferences' => $this->location,
					]
				);
			}

			if ( ! $this->skip_block_json ) {
				BlockExtractor::fromDirectory(
					$this->source,
					$translations,
					[
						// Only look for block.json files, nothing else.
						'restrictFileNames' => [ 'block.json' ],
						'include'           => $this->include,
						'exclude'           => $this->exclude,
						'extensions'        => [ 'json' ],
						'addReferences'     => $this->location,
					]
				);
			}

			if ( ! $this->skip_theme_json ) {
				ThemeJsonExtractor::fromDirectory(
					$this->source,
					$translations,
					[
						// Only look for theme.json files, nothing else.
						'restrictFileNames' => [ 'theme.json' ],
						'include'           => $this->include,
						'exclude'           => $this->exclude,
						'extensions'        => [ 'json' ],
					]
				);
			}
		} catch ( \Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		foreach ( $this->exceptions as $file => $exception_translations ) {
			/** @var Translation $exception_translation */
			foreach ( $exception_translations as $exception_translation ) {
				if ( ! $translations->find( $exception_translation ) ) {
					continue;
				}

				if ( $this->subtract_and_merge ) {
					$translation = $translations[ $exception_translation->getId() ];
					$exception_translation->mergeWith( $translation );
				}

				unset( $translations[ $exception_translation->getId() ] );
			}

			if ( $this->subtract_and_merge ) {
				PotGenerator::toFile( $exception_translations, $file );
			}
		}

		if ( ! $this->skip_audit ) {
			$this->audit_strings( $translations );
		}

		return $translations;
	}

	/**
	 * Audits strings.
	 *
	 * Goes through all extracted strings to find possible mistakes.
	 *
	 * @param Translations $translations Translations object.
	 */
	protected function audit_strings( $translations ) {
		foreach ( $translations as $translation ) {
			/** @var Translation $translation */

			$references = $translation->getReferences();

			// File headers don't have any file references.
			$location = $translation->hasReferences() ? '(' . implode( ':', array_shift( $references ) ) . ')' : '';

			// Check 1: Flag strings with placeholders that should have translator comments.
			if (
				! $translation->hasExtractedComments() &&
				preg_match( self::SPRINTF_PLACEHOLDER_REGEX, $translation->getOriginal(), $placeholders ) >= 1
			) {
				$message = sprintf(
					'The string "%1$s" contains placeholders but has no "translators:" comment to clarify their meaning. %2$s',
					$translation->getOriginal(),
					$location
				);
				WP_CLI::warning( $message );
			}

			// Check 2: Flag strings with different translator comments.
			if ( $translation->hasExtractedComments() ) {
				$comments = $translation->getExtractedComments();

				// Remove plugin header information from comments.
				$comments = array_filter(
					$comments,
					function ( $comment ) {
						/** @var ParsedComment|string $comment */
						/** @var string $file_header */
						foreach ( $this->get_file_headers( $this->project_type ) as $file_header ) {
							if ( 0 === strpos( ( $comment instanceof ParsedComment ? $comment->getComment() : $comment ), $file_header ) ) {
								return null;
							}
						}

						return $comment;
					}
				);

				$unique_comments = array();

				// Remove duplicate comments.
				$comments = array_filter(
					$comments,
					function ( $comment ) use ( &$unique_comments ) {
						/** @var ParsedComment|string $comment */
						if ( in_array( ( $comment instanceof ParsedComment ? $comment->getComment() : $comment ), $unique_comments, true ) ) {
							return null;
						}

						$unique_comments[] = ( $comment instanceof ParsedComment ? $comment->getComment() : $comment );

						return $comment;
					}
				);

				$comments_count = count( $comments );

				if ( $comments_count > 1 ) {
					$message = sprintf(
						'The string "%1$s" has %2$d different translator comments. %3$s',
						$translation->getOriginal(),
						$comments_count,
						$location
					);
					WP_CLI::warning( $message );
				}
			}

			$non_placeholder_content = trim( preg_replace( '`^([\'"])(.*)\1$`Ds', '$2', $translation->getOriginal() ) );
			$non_placeholder_content = preg_replace( self::SPRINTF_PLACEHOLDER_REGEX, '', $non_placeholder_content );

			// Check 3: Flag empty strings without any translatable content.
			if ( '' === $non_placeholder_content ) {
				$message = sprintf(
					'Found string without translatable content. %s',
					$location
				);
				WP_CLI::warning( $message );
			}

			// Check 4: Flag strings with multiple unordered placeholders (%s %s %s vs. %1$s %2$s %3$s).
			$unordered_matches_count = preg_match_all( self::UNORDERED_SPRINTF_PLACEHOLDER_REGEX, $translation->getOriginal(), $unordered_matches );
			$unordered_matches       = $unordered_matches[0];

			if ( $unordered_matches_count >= 2 ) {
				$message = sprintf(
					'Multiple placeholders should be ordered. %s',
					$location
				);
				WP_CLI::warning( $message );
			}

			if ( $translation->hasPlural() ) {
				preg_match_all( self::SPRINTF_PLACEHOLDER_REGEX, $translation->getOriginal(), $single_placeholders );
				$single_placeholders = $single_placeholders[0];

				preg_match_all( self::SPRINTF_PLACEHOLDER_REGEX, $translation->getPlural(), $plural_placeholders );
				$plural_placeholders = $plural_placeholders[0];

				// see https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#plurals
				if ( count( $single_placeholders ) < count( $plural_placeholders ) ) {
					// Check 5: Flag things like _n( 'One comment', '%s Comments' )
					$message = sprintf(
						'Missing singular placeholder, needed for some languages. See https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#plurals %s',
						$location
					);
					WP_CLI::warning( $message );
				} else {
					// Reordering is fine, but mismatched placeholders is probably wrong.
					sort( $single_placeholders );
					sort( $plural_placeholders );

					// Check 6: Flag things like _n( '%s Comment (%d)', '%s Comments (%s)' )
					if ( $single_placeholders !== $plural_placeholders ) {
						$message = sprintf(
							'Mismatched placeholders for singular and plural string. %s',
							$location
						);
						WP_CLI::warning( $message );
					}
				}
			}
		}
	}

	/**
	 * Returns the copyright comment for the given package.
	 *
	 * @return string File comment.
	 */
	protected function get_file_comment() {
		if ( '' === $this->file_comment ) {
			return '';
		}

		if ( isset( $this->file_comment ) ) {
			return implode( "\n", explode( '\n', $this->file_comment ) );
		}

		if ( isset( $this->main_file_data['Theme Name'] ) ) {
			if ( isset( $this->main_file_data['License'] ) ) {
				return sprintf(
					"Copyright (C) %1\$s %2\$s\nThis file is distributed under the %3\$s.",
					date( 'Y' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					$this->main_file_data['Author'],
					$this->main_file_data['License']
				);
			}

			return sprintf(
				"Copyright (C) %1\$s %2\$s\nThis file is distributed under the same license as the %3\$s theme.",
				date( 'Y' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				$this->main_file_data['Author'],
				$this->main_file_data['Theme Name']
			);
		}

		if ( isset( $this->main_file_data['Plugin Name'] ) ) {
			if ( isset( $this->main_file_data['License'] ) && ! empty( $this->main_file_data['License'] ) ) {
				return sprintf(
					"Copyright (C) %1\$s %2\$s\nThis file is distributed under the %3\$s.",
					date( 'Y' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					$this->main_file_data['Author'],
					$this->main_file_data['License']
				);
			}

			return sprintf(
				"Copyright (C) %1\$s %2\$s\nThis file is distributed under the same license as the %3\$s plugin.",
				date( 'Y' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				$this->main_file_data['Author'],
				$this->main_file_data['Plugin Name']
			);
		}

		return '';
	}

	/**
	 * Sets default POT file headers for the project.
	 *
	 * @param Translations $translations Translations object.
	 */
	protected function set_default_headers( $translations ) {
		$name         = null;
		$version      = $this->get_wp_version();
		$bugs_address = null;

		if ( ! $version && isset( $this->main_file_data['Version'] ) ) {
			$version = $this->main_file_data['Version'];
		}

		if ( isset( $this->main_file_data['Theme Name'] ) ) {
			$name         = $this->main_file_data['Theme Name'];
			$bugs_address = sprintf( 'https://wordpress.org/support/theme/%s', $this->slug );
		} elseif ( isset( $this->main_file_data['Plugin Name'] ) ) {
			$name         = $this->main_file_data['Plugin Name'];
			$bugs_address = sprintf( 'https://wordpress.org/support/plugin/%s', $this->slug );
		}

		if ( null !== $this->package_name ) {
			$name = $this->package_name;
		}

		if ( null !== $name ) {
			$translations->setHeader( 'Project-Id-Version', $name . ( $version ? ' ' . $version : '' ) );
		}

		if ( null !== $bugs_address ) {
			$translations->setHeader( 'Report-Msgid-Bugs-To', $bugs_address );
		}

		$translations->setHeader( 'Last-Translator', 'FULL NAME <EMAIL@ADDRESS>' );
		$translations->setHeader( 'Language-Team', 'LANGUAGE <LL@li.org>' );
		$translations->setHeader( 'X-Generator', 'WP-CLI ' . WP_CLI_VERSION );

		foreach ( $this->headers as $key => $value ) {
			$translations->setHeader( $key, $value );
		}
	}

	/**
	 * Extracts the WordPress version number from wp-includes/version.php.
	 *
	 * @return string|false Version number on success, false otherwise.
	 */
	protected function get_wp_version() {
		$version_php = $this->source . '/wp-includes/version.php';
		if ( ! file_exists( $version_php ) || ! is_readable( $version_php ) ) {
			return false;
		}

		return preg_match( '/\$wp_version\s*=\s*\'(.*?)\';/', file_get_contents( $version_php ), $matches ) ? $matches[1] : false;
	}

	/**
	 * Retrieves metadata from a file.
	 *
	 * Searches for metadata in the first 8kiB of a file, such as a plugin or theme.
	 * Each piece of metadata must be on its own line. Fields can not span multiple
	 * lines, the value will get cut at the end of the first line.
	 *
	 * If the file data is not within that first 8kiB, then the author should correct
	 * their plugin file and move the data headers to the top.
	 *
	 * @see get_file_data()
	 *
	 * @param string $file Path to the file.
	 * @param array $headers List of headers, in the format array('HeaderKey' => 'Header Name').
	 *
	 * @return array Array of file headers in `HeaderKey => Header Value` format.
	 */
	protected static function get_file_data( $file, $headers ) {
		// We don't need to write to the file, so just open for reading.
		$fp = fopen( $file, 'rb' );

		// Pull only the first 8kiB of the file in.
		$file_data = fread( $fp, 8192 );

		// PHP will close file handle, but we are good citizens.
		fclose( $fp );

		// Make sure we catch CR-only line endings.
		$file_data = str_replace( "\r", "\n", $file_data );

		return static::get_file_data_from_string( $file_data, $headers );
	}

	/**
	 * Retrieves metadata from a string.
	 *
	 * @param string $string String to look for metadata in.
	 * @param array $headers List of headers.
	 *
	 * @return array Array of file headers in `HeaderKey => Header Value` format.
	 */
	public static function get_file_data_from_string( $string, $headers ) {
		foreach ( $headers as $field => $regex ) {
			if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $string, $match ) && $match[1] ) {
				$headers[ $field ] = static::_cleanup_header_comment( $match[1] );
			} else {
				$headers[ $field ] = '';
			}
		}

		return $headers;
	}

	/**
	 * Strip close comment and close php tags from file headers used by WP.
	 *
	 * @see _cleanup_header_comment()
	 *
	 * @param string $str Header comment to clean up.
	 *
	 * @return string
	 */
	protected static function _cleanup_header_comment( $str ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore -- Not changing because third-party commands might use/extend.
		return trim( preg_replace( '/\s*(?:\*\/|\?>).*/', '', $str ) );
	}
}
