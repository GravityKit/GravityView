<?php
/**
 * @package GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      https://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 */

/**
 * Extend this class to create a GravityView extension that gets updates from GravityView.co
 *
 * @since 1.1
 *
 * @deprecated Use \GV\Extension instead
 *
 * @version 1.1.2 Fixed `/lib/` include path for EDDSL
 */
abstract class GravityView_Extension extends \GV\Extension {
	public function __construct() {
		gravityview()->log->warning( '\GravityView_Extension is deprecated. Inherit from \GV\Extension instead', array( 'data' => $this ) );
		parent::__construct();
	}
}
