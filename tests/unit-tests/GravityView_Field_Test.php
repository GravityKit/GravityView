<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group fields
 */
class GravityView_Field_Test extends GV_UnitTestCase {

	/**
	 * @covers GravityView_Field_Transaction_Type::get_content()
	 * @group gvajax
	 */
	function test_Transaction_Type_get_content() {

		$Transaction_Type = GravityView_Fields::get('transaction_type');

		$this->assertEquals( 'One-Time Payment', $Transaction_Type->get_content( 1 ) );

		// Default
		$this->assertEquals( 'One-Time Payment', $Transaction_Type->get_content( 0 ) );
		$this->assertEquals( 'One-Time Payment', $Transaction_Type->get_content( 1004220 ) );

		$this->assertEquals( 'Subscription', $Transaction_Type->get_content( 2 ) );
	}

	/**
	 * @covers GravityView_Field_Is_Fulfilled::get_content()
	 * @group gvajax
	 */
	function test_Is_Fulfilled_get_content() {

		$Is_Fulfilled = new GravityView_Field_Is_Fulfilled;

		$this->assertEquals( 'Not Fulfilled', $Is_Fulfilled->get_content( 0 ) );

		// Default
		$this->assertEquals( 'Not Fulfilled', $Is_Fulfilled->get_content( 1004220 ) );

		$this->assertEquals( 'Fulfilled', $Is_Fulfilled->get_content( 1 ) );
	}

}
