<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * A generic Context base class.
 */
class Context {
	/**
	 * @var string The context identifier, used in filters.
	 */
	private $_identifier = 'generic';

	/**
	 * @var array Context key-value storage.
	 */
	private $_context = array();

	/**
	 * Set a key to a value.
	 *
	 * @param mixed $key The key the value should be added under.
	 * @param mixed $value The value to be added to the key.
	 *
	 * @api
	 * @since future
	 * @return void
	 */
	public function __set( $key, $value ) {
		$this->_context[$key] = $value;
	}

	/**
	 * Set an setting.
	 *
	 * @param mixed $key The key in this setting to retrieve.
	 * @param mixed $default A default in case the key is not set. 
	 *
	 * @api
	 * @since future
	 * @return mixed|null
	 */
	public function __get( $key ) {
		return isset( $this->_context[$key] ) ? $this->_context[$key] : null;
	}
}

/** Load implementations. */
require gravityview()->plugin->dir( 'future/includes/class-gv-context-field-value.php' );
