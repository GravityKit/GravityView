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
		add_filter( 'gk/gravityview/view/entries/cache', '__return_false' );
		add_filter( 'gravityview_use_cache', '__return_false' );

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'page_size' => 10,
				'show_only_approved' => 0,
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

		remove_all_filters( 'gk/gravityview/view/entries/cache' );
		remove_all_filters( 'gravityview_use_cache' );
	}

	function test_GravityView_Field_Sequence() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'page_size' => 3,
				'show_only_approved' => 0,
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
		add_filter( 'gk/gravityview/view/entries/cache', '__return_false' );
		add_filter( 'gravityview_use_cache', '__return_false' );

		$form = $this->factory->form->import_and_get( 'simple.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'page_size' => 3,
				'show_only_approved' => 0,
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

		remove_all_filters( 'gk/gravityview/view/entries/cache' );
		remove_all_filters( 'gravityview_use_cache' );
	}

	function test_GravityView_Field_Unsubscribe_render_permissions() {
		$administrator = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => 'administrator' )
		);

		$author = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => 'author' )
		);

		$field = \GV\Internal_Field::by_id( 'unsubscribe' )->field;

		wp_set_current_user( 0 );

		$the_field = \GV\Internal_Field::by_id( 'unsubscribe' );

		$this->assertFalse( $field->maybe_not_visible( true, $the_field ) );
		$this->assertEquals( 'sentinel', $field->modify_entry_value_unsubscribe( 'sentinel', null, null, null ) );

		wp_set_current_user( $administrator );

		$this->assertTrue( $field->maybe_not_visible( true, $the_field ) );
		$this->assertFalse( $field->maybe_not_visible( false, $the_field ) );
		$this->assertEquals( 'sentinel', $field->modify_entry_value_unsubscribe( 'sentinel', null, null, null ) );
		$this->assertEquals( 'sentinel', $field->modify_entry_value_unsubscribe( 'sentinel', array( 'created_by' => -1 ), null, null ) );
		$this->assertEquals( 'sentinel', $field->modify_entry_value_unsubscribe( 'sentinel', array( 'created_by' => $administrator ), null, null ) );
		$this->assertEquals( 'sentinel', $field->modify_entry_value_unsubscribe( 'sentinel', array( 'created_by' => $author ), null, null ) );
		$this->assertEquals( 'sentinel', $field->modify_entry_value_unsubscribe( 'sentinel', array( 'created_by' => $author, 'id' => 1 ), array( 'unsub_all' => true ), null ) );
		$this->assertEquals( 'sentinel', $field->modify_entry_value_unsubscribe( 'sentinel', array( 'created_by' => $author, 'id' => 1, 'payment_status' => 'null' ), array( 'unsub_all' => true ), null ) );

		$this->assertStringContainsString( 'Unsubscribe', $field->modify_entry_value_unsubscribe( 'sentinel', array( 'created_by' => $author, 'id' => 1, 'payment_status' => 'active' ), array( 'unsub_all' => true ), null ) );
		$this->assertStringContainsString( 'Unsubscribe', $field->modify_entry_value_unsubscribe( 'sentinel', array( 'created_by' => $administrator, 'id' => 1, 'payment_status' => 'active' ), null, null ) );

		wp_set_current_user( $author );

		$this->assertTrue( $field->maybe_not_visible( true, $the_field ) );
		$this->assertFalse( $field->maybe_not_visible( false, $the_field ) );
		$this->assertEquals( 'sentinel', $field->modify_entry_value_unsubscribe( 'sentinel', null, null, null ) );
		$this->assertEquals( 'sentinel', $field->modify_entry_value_unsubscribe( 'sentinel', array( 'created_by' => -1 ), null, null ) );
		$this->assertEquals( 'sentinel', $field->modify_entry_value_unsubscribe( 'sentinel', array( 'created_by' => $administrator ), null, null ) );
		$this->assertEquals( 'sentinel', $field->modify_entry_value_unsubscribe( 'sentinel', array( 'created_by' => $author ), null, null ) );
		$this->assertEquals( 'sentinel', $field->modify_entry_value_unsubscribe( 'sentinel', array( 'created_by' => $author, 'id' => 1, 'payment_status' => 'null' ), null, null ) );

		$this->assertStringContainsString( 'Unsubscribe', $field->modify_entry_value_unsubscribe( 'sentinel', array( 'created_by' => $author, 'id' => 1, 'payment_status' => 'active' ), null, null ) );

		wp_set_current_user( 0 );
	}

	function test_GravityView_Field_Unsubscribe_unsubscribe_permissions() {
		$form = $this->factory->form->import_and_get( 'simple.json' );

		$administrator = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => 'administrator' )
		);

		$author = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => 'author' )
		);

		$field = \GV\Internal_Field::by_id( 'unsubscribe' )->field;

		wp_set_current_user( $administrator );

		$entry = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $administrator,
			'status' => 'active',
			'payment_status' => 'active',
		) ) );

		$this->assertStringContainsString( 'Unsubscribe', $field->modify_entry_value_unsubscribe( 'sentinel', $entry, null, null ) );

		$entry = \GV\GF_Entry::by_id( $entry['id'] )->as_entry();

		$this->assertEquals( 'active', $entry['payment_status'] );

		$_GET = array(
			'unsubscribe' => wp_create_nonce( 'unsubscribe_' . $entry['id'] ),
		);

		$this->assertStringContainsString( 'Unsubscribe', $field->modify_entry_value_unsubscribe( 'sentinel', $entry, null, null ) );

		$entry = \GV\GF_Entry::by_id( $entry['id'] )->as_entry();

		$this->assertEquals( 'active', $entry['payment_status'] );

		$_GET = $_REQUEST = array(
			'unsubscribe' => wp_create_nonce( 'unsubscribe_' . $entry['id'] ),
			'uid' => $entry['id'],
		);

		$feed_id = \GFAPI::add_feed( $form['id'], array( 'transactionType' => 'subscription' ), 'gf_paymentaddon_test' );

		gform_update_meta( $entry['id'], 'processed_feeds', array( 'gf_paymentaddon_test' => array( $feed_id ) ) );

		$this->assertStringContainsString( 'Cancelled', $field->modify_entry_value_unsubscribe( 'sentinel', $entry, null, null ) );

		$entry = \GV\GF_Entry::by_id( $entry['id'] )->as_entry();

		$this->assertEquals( 'Cancelled', $entry['payment_status'] );

		wp_set_current_user( $author );

		wp_set_current_user( 0 );

		$_GET = $_REQUEST = array();
	}

	/**
	 * Test name field respects isHidden setting for inputs.
	 *
	 * When a name field input (prefix, first, middle, last, suffix) is marked
	 * as hidden in Gravity Forms, it should not appear in the display output.
	 *
	 * @covers templates/fields/field-name-html.php
	 */
	function test_name_field_respects_hidden_inputs() {
		$form_id = $this->factory->form->create( [
			'fields' => [
				GF_Fields::create( [
					'type'   => 'name',
					'id'     => 15,
					'inputs' => [
						[ 'id' => '15.2', 'label' => 'Prefix', 'isHidden' => true ],
						[ 'id' => '15.3', 'label' => 'First', 'isHidden' => true ],
						[ 'id' => '15.4', 'label' => 'Middle', 'isHidden' => true ],
						[ 'id' => '15.6', 'label' => 'Last', 'isHidden' => false ],
						[ 'id' => '15.8', 'label' => 'Suffix', 'isHidden' => true ],
					],
				] ),
			],
		] );

		$form = GFAPI::get_form( $form_id );

		$entry = $this->factory->entry->create_and_get( [
			'form_id' => $form_id,
			'15.2'    => 'Mr.',
			'15.3'    => 'John',
			'15.4'    => 'Middle',
			'15.6'    => 'Doe',
			'15.8'    => 'Jr.',
			'status'  => 'active',
		] );

		// Mock the template context with all name parts.
		$value = [
			'15.2' => 'Mr.',
			'15.3' => 'John',
			'15.4' => 'Middle',
			'15.6' => 'Doe',
			'15.8' => 'Jr.',
		];

		$gf_field = $form['fields'][0];

		// Use anonymous class so as_configuration() is a callable method.
		$field_mock = new class( $gf_field ) {
			public $ID = 15;
			public $field;
			public function __construct( $field ) {
				$this->field = $field;
			}
			public function as_configuration() {
				return [];
			}
		};

		$gravityview = (object) [
			'template'      => 'table',
			'field'         => $field_mock,
			'value'         => $value,
			'display_value' => 'Mr. John Middle Doe Jr.',
			'entry'         => \GV\GF_Entry::from_entry( $entry ),
		];

		ob_start();
		include( GRAVITYVIEW_DIR . 'templates/fields/field-name-html.php' );
		$output = ob_get_clean();

		// Should only show "Doe" (the only non-hidden input).
		$this->assertStringContainsString( 'Doe', $output );
		$this->assertStringNotContainsString( 'Mr.', $output );
		$this->assertStringNotContainsString( 'John', $output );
		$this->assertStringNotContainsString( 'Middle', $output );
		$this->assertStringNotContainsString( 'Jr.', $output );
	}

	/**
	 * Test name field shows all inputs when none are hidden.
	 *
	 * @covers templates/fields/field-name-html.php
	 */
	function test_name_field_shows_all_when_none_hidden() {
		$form_id = $this->factory->form->create( array(
			'fields' => array(
				GF_Fields::create( array(
					'type'   => 'name',
					'id'     => 15,
					'inputs' => array(
						array( 'id' => '15.2', 'label' => 'Prefix', 'isHidden' => false ),
						array( 'id' => '15.3', 'label' => 'First', 'isHidden' => false ),
						array( 'id' => '15.4', 'label' => 'Middle', 'isHidden' => false ),
						array( 'id' => '15.6', 'label' => 'Last', 'isHidden' => false ),
						array( 'id' => '15.8', 'label' => 'Suffix', 'isHidden' => false ),
					),
				) ),
			),
		) );

		$form = GFAPI::get_form( $form_id );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form_id,
			'15.2'    => 'Mr.',
			'15.3'    => 'John',
			'15.4'    => 'Middle',
			'15.6'    => 'Doe',
			'15.8'    => 'Jr.',
			'status'  => 'active',
		) );

		$value = array(
			'15.2' => 'Mr.',
			'15.3' => 'John',
			'15.4' => 'Middle',
			'15.6' => 'Doe',
			'15.8' => 'Jr.',
		);

		$gf_field = $form['fields'][0];

		// Use anonymous class so as_configuration() is a callable method.
		$field_mock = new class( $gf_field ) {
			public $ID = 15;
			public $field;
			public function __construct( $field ) {
				$this->field = $field;
			}
			public function as_configuration() {
				return array();
			}
		};

		$gravityview = (object) array(
			'template'      => 'table',
			'field'         => $field_mock,
			'value'         => $value,
			'display_value' => 'Mr. John Middle Doe Jr.',
			'entry'         => \GV\GF_Entry::from_entry( $entry ),
		);

		ob_start();
		include( GRAVITYVIEW_DIR . 'templates/fields/field-name-html.php' );
		$output = ob_get_clean();

		// Should show all parts.
		$this->assertStringContainsString( 'Mr.', $output );
		$this->assertStringContainsString( 'John', $output );
		$this->assertStringContainsString( 'Middle', $output );
		$this->assertStringContainsString( 'Doe', $output );
		$this->assertStringContainsString( 'Jr.', $output );
	}

	/**
	 * Test time field displays correctly regardless of server/WP timezone differences.
	 *
	 * @covers templates/fields/field-time-html.php
	 */
	function test_time_field_timezone_conversion() {
		// Save original timezone settings.
		$original_wp_timezone = get_option( 'timezone_string' );
		$original_php_timezone = date_default_timezone_get();

		// Set different timezones to simulate the reported issue.
		update_option( 'timezone_string', 'America/New_York' );
		date_default_timezone_set( 'America/Chicago' );

		$form_id = $this->factory->form->create( array(
			'fields' => array(
				GF_Fields::create( array(
					'type' => 'time',
					'id' => 1,
					'timeFormat' => '12',
				) ),
			),
		) );

		$form = GFAPI::get_form( $form_id );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form_id,
			'1' => '11:00 AM',
			'status' => 'active',
		) );

		// Mock the template context.
		$gravityview = (object) array(
			'template' => 'table',
			'field' => (object) array(
				'ID' => 1,
				'field' => $form['fields'][0],
				'date_display' => '',
			),
			'value' => '11:00 AM',
		);

		ob_start();

		include( GRAVITYVIEW_DIR . 'templates/fields/field-time-html.php' );

		$output = ob_get_contents();

		ob_end_clean();

		// Assert displayed time matches stored time (no timezone conversion).
		$this->assertStringContainsString( '11:00', $output );
		$this->assertStringNotContainsString( '3:00', $output );

		// Restore original settings.
		if ( $original_wp_timezone ) {
			update_option( 'timezone_string', $original_wp_timezone );
		} else {
			delete_option( 'timezone_string' );
		}

		date_default_timezone_set( $original_php_timezone );
	}
}

GFForms::include_feed_addon_framework();

class GF_PaymentAddon_Test extends GFFeedAddOn {
	protected $_slug = 'gf_paymentaddon_test';

	public static function get_instance() {
		return new self;
	}

	public function cancel( $entry, $feed ) {
		return true;
	}

	public function cancel_subscription( $entry, $feed ) {
		$entry['payment_status'] = 'Cancelled';
		GFAPI::update_entry( $entry );
		$entry = GFAPI::get_entry( $entry['id'] );
	}
}
GF_PaymentAddon_Test::register( 'GF_PaymentAddon_Test' );
