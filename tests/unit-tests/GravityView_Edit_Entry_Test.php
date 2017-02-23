<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group editentry
 */
class GravityView_Edit_Entry_Test extends GV_UnitTestCase {

	/**
	 * @var int
	 */
	var $form_id = 0;

	/**
	 * @var array GF Form array
	 */
	var $form = array();

	/**
	 * @var int
	 */
	var $entry_id = 0;

	/**
	 * @var array GF Entry array
	 */
	var $entry = array();

	var $is_set_up = false;

	/**
	 * @covers GravityView_Edit_Entry::getInstance()
	 */
	function test_getInstance() {
		$this->assertTrue( GravityView_Edit_Entry::getInstance() instanceof GravityView_Edit_Entry );
	}

	/**
	 * @covers GravityView_Edit_Entry::get_edit_link()
	 */
	function test_get_edit_link() {

		$form = $this->factory->form->create_and_get();

		$editor = $this->factory->user->create_and_set( array(
			'user_login' => 'editor',
			'role' => 'editor'
		) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $editor->ID,
		) );

		$view = $this->factory->view->create_and_get(array(
			'form_id' => $form['id'],
			'settings' => array(
				'user_edit' => 1
			),
		));

		$this->assertNotEmpty( $view, 'There was an error creating the View' );

		$post_title_sequence = new WP_UnitTest_Generator_Sequence( __METHOD__ . ' %s' );
		$post_id = $this->factory->post->create(array(
			'post_title' => $post_title_sequence->next(),
			'post_content' => sprintf( '[gravityview id="%d"]', $view->ID ),
		));

		$nonce_key = GravityView_Edit_Entry::get_nonce_key( $view->ID, $entry['form_id'], $entry['id']  );

		$nonce = wp_create_nonce( $nonce_key );

		###
		### NO POST
		###
		$edit_link_no_post = GravityView_Edit_Entry::get_edit_link( $entry, $view->ID );

		// A link to the raw
		$this->assertEquals( '?page=gf_entries&view=entry&edit='.$nonce, $edit_link_no_post );

		$args = array(
			'p' => $post_id,
			'entry' => $entry['id'],
			'page' => 'gf_entries',
			'view' => 'entry',
			'edit' => $nonce,
		);

		// When running all tests, this test thinks we have multiple Views. Correct that.
		GravityView_View::getInstance()->setViewId( $view->ID );

		###
		### WITH POST
		###
		$edit_link_with_post = GravityView_Edit_Entry::get_edit_link( $entry, $view->ID, $post_id );

		$this->assertEquals( add_query_arg( $args, 'http://example.org/' ), $edit_link_with_post );
	}

	/**
	 * @covers GravityView_Edit_Entry::add_template_path
	 */
	public function test_add_template_path() {

		$template_paths = GravityView_Edit_Entry::getInstance()->add_template_path( array() );

		$expected = array(
			110 => GravityView_Edit_Entry::$file
		);

		$this->assertEquals( $expected, $template_paths );
	}

	/**
	 * @group capabilities
	 * @covers GravityView_Edit_Entry::check_user_cap_edit_entry()
	 */
	public function test_check_user_cap_edit_entry() {

		$form = $this->factory->form->create_and_get();

		$view_user_edit_enabled = $this->factory->view->create_and_get(array(
			'form_id' => $form['id'],
			'settings' => array(
				'user_edit' => 1
			),
		));

		$view_user_edit_disabled = $this->factory->view->create_and_get(array(
			'form_id' => $form['id'],
			'settings' => array(
				'user_edit' => 0
			),
		));

		$author = $this->factory->user->create_and_get( array(
			'user_login' => 'author',
			'role' => 'author'
		) );

		$author_id = $author->ID;

		$contributor = $this->factory->user->create_and_get( array(
			'user_login' => 'contributor',
			'role' => 'contributor'
		) );

		$contributor_id = $contributor->ID;

		$editor_id = $this->factory->user->create( array(
			'user_login' => 'editor',
			'role' => 'editor'
		) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $contributor_id
		) );


		$subscriber_id = $this->factory->user->create( array(
			'user_login' => 'subscriber',
			'role' => 'subscriber'
		) );

		#####
		##### Test Caps & Permissions always being able to edit
		#####
		$this->_add_and_remove_caps_test( $entry, $view_user_edit_enabled );

		#####
		##### Test Entry with "Created By"
		#####

		$this->factory->user->set( $contributor_id );

		// User Edit Enabled
		$this->assertTrue( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry, $view_user_edit_enabled->ID ) );

		// User Edit Disabled
		$this->assertFalse( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry, $view_user_edit_disabled->ID ) );

		/** @var WP_User $admin */
		$admin = $this->factory->user->create_and_get( array(
			'user_login' => 'administrator',
			'role' => 'administrator'
		) );

		$admin_id = $admin->ID;

		#####
		##### Test Admin always being able to edit
		#####

		$this->factory->user->set( $admin_id );

		// Admin always can edit
		$this->assertTrue( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry, $view_user_edit_enabled->ID ) );

		// Admin always can edit
		$this->assertTrue( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry, $view_user_edit_disabled->ID ) );


		#####
		##### Test Entry _without_ "Created By"
		#####

		$entry_without_created_by = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $contributor_id
		) );

		unset( $entry_without_created_by['created_by'] );

		$this->factory->user->set( $admin_id );

		// Admin always can edit, even without "created_by"
		$this->assertTrue( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry_without_created_by, $view_user_edit_disabled->ID ) );

		$this->factory->user->set( $contributor_id );

		$this->assertFalse( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry_without_created_by, $view_user_edit_enabled->ID ) );

		#####
		##### Test TRUE Filter
		#####

		add_filter( 'gravityview/edit_entry/user_can_edit_entry', '__return_true' );

		// Should be true anyway
		$this->factory->user->set( $admin_id );
			$this->assertTrue( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry, $view_user_edit_enabled->ID ) );
			$this->assertTrue( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry_without_created_by, $view_user_edit_enabled->ID ) );

		// Should be false, but we have filter set to true
		$this->factory->user->set( $editor_id );
			$this->assertTrue( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry_without_created_by, $view_user_edit_enabled->ID ) );
			$this->assertTrue( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry, $view_user_edit_enabled->ID ) );

		// Should be false, but we have filter set to true
		$this->factory->user->set( $contributor_id );
			$this->assertTrue( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry, $view_user_edit_enabled->ID ) );
			$this->assertTrue( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry_without_created_by, $view_user_edit_enabled->ID ) );

		remove_filter( 'gravityview/edit_entry/user_can_edit_entry', '__return_true' );


		#####
		##### Test FALSE Filter
		#####

		add_filter( 'gravityview/edit_entry/user_can_edit_entry', '__return_false' );

			// Should be true but the filter is set to false
			$this->factory->user->set( $admin_id );
			$this->assertFalse( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry, $view_user_edit_enabled->ID ) );
			$this->assertFalse( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry_without_created_by, $view_user_edit_enabled->ID ) );

			// Should be true, but we have filter set to false
			$this->factory->user->set( $editor_id );
			$this->assertFalse( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry, $view_user_edit_enabled->ID ) );

			// Should be false, and we have filter set to false
			$this->factory->user->set( $contributor_id );
			$this->assertFalse( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry, $view_user_edit_enabled->ID ) );

		remove_filter( 'gravityview/edit_entry/user_can_edit_entry', '__return_false' );

	}

	/**
	 * Test Caps & Permissions always being able to edit
	 *
	 * @param $entry
	 * @param $view_user_edit_enabled
	 */
	public function _add_and_remove_caps_test( $entry, $view_user_edit_enabled ) {

		$user = $this->factory->user->create_and_set( array( 'role' => 'zero' ) );

		$current_user = wp_get_current_user();

		$this->assertEquals( $user->ID, $current_user->ID );

		$full_access = array(
			'gravityview_full_access',
			'gform_full_access',
			'gravityview_edit_others_entries',
		);

		foreach( $full_access as $cap ) {

			$user->remove_all_caps();

			// Can't edit now
			$this->assertFalse( current_user_can( $cap ), $cap );
			$this->assertFalse( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry, $view_user_edit_enabled->ID ), $cap );

			$user->add_cap( $cap );
			$user->get_role_caps(); // WordPress 4.2 and lower need this to refresh caps

			// Can edit now
			$this->assertTrue( current_user_can( $cap ), $cap );
			$this->assertTrue( GravityView_Edit_Entry::check_user_cap_edit_entry( $entry, $view_user_edit_enabled->ID ), $cap );
		}
	}

	/**
	 * @covers GravityView_Edit_Entry::get_nonce_key()
	 */
	public function test_get_nonce_key() {

		$view_id = 1;
		$form_id = 2;
		$entry_id = 3;

		$nonce_key = GravityView_Edit_Entry::get_nonce_key( $view_id, $form_id, $entry_id );

		$this->assertEquals( $nonce_key, sprintf( 'edit_%d_%d_%d', $view_id, $form_id, $entry_id ) );

	}

	/**
	 * Reset the edit entry context.
	 *
	 * @return void
	 */
	private function _reset_context() {
		GravityView_Edit_Entry::$instance = null;
		GravityView_frontend::$instance = null;
		GravityView_View_Data::$instance = null;
		GravityView_View::$instance = null;

		wp_set_current_user( 0 );
		remove_all_filters( 'gravityview/is_single_entry' );
		remove_all_filters( 'gravityview/edit_entry/form_fields' );
		$_GET = array(); $_POST = array();
	}

	/**
	 * Emulate a valid edit view hit.
	 *
	 * @param $form A $view object returned by our factory.
	 * @param $view A $view object returned by our factory.
	 * @param $entry An $entry object returned by our factory.
	 *
	 * @return array With first item the rendered output,
	 *  and second item the render instance, and third item is the reloaded entry.
	 */
	private function _emulate_render( $form, $view, $entry ) {
		$loader = GravityView_Edit_Entry::getInstance();
		$render = $loader->instances['render'];

		add_filter( 'gravityview/is_single_entry', '__return_true' );
		$data = GravityView_View_Data::getInstance( $view );
		$template = GravityView_View::getInstance( array(
			'form' => $form,
			'form_id' => $form['id'],
			'view_id' => $view->ID,
			'entries' => array( $entry ),
			'atts' => GVCommon::get_template_settings( $view->ID ),
		) );

		$_GET['edit'] = wp_create_nonce(
			GravityView_Edit_Entry::get_nonce_key( $view->ID, $form['id'], $entry['id'] )
		);

		/** Render */
		ob_start() && $render->init( $data );
		$rendered_form = ob_get_clean();

		return array( $rendered_form, $render, GFAPI::get_entry( $entry['id'] ) );
	}

	/**
	 * Create a user with a random email/username.
	 *
	 * @param string $role The role
	 *
	 * @return int The ID of the created user.
	 */
	private function _generate_user( $role ) {
		return $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => $role )
		);
	}

	/**
	 * All rendering stuff here for now to save time. Move to logical classes and methods later.
	 *
	 * @covers GravityView_Edit_Entry_Render::is_edit_entry()
	 * @covers GravityView_Edit_Entry_Render::prevent_render_form()
	 * @covers GravityView_Edit_Entry_Render::init()
	 * @covers GravityView_Edit_Entry_Render::process_save()
	 */
	public function test_edit_entry_render() {
		/** A clean slate, please. */
		$this->_reset_context();

		$loader = GravityView_Edit_Entry::getInstance();
		$render = $loader->instances['render'];
		$this->assertInstanceOf( 'GravityView_Edit_Entry_Render', $render );

		$form = $this->factory->form->create_and_get();
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		/** GravityView_Edit_Entry_Render::is_edit_entry */
		$this->assertFalse( $render->is_edit_entry() );
		add_filter( 'gravityview/is_single_entry', '__return_true' );
		$_GET['edit'] = 'this is an edit';
		$this->assertTrue( $render->is_edit_entry() );

		/** GravityView_Edit_Entry_Render::prevent_render_form */
		global $wp_current_filter;
		$render->prevent_render_form();
		$this->assertNotEmpty( do_shortcode( sprintf( '[gravityform id="%d"]', $form['id'] ) ) );

		$wp_current_filter = array( 'wp_head' );
		$render->prevent_render_form();
		$this->assertEmpty( do_shortcode( sprintf( '[gravityform id="%d"]', $form['id'] ) ) );

		$wp_current_filter = array( 'wp_footer' );
		$render->prevent_render_form();
		$this->assertNotEmpty( do_shortcode( sprintf( '[gravityform id="%d"]', $form['id'] ) ) );

		/** Main rendering emulation. */
		$form = $this->factory->form->create_and_get();
		$entry = $this->factory->entry->create_and_get( array( 'form_id' => $form['id'], 'status' => 'active' ) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		GravityView_View_Data::$instance = null;
		GravityView_View::$instance = null;
		$data = GravityView_View_Data::getInstance( $view );
		$template = GravityView_View::getInstance( array(
			'form' => $form,
			'form_id' => $form['id'],
			'view_id' => $view->ID,
			'entries' => array( $entry ),
		) );
		ob_start() && $render->init( $data );
		$this->assertContains( 'do not have permission', ob_get_clean() );

		/** Let's try again. */
		$subscriber = $this->_generate_user( 'subscriber' );
		wp_set_current_user( $subscriber );
		ob_start() && $render->init( $data );
		$this->assertContains( 'do not have permission', ob_get_clean() );

		$administrator = $this->_generate_user( 'administrator' );
		wp_set_current_user( $administrator );
		ob_start() && $render->init( $data );
		$this->assertContains( 'link to edit this entry is not valid', ob_get_clean() );

		$_GET['edit'] = wp_create_nonce( $render::$nonce_key ); /** @todo: also test gravityview/edit_entry/verify_nonce */
		ob_start() && $render->init( $data );
		$this->assertContains( 'gv-edit-entry-wrapper', ob_get_clean() );

		/** So this is the basic emulation of viewing the edit entry. Let's try something more complex: */

		$_this = &$this;
		$_this->disable_action_1 = false;;
		add_action( 'gform_pre_render', function( $form, $ajax = false, $field_values = '' ) use ( $_this  ) {
			if ( $_this->disable_action_1 ) /** Run only when inside this test. */ {
				return $form;
			}

			/** @todo Add output form assertions here, like, are the needed fields hidden? ... */
			$_this->assertTrue( false );

			return $form;
		}, 9999, 3 );
		$_this->disable_action_1 = true;
		ob_start() && $render->init( $data ); ob_get_clean();

		/** Great, now how about some saving? The default form. Although we should be testing specific forms as well. */
		$_POST = array();
		$_POST['lid'] = $entry['id'];
		$_POST['is_submit_' . $form['id']] = true;
		foreach ( $form['fields'] as $field ) {
			/** Emulate a $_POST */
			foreach ( $field->inputs ? : array( array( 'id' => $field->id ) ) as $input ) {
				if ( $field->type == 'time' ) { /** An old incompatibility in the time field. */
					$_POST["input_{$field->id}"] = $entry[$field->id];
				} else {
					$_POST["input_{$field->id}"] = $entry[strval($input['id'])];
				}
			}
		}
		$_POST['input_1'] = "This has been changed";
		ob_start() && $render->init( $data ); ob_get_clean();
		$this->assertEquals( $_POST['input_1'], $render->entry[1] );

		/**
		 * This covers the basics of editing, I think.
		 * A lot here is missing, many more edge cases should be covered!
		 *
		 * Here be dragons.
		 */

		/** Cleanup */
		$this->_reset_context();
	}

	public function test_edit_entry_simple() {
		/** Create a user */
		$administrator = $this->_generate_user( 'administrator' );

		/** Create the form, entry and view */
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'created_by' => $administrator,
			'form_id' => $form['id'],
			/** Fields, more complex entries may have hundreds of fields defined in the JSON file. */
			'1' => 'this is field one',
			'2' => 102,
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		/** Request the rendered form */
		$this->_reset_context();
		wp_set_current_user( $administrator );
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		$this->assertContains( 'gform_submit', $output );

		/** Submit an edit */
		$this->_reset_context();
		wp_set_current_user( $administrator );

		$_POST = array(
			'lid' => $entry['id'],
			'is_submit_' . $form['id'] => true,

			/** Fields */
			'input_1' => 'we changed it',
			'input_2' => 102,
		);
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		/** Check updates */
		$this->assertEquals( $entry['1'], 'we changed it' );
		$this->assertEquals( $entry['2'], 102 );

		/** Cleanup */
		$this->_reset_context();
	}

	/**
	 * @covers GravityView_Edit_Entry_Render::custom_validation()
	 * @covers GravityView_Edit_Entry_Render::validate()
	 * @covers GravityView_Edit_Entry_Render::get_configured_edit_fields()
	 * @covers GravityView_Edit_Entry_Render::user_can_edit_entry()
	 * @group editme
	 */
	public function test_edit_entry_simple_fails() {
		/** Create a couple of users */
		$subscriber1 = $this->_generate_user( 'subscriber' );
		$subscriber2 = $this->_generate_user( 'subscriber' );
		$administrator = $this->_generate_user( 'administrator' );

		/** Create the form, entry and view */
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'created_by' => $subscriber1,
			'form_id' => $form['id'],
			/** Fields, more complex entries may have hundreds of fields defined in the JSON file. */
			'1' => 'set all the fields!',
			'2' => 107,
		) );
		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'settings' => wp_parse_args( array(
				'user_edit' => 1, /** Allow users to edit entries in this view. */
			), GravityView_View_Data::get_default_args() ),
		) );

		/** Let's get failing... */

		$post = array(
			'lid' => $entry['id'],
			'is_submit_' . $form['id'] => true,

			/** Fields */
			'input_1' => 'we changed it',
			'input_2' => 102310,
		);

		/** No permissions to edit this entry */
		$this->_reset_context(); $_POST = $post;
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		$this->assertContains( 'do not have permission to edit this entry', $output );
		$this->assertEquals( $entry['1'], $entry['1'] );
		$this->assertEquals( $entry['2'], $entry['2'] );

		/** No permissions to edit this entry, not logged in. */
		$this->_reset_context(); $_POST = $post;
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		$this->assertContains( 'do not have permission to edit this entry', $output );
		$this->assertEquals( $entry['1'], $entry['1'] );
		$this->assertEquals( $entry['2'], $entry['2'] );

		/** No permissions to edit this entry, logged in as someone else. */
		$this->_reset_context(); $_POST = $post;
		wp_set_current_user( $subscriber2 );
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		$this->assertContains( 'do not have permission to edit this entry', $output );
		$this->assertEquals( $entry['1'], $entry['1'] );
		$this->assertEquals( $entry['2'], $entry['2'] );

		/** Only one field is visible and editable. */
		$this->_reset_context(); $_POST = $post;
		wp_set_current_user( $subscriber1 );

		add_filter( 'gravityview/edit_entry/form_fields', function( $fields, $edit_fields, $form, $view_id ) {
			unset( $fields[0] ); /** The first text field is now hidden. */
			return $fields;
		}, 10, 4 );

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		$this->assertEquals( $entry['1'], $entry['1'], 'Oh no! The first field was edited.' );
		$this->assertEquals( $entry['2'], $post['input_2'], 'The second field was not edited... Why?' );

		/** Test internal validation. */
		$this->_reset_context(); $_POST = $post;
		wp_set_current_user( $administrator );
		$_POST['input_2'] = 'this is not a number nanananana';
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		$this->assertEquals( $entry['2'], $post['input_2'], 'A numeric field was changed! WTF?' );
		$this->assertContains( 'enter a valid number', $output );

		/** Cleanup */
		$this->_reset_context();
	}

	/**
	 * @since 1.20
	 * @covers GravityView_Edit_Entry_User_Registration::restore_display_name()
	 */
	public function test_restore_display_name() {

		/** A clean slate, please. */
		GravityView_Edit_Entry::$instance = null;

		$loader = GravityView_Edit_Entry::getInstance();

		/** @var GravityView_Edit_Entry_User_Registration $registration */
		$registration = $loader->instances['user-registration'];
		$this->assertInstanceOf( 'GravityView_Edit_Entry_User_Registration', $registration );
		$_user_before_update_prop = new ReflectionProperty( 'GravityView_Edit_Entry_User_Registration', '_user_before_update' );
		$_user_before_update_prop->setAccessible( true ); // It was private; let's make it public


		$microtime = md5( microtime() );

		$user_before_update = $this->factory->user->create_and_get( array(
			'user_login' => $microtime,
			'user_email' => $microtime . '@gravityview.tests',
			'display_name' => 'Zeek LaBeek',
			'nickname' => 'Zeekary',
			'first_name' => 'Zeek',
			'last_name' => 'LaBeek',
			'role' => 'subscriber',
		) );

		$user_id = $user_before_update->ID;

	// Make the value of $_user_before_update non-null so it passes the test
		$_user_before_update_prop->setValue( $registration, $user_before_update );


		// Set it to anything other than "update" so that the logic passes
		$config = array(
			'meta' => array(
				'feed_type' => 'create',
			),
		);

	// Test that the filter works
		add_filter( 'gravityview/edit_entry/restore_display_name', '__return_false' );

		$should_be_null = $registration->restore_display_name( $user_id, $config, array(), '' );

		$this->assertNull( $should_be_null, 'the `gravityview/edit_entry/restore_display_name` filter didn\'t work' );

		remove_filter( 'gravityview/edit_entry/restore_display_name', '__return_false' );


	// Set it to "update" so that the logic fails
		$config = array(
			'meta' => array(
				'feed_type' => 'update',
			),
		);

		$should_be_null = $registration->restore_display_name( $user_id, $config, array(), '' );

		$this->assertNull( $should_be_null, '$config should have blocked processing; it was `update` feed type' );



		// Now use "Create" to pass through initial logic check
		$config = array(
			'meta' => array(
				'feed_type' => 'create',
			),
		);

	// Change something that should be changed back
		$this->factory->user->update_object( $user_id, array( 'display_name' => 'Changed During Update') );

		$user_after_update = get_userdata( $user_id );

		$this->assertEquals( 'Changed During Update', $user_after_update->display_name ); // Make sure the value changed properly

		// The user should still be saved in $_user_before_update_prop
		$this->assertEquals( $user_before_update, $_user_before_update_prop->getValue( $registration ) );

		// Update the user
		$should_be_int_user_id = $registration->restore_display_name( $user_id, $config, array(), '' );

		$this->assertEquals( $user_id, $should_be_int_user_id );

		// $_user_before_update_prop is reset at the end of the method
		$this->assertEquals( NULL, $_user_before_update_prop->getValue( $registration ) );

		$user_after_update = get_userdata( $user_id );

		$this->assertEquals( $user_before_update->display_name, $user_after_update->display_name );



	// Test gravityview/edit_entry/user_registration/restored_user filter
		$_user_before_update_prop->setValue( $registration, $user_before_update );

		// To check the filter and the WP_Error return at the same time,
		// Delete the ID from $user_data, which will throw a WP_Error in wp_update_user() (since ID isn't defined)
		add_filter('gravityview/edit_entry/user_registration/restored_user', function( $user_data ) {

			$user_data->ID = 0;
			$user_data->data->ID = 0;

			return $user_data;
		});

		$should_be_wp_error = $registration->restore_display_name( $user_id, $config, array(), '' );

		$this->assertWPError( $should_be_wp_error );

		remove_all_filters('gravityview/edit_entry/user_registration/restored_user' );


	// Test User not exists
		$_user_before_update_prop->setValue( $registration, $user_before_update );

		// remove the user; should be WP_Error
		parent::delete_user( $user_id );

		$this->assertFalse( get_userdata( $user_id ), 'The user was not successfully deleted.' );

		$should_be_false = $registration->restore_display_name( $user_id, $config, array(), '' );

		$this->assertFalse( $should_be_false );

		/** Cleanup. */
		GravityView_Edit_Entry::$instance = null;
	}

	/**
	 * @since 1.20
	 *
	 * @covers GravityView_Edit_Entry_User_Registration::generate_display_names
	 */
	public function test_edit_entry_ur_generate_display_names() {

		/** A clean slate, please */
		GravityView_Edit_Entry::$instance = null;

		$loader = GravityView_Edit_Entry::getInstance();

		/** @var GravityView_Edit_Entry_User_Registration $registration */
		$registration = $loader->instances['user-registration'];
		$this->assertInstanceOf( 'GravityView_Edit_Entry_User_Registration', $registration );

		$microtime = md5( microtime() );

		$complete_subscriber = $this->factory->user->create_and_get( array(
			'user_login' => $microtime,
			'user_email' => $microtime . '@gravityview.tests',
			'display_name' => 'Zeek LaBeek',
			'nickname' => 'Zeekary',
			'first_name' => 'Zeek',
			'last_name' => 'LaBeek',
			'role' => 'subscriber',
		) );

		$display_names = $registration->generate_display_names( $complete_subscriber );

		$this->assertEquals( 'Zeek', $display_names['firstname'] );
		$this->assertEquals( 'LaBeek', $display_names['lastname'] );
		$this->assertEquals( 'Zeek LaBeek', $display_names['firstlast'] );
		$this->assertEquals( 'LaBeek Zeek', $display_names['lastfirst'] );
		$this->assertEquals( 'Zeekary', $display_names['nickname'] );
		$this->assertEquals( $microtime, $display_names['username'] );


		// When the first name and last name aren't available, they should not be set in the returned array
		$incomplete_subscriber = clone $complete_subscriber;
		$incomplete_subscriber->first_name = '';
		$incomplete_subscriber->last_name = '';

		$incomplete_display_names = $registration->generate_display_names( $complete_subscriber );

		$this->assertFalse( isset( $incomplete_display_names['firstname'] ) );
		$this->assertFalse( isset( $incomplete_display_names['lastname'] ) );
		$this->assertFalse( isset( $incomplete_display_names['firstlast'] ) );
		$this->assertFalse( isset( $incomplete_display_names['lastfirst'] ) );

		/** Cleanup. */
		GravityView_Edit_Entry::$instance = null;
	}

	/**
	 * @since 1.20
	 *
	 * @covers GravityView_Edit_Entry_User_Registration::match_current_display_name()
	 * @covers GravityView_Edit_Entry_User_Registration::update_user()
	 */
	public function test_edit_entry_registration() {

		/** A clean slate, please. */
		GravityView_Edit_Entry::$instance = null;

		$loader = GravityView_Edit_Entry::getInstance();

		/** @var GravityView_Edit_Entry_User_Registration $registration */
		$registration = $loader->instances['user-registration'];
		$this->assertInstanceOf( 'GravityView_Edit_Entry_User_Registration', $registration );

		/** Some fixtures... */
		$subscriber = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'display_name' => 'Zeek LaBeek',
			'nickname' => 'Zeekary',
			'first_name' => 'Zeek',
			'last_name' => 'LaBeek',
			'role' => 'subscriber' )
		);

		$form = $this->factory->form->create_and_get();
		$entry = $this->factory->entry->create_and_get( array( 'form_id' => $form['id'], 'status' => 'publish', 'created_by' => $subscriber ) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		/** All good here! */
		$registration->update_user( $form, $entry['id'] );

		parent::delete_user( $subscriber );

		/**
		 * When updating an user that doesn't exist, make sure no errors are thrown
		 * @see GravityView_Edit_Entry_User_Registration::match_current_display_name
		 */
		$registration->update_user( $form, $entry['id'] );

		/** Cleanup. */
		GravityView_Edit_Entry::$instance = null;
	}
}

/** The GF_User_Registration mock if not exists. */
if ( ! class_exists( 'GF_User_Registration' ) ) {

	if( file_exists( plugin_dir_path('gravityformsuserregistration/userregistration.php') ) ) {
		include plugin_dir_path( 'gravityformsuserregistration/userregistration.php' );

		GF_User_Registration_Bootstrap::load();
	}

	// Still doesn't exist!

	if ( ! class_exists('GF_User_Registration') ) {

		class GF_User_Registration {
			public static function get_instance() {
				return new self();
			}

			public function get_single_submission_feed( $entry, $form ) {
				return array(
					'meta' => array(
						'displayname' => 'user_login',
						// Default. `firstname`, `lastname`, `firstlast`, `lastfirst`, `nickname` are valid options
						'role'        => 'gfur_preserve_role',
						// Default value.
					),
				);
			}
		}
	}
}
