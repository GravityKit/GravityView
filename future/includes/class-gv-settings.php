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
	 * @var array Main storage for key-values in this collection.
	 */
	private $settings = array();

	/**
	 * Create with new.
	 *
	 * @api
	 * @since 2.0
	 *
	 * @param array $settings Initial settings. Default: none.
	 * @return \GV\Settings
	 */
	public function __construct( $settings = array() ) {
		if ( is_array( $settings ) && ! empty( $settings ) ) {
			$this->update( $settings );
		}
	}

	/**
	 * Mass update values from the allowed ones.
	 *
	 * @api
	 * @since 2.0
	 *
	 * @param array $settings An array of settings to update.
	 * @return \GV\Settings self chain.
	 */
	public function update( $settings ) {
		foreach ( $settings as $key => $value ) {
			$this->set( $key, $value );
		}
		return $this;
	}

	/**
	 * Set a setting.
	 *
	 * @param mixed $key The key the value should be added under.
	 * @param mixed $value The value to be added to the key.
	 *
	 * @api
	 * @since 2.0
	 * @return void
	 */
	public function set( $key, $value ) {
		$this->settings[ $key ] = $value;
	}

	/**
	 * Set an setting.
	 *
	 * @param mixed $key The key in this setting to retrieve.
	 * @param mixed $default A default in case the key is not set.
	 *
	 * @api
	 * @since 2.0
	 * @return mixed|null
	 */
	public function get( $key, $default = null ) {
		return Utils::get( $this->settings, $key, $default );
	}

	/**
	 * Returns all the objects in this collection as an array.
	 *
	 * @api
	 * @since 2.0
	 * @return array The objects in this collection.
	 */
	public function all() {
		return $this->settings;
	}
}
