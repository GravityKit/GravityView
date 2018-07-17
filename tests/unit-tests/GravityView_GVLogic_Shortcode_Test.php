<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group shortcode
 * @group gvlogicshortcode
 */
class GravityView_GVLogic_Shortcode_Test extends GV_UnitTestCase {

	/**
	 * Just covers that it renders something
	 * @covers GVLogic_Shortcode::get_instance
	 * @covers GVLogic_Shortcode::add_hooks
	 */
	function test_shortcode() {

		$value = do_shortcode( '[gvlogic]' );

		$this->assertEquals( '', $value );

	}

	/**
	 * @covers GVLogic_Shortcode::shortcode
	 */
	function test_comparisons( $shortcode = 'gvlogic') {

		$correct = array(
			'if="4" is="4"',
			'if="4" equals="4"',
			'if="4" isnot="3"',
			'if="carbon" contains="car"',
			'if="carbon" starts_with="car"',
			'if="carbon" ends_with="bon"',
			'if="4" greater_than="1"',
			'if="4" greater_than_or_is="1"',
			'if="4" greater_than_or_equals="1"',
			'if="1"',
		);

		foreach ( $correct as $i => $true_statement ) {
			$this->assertEquals( 'Correct a' . $i, do_shortcode( '['.$shortcode.' ' . $true_statement .' else="Incorrect a' . $i .'"]Correct a' . $i .'[/'.$shortcode.']') );
			$this->assertEquals( 'Correct b' . $i, do_shortcode( '['.$shortcode.' ' . $true_statement .']Correct b' . $i .'[else]Incorrect b' . $i .'[/'.$shortcode.']') );
		}


		$incorrect = array(
			'if="4" is="2"',
			'if="4" equals="asd"',
			'if="4" isnot="4"',
			'if="carbon" contains="donkey"',
			'if="carbon" starts_with="dandy"',
			'if="carbon" ends_with="lion"',
			'if="4" greater_than="400"',
			'if="4" greater_than_or_is="400"',
			'if="4" greater_than_or_equals="400"',
			'if=""',
		);

		foreach ( $incorrect as $i => $false_statement ) {
			$this->assertEquals( 'Incorrect c' . $i, do_shortcode( '['.$shortcode.' ' . $false_statement .' else="Incorrect c'  . $i . '"]Correct c'  . $i . '[/'.$shortcode.']'), $false_statement );
			$this->assertEquals( 'Incorrect d' . $i, do_shortcode( '['.$shortcode.' ' . $false_statement .']Correct d'  . $i . '[else]Incorrect d'  . $i . '[/'.$shortcode.']'), $false_statement );
		}
	}

	/**
	 *
	 */
	function test_basic_if_else() {

		$value = do_shortcode( '[gvlogic if="4" is="4"]Correct 1[else]Incorrect[/gvlogic]' );

		$this->assertEquals( 'Correct 1', $value );

		$value = do_shortcode( '[gvlogic if="4" is="4" else="Incorrect"]Correct 2[/gvlogic]' );

		$this->assertEquals( 'Correct 2', $value );

		$value = do_shortcode( '[gvlogic if="not empty"]Correct 3[/gvlogic]' );

		$this->assertEquals( 'Correct 3', $value );

		$value = do_shortcode( '[gvlogic if="4" is="5" else="Incorrect 1"]Correct[/gvlogic]' );

		$this->assertEquals( 'Incorrect 1', $value );

		$value = do_shortcode( '[gvlogic if="4" is="5"]Correct[else]Incorrect 2[/gvlogic]' );

		$this->assertEquals( 'Incorrect 2', $value );

		$empty_value = do_shortcode( '[gvlogic if=""]Correct[else]Incorrect 3[/gvlogic]' );

		$this->assertEquals( 'Incorrect 3', $empty_value );

		$empty_value = do_shortcode( '[gvlogic if="4" is="5"]Empty because Incorrect[/gvlogic]' );

		$this->assertEquals( '', $empty_value );

		$empty_value = do_shortcode( '[gvlogic if=""]Empty because Incorrect 2[/gvlogic]' );

		$this->assertEquals( '', $empty_value );

	}

	/**
	 * Make sure our official way of registering a second shortcode actually works
	 *
	 * @since 2.0
	 */
	function test_register_another_gvlogic_shortcode() {

		$GVLogic_Shortcode            = GVLogic_Shortcode::get_instance();
		$GVLogic_Shortcode->shortcode = 'gvlogic2';

		add_shortcode( 'gvlogic2', array( $GVLogic_Shortcode, 'shortcode' ) );

		$this->test_comparisons('gvlogic2' );
	}

	/**
	 * Make sure the shortcode parses shortcodes inside the shortcode...
	 */
	function test_recursive_do_shortcode() {

		add_shortcode('return_correct', function( $atts, $content = '') { return 'Correct' . $content; } );

		// Single correct
		$value = do_shortcode( '[gvlogic if="4" is="5"]Incorrect[else][return_correct]1[/return_correct][/gvlogic]' );

		$this->assertEquals( 'Correct1', $value );

		// Single correct
		$value = do_shortcode( '[gvlogic if="5" is="5"][return_correct]2[/return_correct][else]Incorrect[/gvlogic]' );

		$this->assertEquals( 'Correct2', $value );
	}

	/**
	 * Test the advanced [else if] functionality
	 * @since 1.22.2
	 *
	 * @covers GVLogic_Shortcode::set_content_and_else_content
	 * @covers GVLogic_Shortcode::process_elseif
	 *
	 * @group gvlogicelseif
	 */
	function test_elseif() {

		// Single correct
		$value = do_shortcode( '[gvlogic if="4" is="5"]Test 1 Incorrect[else if="5" is="5"]Test 1 Correct[else]Test 1 Incorrect 2[/gvlogic]' );

		$this->assertEquals( 'Test 1 Correct', $value );

		// Multiple correct elseif should choose first value
		$value = do_shortcode( '[gvlogic if="4" is="5"]Test 2 Incorrect[else if="5" greater_than="1"]Test 2 Correct[else if="6" less_than="100"]Test 2 Incorrect 2[/gvlogic]' );

		$this->assertEquals( 'Test 2 Correct', $value, 'Should choose first value' );

		// Multiple incorrect elseifs, then use [else]
		$value = do_shortcode( '[gvlogic if="1" is="2"]Test 3 Incorrect[else if="2" is="3"]Test 3 Incorrect 2[else if="3" is="4"]Test 3 Incorrect 3[else if="4" is="5"]Test 3 Incorrect 4[else]Test 3 Correct[/gvlogic]' );

		$this->assertEquals( 'Test 3 Correct', $value );

		// Multiple incorrect elseifs, then one that works
		$value = do_shortcode( '[gvlogic if="1" is="2"]Test 4 Incorrect[else if="2" is="3"]Test 4 Incorrect 2[else if="3" is="4"]Test 4 Incorrect 3[else if="4" is="5"]Test 4 Incorrect 4[else if="5" is="5"]Test 4 Correct[else]Test 4 Incorrect 5[/gvlogic]' );

		$this->assertEquals( 'Test 4 Correct', $value );

		// First one is right
		$value = do_shortcode( '[gvlogic if="2" is="2"]Test 5 Correct[else if="2" is="3"]Test 5 Incorrect 2[else if="3" is="4"]Test 5 Incorrect 3[else if="4" is="5"]Test 5 Incorrect 4[else if="5" is="5"]Test 5 Incorrect 5[else]Test 5 Incorrect 6[/gvlogic]' );

		$this->assertEquals( 'Test 5 Correct', $value );

		// Empty comparison
		$value = do_shortcode( '[gvlogic if=""]Test 6 Incorrect[else if="1"]Test 6 Correct[/gvlogic]' );

		$this->assertEquals( 'Test 6 Correct', $value );

		// Empty comparison
		$value = do_shortcode( '[gvlogic if=""]Test 7 Incorrect[else if="1" is="2"]Test 7 Incorrect 2[else if=""]Test 7 Incorrect 3[else if="1" greater_than="0"]Test 7 Correct[/gvlogic]' );

		$this->assertEquals( 'Test 7 Correct', $value );

	}

}
