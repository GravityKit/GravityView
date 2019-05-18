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

	/**
	 * https://github.com/gravityview/GravityView/issues/1223
	 */
	function test_GravityView_Field_Other_Entries_get_entries() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'page_size' => 10,
			),
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
				),
			),
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'search_bar',
						'search_fields' => '[{"field":"search_all","input":"input_text"}]',
					),
				),
			),
		) );
		$view = \GV\View::from_post( $post );

		$user_1 = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'display_name' => 'John John',
		) );

		$field = \GV\Internal_Field::by_id( 'other_entries' );

		$null_entry = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => 0,
			'status' => 'active',
		) ) );

		$entry = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $user_1,
			'status' => 'active',
		) ) );

		$context = \GV\Template_Context::from_template( array(
			'view' => $view,
			'entry' => $entry,
			'field' => $field,
		) );

		$this->assertEmpty( $field->field->get_entries( $context ) );

		$another_entry = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $user_1,
			'status' => 'active',
		) ) );

		$entries = $field->field->get_entries( $context );
		$this->assertCount( 1, $entries );
		$this->assertEquals( $another_entry->ID, $entries[0]->ID );

		$and_another_entry = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $user_1,
			'status' => 'active',
		) ) );

		$entries = $field->field->get_entries( $context );
		$this->assertCount( 2, $entries );
		$this->assertEquals( $another_entry->ID, $entries[1]->ID );
		$this->assertEquals( $and_another_entry->ID, $entries[0]->ID );

		/**
		 * Filter by date.
		 */
		$valid_date_entry = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $user_1,
			'status' => 'active',
			'date_created' => '1990-12-22 01:02:03',
		) ) );

		$this->assertCount( 3, $field->field->get_entries( $context ) );

		$view->settings->update( array(
			'start_date' => '1990-01-01',
			'end_date' => '1991-01-01',
		) );

		$entries = $field->field->get_entries( $context );
		$this->assertCount( 1, $entries );
		$this->assertEquals( $valid_date_entry->ID, $entries[0]->ID );

		/**
		 * Make sure search doesn't interfere.
		 */
		$_GET['gv_search'] = 'hello';

		$entries = $field->field->get_entries( $context );
		$this->assertCount( 1, $entries );
		$this->assertEquals( $valid_date_entry->ID, $entries[0]->ID );
	}

	function test_GravityView_Field_Sequence() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'page_size' => 3,
			),
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'sequence',
					),
				),
			),
		) );
		$view = \GV\View::from_post( $post );

		$field = \GV\Internal_Field::by_id( 'sequence' );

		$entry_0 = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
		) ) );

		$context = \GV\Template_Context::from_template( array(
			'view' => $view,
			'entry' => $entry_0,
			'field' => $field,
		) );

		$this->assertEquals( 0, $field->field->get_sequence( $context ) );
		$this->assertEquals( 1, $field->field->get_sequence( $context ) );
		$this->assertEquals( 2, $field->field->get_sequence( $context ) );

		$field->UID   = wp_generate_password( 8, false );
		$field->start = 1000;

		$this->assertEquals( 1000, $field->field->get_sequence( $context ) );
		$this->assertEquals( 1001, $field->field->get_sequence( $context ) );
		$this->assertEquals( 1002, $field->field->get_sequence( $context ) );

		$field->start = 1;
		$field->UID   = wp_generate_password( 8, false );

		$_GET['pagenum'] = 3;

		$this->assertEquals( 7, $field->field->get_sequence( $context ) );
		$this->assertEquals( 8, $field->field->get_sequence( $context ) );
		$this->assertEquals( 9, $field->field->get_sequence( $context ) );

		$field->UID   = wp_generate_password( 8, false );
		$_GET['pagenum'] = 0;

		foreach ( range( 1, 10 ) as $_ ) {
			\GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( array(
				'form_id' => $form['id'],
				'status' => 'active',
			) ) );
		}

		$field->reverse = true;

		$this->assertEquals( 11, $field->field->get_sequence( $context ) );
		$this->assertEquals( 10, $field->field->get_sequence( $context ) );
		$this->assertEquals(  9, $field->field->get_sequence( $context ) );

		$field->UID   = wp_generate_password( 8, false );
		$_GET['pagenum'] = 3;

		$this->assertEquals( 5, $field->field->get_sequence( $context ) );
		$this->assertEquals( 4, $field->field->get_sequence( $context ) );
		$this->assertEquals( 3, $field->field->get_sequence( $context ) );

		$_GET         = 0;

		$field->UID   = wp_generate_password( 8, false );
		$field->start = 5;

		$this->assertEquals( 15, $field->field->get_sequence( $context ) );
		$this->assertEquals( 14, $field->field->get_sequence( $context ) );
		$this->assertEquals( 13, $field->field->get_sequence( $context ) );
	}

	function test_GravityView_Field_Sequence_single() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'page_size' => 3,
			),
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'sequence',
					),
				),
			),
		) );
		$view = \GV\View::from_post( $post );

		$field = \GV\Internal_Field::by_id( 'sequence' );

		$entry_0 = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
		) ) );

		$context = \GV\Template_Context::from_template( array(
			'view' => $view,
			'entry' => $entry_0,
			'field' => $field,
			'request' => new \GV\Mock_Request(),
		) );

		$context->request->returns['is_entry'] = $entry_0;

		foreach ( range( 1, 10 ) as $_ ) {
			\GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( array(
				'form_id' => $form['id'],
				'status' => 'active',
			) ) );
		}

		$this->assertEquals( 11, $field->field->get_sequence( $context ) );

		$field->reverse = true;

		$this->assertEquals( 1, $field->field->get_sequence( $context ) );
	}
}
