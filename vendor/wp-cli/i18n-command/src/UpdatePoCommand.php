<?php

namespace WP_CLI\I18n;

use DirectoryIterator;
use Gettext\Extractors\Po;
use Gettext\Merge;
use Gettext\Translations;
use IteratorIterator;
use SplFileInfo;
use WP_CLI;
use WP_CLI\Utils;
use WP_CLI_Command;

class UpdatePoCommand extends WP_CLI_Command {
	/**
	 * Update PO files from a POT file.
	 *
	 * This behaves similarly to the [msgmerge](https://www.gnu.org/software/gettext/manual/html_node/msgmerge-Invocation.html) command.
	 *
	 * ## OPTIONS
	 *
	 * <source>
	 * : Path to an existing POT file to use for updating
	 *
	 * [<destination>]
	 * : PO file to update or a directory containing multiple PO files.
	 *   Defaults to all PO files in the source directory.
	 *
	 * @when before_wp_load
	 *
	 * @throws WP_CLI\ExitException
	 */
	public function __invoke( $args, $assoc_args ) {
		$source = realpath( $args[0] );
		if ( ! $source || ! is_file( $source ) ) {
			WP_CLI::error( 'Source file does not exist!' );
		}

		$destination = dirname( $source );

		if ( isset( $args[1] ) ) {
			$destination = $args[1];
		}

		if ( is_file( $destination ) ) {
			$files = [ new SplFileInfo( $destination ) ];
		} else {
			$files = new IteratorIterator( new DirectoryIterator( $destination ) );
		}

		$pot_translations = Translations::fromPoFile( $source );

		$result_count = 0;
		/** @var DirectoryIterator $file */
		foreach ( $files as $file ) {
			if ( 'po' !== $file->getExtension() ) {
				continue;
			}

			if ( ! $file->isFile() || ! $file->isReadable() ) {
				WP_CLI::warning( sprintf( 'Could not read file %s', $file->getFilename() ) );
				continue;
			}

			$po_translations = Translations::fromPoFile( $file->getPathname() );
			$po_translations->mergeWith(
				$pot_translations,
				Merge::ADD | Merge::REMOVE | Merge::COMMENTS_THEIRS | Merge::EXTRACTED_COMMENTS_THEIRS | Merge::REFERENCES_THEIRS
			);

			if ( ! $po_translations->toPoFile( $file->getPathname() ) ) {
				WP_CLI::warning( sprintf( 'Could not update file %s', $file->getPathname() ) );
				continue;
			}

			$result_count++;
		}

		WP_CLI::success( sprintf( 'Updated %d %s.', $result_count, Utils\pluralize( 'file', $result_count ) ) );
	}
}
