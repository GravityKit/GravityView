<?php

namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * Manages Grid displays.
 *
 * @since $ver$
 */
final class Grid {
	/**
	 * A prefix to use for the area key.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	private static string $area_prefix = '';

	/**
	 * Internal counter to avoid UID clashes.
	 *
	 * @since $ver$
	 *
	 * @var int
	 */
	private static int $counter = 0;

	/**
	 * Returns the row configuration based on a type.
	 *
	 * @since $ver$
	 *
	 * @param string      $type         The type.
	 * @param string|null $id           The row ID. WIll be generated if not provided.
	 * @param bool        $keep_area_id Whether to keep the existing area IDs.
	 *
	 * @return array The row configuration.
	 */
	public static function get_row_by_type( string $type, ?string $id = null, bool $keep_area_id = false ): array {
		$rows = self::get_row_types();
		$row  = $rows[ $type ] ?? [];

		$id ??= substr( md5( ++ self::$counter . microtime( true ) ), 0, 13 );

		if ( $keep_area_id ) {
			return $row;
		}

		foreach ( $row as $col => $areas ) {
			foreach ( $areas as $i => $area ) {
				$row[ $col ][ $i ]['areaid'] = implode( '::', [ $row[ $col ][ $i ]['areaid'], $type, $id ] );
			}
		}

		return $row;
	}

	/**
	 * Calculates and returns the row configurations based on a collection and the zone.
	 *
	 * @since $ver$
	 *
	 * @param Widget_Collection|Field_Collection $collection The collection.
	 * @param string                             $zone       The zone.
	 *
	 * @return array The row configurations.
	 */
	public static function get_rows_from_collection( Collection $collection, string $zone ): array {
		$rows = [];
		if ( ! $collection instanceof Widget_Collection && ! $collection instanceof Field_Collection ) {
			return $rows;
		}

		foreach ( $collection->by_position( $zone . '*' )->all() as $element ) {
			$parts = explode( '::', explode( '_', $element->position, 2 )[1] ?? '', 3 );

			$area = $parts[0] ?? '';
			$type = $parts[1] ?? ( in_array( $area, [ 'left', 'right' ], true ) ? '50/50' : '100' );
			$id   = $parts[2] ?? $type;

			$rows[ $id ] ??= self::get_row_by_type( $type, $id, ! ( $parts[1] ?? false ) );
		}

		return array_values( $rows );
	}

	/**
	 * Prefixes any area's for methods called within the callback.
	 *
	 * @param string        $prefix   The prefix.
	 * @param callable|null $callback The callback
	 *
	 * @return array
	 */
	public static function prefixed( string $prefix, callable $callback ): array {
		self::$area_prefix = $prefix;
		$result            = $callback();
		self::$area_prefix = '';

		return $result;
	}

	/**
	 * Returns all registered row types.
	 *
	 * @since $ver$
	 *
	 * @return array The row types with their configuration.
	 */
	public static function get_row_types(): array {
		$types = [
			'100'      => [
				'1-1' => [
					[
						'areaid'   => 'top',
						'title'    => __( 'Top', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
			],
			'50/50'    => [
				'1-2 left'  => [
					[
						'areaid'   => 'left',
						'title'    => __( 'Left', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
				'1-2 right' => [
					[
						'areaid'   => 'right',
						'title'    => __( 'Right', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
			],
			'33/66'    => [
				'1-3 left'  => [
					[
						'areaid'   => 'left',
						'title'    => __( 'Left', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
				'2-3 right' => [
					[
						'areaid'   => 'right',
						'title'    => __( 'Right', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
			],
			'66/33'    => [
				'2-3 left'  => [
					[
						'areaid'   => 'left',
						'title'    => __( 'Left', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
				'1-3 right' => [
					[
						'areaid'   => 'right',
						'title'    => __( 'Right', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
			],
			'33/33/33' => [
				'1-3 left'   => [
					[
						'areaid'   => 'left',
						'title'    => __( 'Left', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
				'1-3 middle' => [
					[
						'areaid'   => 'middle',
						'title'    => __( 'Middle', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
				'1-3 right'  => [
					[
						'areaid'   => 'right',
						'title'    => __( 'Right', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
			],
			'50/25/25' => [
				'1-2 left'   => [
					[
						'areaid'   => 'left',
						'title'    => __( 'Left', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
				'1-4 middle' => [
					[
						'areaid'   => 'middle',
						'title'    => __( 'Middle', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
				'1-4 right'  => [
					[
						'areaid'   => 'right',
						'title'    => __( 'Right', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
			],
			'25/25/50' => [
				'1-4 left'   => [
					[
						'areaid'   => 'left',
						'title'    => __( 'Left', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
				'1-4 mdidle' => [
					[
						'areaid'   => 'middle',
						'title'    => __( 'Middle', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
				'1-2 right'  => [
					[
						'areaid'   => 'right',
						'title'    => __( 'Right', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
			],
			'25/50/25' => [
				'1-4 left'   => [
					[
						'areaid'   => 'left',
						'title'    => __( 'Left', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
				'1-2 middle' => [
					[
						'areaid'   => 'middle',
						'title'    => __( 'Middle', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
				'1-4 right'  => [
					[
						'areaid'   => 'right',
						'title'    => __( 'Right', 'gk-gravityview' ),
						'subtitle' => '',
					],
				],
			],
		];

		array_walk_recursive(
			$types,
			function ( &$value, $key ) {
				if ( 'areaid' === $key ) {
					$value = ( self::$area_prefix ? self::$area_prefix . '-' : '' ) . $value;
				}
			}
		);

		return $types;
	}
}
