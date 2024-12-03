<?php
/**
 * Registers the autoloader.
 *
 * @since $ver$
 */

spl_autoload_register(
	static function ( string $class_name ): void {
		// Map the namespace to the corresponding folder.
		$namespace_mapping = [
			'GV\\Search\\' => GRAVITYVIEW_DIR . 'includes/search/',
		];

		foreach ( $namespace_mapping as $namespace => $directory ) {
			$namespace = trim( $namespace, '\\' );
			if ( 0 !== strpos( $class_name, $namespace ) ) {
				continue; // Class name doesn't match.
			}

			$directory = realpath( rtrim( $directory, DIRECTORY_SEPARATOR ) );
			if ( ! $directory ) {
				continue; // Directory doesn't exist.
			}

			$file_path = strtolower(
				str_replace(
					[ $namespace, '\\', '_' ],
					[ '', DIRECTORY_SEPARATOR, '-' ],
					$class_name
				)
			);

			$parts = explode( DIRECTORY_SEPARATOR, $file_path );
			$f     = count( $parts ) - 1;

			// Filename can be prefixed with nothing, or `class-`, `trait-`, etc.
			foreach ( [ '', 'class', 'trait', 'abstract', 'interface' ] as $type ) {
				$file_parts       = $parts;
				$file_parts[ $f ] = ( $type ? $type . '-' : '' ) . $file_parts[ $f ];

				$class_file = $directory . implode( DIRECTORY_SEPARATOR, $file_parts ) . '.php';
				if ( is_readable( $class_file ) ) {
					require_once $class_file;
					break;
				}
			}
		}
	}
);
