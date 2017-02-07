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
	 * All rendering stuff here for now to save time. Move to logical classes and methods later.
	 *
	 * @covers GravityView_Edit_Entry_Render::is_edit_entry()
	 * @covers GravityView_Edit_Entry_Render::prevent_render_form()
	 * @covers GravityView_Edit_Entry_Render::init()
	 * @covers GravityView_Edit_Entry_Render::process_save()
	 */
	public function test_edit_entry_render() {
		/** A clean slate, please. */
		/** @todo: maybe invoke this on setup automatically for each test? */
		GravityView_Edit_Entry::$instance = null;
		GravityView_frontend::$instance = null;
		GravityView_View_Data::$instance = null;
		GravityView_View::$instance = null;

		$loader = GravityView_Edit_Entry::getInstance();
		$render = $loader->instances['render'];
		$this->assertInstanceOf( 'GravityView_Edit_Entry_Render', $render );

		$form = $this->factory->form->create_and_get();
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		/** GravityView_Edit_Entry_Render::is_edit_entry */
		$this->assertFalse( $render->is_edit_entry() );
		add_filter( 'gravityview/is_single_entry', '__return_true', 667 );
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
		$entry = $this->factory->entry->create_and_get( array( 'form_id' => $form['id'], 'status' => 'publish' ) );
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
		$subscriber = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => 'subscriber' )
		);
		wp_set_current_user( $subscriber );
		ob_start() && $render->init( $data );
		$this->assertContains( 'do not have permission', ob_get_clean() );

		$administrator = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => 'administrator' )
		);
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
		remove_filter( 'gravityview/is_single_entry', '__return_true', 667 );
		/** @todo: maybe invoke this on teardown automatically for each test? */
		GravityView_Edit_Entry::$instance = null;
		GravityView_frontend::$instance = null;
		GravityView_View_Data::$instance = null;
		GravityView_View::$instance = null;
		$_GET = array(); $_POST = array();
		wp_set_current_user( 0 );
	}

	public function test_edit_entry_registration() {
		/** A clean slate, please. */
		GravityView_Edit_Entry::$instance = null;

		$loader = GravityView_Edit_Entry::getInstance();
		$registration = $loader->instances['user-registration'];
		$this->assertInstanceOf( 'GravityView_Edit_Entry_User_Registration', $registration );

		/** Some fixtures... */
		$subscriber = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => 'subscriber' )
		);

		$form = $this->factory->form->create_and_get();
		$entry = $this->factory->entry->create_and_get( array( 'form_id' => $form['id'], 'status' => 'publish', 'created_by' => $subscriber ) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		/** All good here! */
		$registration->update_user( $form, $entry['id'] );

		wp_delete_user( $subscriber );

		/**
			There was 1 error:

			1) GravityView_Edit_Entry_Test::test_edit_entry_registration
			Trying to get property of non-object

			includes/extensions/edit-entry/class-edit-entry-user-registration.php:185
			includes/extensions/edit-entry/class-edit-entry-user-registration.php:159
			includes/extensions/edit-entry/class-edit-entry-user-registration.php:111
			tests/unit-tests/GravityView_Edit_Entry_Test.php:485
		 */
		$registration->update_user( $form, $entry['id'] );

		/** Cleanup. */
		GravityView_Edit_Entry::$instance = null;
	}
}

/** The GF_User_Registration mock if not exists. */
if ( ! class_exists( 'GF_User_Registration' ) ) {
	class GF_User_Registration {
		public static function get_instance() {
			return new self();
		}

		public function get_single_submission_feed( $entry, $form ) {
			return array(
			);
		}
	}
}
