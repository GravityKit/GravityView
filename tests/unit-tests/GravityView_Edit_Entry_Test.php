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


}
