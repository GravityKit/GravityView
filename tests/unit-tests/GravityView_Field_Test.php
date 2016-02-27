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

	/**
	 * @covers GravityView_Field_Post_Image::explode_value
	 * @group post_image_field
	 */
	function test_GravityView_Field_Post_Image_explode_value() {

		// it's a private method, make it public
		$method = new ReflectionMethod( 'GravityView_Field_Post_Image', 'explode_value' );
		$method->setAccessible( true );
		$GravityView_Field_Post_Image = new GravityView_Field_Post_Image;


		$complete = 'http://example.com|:|example title|:|example caption|:|example description';
		$expected_complete_array_value = array(
			'url' => 'http://example.com',
			'title' => 'example title',
			'caption' => 'example caption',
			'description' => 'example description',
		);

		$this->assertEquals( $expected_complete_array_value, $method->invoke( $GravityView_Field_Post_Image, $complete ) );

		$missing_url = '|:|example title|:|example caption|:|example description';
		$expected_missing_url_array_value = array(
			'url' => '',
			'title' => 'example title',
			'caption' => 'example caption',
			'description' => 'example description',
		);

		$this->assertEquals( $expected_missing_url_array_value, $method->invoke( $GravityView_Field_Post_Image, $missing_url ) );

		$missing_title = 'http://example.com|:||:|example caption|:|example description';
		$expected_missing_title_array_value = array(
			'url' => 'http://example.com',
			'title' => '',
			'caption' => 'example caption',
			'description' => 'example description',
		);

		$this->assertEquals( $expected_missing_title_array_value, $method->invoke( $GravityView_Field_Post_Image, $missing_title ) );

		$expected_missing_all_but_description_array_value = array(
			'url' => '',
			'title' => '',
			'caption' => '',
			'description' => 'example description',
		);
		$missing_all_but_description = '|:||:||:|example description';
		$this->assertEquals( $expected_missing_all_but_description_array_value, $method->invoke( $GravityView_Field_Post_Image, $missing_all_but_description ) );

		// Test non-imploded strings
		$expected_empty_array = array(
			'url' => '',
			'title' => '',
			'caption' => '',
			'description' => '',
		);
		$this->assertEquals( $expected_empty_array, $method->invoke( $GravityView_Field_Post_Image, 'NOT AN IMPLODED STRING' ) );

		// Test arrays being passed
		$not_valid_array = array( 'ooga' => 'booga' );
		$this->assertEquals( $not_valid_array, $method->invoke( $GravityView_Field_Post_Image, $not_valid_array ) );

		// Test arrays being passed
		$object = new stdClass();
		$object->test = 'asdsad';
		$this->assertEquals( $object, $method->invoke( $GravityView_Field_Post_Image, $object ) );

	}

}
