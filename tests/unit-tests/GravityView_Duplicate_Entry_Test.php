<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group duplicateentry
 */
class GravityView_Duplicate_Entry_Test extends GV_UnitTestCase {
	/**
	 * Reset the duplicate entry context.
	 *
	 * @return void
	 */
	private function _reset_context() {
		GravityView_Duplicate_Entry::$instance = null;
		GravityView_frontend::$instance = null;
		GravityView_View_Data::$instance = null;
		GravityView_View::$instance = null;

		wp_set_current_user( 0 );

		$_GET = array(); $_POST = array(); $_FILES = array();
	}

	/**
	 * @covers GravityView_Duplicate_Entry::getInstance()
	 */
	function test_getInstance() {
		$this->assertTrue( GravityView_Duplicate_Entry::getInstance() instanceof GravityView_Duplicate_Entry );
	}

	/**
	 * @covers GravityView_Duplicate_Entry::get_duplicate_link()
	 */
	function test_get_duplicate_link() {

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
		));

		$post_title_sequence = new WP_UnitTest_Generator_Sequence( __METHOD__ . ' %s' );
		$post_id = $this->factory->post->create(array(
			'post_title' => $post_title_sequence->next(),
			'post_content' => sprintf( '[gravityview id="%d"]', $view->ID ),
		));

		$nonce_key = GravityView_Duplicate_Entry::get_nonce_key( $entry['id']  );

		$nonce = wp_create_nonce( $nonce_key );

		###
		### NO POST
		###
		$duplicate_link_no_post = GravityView_Duplicate_Entry::get_duplicate_link( $entry, $view->ID );

		$args = array(
			'action' => 'duplicate',
			'entry_id' => $entry['id'],
			'gvid' => $view->ID,
			'view_id' => $view->ID,
			'duplicate' => $nonce,
		);

		// A link to the raw
		$this->assertEquals( add_query_arg( $args, get_permalink( $view->ID ) ), $duplicate_link_no_post );

		$args = array(
			'p' => $post_id,
			'action' => 'duplicate',
			'entry_id' => $entry['id'],
			'gvid' => $view->ID,
			'view_id' => $view->ID,
			'duplicate' => $nonce,
		);

		// When running all tests, this test thinks we have multiple Views. Correct that.
		GravityView_View::getInstance()->setViewId( $view->ID );

		###
		### WITH POST
		###
		$duplicate_link_with_post = GravityView_Duplicate_Entry::get_duplicate_link( $entry, $view->ID, $post_id );

		$this->assertEquals( add_query_arg( $args, site_url( '/' ) ), $duplicate_link_with_post );
	}

	/**
	 * @covers GravityView_Duplicate_Entry::get_confirm_dialog
	 */
	public function test_get_confirm_dialog() {

		$confirm_dialog = GravityView_Duplicate_Entry::get_confirm_dialog();

		$this->assertStringContainsString( 'return window.confirm', $confirm_dialog, 'confirm JS is not in the confirm dialog response' );

		add_filter( 'gravityview/duplicate-entry/confirm-text', '__return_empty_string' );

		$confirm_dialog = GravityView_Duplicate_Entry::get_confirm_dialog();

		$this->assertEquals( '', $confirm_dialog, 'filter did not apply' );

		remove_all_filters( 'gravityview/duplicate-entry/confirm-text' );
	}

	/**
	 * @covers GravityView_Duplicate_Entry::get_duplicate_link()
	 * @see https://github.com/gravityview/GravityView/issues/842
	 */
	public function test_get_duplicate_link_loop() {
		$this->_reset_context();

		{ /** Test fixtures */
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
			) );
			$view->entry = $entry;

			$a_post = $this->factory->post->create_and_get( array(
				'post_content' => 'This is a post',
			) );

			$view_post = $this->factory->post->create_and_get( array(
				'post_content' => '[gravityview id="' . $view->ID . '"]',
			) );
			$view_post->view = $view;

			$another_form = $this->factory->form->create_and_get();

			$another_entry = $this->factory->entry->create_and_get( array(
				'form_id' => $another_form['id'],
				'created_by' => $editor->ID,
			) );

			$another_view = $this->factory->view->create_and_get(array(
				'form_id' => $another_form['id'],
			) );
			$another_view->entry = $another_entry;

			$another_post = $this->factory->post->create_and_get( array(
				'post_content' => 'This is a post',
			) );

			$another_view_post = $this->factory->post->create_and_get( array(
				'post_content' => '[gravityview id="' . $another_view->ID . '"]',
			) );
			$another_view_post->view = $another_view;

			$and_another_form = $this->factory->form->create_and_get();

			$and_another_entry = $this->factory->entry->create_and_get( array(
				'form_id' => $another_form['id'],
				'created_by' => $editor->ID,
			) );

			$and_another_view = $this->factory->view->create_and_get(array(
				'form_id' => $another_form['id'],
			) );
			/** Fake it until you make it... */
			$and_another_view->view = $and_another_view;
			$and_another_view->view->entry = $and_another_entry;
		}

		/** Let's mix it up, we have posts, view shortcodes and an actual view */
		$posts = array( $a_post, $view_post, $another_post, $another_view_post, $and_another_view );

		$data = GravityView_View_Data::getInstance( $posts );
		$fe = GravityView_frontend::getInstance();
		$fe->setGvOutputData( $data );

		/** Fake the loop, sort of... */
		global $wp_actions, $wp_query;
		$wp_actions['loop_start'] = 1;
		$wp_query->in_the_loop = true;

		add_filter( 'gravityview/duplicate-entry/verify_nonce', '__return_true' );

		foreach ( $posts as $_post ) {
			setup_postdata( $GLOBALS['post'] =& $_post );

			/**
			 * We can also check the actual content output here to make sure all is well
			 *  and no IDs are messed up, etc. @todo for another day, for another test. */
			$fe->insert_view_in_content( get_the_content() );

			if ( empty( $_post->view ) ) {
				continue;
			}

			$args = array(
				'action' => 'duplicate',
				'entry_id' => $_post->view->entry['id'],
				'gvid' => $_post->view->ID,
				'view_id' => $_post->view->ID,
			);
			$expected = add_query_arg( $args, get_permalink( $_post->ID ) );

			$duplicate_link_with_post = GravityView_Duplicate_Entry::get_duplicate_link( $_post->view->entry, $_post->view->ID, $_post->ID );
			$this->assertEquals( $expected, remove_query_arg( array( 'duplicate' ), $duplicate_link_with_post ) );
		}

		remove_all_filters( 'gravityview/duplicate-entry/verify_nonce' );
		unset( $wp_actions['loop_start'] );
		$wp_query->in_the_loop = false;
		$this->_reset_context();
	}

	/**
	 * @covers GravityView_Duplicate_Entry::add_template_path
	 */
	public function test_add_template_path() {

		$template_paths = GravityView_Duplicate_Entry::getInstance()->add_template_path( array() );

		$expected = array(
			117 => GravityView_Duplicate_Entry::$file
		);

		$this->assertEquals( $expected, $template_paths );
	}

	/**
	 * @group capabilities
	 * @covers GravityView_Duplicate_Entry::check_user_cap_duplicate_entry()
	 */
	public function test_check_user_cap_duplicate_entry() {

		$form = $this->factory->form->create_and_get();

		$view = $this->factory->view->create_and_get(array(
			'form_id' => $form['id'],
			'settings' => array(
				'user_duplicate' => true,
			)
		) );

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
		##### Test Caps & Permissions always being able to duplicate
		#####
		$this->_add_and_remove_caps_test( $entry, $view );

		#####
		##### Test Entry with "Created By"
		#####
		$this->factory->user->set( $contributor_id );

		// User Duplicate Enabled
		$this->assertTrue( GravityView_Duplicate_Entry::check_user_cap_duplicate_entry( $entry, array(), $view->ID ) );

		$view_user_duplicate_disabled = $this->factory->view->create_and_get(array(
			'form_id' => $form['id'],
			'settings' => array(
				'user_duplicate' => false,
			)
		));

		// User Duplicate Disabled
		$this->assertFalse( GravityView_Duplicate_Entry::check_user_cap_duplicate_entry( $entry, array(), $view_user_duplicate_disabled->ID ) );

		/** @var WP_User $admin */
		$admin = $this->factory->user->create_and_get( array(
			'user_login' => 'administrator',
			'role' => 'administrator'
		) );

		$admin_id = $admin->ID;

		#####
		##### Test Admin always being able to duplicate
		#####

		$this->factory->user->set( $admin_id );

		// Admin always can duplicate
		$this->assertTrue( GravityView_Duplicate_Entry::check_user_cap_duplicate_entry( $entry, array(), $view->ID ) );

		// Admin always can duplicate
		$this->assertTrue( GravityView_Duplicate_Entry::check_user_cap_duplicate_entry( $entry, array(), $view_user_duplicate_disabled->ID ) );

		#####
		##### Test Entry _without_ "Created By"
		#####

		$entry_without_created_by = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $contributor_id
		) );

		unset( $entry_without_created_by['created_by'] );

		$this->factory->user->set( $admin_id );

		// Admin always can duplicate, even without "created_by"
		$this->assertTrue( GravityView_Duplicate_Entry::check_user_cap_duplicate_entry( $entry_without_created_by, array(), $view_user_duplicate_disabled->ID ) );

		$this->factory->user->set( $contributor_id );

		$this->assertFalse( GravityView_Duplicate_Entry::check_user_cap_duplicate_entry( $entry_without_created_by, array(), $view->ID ) );
	}

	/**
	 * Test Caps & Permissions always being able to duplicate
	 *
	 * @param $entry
	 * @param $view
	 */
	public function _add_and_remove_caps_test( $entry, $view ) {

		$user = $this->factory->user->create_and_set( array( 'role' => 'zero' ) );

		$current_user = wp_get_current_user();

		$this->assertEquals( $user->ID, $current_user->ID );

		$full_access = array(
			'gravityview_full_access',
			'gform_full_access',
			'gravityforms_edit_entries',
		);

		foreach ( $full_access as $cap ) {
			$user->remove_all_caps();
			$user->get_role_caps(); // WordPress 4.2 and lower need this to refresh caps

			// Can't duplicate now
			$this->assertFalse( current_user_can( $cap ), $cap );
			$this->assertFalse( GravityView_Duplicate_Entry::check_user_cap_duplicate_entry( $entry, array(), $view->ID ), $cap );

			$user->add_cap( $cap );
			$user->get_role_caps(); // WordPress 4.2 and lower need this to refresh caps

			// Can duplicate now
			$this->assertTrue( current_user_can( $cap ), $cap );
			$this->assertTrue( GravityView_Duplicate_Entry::check_user_cap_duplicate_entry( $entry, array(), $view->ID ), $cap );
		}
	}

	/**
	 * @covers GravityView_Duplicate_Entry::get_nonce_key()
	 */
	public function test_get_nonce_key() {
		$entry_id = 3;

		$nonce_key = GravityView_Duplicate_Entry::get_nonce_key( $entry_id );

		$this->assertEquals( $nonce_key, sprintf( 'duplicate_%d', $entry_id ) );
	}

	public function test_duplicate_basic() {
		$this->_reset_context();

		$duplicate = GravityView_Duplicate_Entry::getInstance();

		$admin = $this->factory->user->create_and_get( array(
			'user_login' => 'administrator',
			'role' => 'administrator'
		) );

		$contributor = $this->factory->user->create_and_get( array(
			'user_login' => 'contributor',
			'role' => 'contributor'
		) );

		$form = $this->factory->form->create_and_get();

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $contributor->ID,
			'date_created' => '2012-01-02 03:04:05',
			'date_updated' => '2012-01-02 03:04:05',
			'ip' => '8.8.8.8',
			'is_starred' => true,
			'is_read' => true,
		) );

		$this->assertNull( $duplicate->process_duplicate() ); // No action

		$_GET['action'] = 'duplicate';
		$_GET['entry_id'] = $entry['id'] + 99;

		$this->assertNull( $duplicate->process_duplicate() ); // Missing nonce

		$_GET['duplicate'] = 'hello';

		$this->assertNull( $duplicate->process_duplicate() ); // Invalid nonce

		$_GET['duplicate'] = wp_create_nonce( GravityView_Duplicate_Entry::get_nonce_key( $_GET['entry_id'] ) );

		$this->assertStringContainsString( 'The+entry+does+not+exist', $duplicate->process_duplicate() ); // Invalid entry

		$_GET['entry_id'] = $entry['id'];
		$_GET['duplicate'] = wp_create_nonce( GravityView_Duplicate_Entry::get_nonce_key( $_GET['entry_id'] ) );

		$this->assertStringContainsString( 'You+do+not+have+permission+to+duplicate+this+entry', $duplicate->process_duplicate() ); // Not allowed

		$this->factory->user->set( $admin->ID );

		$_GET['duplicate'] = wp_create_nonce( GravityView_Duplicate_Entry::get_nonce_key( $_GET['entry_id'] ) );

		global $wpdb;

		$wpdb->suppress_errors( true );

		$this->assertStringContainsString( 'There+was+an+error+duplicating+the+entry.', $duplicate->process_duplicate() ); // User agent cannot be NULL

		$wpdb->suppress_errors( false );


		$_server_bak = $_SERVER;

		$_SERVER['HTTP_USER_AGENT'] = 'Tests';
		$_SERVER['REQUEST_URI'] = 'http://tests?action=duplicate';

		$this->assertCount( 1, GFAPI::get_entries( $form['id'] ) );

		$this->assertStringContainsString( '?status=duplicated', $duplicate->process_duplicate() ); // OK

		$this->assertCount( 2, list( $duplicate_entry, $source_entry ) = GFAPI::get_entries( $form['id'] ) );

		$this->assertNotEmpty( $duplicate_entry['date_updated'] );
		$this->assertNotEmpty( $duplicate_entry['date_created'] );

		$this->assertNotEquals( $duplicate_entry['date_updated'], $source_entry['date_updated'] );
		$this->assertNotEquals( $duplicate_entry['date_created'], $source_entry['date_created'] );

		$this->assertNotEquals( $duplicate_entry['id'], $source_entry['id'] );
		$this->assertNotEquals( $duplicate_entry['source_url'], $source_entry['source_url'] );
		$this->assertEquals( $duplicate_entry['source_id'], null );

		$this->assertStringContainsString( 'tests', $duplicate_entry['source_url'] );

		$this->assertEquals( 'Tests', $duplicate_entry['user_agent'] );

		$this->assertEmpty( $duplicate_entry['is_starred'] );
		$this->assertEmpty( $duplicate_entry['is_read'] );

		$this->assertEquals( '127.0.0.1', $duplicate_entry['ip'] );

		$this->assertEquals( $admin->ID, $duplicate_entry['created_by'] );

		$_SERVER = $_server_bak;

		$this->_reset_context();
	}
}
