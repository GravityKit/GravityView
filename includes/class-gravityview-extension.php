<?php
/**
 * @package GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      https://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 */

/**
 * Extend this class to create a GravityView extension that gets updates from gravitykit.com
 *
 * @deprecated Use \GV\Extension instead
 *
 * @TODO Remove once all extensions have been updated to use Foundation.
 */
abstract class GravityView_Extension extends \GV\Extension {
	public function __construct() {
		if ( ! in_array( $this->_author, array( 'GravityView', 'Katz Web Services, Inc.', true ) ) ) {
			gravityview()->log->warning( '\GravityView_Extension is deprecated. Inherit from \GV\Extension instead', array( 'data' => $this ) );
		}
		parent::__construct();
	}
}
