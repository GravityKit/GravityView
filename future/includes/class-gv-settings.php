<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * A generic Settings base class.
 */
class Settings {
	/**
	 * @var array Main storage for key-avlues in this collection.
	 */
	private $settings = array();

	/**
	 * Set a setting.
	 *
	 * @param mixed $key The key the value should be added under.
	 * @param mixed $value The value to be added to the key.
	 *
	 * @api
	 * @since future
	 * @return void
	 */
	public function set( $key, $value ) {
		$this->settings[$key] = $value;
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
	public function get( $key, $default = null ) {
		return isset( $this->settings[$key] ) ? $this->settings[$key] : $default;
	}

	/**
	 * Returns all the objects in this collection as an an array.
	 *
	 * @api
	 * @since future
	 * @return array The objects in this collection.
	 */
	public function all() {
		return $this->settings;
	}
}
