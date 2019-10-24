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

	/**
	 * Make sure a basic "logged-in" check works
	 */
	function test_gv_shortcode_for_user_id_logged_in() {

		$this->expected_deprecated[] = 'WP_User->id';

		$administrator = $this->factory->user->create( array(
				'user_login' => md5( microtime() ),
				'user_email' => md5( microtime() ) . '@gravityview.tests',
				'role' => 'administrator' )
		);

		wp_set_current_user( 0 );

		// $current_user->id bypasses false, which gets replaced with empty string.
		$this->assertEquals( '0', GFCommon::replace_variables_prepopulate( '{user:id}' ) );

		// $current_user->get("ID") returns false, which gets replaced with empty string.
		$this->assertEquals( '', GFCommon::replace_variables_prepopulate( '{user:ID}' ) );

		$this->assertEquals( 'Logged-Out', do_shortcode( GFCommon::replace_variables_prepopulate( '[gvlogic if="{user:id}" is="0"]Logged-Out[else]Logged-In[/gvlogic]' ) ) );
		$this->assertEquals( 'Logged-Out', do_shortcode( GFCommon::replace_variables_prepopulate( '[gvlogic if="{user:ID}" is=""]Logged-Out[else]Logged-In[/gvlogic]' ) ) );
		$this->assertEquals( 'Logged-Out', do_shortcode( GFCommon::replace_variables_prepopulate( '[gvlogic if="{user:id}" greater_than="0"]Logged-In[else]Logged-Out[/gvlogic]' ) ) );
		$this->assertEquals( 'Logged-Out', do_shortcode( GFCommon::replace_variables_prepopulate( '[gvlogic if="{user:ID}" greater_than="0"]Logged-In[else]Logged-Out[/gvlogic]' ) ) );

		wp_set_current_user( $administrator );

		$this->assertEquals( "{$administrator}", GFCommon::replace_variables_prepopulate( '{user:id}' ) );
		$this->assertEquals( "{$administrator}", GFCommon::replace_variables_prepopulate( '{user:ID}' ) );

		$this->assertEquals( 'Logged-In', do_shortcode( GFCommon::replace_variables_prepopulate( '[gvlogic if="{user:id}" is="0"]Logged-Out[else]Logged-In[/gvlogic]' ) ) );
		$this->assertEquals( 'Logged-In', do_shortcode( GFCommon::replace_variables_prepopulate( '[gvlogic if="{user:ID}" is=""]Logged-Out[else]Logged-In[/gvlogic]' ) ) );
		$this->assertEquals( 'Logged-In', do_shortcode( GFCommon::replace_variables_prepopulate( '[gvlogic if="{user:id}" greater_than="0"]Logged-In[else]Logged-Out[/gvlogic]' ) ) );
		$this->assertEquals( 'Logged-In', do_shortcode( GFCommon::replace_variables_prepopulate( '[gvlogic if="{user:ID}" greater_than="0"]Logged-In[else]Logged-Out[/gvlogic]' ) ) );
	}

	/**
	 * @dataProvider get_test_gv_shortcode_date_comparison
	 */
	function test_gv_shortcode_date_comparison( $date1, $date2, $op, $result ) {
		$form_id = \GFAPI::add_form( array(
			'title'  => __FUNCTION__,
			'fields' => array(
				array( 'id' => 1, 'label' => 'Date 1', 'type'  => 'date' ),
				array( 'id' => 2, 'label' => 'Date 2', 'type'  => 'date' ),
			),
		) );
		$form = \GV\GF_Form::by_id( $form_id );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form_id,
			'template_id' => 'table',
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'content' => 'You are here.',
					),
				),
			)
		) );
		$view = \GV\View::from_post( $post );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form_id,
			'status' => 'active',
			'1' => $date1,
			'2' => $date2,
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$renderer = new \GV\Field_Renderer();
		$field = \GV\Internal_Field::by_id( 'custom' );

		$field->content = sprintf( '[gvlogic if="{Date Field:1}" %s="{Date Field 2:2}"]CORRECT[/gvlogic]', $op );
		$this->assertEquals( $result ? 'CORRECT' : '', $renderer->render( $field, $view, null, $entry ) );
	}

	function get_test_gv_shortcode_date_comparison() {
		return array(
			array( '2019-01-13', '2019-01-13', 'equals', true ),
			array( '2019-01-14', '2019-01-13', 'equals', false ),
			array( '2019-01-14', '2019-01-13', 'isnot', true ),
			array( '2019-01-14', '2019-01-14', 'isnot', false ),
			array( '2019-01-11', '2019-01-14', 'greater_than', false ),
			array( '2019-01-11', '2019-01-14', 'less_than', true ),
			array( '2019-01-17', '2019-01-14', 'greater_than_or_is', true ),
			array( '2019-01-17', '2019-01-14', 'less_than_or_is', false ),
		);
	}

	/**
	 * @dataProvider get_test_gv_shortcode_date_comparison_format
	 */
	function test_gv_shortcode_date_comparison_format( $date1, $date2, $op, $result ) {
		$form_id = \GFAPI::add_form( array(
			'title'  => __FUNCTION__,
			'fields' => array(
				array( 'id' => 1, 'label' => 'Date 1', 'type'  => 'date', 'date_format' => 'mdy' ),
				array( 'id' => 2, 'label' => 'Date 2', 'type'  => 'date', 'date_format' => 'ymd_slash' ),
			),
		) );
		$form = \GV\GF_Form::by_id( $form_id );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form_id,
			'template_id' => 'table',
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'content' => 'You are here.',
					),
				),
			)
		) );
		$view = \GV\View::from_post( $post );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form_id,
			'status' => 'active',
			'1' => $date1,
			'2' => $date2,
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$renderer = new \GV\Field_Renderer();
		$field = \GV\Internal_Field::by_id( 'custom' );

		$field->content = sprintf( '[gvlogic if="{Date Field:1}" %s="{Date Field 2:2}"]CORRECT[/gvlogic]', $op );
		$this->assertEquals( $result ? 'CORRECT' : '', $renderer->render( $field, $view, null, $entry ) );
	}

	function get_test_gv_shortcode_date_comparison_format() {
		return array(
			array( '2019-01-13', '2019-01-13', 'equals', true ),
			array( '2019-01-14', '2019-01-13', 'equals', false ),
			array( '2019-01-14', '2019-01-13', 'isnot', true ),
			array( '2019-01-14', '2019-01-14', 'isnot', false ),
			array( '2019-01-11', '2019-01-14', 'greater_than', false ),
			array( '2019-01-11', '2019-01-14', 'less_than', true ),
			array( '2019-01-17', '2019-01-14', 'greater_than_or_is', true ),
			array( '2019-01-17', '2019-01-14', 'less_than_or_is', false ),
		);
	}

	function test_gv_shortcode_loggedin() {
		$form_id = \GFAPI::add_form( array(
			'title'  => __FUNCTION__,
			'fields' => array(
				array( 'id' => 1, 'label' => 'Text', 'type'  => 'text' ),
			),
		) );
		$form = \GV\GF_Form::by_id( $form_id );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form_id,
			'template_id' => 'table',
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'content' => 'You are here.',
					),
				),
			)
		) );
		$view = \GV\View::from_post( $post );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form_id,
			'status' => 'active',
			'1' => 'hello world',
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$administrator = $this->factory->user->create( array(
				'user_login' => md5( microtime() ),
				'user_email' => md5( microtime() ) . '@gravityview.tests',
				'role' => 'administrator' )
		);

		wp_set_current_user( 0 );

		$renderer = new \GV\Field_Renderer();
		$field = \GV\Internal_Field::by_id( 'custom' );

		$field->content = '[gvlogic logged_in="true"]logged in[else]not logged in[/gvlogic]';
		$this->assertEquals( 'not logged in', $renderer->render( $field, $view, null, $entry ) );

		$field->content = '[gvlogic logged_in]logged in[else]not logged in[/gvlogic]';
		$this->assertEquals( 'not logged in', $renderer->render( $field, $view, null, $entry ) );

		wp_set_current_user( $administrator );

		$this->assertEquals( 'logged in', $renderer->render( $field, $view, null, $entry ) );

		$field->content = '[gvlogic logged_in="false"]not logged in[else]logged in[/gvlogic]';
		$this->assertEquals( 'logged in', $renderer->render( $field, $view, null, $entry ) );

		$field->content = '[gvlogic logged_in]not logged in[else]logged in[/gvlogic]';
		$this->assertEquals( 'logged in', $renderer->render( $field, $view, null, $entry ) );

		$field->content = '[gvlogic logged_in]not logged in[else]logged in[/gvlogic]';
		$this->assertEquals( 'logged in', $renderer->render( $field, $view, null, $entry ) );

		$field->content = '[gvlogic logged_in="no"]not logged in[else]logged in[/gvlogic]';
		$this->assertEquals( 'logged in', $renderer->render( $field, $view, null, $entry ) );

		$field->content = '[gvlogic logged_in="true"]logged in[else]not logged in[/gvlogic]';
		$this->assertEquals( 'logged in', $renderer->render( $field, $view, null, $entry ) );

		$field->content = '[gvlogic if="{Example:1}" isnot="hello world"]not passed: {Example:1}[else]passed[/gvlogic]';
		$this->assertEquals( 'passed', $renderer->render( $field, $view, null, $entry ) );

		$field->content = '{Example:1}';
		$this->assertEquals( 'hello world', $renderer->render( $field, $view, null, $entry ) );

		$field->content = '[gvlogic if="{Example:1}" is="hello world"]passed[else]not passed: {Example:1}[/gvlogic]';
		$this->assertEquals( 'passed', $renderer->render( $field, $view, null, $entry ) );

		$field->content = '[gvlogic if="{Text:1}" is="hello world" logged_in="false"]not logged in or not hello world[else]logged in and hello world[/gvlogic]';
		$this->assertEquals( 'logged in and hello world', $renderer->render( $field, $view, null, $entry ) );

		$field->content = '[gvlogic if="{example:1}" is="hello world" logged_in="1"]logged in and hello world[else]not logged in or not hello world[/gvlogic]';
		$this->assertEquals( 'logged in and hello world', $renderer->render( $field, $view, null, $entry ) );

		$field->content = '[gvlogic if="asdasdasdas" is="hello world" logged_in="1" else="inline else for the win"]logged in and hello world[/gvlogic]';
		$this->assertEquals( 'inline else for the win', $renderer->render( $field, $view, null, $entry ), 'testing inline else' );

		wp_set_current_user( 0 );

		$field->content = '[gvlogic logged_in="true"]logged in[else]not logged in[/gvlogic]';
		$this->assertEquals( 'not logged in', $renderer->render( $field, $view, null, $entry ) );

		$field->content = '[gvlogic if="{example:1}" is="hello world" logged_in="0"]not logged in and hello world[else]logged in or not hello world[/gvlogic]';
		$this->assertEquals( 'not logged in and hello world', $renderer->render( $field, $view, null, $entry ) );

		$field->content = '[gvlogic if="{example:1}" isnot="hello world" logged_in="0"]not logged in and hello world[else]logged in or not hello world[/gvlogic]';
		$this->assertEquals( 'logged in or not hello world', $renderer->render( $field, $view, null, $entry ) );
	}

	/**
	 * https://github.com/gravityview/GravityView/issues/949
	 */
	function test_gv_shortcode_nested_gvlogic2() {
		$GVLogic_Shortcode            = GVLogic_Shortcode::get_instance();
		$GVLogic_Shortcode->shortcode = 'gvlogic2';

		add_shortcode( 'gvlogic2', array( $GVLogic_Shortcode, 'shortcode' ) );

		$value = do_shortcode( sprintf(
			'[gvlogic if="%s" is="MATCH"]Match 1[else][gvlogic2 if="%s" is="MATCH"]Match 2[else]Match 3[/gvlogic2]Show me.[/gvlogic]',
			'MATCH', ''
		) );
		$this->assertEquals( 'Match 1', $value );

		$value = do_shortcode( sprintf(
			'[gvlogic if="%s" is="MATCH"]Match 1[else][gvlogic2 if="%s" is="MATCH"]Match 2[else]Match 3[/gvlogic2]Show me.[/gvlogic]',
			'', 'MATCH'
		) );
		$this->assertEquals( 'Match 2Show me.', $value );

		$value = do_shortcode( sprintf(
			'[gvlogic if="%s" is="MATCH"]Match 1[else][gvlogic2 if="%s" is="MATCH"]Match 2[else]Match 3[/gvlogic2]Show me.[/gvlogic]',
			'', ''
		) );
		$this->assertEquals( '(Before nested) Match 3Show me. (After nested)', $value );

		/** @link https://github.com/gravityview/GravityView/issues/949#issuecomment-546121739 */
		$value = do_shortcode( '[gvlogic if="1" is="1"]1 is 1.[else]Whoops.[/gvlogic]' );
		$this->assertEquals( '1 is 1.', $value );

		$value = do_shortcode( '[gvlogic2 if="2" is="3"]2 is 3.[else]2 is NOT three.[/gvlogic2]' );
		$this->assertEquals( '2 is NOT three.', $value );

		$value = do_shortcode( '[gvlogic2 if="2" is="3"]2 is 3.[else]2 is NOT three.[/gvlogic2]' );
		$this->assertEquals( '2 is NOT three.', $value );

		$value = do_shortcode( '[gvlogic if="1" is="1"]1 is 1. [gvlogic2 if="2" is="3"]2 is 3.[else]2 is NOT three.[/gvlogic2][else]1 isn\'t 1. Weird.[/gvlogic]' );
		$this->assertEquals( '1 is 1. 2 is NOT three.', $value );

	}

}
