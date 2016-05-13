<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group gravityview_image
 */
class GravityView_Image_Test extends GV_UnitTestCase {

	/**
	 * Get a GravityView_Image object with attributes set
	 * 
	 * @return GravityView_Image
	 */
	function get_loaded_image() {
		
		$Image = new GravityView_Image( array(
			'width' => 147,
			'height' => 31,
			'alt' => 'Test Alt',
			'title' => 'Test Title',
			'src' => 'http://l.yimg.com/a/i/yahoo.gif',
			'class' => 'test-class',
			'getimagesize' => true,
			'validate_src' => false
		));
		
		return $Image;
	}
	
	/**
	 * @covers GravityView_Image::__construct
	 */
	function test_construct() {
		
		$Image = $this->get_loaded_image();

		$this->assertEquals( 147, $Image->width );
		$this->assertEquals( 31, $Image->height );
		$this->assertEquals( 'Test Alt', $Image->alt );
		$this->assertEquals( 'Test Title', $Image->title );
		$this->assertEquals( 'http://l.yimg.com/a/i/yahoo.gif', $Image->src );
		$this->assertEquals( 'test-class', $Image->class );
		$this->assertTrue( $Image->getimagesize );
		$this->assertFalse( $Image->validate_src );
	}

	/**
	 * @covers GravityView_Image::html
	 */
	function test_html() {

		$Image = new GravityView_Image();

		$this->assertEquals( '', $Image->html() );
		
		$Image = $this->get_loaded_image();

		$this->assertEquals( '<img src="http://l.yimg.com/a/i/yahoo.gif" width="147" height="31" alt="Test Alt" title="Test Title" class="test-class" />', $Image->html() );
	}

	/**
	 * @covers GravityView_Image::set_image_size()
	 */
	function test_set_image_size() {

		$Image = new GravityView_Image();

		$Image->src = 'http://l.yimg.com/a/i/yahoo.gif';
		$Image->getimagesize = true;
		$Image->set_image_size();
		$this->assertEquals( 147, $Image->width );
		$this->assertEquals( 31, $Image->height );

		// DON'T FETCH IMAGE SIZE
		$Image->getimagesize = false;
		$Image->height = NULL;
		$Image->width = NULL;
		$Image->src = 'http://l.yimg.com/a/i/yahoo.gif';
		$Image->set_image_size();
		$this->assertEquals( 0, $Image->height );
		$this->assertEquals( 0, $Image->width );

	}

	/**
	 * @covers GravityView_Image::validate_image_src
	 */
	function test_validate_image_src() {

		$Image = new GravityView_Image();

		$valid_extensions = $this->get_extensions( 'valid' );
		$invalid_extensions = $this->get_extensions( 'invalid' );

		$path_types = array(
			'filename.%s',
			'/relative/filename.%s',
			'http://example.com/uri/filename.%s',
			'file://example/file/filename.%s',
			'file:///example/unix/filename.%s',
			'file:///c:/WINDOWS/filename.%s'
		);

		$Image->validate_src = false;

		// Invalid and valid should both be true because validation is turned off
		foreach ( $path_types as $path_type ) {
			foreach ( $valid_extensions as $valid_extension ) {
				$Image->src = sprintf( $path_type, $valid_extension );
				$this->assertTrue( $Image->validate_image_src() );
			}
			foreach ( $invalid_extensions as $valid_extension ) {
				$Image->src = sprintf( $path_type, $valid_extension );
				$this->assertTrue( $Image->validate_image_src() );
			}
		}

		$Image->validate_src = true;

		// Now we turn validation on, and only valid should be valid
		foreach ( $path_types as $path_type ) {

			// Valid
			foreach ( $valid_extensions as $valid_extension ) {
				$Image->src = sprintf( $path_type, $valid_extension );
				$this->assertTrue( $Image->validate_image_src() );
			}

			// Not valid
			foreach ( $invalid_extensions as $valid_extension ) {
				$Image->src = sprintf( $path_type, $valid_extension );
				$this->assertFalse( $Image->validate_image_src() );
			}
		}

		// Now we modify what image extensions should be valid to include the other ones
		add_filter( 'gravityview_image_extensions', array( $this, 'get_extensions') );

		$Image->validate_src = true;

		foreach ( $path_types as $path_type ) {
			foreach ( $valid_extensions as $valid_extension ) {
				$Image->src = sprintf( $path_type, $valid_extension );
				$this->assertTrue( $Image->validate_image_src(), $valid_extension );
			}
			foreach ( $invalid_extensions as $valid_extension ) {
				$Image->src = sprintf( $path_type, $valid_extension );
				$this->assertTrue( $Image->validate_image_src(), $valid_extension );
			}
		}

		remove_filter( 'gravityview_image_extensions', array( $this, 'get_extensions') );
	}

	/**
	 * Get the allowed extensions for the image
	 *
	 * @param string $group
	 *
	 * @return array
	 */
	function get_extensions( $group = 'all' ) {

		$valid_extensions = array( 'jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tif', 'tiff', 'ico' );
		$invalid_extensions = array( 'nope', 'wontwork', 'notimage', 'ursilly' );
		$all_extensions = array_merge( $valid_extensions, $invalid_extensions );

		switch ( $group ) {
			case 'valid':
				return $valid_extensions;
			case 'invalid':
				return $invalid_extensions;
			case 'all':
			default:
				return $all_extensions;
		}
	}

}
