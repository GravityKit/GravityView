<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\Helpers;

use \GravityKit\GravityView\Foundation\ThirdParty\Illuminate\Support\Arr as IlluminateArr;

/**
 * We use Laravel's Arr class for all array helper methods. This is a wrapper as we may swap out the underlying class in the future, add or modify methods, etc.
 *
 * @since 1.0.0
 *
 * @see   https://github.com/illuminate/support/blob/5.4/Arr.php
 */
class Arr extends IlluminateArr {
	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function accessible( $value ) {
		return parent::accessible( $value );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function add( $array, $key, $value ) {
		return parent::add( $array, $key, $value );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function collapse( $array ) {
		return parent::collapse( $array );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function crossJoin( ...$arrays ) {
		return parent::crossJoin( $arrays );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function divide( $array ) {
		return parent::divide( $array );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function dot( $array, $prepend = '' ) {
		return parent::dot( $array, $prepend );
	}

	/**
	 * Convert a flattened "dot" notation array into an expanded array.
	 *
	 * @since 1.0.3
	 *
	 * @param iterable $array
	 *
	 * @return array
	 */
	public static function undot( $array ) {
		$results = [];

		foreach ( $array as $key => $value ) {
			self::set( $results, $key, $value );
		}

		return $results;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function get( $array, $key, $default = null ) {
		return parent::get( $array, $key, $default );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function except( $array, $keys ) {
		return parent::except( $array, $keys );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function exists( $array, $key ) {
		return parent::exists( $array, $key );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function first( $array, callable $callback = null, $default = null ) {
		return parent::first( $array, $callback, $default );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function flatten( $array, $depth = INF ) {
		return parent::flatten( $array, $depth );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function forget( &$array, $keys ) {
		return parent::forget( $array, $keys );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function has( $array, $keys ) {
		return parent::has( $array, $keys );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function isAssoc( array $array ) {
		return parent::isAssoc( $array );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function last( $array, callable $callback = null, $default = null ) {
		return parent::last( $array, $callback, $default );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function only( $array, $keys ) {
		return parent::only( $array, $keys );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function pluck( $array, $value, $key = null ) {
		return parent::pluck( $array, $value, $key );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function prepend( $array, $value, $key = null ) {
		return parent::prepend( $array, $value, $key );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function pull( &$array, $key, $default = null ) {
		return parent::pull( $array, $key, $default );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function random( $array, $number = null ) {
		return parent::random( $array, $number );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function set( &$array, $key, $value ) {
		return parent::set( $array, $key, $value );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function shuffle( $array ) {
		return parent::shuffle( $array );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function sort( $array, $callback ) {
		return parent::sort( $array, $callback );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function sortRecursive( $array ) {
		return parent::sortRecursive( $array );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function where( $array, callable $callback ) {
		return parent::where( $array, $callback );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function wrap( $value ) {
		return parent::wrap( $value );
	}
}
