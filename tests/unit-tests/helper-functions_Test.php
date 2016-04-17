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
		$this->assertTrue( gv_empty( $not_defined ) );

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
			'0' => '1,000',
			'1' => '1,000.0',
			'2' => '1,000.00',
			'7' => '1,000,000.0000000',
			'17' => '1.00000000000000000',
		);

		foreach( $numbers as $expected_decimals => $number ) {
			$this->assertEquals( number_format_i18n( $number, $expected_decimals ), gravityview_number_format( $number ) );
		}

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
}
