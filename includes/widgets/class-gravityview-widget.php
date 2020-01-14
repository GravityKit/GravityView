<?php
/**
 * Main GravityView widget class
 *
 * @deprecated Use \GV\Widget instead
 */
class GravityView_Widget extends \GV\Widget {

	/**
	 * GravityView_Widget constructor.
	 */
	public function __construct( $label, $id, $defaults = array(), $settings = array() ) {
		return parent::__construct( $label, $id, $defaults, $settings );
	}
}
