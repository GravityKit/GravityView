<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group helperfunctions
 */
class GravityView_Helper_Functions_Test extends GV_UnitTestCase {

	/**
	 * @since 1.15.1
	 * @covers ::gv_empty()
	 */
	public function test_gv_empty() {

		$this->assertTrue( gv_empty( array() ) );
		$this->assertTrue( gv_empty( false ) );
		$this->assertTrue( gv_empty( null ) );
		$this->assertTrue( gv_empty( new stdClass() ) );
		$this->assertTrue( gv_empty( '' ) );
		$this->assertTrue( gv_empty( @$not_defined ) );
		$this->assertTrue( gv_empty( array( 'a' => '', '', array() ) ) );
		$this->assertFalse( gv_empty( array( 'a' => '34', '', array() ) ) );

		// Test $zero_is_empty
		$this->assertTrue( gv_empty( 0 ) );
		$this->assertTrue( gv_empty( '0' ) );
		$this->assertTrue( gv_empty( floatval( 0 ) ) );
		$this->assertFalse( gv_empty( '0.0' ) );

		$this->assertFalse( gv_empty( 0, false ) );
		$this->assertFalse( gv_empty( '0', false ) );
		$this->assertFalse( gv_empty( floatval( 0 ), false ) );
		$this->assertFalse( gv_empty( '0.0', false ) );

		// Test $allow_string_booleans
		$this->assertTrue( gv_empty( 'false' ) );
		$this->assertTrue( gv_empty( 'no' ) );
		$this->assertFalse( gv_empty( 'false', true, false ) );
		$this->assertFalse( gv_empty( 'no', true, false ) );
		$this->assertFalse( gv_empty( 'true', true, false ) );
		$this->assertFalse( gv_empty( 'yes', true, false ) );

	}

	/**
	 * @covers ::gravityview_is_valid_datetime
	 * @since 1.15.2
	 * @group datetime
	 */
	public function test_gravityview_is_valid_datetime() {

		$falses = array(
			'now',
			'-1 week',
			'gobbily gook',
			'first monday of november 2005',
			'first day of november 2005',
			'2001-01-20 12:29:30',
			'2001-01-40',
		);

		foreach( $falses as $false ) {
			$this->assertFalse( gravityview_is_valid_datetime( $false ), $false );
		}

		// YYYY-MM-DD
		$trues = array(
			'2001-01-20',
			'2051-11-20',
		);

		foreach( $trues as $true ) {
			$this->assertTrue( gravityview_is_valid_datetime( $true ), $true );
		}

		$format_checks = array(
			'01/30/2001',
			'11/30/2051',
		);

		foreach( $format_checks as $format_check ) {

			// Correct format
			$this->assertTrue( gravityview_is_valid_datetime( $format_check, 'm/d/Y' ), $format_check );

			// Wrong format
			$this->assertFalse( gravityview_is_valid_datetime( $format_check, 'm-d-Y' ), $format_check );
			$this->assertFalse( gravityview_is_valid_datetime( $format_check, 'Y-m-d' ), $format_check );
		}
	}

	/**
	 * @covers ::gravityview_strip_whitespace()
	 */
	public function test_gravityview_strip_whitespace() {

		// Pure whitespace gets stripped
		$this->assertEquals( '', gravityview_strip_whitespace( ' ' ) );
		$this->assertEquals( '', gravityview_strip_whitespace( '  ' ) );
		$this->assertEquals( '', gravityview_strip_whitespace( "\t" ) );
		$this->assertEquals( '', gravityview_strip_whitespace( "\t\t" ) );
		$this->assertEquals( '', gravityview_strip_whitespace( "\n" ) );
		$this->assertEquals( '', gravityview_strip_whitespace( "\n\n" ) );
		$this->assertEquals( '', gravityview_strip_whitespace( "\r" ) );
		$this->assertEquals( '', gravityview_strip_whitespace( "\r\r" ) );
		$this->assertEquals( '', gravityview_strip_whitespace( "\r\n\t " ) );

		$this->assertEquals( 'Word', gravityview_strip_whitespace( "\nWord\n" ) );
		$this->assertEquals( 'Word Word', gravityview_strip_whitespace( "Word\nWord\n" ) );
		$this->assertEquals( 'Word Word Word', gravityview_strip_whitespace( "Word\nWord\nWord\n" ) );
		$this->assertEquals( 'Word Word Word', gravityview_strip_whitespace( "Word  Word  Word  " ) );
		$this->assertEquals( 'Word Word Word Word', gravityview_strip_whitespace( "Word\n\tWord\n\tWord\n\tWord" ) );
	}

	/**
	 * @covers ::gravityview_is_not_empty_string
	 */
	public function test_gravityview_is_not_empty_string() {

		$not_empty_strings = array(
			array(),
			true,
			false,
			null,
			0,
		    '0',
			'asdsad',
			' ',
		);

		foreach ( $not_empty_strings as $not_empty_string ) {
			$this->assertTrue( gravityview_is_not_empty_string( $not_empty_string ) );
		}

		// The one true empty string
		$this->assertFalse( gravityview_is_not_empty_string( '' ) );
	}

	/**
	 * We only test gravityview_number_format() without a decimal defined; otherwise it's an alias for number_format_i18n()
	 *
	 * @see number_format_i18n()
	 * @covers ::gravityview_number_format()
	 */
	public function test_gravityview_number_format() {

		$numbers = array(
			'0' => '1000',
			'1' => '1000.0',
			'2' => '1000.00',
			'7' => '1000000.0000000',
			'17' => '1.00000000000000000',
		);

		foreach( $numbers as $expected_decimals => $number ) {
			$this->assertEquals( number_format_i18n( $number, $expected_decimals ), gravityview_number_format( $number ) );
		}

	}

	/**
	 * @since 2.1
	 * @covers ::gravityview_get_floaty()
	 */
	public function test_gravityview_get_floaty() {
		$this->assertStringContainsString( 'src="' . esc_url( plugins_url( 'assets/images/astronaut-200x263.png', GRAVITYVIEW_FILE ) ) . '"', gravityview_get_floaty( 100 ) );
		$this->assertStringContainsString( 'height="100"', gravityview_get_floaty( 100 ) );
		$this->assertStringContainsString( 'width="75.86"', gravityview_get_floaty( 100 ) );
		$this->assertStringContainsString( 'class="alignleft"', gravityview_get_floaty( 100 ) );
		$this->assertStringContainsString( 'class="testing-custom-class"', gravityview_get_floaty( 100, 'testing-custom-class' ) );
		$this->assertStringContainsString( 'class="scriptNot Safescript"', gravityview_get_floaty( 100, '"><script>Not Safe!</script>"' ) );
		$this->assertStringContainsString( 'height="3"', gravityview_get_floaty( pi() ), 'height should have been converted to int' );
	}

	/**
	 * @since 1.16.4
	 * @covers ::gravityview_get_input_id_from_id()
	 */
	public function test_gravityview_get_input_id_from_id() {

		$tests = array(
			'1' => 0,
			'1.0' => 0,
			'1.10' => 10,
			'12.2' => 2,
			'12.02' => 2, // Shouldn't happen
			'871.57' => 57,
			'asdasdsd' => false, // non-numeric is false
		);

		foreach ( $tests as $field_id => $expected ) {
			$formatted = gravityview_get_input_id_from_id( $field_id );
			$this->assertEquals( $expected, $formatted );
		}

		$this->assertEquals( 0, gravityview_get_input_id_from_id( 38 ), 'integer value' );
		$this->assertEquals( 12, gravityview_get_input_id_from_id( 38.12 ), 'float value' );
	}

	/**
	 * @covers ::gravityview_sanitize_html_class()
	 */
	public function test_gravityview_sanitize_html_class() {

		$classes = array(

			// basic
			'example' => gravityview_sanitize_html_class( 'example' ),

			// Don't strip dashes
			'example-dash' => gravityview_sanitize_html_class( 'example-dash' ),

			// Keep spaces
			'example dash' => gravityview_sanitize_html_class( 'example dash' ),

			// Strip whitespace string
			'foo cocktail' => gravityview_sanitize_html_class( '   foo   cocktail   ' ),

			// Implode with spaces
			'example dash bar' => gravityview_sanitize_html_class( array( 'example', 'dash', 'bar' ) ),

			// Again, don't strip spaces and implode
			'example-dash bar' => gravityview_sanitize_html_class( array( 'example-dash', 'bar' ) ),

			// Don't strip numbers or caps
			'Foo Bar0' => gravityview_sanitize_html_class( array( 'Foo', 'Bar0' ) ),

			// Strip whitespace
			'foo bar' => gravityview_sanitize_html_class( array( 'foo    ', '           bar       ' ) ),

			// Strip not A-Z a-z 0-9 _ -
			'Foo Bar2_-' => gravityview_sanitize_html_class( 'Foo Bar2!_-' ),
		);

		foreach ( $classes as $expected => $formatted ) {
			$this->assertEquals( $expected, $formatted );
		}

	}

	/**
	 * @covers ::gravityview_format_link()
	 * @covers ::_gravityview_strip_subdomain()
	 */
	public function test_gravityview_format_link_DEFAULT() {

		$urls = array(

			// NOT URL
			'asdsadas' => 'asdsadas',

			// Path to root directory
			'http://example.com/example/' => 'example.com',
			'http://example.com/example/1/2/3/4/5/6/7/?example=123' => 'example.com',
			'https://example.com/example/page.html' => 'example.com',

			// No WWW
			'http://example.com' => 'example.com', // http
			'https://example.com' => 'example.com', // https
			'https://example.com/' => 'example.com', // trailing slash
			'https://example.com?example=123' => 'example.com', // no slash qv
			'https://example.com/?example=123' => 'example.com', // trailing slash qv

			// strip WWW
			'http://www.example.com' => 'example.com', // http
			'https://www.example.com' => 'example.com', // https
			'https://www.example.com/' => 'example.com', // trailing slash
			'https://www.example.com?example=123' => 'example.com', // no slash qv
			'https://www.example.com/?example=123' => 'example.com', // trailing slash qv
			'https://www.example.com/?example=123&test<0>=123' => 'example.com', // complex qv

			// strip subdomain
			'http://demo.example.com' => 'example.com', // http
			'https://demo.example.com' => 'example.com', // https
			'https://demo.example.com/' => 'example.com', // trailing slash
			'https://demo.example.com?example=123' => 'example.com', // no slash qv
			'https://demo.example.com/?example=123' => 'example.com', // trailing slash qv

			// Don't strip actual domain when using 2nd tier TLD
			'http://example.ac.za' => 'example.ac.za',
			'http://example.gov.za' => 'example.gov.za',
			'http://example.law.za' => 'example.law.za',
			'http://example.school.za' => 'example.school.za',
			'http://example.me.uk' => 'example.me.uk',
			'http://example.tm.fr' => 'example.tm.fr',
			'http://example.asso.fr' => 'example.asso.fr',
			'http://example.com.fr' => 'example.com.fr',
			'http://example.telememo.au' => 'example.telememo.au',
			'http://example.cg.yu' => 'example.cg.yu',
			'http://example.msk.ru' => 'example.msk.ru',
			'http://example.irkutsks.ru' => 'example.irkutsks.ru',
			'http://example.com.ru' => 'example.com.ru',
			'http://example.sa.au' => 'example.sa.au',
			'http://example.act.au' => 'example.act.au',
			'http://example.net.uk' => 'example.net.uk',
			'http://example.police.uk' => 'example.police.uk',
			'http://example.plc.uk' => 'example.plc.uk',
			'http://example.co.uk' => 'example.co.uk',
			'http://example.gov.uk' => 'example.gov.uk',
			'http://example.mod.uk' => 'example.mod.uk',

			// Strip subdomains in 2nd tier TLD
			'http://demo.example.ac.za' => 'example.ac.za',
			'http://demo.example.gov.za' => 'example.gov.za',
			'http://demo.example.law.za' => 'example.law.za',
			'http://demo.example.school.za' => 'example.school.za',
			'http://demo.example.me.uk' => 'example.me.uk',
			'http://demo.example.tm.fr' => 'example.tm.fr',
			'http://demo.example.asso.fr' => 'example.asso.fr',
			'http://demo.example.com.fr' => 'example.com.fr',
			'http://demo.example.telememo.au' => 'example.telememo.au',
			'http://demo.example.cg.yu' => 'example.cg.yu',
			'http://demo.example.msk.ru' => 'example.msk.ru',
			'http://demo.example.irkutsks.ru' => 'example.irkutsks.ru',
			'http://demo.example.com.ru' => 'example.com.ru',
			'http://demo.example.sa.au' => 'example.sa.au',
			'http://demo.example.act.au' => 'example.act.au',
			'http://demo.example.net.uk' => 'example.net.uk',
			'http://demo.example.police.uk' => 'example.police.uk',
			'http://demo.example.plc.uk' => 'example.plc.uk',
			'http://demo.example.co.uk' => 'example.co.uk',
			'http://demo.example.gov.uk' => 'example.gov.uk',
			'http://demo.example.mod.uk' => 'example.mod.uk',
		);

		foreach ( $urls as $original => $expected ) {

			$formatted = gravityview_format_link( $original );

			$this->assertEquals( $expected, $formatted, 'Failed the formatting test' );

		}

	}

	/**
	 * @covers ::gravityview_format_link()
	 */
	public function test_gravityview_format_link_WHEN_FILTER_ROOTONLY_FALSE() {

		// SET FILTER TO FALSE
		add_filter( 'gravityview_anchor_text_rootonly', '__return_false' );

		$urls = array(

			// DO NOT strip subdomains in 2nd tier TLD
			'http://example.com/path/to/webpage' => 'example.com/path/to/webpage',
			'http://example.com/path/to/webpage/' => 'example.com/path/to/webpage/',
			'http://example.com/webpage/?aasdasd=asdasd&asdasdasd=484ignasf' => 'example.com/webpage/',
			'http://example.com/webpage.html' => 'example.com/webpage.html',
		);

		foreach ( $urls as $original => $expected ) {

			$formatted = gravityview_format_link( $original );

			$this->assertEquals( $expected, $formatted, 'Failed the formatting test' );

		}

		// RETURN FILTER TO TRUE
		add_filter( 'gravityview_anchor_text_rootonly', '__return_true' );

	}

	/**
	 * @covers ::gravityview_format_link()
	 */
	public function test_gravityview_format_link_WHEN_FILTER_NOSUBDOMAIN_FALSE() {

		// SET FILTER TO FALSE
		add_filter( 'gravityview_anchor_text_nosubdomain', '__return_false' );

		$urls = array(

			// DO NOT strip subdomains in 2nd tier TLD
			'http://demo.example.ac.za' => 'demo.example.ac.za',
			'http://demo.example.gov.za' => 'demo.example.gov.za',
			'http://demo.example.law.za' => 'demo.example.law.za',
			'http://demo.example.school.za' => 'demo.example.school.za',
			'http://demo.example.me.uk' => 'demo.example.me.uk',
			'http://demo.example.tm.fr' => 'demo.example.tm.fr',
			'http://demo.example.asso.fr' => 'demo.example.asso.fr',
			'http://demo.example.com.fr' => 'demo.example.com.fr',
			'http://demo.example.telememo.au' => 'demo.example.telememo.au',
			'http://demo.example.cg.yu' => 'demo.example.cg.yu',
			'http://demo.example.msk.ru' => 'demo.example.msk.ru',
			'http://demo.example.irkutsks.ru' => 'demo.example.irkutsks.ru',
			'http://demo.example.com.ru' => 'demo.example.com.ru',
			'http://demo.example.sa.au' => 'demo.example.sa.au',
			'http://demo.example.act.au' => 'demo.example.act.au',
			'http://demo.example.net.uk' => 'demo.example.net.uk',
			'http://demo.example.police.uk' => 'demo.example.police.uk',
			'http://demo.example.plc.uk' => 'demo.example.plc.uk',
			'http://demo.example.co.uk' => 'demo.example.co.uk',
			'http://demo.example.gov.uk' => 'demo.example.gov.uk',
			'http://demo.example.mod.uk' => 'demo.example.mod.uk',
		);

		foreach ( $urls as $original => $expected ) {

			$formatted = gravityview_format_link( $original );

			$this->assertEquals( $expected, $formatted, 'Failed the formatting test' );

		}

		// RETURN FILTER TO TRUE
		add_filter( 'gravityview_anchor_text_nosubdomain', '__return_true' );

	}

	/**
	 * @covers ::gravityview_format_link()
	 */
	public function test_gravityview_format_link_WHEN_FILTER_NOQUERYSTRING_FALSE() {

		// SET FILTER TO FALSE
		add_filter( 'gravityview_anchor_text_noquerystring', '__return_false' );

		$urls = array(

			// NOT URL
			'asdsadas' => 'asdsadas',

			// No WWW
			'https://example.com?example=123' => 'example.com?example=123', // no slash qv
			'https://example.com/?example=123' => 'example.com?example=123', // trailing slash qv

			// strip WWW
			'https://www.example.com?example=123' => 'example.com?example=123', // no slash qv
			'https://www.example.com/?example=123' => 'example.com?example=123', // trailing slash qv

			// no subdomain
			'https://demo.example.com?example=123' => 'example.com?example=123', // no slash qv
			'https://demo.example.com/?example=123' => 'example.com?example=123', // trailing slash qv
		);

		foreach ( $urls as $original => $expected ) {

			$formatted = gravityview_format_link( $original );

			$this->assertEquals( $expected, $formatted, 'Failed the formatting test' );

		}

		// RETURN FILTER TO TRUE
		add_filter( 'gravityview_anchor_text_noquerystring', '__return_true' );
	}

	/**
	 * @covers \GV\Utils::strip_excel_formulas()
	 * @since 2.1
	 */
	public function test_gravityview_strip_excel_formulas() {

		$this->assertEquals( 'No equals', \GV\Utils::strip_excel_formulas( 'No equals' ) );

		$this->assertEquals( "Equals in the = Middle", \GV\Utils::strip_excel_formulas( 'Equals in the = Middle' ) );

		$this->assertEquals( "'=Equals", \GV\Utils::strip_excel_formulas( '=Equals' ) );

	}

	/**
	 * @covers \gv_map_deep()
	 * @since 2.29.0
	 */
	public function test_gv_map_deep() {
		$test_data = [
			'scalar_string'   => 'hello world',
			'scalar_int'      => 42,
			'scalar_float'    => 3.14,
			'scalar_bool'     => true,
			'null_value'      => null,
			'simple_array'    => [ 'apple', 'banana', null, 99 ],
			'nested_array'    => [ 'fruits' => [ 'cherry', 'date' ], 'numbers' => [ 1, 2, null ] ],
			'simple_object'   => (object) [ 'name' => 'John', 'age' => 30, 'city' => null ],
			'nested_object'   => (object) [
				'person'   => (object) [ 'name' => 'Jane', 'details' => [ 'hobby' => 'reading', 'pet' => null ] ],
				'location' => 'New York'
			],
			'mixed_structure' => [
				'array'  => [ 'nested' => 'array', 'null' => null ],
				'object' => (object) [ 'prop' => 'value', 'null_prop' => null ],
				'scalar' => 'mixed',
				'null'   => null
			]
		];

		// Test with strtoupper (safe callback)
		$resultUpper = gv_map_deep( $test_data, 'strtoupper' );

		$this->assertEquals( 'HELLO WORLD', $resultUpper['scalar_string'] );
		$this->assertEquals( 42, $resultUpper['scalar_int'] );  // No change
		$this->assertEquals( 3.14, $resultUpper['scalar_float'] );  // No change
		$this->assertEquals( true, $resultUpper['scalar_bool'] );  // No change
		$this->assertEquals( '', $resultUpper['null_value'] );
		$this->assertEquals( [ 'APPLE', 'BANANA', null, 99 ], $resultUpper['simple_array'] );
		$this->assertEquals( [
			'fruits'  => [ 'CHERRY', 'DATE' ],
			'numbers' => [ 1, 2, null ]
		], $resultUpper['nested_array'] );
		$this->assertEquals( (object) [
			'name' => 'JOHN',
			'age'  => 30,
			'city' => null
		], $resultUpper['simple_object'] );
		$this->assertEquals(
			(object) [
				'person'   => (object) [ 'name' => 'JANE', 'details' => [ 'hobby' => 'READING', 'pet' => null ] ],
				'location' => 'NEW YORK'
			],
			$resultUpper['nested_object']
		);
		$this->assertEquals(
			[
				'array'  => [ 'nested' => 'ARRAY', 'null' => null ],
				'object' => (object) [ 'prop' => 'VALUE', 'null_prop' => null ],
				'scalar' => 'MIXED',
				'null'   => null
			],
			$resultUpper['mixed_structure']
		);

		// Test with rawurlencode (unsafe callback)
		$resultEncoded = gv_map_deep( $test_data, 'rawurlencode' );

		$this->assertEquals( 'hello%20world', $resultEncoded['scalar_string'] );
		$this->assertEquals( 42, $resultEncoded['scalar_int'] );  // No change
		$this->assertEquals( 3.14, $resultEncoded['scalar_float'] );  // No change
		$this->assertEquals( true, $resultEncoded['scalar_bool'] );  // No change
		$this->assertNull( $resultEncoded['null_value'] );
		$this->assertEquals( [ 'apple', 'banana', null, 99 ], $resultEncoded['simple_array'] );
		$this->assertEquals( [
			'fruits'  => [ 'cherry', 'date' ],
			'numbers' => [ 1, 2, null ]
		], $resultEncoded['nested_array'] );
		$this->assertEquals( (object) [
			'name' => 'John',
			'age'  => 30,
			'city' => null
		], $resultEncoded['simple_object'] );
		$this->assertEquals(
			(object) [
				'person'   => (object) [ 'name' => 'Jane', 'details' => [ 'hobby' => 'reading', 'pet' => null ] ],
				'location' => 'New%20York'
			],
			$resultEncoded['nested_object']
		);
		$this->assertEquals(
			[
				'array'  => [ 'nested' => 'array', 'null' => null ],
				'object' => (object) [ 'prop' => 'value', 'null_prop' => null ],
				'scalar' => 'mixed',
				'null'   => null
			],
			$resultEncoded['mixed_structure']
		);

		// Test with custom callback
		$customCallback = function ( $value ) {
			return is_string( $value ) ? strrev( $value ) : $value;
		};

		$resultCustom   = gv_map_deep( $test_data, $customCallback );

		$this->assertEquals( 'dlrow olleh', $resultCustom['scalar_string'] );
		$this->assertEquals( 42, $resultCustom['scalar_int'] );
		$this->assertEquals( 3.14, $resultCustom['scalar_float'] );
		$this->assertTrue( $resultCustom['scalar_bool'] );
		$this->assertNull( $resultCustom['null_value'] );
		$this->assertEquals( [ 'elppa', 'ananab', null, 99 ], $resultCustom['simple_array'] );
		$this->assertEquals( [
			'fruits'  => [ 'yrrehc', 'etad' ],
			'numbers' => [ 1, 2, null ]
		], $resultCustom['nested_array'] );
		$this->assertEquals( (object) [
			'name' => 'nhoJ',
			'age'  => 30,
			'city' => null
		], $resultCustom['simple_object'] );
		$this->assertEquals(
			(object) [
				'person'   => (object) [ 'name' => 'enaJ', 'details' => [ 'hobby' => 'gnidaer', 'pet' => null ] ],
				'location' => 'kroY weN'
			],
			$resultCustom['nested_object']
		);
		$this->assertEquals(
			[
				'array'  => [ 'nested' => 'yarra', 'null' => null ],
				'object' => (object) [ 'prop' => 'eulav', 'null_prop' => null ],
				'scalar' => 'dexim',
				'null'   => null
			],
			$resultCustom['mixed_structure']
		);
	}

	/**
	 * @covers ::gv_current_shortcode_tag
	 *
	 * @since 2.45.1
	 *
	 * @group shortcode
	 */
	public function test_gv_current_shortcode_tag() {
		// Test initial state - should return null.
		$this->assertNull( gv_current_shortcode_tag() );
		$this->assertNull( gv_current_shortcode_tag( 'top' ) );

		// Test pushing tags onto the stack.
		$result = gv_current_shortcode_tag( 'push', 'gravityview' );
		$this->assertEquals( 'gravityview', $result );
		$this->assertEquals( 'gravityview', gv_current_shortcode_tag() );

		// Test nested shortcodes.
		$result = gv_current_shortcode_tag( 'push', 'gv_entry_link' );
		$this->assertEquals( 'gv_entry_link', $result );
		$this->assertEquals( 'gv_entry_link', gv_current_shortcode_tag() );

		// Test popping from the stack.
		$result = gv_current_shortcode_tag( 'pop' );
		$this->assertEquals( 'gravityview', $result );
		$this->assertEquals( 'gravityview', gv_current_shortcode_tag() );

		// Test popping the last item.
		$result = gv_current_shortcode_tag( 'pop' );
		$this->assertNull( $result );
		$this->assertNull( gv_current_shortcode_tag() );

		// Test invalid operations.
		$this->assertNull( gv_current_shortcode_tag( 'invalid' ) );
		$this->assertNull( gv_current_shortcode_tag( '' ) );

		// Test pushing invalid tags.
		$this->assertNull( gv_current_shortcode_tag( 'push', '' ) );
		$this->assertNull( gv_current_shortcode_tag( 'push', null ) );
		$this->assertNull( gv_current_shortcode_tag( 'push', 123 ) );

		// Test WP shortcode filter integration.
		if ( function_exists( 'do_shortcode' ) ) {
			add_shortcode( 'test_shortcode', function ( $atts ) {
				return 'Current: ' . ( gv_current_shortcode_tag() ?: 'none' );
			} );

			// Test that the shortcode stack works during shortcode execution.
			$result = do_shortcode( '[test_shortcode]' );
			$this->assertEquals( 'Current: test_shortcode', $result );

			// Test nested shortcodes.
			add_shortcode( 'outer_shortcode', function ( $atts ) {
				$outer        = gv_current_shortcode_tag();
				$inner_result = do_shortcode( '[test_shortcode]' );
				$outer_after  = gv_current_shortcode_tag();

				return "Outer: $outer, Inner result: $inner_result, Outer after: $outer_after";
			} );

			$result = do_shortcode( '[outer_shortcode]' );
			$this->assertEquals( 'Outer: outer_shortcode, Inner result: Current: test_shortcode, Outer after: outer_shortcode', $result );

			// Clean up test shortcodes.
			remove_shortcode( 'test_shortcode' );
			remove_shortcode( 'outer_shortcode' );
		}

		// Ensure stack is clean after tests.
		$this->assertNull( gv_current_shortcode_tag() );
	}
}
