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

		$args = array(
			'entry' => $entry['id'],
			'edit' => $nonce,
		);

		// A link to the raw
		$this->assertEquals( add_query_arg( $args, get_permalink( $view->ID ) ), $edit_link_no_post );

		$args = array(
			'p'     => $post_id,
			'entry' => $entry['id'],
			'edit'  => $nonce,
			'gvid'  => $view->ID,
		);

		// When running all tests, this test thinks we have multiple Views. Correct that.
		GravityView_View::getInstance()->setViewId( $view->ID );

		###
		### WITH POST
		###
		$edit_link_with_post = GravityView_Edit_Entry::get_edit_link( $entry, $view->ID, $post_id );

		$this->assertEquals( add_query_arg( $args, site_url( '/' ) ), $edit_link_with_post );
	}

	/**
	 * @covers GravityView_Edit_Entry::get_edit_link()
	 * @see https://github.com/gravityview/GravityView/issues/842
	 */
	public function test_get_edit_link_loop() {
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
				'settings' => array(
					'user_edit' => 1
				),
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
				'settings' => array(
					'user_edit' => 1
				),
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
				'settings' => array(
					'user_edit' => 1
				),
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

		add_filter( 'gravityview/edit_entry/verify_nonce', '__return_true' );

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
				'entry' => $_post->view->entry['id'],
			);
			$expected = add_query_arg( $args, get_permalink( $_post->ID ) );

			$edit_link_with_post = GravityView_Edit_Entry::get_edit_link( $_post->view->entry, $_post->view->ID, $_post->ID );
			$this->assertEquals( $expected, remove_query_arg( array( 'edit', 'gvid' ), $edit_link_with_post ) );
		}

		remove_all_filters( 'gravityview/edit_entry/verify_nonce' );
		unset( $wp_actions['loop_start'] );
		$wp_query->in_the_loop = false;
		$this->_reset_context();
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
		$_GET = array(); $_POST = array(); $_FILES = array();

		RGFormsModel::$uploaded_files = array();
	}

	/**
	 * Emulate a valid edit view hit.
	 *
	 * @param array $form A $form object returned by our factory.
	 * @param \GV\View $view $view object returned by our factory, or a \GV\View.
	 * @param array $entry $entry object returned by our factory.
	 *
	 * @return array With first item the rendered output,
	 *  and second item the render instance, and third item is the reloaded entry.
	 */
	private function _emulate_render( $form, $view, $entry ) {
		// Get clean form every test.
		if ( method_exists( GFFormDisplay::class, 'flush_cached_forms' ) ) {
			GFFormDisplay::flush_cached_forms();
		}

		$loader = GravityView_Edit_Entry::getInstance();
		$render = $loader->instances['render'];

		if ( ! $view instanceof \WP_Post ) {
			$view = get_post( $view->ID );
		}

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

		if ( ! empty( $_POST ) ) {
			$state = array();
			foreach ( $entry as $key => $value ) {
				if ( is_numeric( $key ) ) {
					$state[ 'input_' . str_replace( '.', '_', $key ) ] = $value;
				}
			}

			$_POST += array(
				'action' => 'update',
				'lid' => $entry['id'],
				'is_submit_' . $form['id'] => true,
				'state_' . $form['id'] => GFFormDisplay::get_state( $form, $state ),
			);
		}

		/** Render */
		if ( ! $view instanceof \GV\View ) {
			$view = \GV\View::from_post( $view );
		}
		ob_start() && $render->init( $data, \GV\Entry::by_id( $entry['id'] ), $view );
		$rendered_form = ob_get_clean();

		remove_filter( 'gravityview/is_single_entry', '__return_true' );

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
		GravityView_oEmbed::$instance = null;
		$data = GravityView_View_Data::getInstance( $view );
		$template = GravityView_View::getInstance( array(
			'form' => $form,
			'form_id' => $form['id'],
			'view_id' => $view->ID,
			'entries' => array( $entry ),
		) );
		ob_start() && $render->init( $data );
		$this->assertStringContainsString( 'do not have permission', ob_get_clean() );

		/** Let's try again. */
		$subscriber = $this->_generate_user( 'subscriber' );
		wp_set_current_user( $subscriber );
		ob_start() && $render->init( $data );
		$this->assertStringContainsString( 'do not have permission', ob_get_clean() );

		$administrator = $this->_generate_user( 'administrator' );
		wp_set_current_user( $administrator );
		ob_start() && $render->init( $data );
		$this->assertStringContainsString( 'link to edit this entry is not valid', ob_get_clean() );

		$_GET['edit'] = wp_create_nonce( $render::$nonce_key ); /** @todo: also test gravityview/edit_entry/verify_nonce */
		ob_start() && $render->init( $data, null, \GV\View::from_post( $view ));
		$this->assertStringContainsString( 'gv-edit-entry-wrapper', ob_get_clean() );

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
		ob_start() && $render->init( $data, null, \GV\View::from_post( $view ) ); ob_get_clean();

		/** Great, now how about some saving? The default form. Although we should be testing specific forms as well. */
		$_POST = array(
			'lid' => $entry['id'],
			'is_submit_' . $form['id'] => true,
		);
		foreach ( $form['fields'] as $field ) {
			/** Emulate a $_POST */
			foreach ( $field->inputs ? : array( array( 'id' => $field->id ) ) as $input ) {
				if ( 'time' == $field->type ) { /** An old incompatibility in the time field. */
					$_POST["input_{$field->id}"] = $entry[$field->id];
				} else {
					$_POST["input_{$field->id}"] = $entry[strval($input['id'])];
				}
			}
		}
		$_POST['input_1'] = "This has been changed";
		ob_start() && $render->init( $data, \GV\Entry::by_id( $entry['id'] ), \GV\View::from_post( $view ) ); ob_get_clean();
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
		$this->assertStringContainsString( 'gform_submit', $output );

		/** Submit an edit */
		$this->_reset_context();
		wp_set_current_user( $administrator );

		$_POST = array(
			'input_1' => 'we changed it',
			'input_2' => 102,
		);
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		/** Check updates */
		$this->assertEquals( $entry['1'], 'we changed it' );
		$this->assertEquals( $entry['2'], 102 );

		$this->assertStringContainsString( 'Entry Updated', $output );

		/** Cleanup */
		$this->_reset_context();
	}

	public function test_edit_entry_upload() {
		add_filter( 'gform_file_upload_whitelisting_disabled', '__return_true' );

		/** Create a user */
		$administrator = $this->_generate_user( 'administrator' );

		$filename = site_url( '/tmp/noexist-392393190d9_007/upload.txt' );

		/** Create the form, entry and view */
		$form = $this->factory->form->import_and_get( 'upload.json' );
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'created_by' => $administrator,
			'form_id' => $form['id'],
			'1' => $filename,
			'2' => '123',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		/** Request the rendered form */
		$this->_reset_context();
		wp_set_current_user( $administrator );
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		$this->assertStringContainsString( 'gform_submit', $output );
		$this->assertStringContainsString( 'upload.txt', $output );

		/** Try saving a change, but no touching the upload field. */
		$_POST = array(
			'gform_uploaded_files' => json_encode( array( 'input_1' => $entry['1'] ) ),
			'input_2' => '40',
			'input_3' => '',
		);
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		$this->assertEquals( $entry['1'], $filename, 'File upload got erased!' );

		/** Edit only the text input, see what happens to the upload. */
		$this->_reset_context();
		wp_set_current_user( $administrator );

		$_POST = array(
			'gform_uploaded_files' => json_encode( array( 'input_1' => $entry['1'] ) ),
			'input_2' => '29',
			'input_3' => '',
		);
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		$this->assertEquals( $entry['1'], $filename );
		$this->assertEquals( $entry['2'], '29' );

		/** Edit the upload, make sure it saves. */
		$_POST['input_2'] ='31';
		unset( $_POST['gform_uploaded_files'] );
		$tmp_name = tempnam( '/tmp/', 'gvtest_' );
		file_put_contents( $tmp_name, 'zZz' );
		$_FILES = array(
			'input_1' => array( 'name' => 'sleep.txt', 'type' => 'text', 'size' => 3, 'tmp_name' => $tmp_name, 'error' => UPLOAD_ERR_OK ),
		);

		// Since move_uploaded_file will not work, let's fake it...
		add_filter( 'gform_save_field_value', array( $this, '_fake_move_uploaded_file' ), 10, 5 );

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( $entry['1'], $this->_target['url'] );
		$this->assertEquals( $entry['2'], '31' );

		remove_filter( 'gform_save_field_value', array( $this, '_fake_move_uploaded_file' ), 10 );
		remove_filter( 'gform_file_upload_whitelisting_disabled', '__return_true' );
	}

	/**
	 * If temp file can't be copied during the test, fake a URL
	 * @used-by test_edit_entry_upload
	 */
	public function _fake_move_uploaded_file( $value, $lead, $field, $form, $input_id ) {

		if ( 'FAILED (Temporary file could not be copied.)' == $value ) {
			$target = GFFormsModel::get_file_upload_path( $form['id'], 'tiny.jpg' );
			$this->_target = $target;
			return $target['url'];
		}

		return $value;
	}

	/**
	 * @covers GravityView_Edit_Entry_Render::custom_validation()
	 * @covers GravityView_Edit_Entry_Render::validate()
	 * @covers GravityView_Edit_Entry_Render::get_configured_edit_fields()
	 * @covers GravityView_Edit_Entry_Render::user_can_edit_entry()
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
			'input_1' => 'we changed it',
			'input_2' => 102310,
		);

		/** No permissions to edit this entry */
		$this->_reset_context(); $_POST = $post;
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		$this->assertStringContainsString( 'do not have permission to edit this entry', $output );
		$this->assertEquals( $entry['1'], $entry['1'] );
		$this->assertEquals( $entry['2'], $entry['2'] );

		/** No permissions to edit this entry, not logged in. */
		$this->_reset_context(); $_POST = $post;
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		$this->assertStringContainsString( 'do not have permission to edit this entry', $output );
		$this->assertEquals( $entry['1'], $entry['1'] );
		$this->assertEquals( $entry['2'], $entry['2'] );

		/** No permissions to edit this entry, logged in as someone else. */
		$this->_reset_context(); $_POST = $post;
		wp_set_current_user( $subscriber2 );
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		$this->assertStringContainsString( 'do not have permission to edit this entry', $output );
		$this->assertEquals( $entry['1'], $entry['1'] );
		$this->assertEquals( $entry['2'], $entry['2'] );

		/** Only one field is visible and editable. */
		$this->_reset_context(); $_POST = $post;
		wp_set_current_user( $subscriber1 );

		add_filter( 'gravityview/edit_entry/form_fields', function( $fields, $edit_fields, $form, $view_id ) {
			unset( $fields[0] ); /** The first text field is now hidden. */
			return array_values( $fields );
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
		$this->assertStringContainsString( 'enter a valid number', $output );

		/** Cleanup */
		$this->_reset_context();
	}

	public function test_edit_entry_post_image() {
		/** Create a user */
		$administrator = $this->_generate_user( 'administrator' );

		$filename = site_url( '/tmp/noexist-9f9a9291490_001/upload.png' );

		/** Create the form, entry and view */
		$form = $this->factory->form->import_and_get( 'post_image.json' );
		$post = $this->factory->view->create_and_get();
		$input = "input_{$form['id']}";
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'created_by' => $administrator,
			'form_id' => $form['id'],
			'post_id' => $post->ID,
			'1' => "$filename|:|this is a title|:|this is a caption|:|this is a description",
			/**
			 * The entry goes through an empty state when
			 * saving, so we need to have a non-empty field
			 * present all the time. This is a bug that may
			 * pop up one day in the edit entry extension.
			 *
			 * Hopefully, we'd have rewritten it by then.
			 */
			'2' => 'oh wow',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		/** Request the rendered form */
		$this->_reset_context();
		wp_set_current_user( $administrator );
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		$this->assertStringContainsString( 'gform_submit', $output );
		$this->assertStringContainsString( "{$input}_1", $output );
		$this->assertStringContainsString( 'upload.png', $output );
		$this->assertStringContainsString( "{$input}_1_1", $output );
		$this->assertStringContainsString( 'this is a title', $output );
		$this->assertStringContainsString( "{$input}_1_4", $output );
		$this->assertStringContainsString( 'this is a caption', $output );
		$this->assertStringContainsString( "{$input}_1_7", $output );
		$this->assertStringContainsString( 'this is a description', $output );

		/** Try saving a change, but not touching the image upload field. */
		$_POST = array(
			'input_1' => $filename,
			'input_2' => 'wut',
			'input_1_1' => 'this is a title',
			'input_1_4' => 'this is another caption',
			'input_1_7' => 'this is another description',

			'gform_uploaded_files' => json_encode( array( 'input_1' => $entry['1'] ) ),
		);
		$_FILES['input_1'] = array( 'name' => '', 'type' => '', 'size' => 0, 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE );

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		$this->assertEquals( "$filename|:|this is a title|:|this is another caption|:|this is another description", $entry['1'], 'Post image data got erased!' );

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

		/** @type GravityView_Edit_Entry_User_Registration $registration */
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

		/** @type GravityView_Edit_Entry_User_Registration $registration */
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

		/** @type GravityView_Edit_Entry_User_Registration $registration */
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

	/**
	 * @covers GravityView_Entry_Approval::autounapprove
	 */
	public function test_unapprove_on_edit() {
		$subscriber = $this->factory->user->create( array(
				'user_login' => md5( microtime() ),
				'user_email' => md5( microtime() ) . '@gravityview.tests',
				'role' => 'subscriber' )
		);

		$administrator = $this->factory->user->create( array(
				'user_login' => md5( microtime() ),
				'user_email' => md5( microtime() ) . '@gravityview.tests',
				'role' => 'administrator' )
		);

		wp_set_current_user( 0 );

		$form = $this->factory->form->import_and_get( 'simple.json' );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'show_only_approved' => true,
				'user_edit' => true,
			),
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1',
					),
				),
			)
		) );
		$view = \GV\View::from_post( $post );

		// Create an initial entry by $subscriber
		$entry = $this->factory->entry->create_and_get( array(
			'created_by' => $subscriber,
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'this is one'
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;
		gravityview()->request->returns['is_entry'] = $entry;

		// The entry is not approved for viewing
		$this->assertStringContainsString( 'are not allowed', \GV\View::content( 'entry' ) );

		// Approve the entry
		gform_update_meta( $entry->ID, \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );

		// The entry is approved for viewing
		$this->assertStringNotContainsString( 'are not allowed', \GV\View::content( 'entry' ) );

		wp_set_current_user( $subscriber );

		// Edit the entry
		$_POST = array(
			'input_1' => 'this is two',
		);
		$this->_emulate_render( $form, $view, $entry->as_entry() );

		// It's still approved and modified
		$this->assertStringNotContainsString( 'this is two', \GV\View::content( 'entry' ) );

		// Update the View settings
		$view->settings->update( array( 'unapprove_edit' => true ) );

		// Edit the entry
		$_POST = array(
			'input_1' => 'this is three',
		);
		$this->_emulate_render( $form, $view, $entry->as_entry() );

		wp_set_current_user( 0 );

		// The entry is no longer approved
		$this->assertStringContainsString( 'are not allowed', \GV\View::content( 'entry' ) );

		// Approve it
		gform_update_meta( $entry->ID, \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );

		wp_set_current_user( $administrator );

		// Edit the entry (by admin)
		$_POST = array(
			'input_1' => 'this is four',
		);
		$this->_emulate_render( $form, $view, $entry->as_entry() );

		// It's still approved and modified
		$this->assertStringNotContainsString( 'this is four', \GV\View::content( 'entry' ) );

		$this->_reset_context();
	}

	public function test_form_render_default_fields() {

		// This deprecation notice has triggered intermittently in the past, so
		// we'll expect it and then trigger it at the end of the test.
		// Note: The structure of the notice is __CLASS__ . ':' . __METHOD__, which
		// looks wrong here, but isn't.
		$this->setExpectedDeprecated( 'GF_Field:GF_Field::get_conditional_logic_event' );

		/** Create a user */
		$administrator = $this->_generate_user( 'administrator' );

		/** Create the form, entry and view */
		$form = $this->factory->form->import_and_get( 'complete.json' );

		$form['fields'][1]->choices[4]['isSelected'] = true;;
		\GFAPI::update_form( $form );

		$entry = $this->factory->entry->create_and_get( array(
			'created_by' => $administrator,
			'status' => 'active',
			'form_id' => $form['id'],
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
		) );

		/** Request the rendered form */
		$this->_reset_context();
		wp_set_current_user( $administrator );
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		$this->assertStringNotContainsString( "value='Much Worse' checked='checked'", $output );

		$this->_reset_context();

		$form['fields'][1]->choices[4]['isSelected'] = true;;
		\GFAPI::update_form( $form );

		$entry = $this->factory->entry->create_and_get( array(
			'created_by' => $administrator,
			'status' => 'active',
			'form_id' => $form['id'],
			'2.1' => 'Much Better',
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
		) );

		/** Request the rendered form */
		$this->_reset_context();
		wp_set_current_user( $administrator );
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		$this->assertStringContainsString( "value='Much Better' checked='checked'", $output );
		$this->assertStringNotContainsString( "value='Much Worse' checked='checked'", $output );

		// Make sure we trigger a deprecation notice, which is expected, just to be sure.
		$field = new GF_Field();
		$field->get_conditional_logic_event( 'keyup' );

		$this->_reset_context();
	}

	public function test_simple_calculations() {
		$this->_reset_context();

		$administrator = $this->_generate_user( 'administrator' );

		$form = $this->factory->form->import_and_get( 'calculations.json' );

		$entry = $this->factory->entry->create_and_get( array(
			'status' => 'active',
			'form_id' => $form['id'],
			'1' => 2,
			'2' => 3,
			'3' => 5,
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
		) );

		add_filter( 'gravityview/edit_entry/form_fields', function( $fields ) {
			unset( $fields[2] ); // Hide the total field
			return array_values( $fields );
		} );

		$_POST = array(
			'input_1' => '5',
			'input_2' => '3',
		);

		wp_set_current_user( $administrator );
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( '5', $entry['1'] );
		$this->assertEquals( '3', $entry['2'] );
		$this->assertEquals( '8', $entry['3'] );

		$this->_reset_context();

		add_filter( 'gravityview/edit_entry/form_fields', function( $fields ) {
			unset( $fields[1] ); // Hide the second field
			unset( $fields[2] ); // Hide the total field
			return array_values( $fields );
		} );

		$_POST = array(
			'input_1' => '7',
		);

		wp_set_current_user( $administrator );
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( '7', $entry['1'] );
		$this->assertEquals( '3', $entry['2'] );
		$this->assertEquals( '10', $entry['3'] );

		$this->_reset_context();

		add_filter( 'gravityview/edit_entry/form_fields', function( $fields ) {
			unset( $fields[0] ); // Hide the first field
			return array_values( $fields );
		} );

		$_POST = array(
			'input_1' => '0', // Test security
			'input_2' => '4',
			'input_3' => '-1', // Test security
		);

		wp_set_current_user( $administrator );
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( '7', $entry['1'] );
		$this->assertEquals( '4', $entry['2'] );
		$this->assertEquals( '11', $entry['3'] );

		$this->_reset_context();
	}

	public function test_simple_product_calculations() {
		$this->_reset_context();

		$administrator = $this->_generate_user( 'administrator' );

		wp_set_current_user( $administrator );

		$form = $this->factory->form->import_and_get( 'calculations.json' );

		unset( $form['fields'][5] ); // Remove the calculation product
		$form['fields'] = array_values( $form['fields'] );
		\GFAPI::update_form( $form );

		$entry = $this->factory->entry->create_and_get( array(
			'status' => 'active',
			'form_id' => $form['id'],

			// No transaction data
			'payment_status' => '',
			'payment_date'   => '',
			'transaction_id' => '',
			'payment_amount' => '',
			'payment_method' => '',

			'4.1' => 'A',
			'4.2' => '$ 66.00',
			'4.3' => '1',

			'5.1' => 'B',
			'5.2' => '$ 12.00',
			'5.3' => '1',

			'7' => '78',
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
		) );

		$_POST = array(
			'input_4_1' => $entry['4.1'],
			'input_4_2' => $entry['4.2'],
			'input_4_3' => '5',

			'input_5_1' => $entry['5.1'],
			'input_5_2' => $entry['5.2'],
			'input_5_3' => $entry['5.3'],

			'input_7' => $entry['7'],
		);

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( 'A', $entry['4.1'] );
		$this->assertEquals( 'B', $entry['5.1'] );

		$this->assertEquals( '$ 66.00', $entry['4.2'] );
		$this->assertEquals( '$ 12.00', $entry['5.2'] );

		$this->assertEquals( '5', $entry['4.3'] );
		$this->assertEquals( '1', $entry['5.3'] );

		$this->assertEquals( '342', $entry['7'] );

		$this->_reset_context();

		wp_set_current_user( $administrator );

		add_filter( 'gravityview/edit_entry/form_fields', function( $fields ) {
			unset( $fields[4] ); // Hide the $12 one
			unset( $fields[7] ); // Hide the total
			return array_values( $fields );
		} );

		$_POST = array(
			'input_4_1' => $entry['4.1'],
			'input_4_2' => $entry['4.2'],
			'input_4_3' => '7',
		);

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( 'A', $entry['4.1'] );
		$this->assertEquals( 'B', $entry['5.1'] );

		$this->assertEquals( '$ 66.00', $entry['4.2'] );
		$this->assertEquals( '$ 12.00', $entry['5.2'] );

		$this->assertEquals( '7', $entry['4.3'] );
		$this->assertEquals( '1', $entry['5.3'] );

		$this->assertEquals( '474', $entry['7'] );

		$this->_reset_context();
	}

	public function test_product_calculations_with_formula() {
		$this->_reset_context();

		$administrator = $this->_generate_user( 'administrator' );

		wp_set_current_user( $administrator );

		$form = $this->factory->form->import_and_get( 'calculations.json' );

		$entry = $this->factory->entry->create_and_get( array(
			'status' => 'active',
			'form_id' => $form['id'],

			// No transaction data
			'payment_status' => '',
			'payment_date'   => '',
			'transaction_id' => '',
			'payment_amount' => '',
			'payment_method' => '',

			'1' => '3',
			'2' => '',
			'3' => '3',

			'4.1' => 'A',
			'4.2' => '$ 66.00',
			'4.3' => '1',

			'5.1' => 'B',
			'5.2' => '$ 12.00',
			'5.3' => '1',

			'6.1' => 'C',
			'6.2' => '$ 36.00',
			'6.3' => '3',

			'7' => '186',
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
		) );

		$_POST = array(
			'input_1' => '5',
			'input_2' => $entry['2'],
			'input_3' => $entry['3'],

			'input_4_1' => $entry['4.1'],
			'input_4_2' => $entry['4.2'],
			'input_4_3' => $entry['4.3'],

			'input_5_1' => $entry['5.1'],
			'input_5_2' => $entry['5.2'],
			'input_5_3' => $entry['5.3'],

			'input_6_1' => $entry['6.1'],
			'input_6_2' => $entry['6.2'],
			'input_6_3' => $entry['6.3'],

			'input_7' => $entry['7'],
		);

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( 'A', $entry['4.1'] );
		$this->assertEquals( 'B', $entry['5.1'] );
		$this->assertEquals( 'C', $entry['6.1'] );

		$this->assertEquals( '$ 66.00', $entry['4.2'] );
		$this->assertEquals( '$ 12.00', $entry['5.2'] );
		$this->assertEquals( '$60.00', $entry['6.2'] ); // Lol, GF recalculation removes the space :)

		$this->assertEquals( '1', $entry['4.3'] );
		$this->assertEquals( '1', $entry['5.3'] );
		$this->assertEquals( '3', $entry['6.3'] );

		$this->assertEquals( '258', $entry['7'] );

		$this->_reset_context();

		wp_set_current_user( $administrator );

		add_filter( 'gravityview/edit_entry/form_fields', function( $fields ) {
			unset( $fields[1] ); // Hide the second number
			unset( $fields[2] ); // Hide the calculation number
			unset( $fields[4] ); // Hide the $12 one
			unset( $fields[6] ); // Hide the total
			return array_values( $fields );
		} );

		$_POST = array(
			'input_1' => '9',

			'input_4_1' => $entry['4.1'],
			'input_4_2' => $entry['4.2'],
			'input_4_3' => '7',

			'input_6_1' => $entry['6.1'],
			'input_6_2' => $entry['6.2'],
			'input_6_3' => '9',
		);

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( 'A', $entry['4.1'] );
		$this->assertEquals( 'B', $entry['5.1'] );
		$this->assertEquals( 'C', $entry['6.1'] );

		$this->assertEquals( '$ 66.00', $entry['4.2'] );
		$this->assertEquals( '$ 12.00', $entry['5.2'] );
		$this->assertEquals( '$108.00', $entry['6.2'] );

		$this->assertEquals( '7', $entry['4.3'] );
		$this->assertEquals( '1', $entry['5.3'] );
		$this->assertEquals( '9', $entry['6.3'] );

		$this->assertEquals( '1446', $entry['7'] );

		$this->_reset_context();
	}

	/**
	 * https://secure.helpscout.net/conversation/676085022/16972/
	 */
	public function test_product_calculations_filtered_fields() {
		$this->_reset_context();

		$administrator = $this->_generate_user( 'administrator' );

		wp_set_current_user( $administrator );

		$form = $this->factory->form->import_and_get( 'calculations.json', 1 );

		$entry = $this->factory->entry->create_and_get( array(
			'status'  => 'active',
			'form_id' => $form['id'],

			// No transaction data
			'payment_status' => '',
			'payment_date'   => '',
			'transaction_id' => '',
			'payment_amount' => '',
			'payment_method' => '',

			'1'    => 'hello',

			'81.1' => 'Shipping',
			'81.2' => '$ 2.00',
			'81.3' => '5',

			'111'  => '1',

			'7'    => '66',
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'fields' => array(
				'edit_edit-fields' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1',
					),
				),
			)
		) );

		$_POST = array(
			'input_1' => 'world',
		);

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( 'world', $entry['1'] );
		$this->assertEquals( 'Shipping', $entry['81.1'] );
		$this->assertEquals( '$2.00', $entry['81.2'] );
		$this->assertEquals( '5', $entry['81.3'] );
		$this->assertEquals( '1', $entry['111'] );
		$this->assertEquals( '2', $entry['7'] );

		$this->_reset_context();
	}

	/**
	 * @dataProvider get_redirect_after_edit_data
	 */
	public function test_redirect_after_edit( $edit_redirect, $location, $edit_redirect_url = false ) {
		/** Create a user */
		$administrator = $this->_generate_user( 'administrator' );

		$form = $this->factory->form->import_and_get( 'simple.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'this is one'
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'show_only_approved' => true,
				'user_edit' => true,
				'edit_redirect' => $edit_redirect,
				'edit_redirect_url' => $edit_redirect_url
			),
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1',
					),
				),
			)
		) );

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = \GV\View::from_post( $view );
		gravityview()->request->returns['is_entry'] = \GV\GF_Entry::by_id( $entry['id'] );

		$this->_reset_context();
		wp_set_current_user( $administrator );

		// Edit the entry
		$_POST = array(
			'input_1' => 'this is ' . wp_generate_password( 4, false ),
		);

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertStringContainsString( 'Entry Updated', $output );

		if ( false !== $location ) {
			$output = str_replace( json_encode( get_permalink( $view ) ), '"{permalink}"', $output );
			$this->assertStringContainsString( sprintf( 'location.href = %s', json_encode( $location ) ), $output );

			$location = str_replace( '{permalink}', get_permalink( $view ), $location );
			$this->assertStringContainsString( sprintf( '<meta http-equiv="refresh" content="0;URL=%s" /></noscript>', esc_attr( $location ) ), $output );
		} else {
			$this->assertStringNotContainsString( 'location.href', $output );
		}

		$this->_reset_context();
	}

	function get_redirect_after_edit_data() {

		$custom_url = 'https://www.gravitykit.com/floaty-loves-you/?with=<>&wild[]=! &characters=",';

		return array(
			array( '', false ),
			array( '0', '' /** homepage; the view is here */ ),
			array( '1', '{permalink}' ),
			array( '2', $custom_url, $custom_url ),
		);
	}

	function test_hidden_conditional_edit_simple_conditioned_not_visible() {
		$this->_reset_context();

		$administrator = $this->_generate_user( 'administrator' );

		wp_set_current_user( $administrator );

		$form = $this->factory->form->import_and_get( 'conditionals.json' );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',

			'1'   => 'One name',
			'2.1' => 'I have another name, though',
			'3'   => 'Another name',
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'user_edit' => true,
			),
			'fields' => array(
				'edit_edit-fields' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1',
					),
				),
			)
		) );

		$_POST = array(
			'input_1' => 'My name',
		);

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( 'My name', $entry['1'] );
		$this->assertEquals( 'I have another name, though', $entry['2.1'] );
		$this->assertEquals( 'Another name', $entry['3'] );

		$this->_reset_context();
	}

	function test_hidden_conditional_edit_simple_conditioned_visible() {
		$this->_reset_context();

		$administrator = $this->_generate_user( 'administrator' );

		wp_set_current_user( $administrator );

		$form = $this->factory->form->import_and_get( 'conditionals.json' );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',

			'1'   => 'One name',
			'2.1' => 'I have another name, though',
			'3'   => 'Another name',
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'user_edit' => true,
			),
			'fields' => array(
				'edit_edit-fields' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '3',
					),
				),
			)
		) );

		$_POST = array(
			'input_1' => 'My name',
			'input_3' => 'My other name',
		);

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( 'My name', $entry['1'] );
		$this->assertEquals( 'I have another name, though', $entry['2.1'] );
		$this->assertEquals( 'My other name', $entry['3'] );

		$this->_reset_context();
	}

	public function test_edit_entry_feeds() {
		$this->_reset_context();

		/** Create a user */
		$administrator = $this->_generate_user( 'administrator' );

		$form = $this->factory->form->import_and_get( 'simple.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'this is one'
		) );

		$feed_id = GFAPI::add_feed( $form['id'], array(), 'GravityView_Edit_Entry_Test_Feed' );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'show_only_approved' => true,
				'user_edit' => true,
				'edit_feeds' => array( $feed_id, ),
			),
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1',
					),
				),
			)
		) );

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = \GV\View::from_post( $view );
		gravityview()->request->returns['is_entry'] = \GV\GF_Entry::by_id( $entry['id'] );

		wp_set_current_user( $administrator );

		// Edit the entry
		$_POST = array(
			'input_1' => 'this is ' . wp_generate_password( 4, false ),
		);

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( array( $entry['id'] ), GravityView_Edit_Entry_Test_Feed::$processed );

		$this->_reset_context();
	}

	public function test_field_visibility_and_editability_admin() {
		$this->_reset_context();

		/** Create a user */
		$administrator = $this->_generate_user( 'administrator' );

		$form = $this->factory->form->import_and_get( 'simple.json' );

		$form['fields'][0]->type = 'hidden';
		$form['fields'][1]->visibility= 'hidden';

		GFAPI::update_form( $form );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'this is one',
			'2' => 'this is two',
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'user_edit' => true,
			),
		) );

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view = \GV\View::from_post( $view );
		gravityview()->request->returns['is_entry'] = \GV\GF_Entry::by_id( $entry['id'] );

		wp_set_current_user( $administrator );

		$random_string = wp_generate_password( 4, false );

		// Edit the entry
		$_POST = array(
			'input_1' => 'this is ' . $random_string,
			'input_2' => '666',
		);

		add_filter( 'gravityview/edit_entry/render_hidden_field', '__return_false' );
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		remove_filter( 'gravityview/edit_entry/render_hidden_field', '__return_false' );

		$this->assertStringNotContainsString( "name='input_1'", $output );
		$this->assertStringNotContainsString( "name='input_2'", $output );
		$this->assertEquals( 'this is one', $entry[1], 'The value should not be updated when the Hidden field is not being rendered.' );
		$this->assertEquals( 'this is two', $entry[2] );

		// Since input 1 is now rendered, the value will be updated by _emulate_render()
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		$this->assertStringContainsString( "name='input_1'", $output );
		$this->assertStringNotContainsString( "name='input_2'", $output );
		$this->assertEquals( 'this is ' . $random_string, $entry[1] );
		$this->assertEquals( 'this is two', $entry[2] );

		// Reset the value after _emulate_render modifies it
		GFAPI::update_entry_field( $entry['id'], '1', 'this is one' );

		$view->fields = \GV\Field_Collection::from_configuration( array(
			'edit_edit-fields' => array(
				wp_generate_password( 4, false ) => array(
					'id' => '2',
				),
			),
		) );

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertStringNotContainsString( "name='input_1'", $output );
		$this->assertStringContainsString( "name='input_2'", $output );
		$this->assertEquals( 'this is one', $entry[1] );
		$this->assertEquals( '666', $entry[2] );

		$view->fields = \GV\Field_Collection::from_configuration( array(
			'edit_edit-fields' => array(
				wp_generate_password( 4, false ) => array(
					'id' => '1',
				),
			),
		) );

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertStringContainsString( "name='input_1'", $output );
		$this->assertStringNotContainsString( "name='input_2'", $output );
		$this->assertNotEquals( 'this is one', $entry[1] );
		$this->assertEquals( '666', $entry[2] );

		$this->_reset_context();
	}

	public function test_field_visibility_and_editability_caps() {
		$this->_reset_context();

		/** Create a user */
		$subscriber1 = $this->_generate_user( 'subscriber' );
		$subscriber2 = $this->_generate_user( 'subscriber' );

		$form = $this->factory->form->import_and_get( 'simple.json' );

		$form['fields'][0]->type = 'hidden';
		$form['fields'][1]->visibility = 'hidden';

		GFAPI::update_form( $form );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'this is one',
			'2' => 'this is two',
			'created_by' => $subscriber1,
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'user_edit' => true,
			),
		) );

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view = \GV\View::from_post( $view );
		gravityview()->request->returns['is_entry'] = \GV\GF_Entry::by_id( $entry['id'] );

		wp_set_current_user( $subscriber1 );

		// Edit the entry
		$_POST = array(
			'input_1' => 'this is ' . wp_generate_password( 4, false ),
			'input_2' => '666',
		);

		add_filter( 'gravityview/edit_entry/render_hidden_field', '__return_false' );
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );
		remove_filter( 'gravityview/edit_entry/render_hidden_field', '__return_false' );

		$this->assertStringNotContainsString( "name='input_1'", $output );
		$this->assertStringNotContainsString( "name='input_2'", $output );
		$this->assertEquals( 'this is one', $entry[1] );
		$this->assertEquals( 'this is two', $entry[2] );

		$view->fields = \GV\Field_Collection::from_configuration( array(
			'edit_edit-fields' => array(
				wp_generate_password( 4, false ) => array(
					'id' => '2',
					'allow_edit_cap' => 'manage_options',
				),
			),
		) );

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertStringNotContainsString( "name='input_1'", $output );
		$this->assertStringNotContainsString( "name='input_2'", $output );
		$this->assertEquals( 'this is one', $entry[1] );
		$this->assertEquals( 'this is two', $entry[2] );

		$view->fields = \GV\Field_Collection::from_configuration( array(
			'edit_edit-fields' => array(
				wp_generate_password( 4, false ) => array(
					'id' => '2',
					'allow_edit_cap' => 'gravity_forms_edit_entry',
				),
			),
		) );

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertStringNotContainsString( "name='input_1'", $output );
		$this->assertStringNotContainsString( "name='input_2'", $output );
		$this->assertEquals( 'this is one', $entry[1] );
		$this->assertEquals( 'this is two', $entry[2] );

		$view->fields = \GV\Field_Collection::from_configuration( array(
			'edit_edit-fields' => array(
				wp_generate_password( 4, false ) => array(
					'id' => '2',
					'allow_edit_cap' => 'read',
				),
			),
		) );

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertStringNotContainsString( "name='input_1'", $output );
		$this->assertStringContainsString( "name='input_2'", $output );
		$this->assertEquals( 'this is one', $entry[1] );
		$this->assertEquals( '666', $entry[2] );

		wp_set_current_user( $subscriber2 );

		$_POST = array(
			'input_1' => 'this is ' . wp_generate_password( 4, false ),
			'input_2' => '777',
		);

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertStringNotContainsString( 'input_1', $output );
		$this->assertStringNotContainsString( 'input_2', $output );
		$this->assertEquals( 'this is one', $entry[1] );
		$this->assertEquals( '666', $entry[2] );

		$this->_reset_context();
	}

	public function test_hidden_calculations() {
		$this->_reset_context();

		$administrator = $this->_generate_user( 'administrator' );

		$form = $this->factory->form->import_and_get( 'calculations.json' );

		$form['fields'][2]->conditionalLogic = array(
			'rules' => array(
				array(
					'fieldId' => '1',
					'value' => 99, // Hide if 99
					'operator' => 'is',
				),
			),
			'logicType' => 'all',
			'actionType' => 'hide',
		);

		\GFAPI::update_form( $form );

		$entry = $this->factory->entry->create_and_get( array(
			'status' => 'active',
			'form_id' => $form['id'],
			'1' => 96,
			'2' => 3,
			'3' => 99,
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
		) );

		$_POST = array(
			'input_1' => '99', // 3 should be hidden
			'input_2' => '7',
		);

		wp_set_current_user( $administrator );
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( '99', $entry['1'] );
		$this->assertEquals( '7', $entry['2'] );
		$this->assertEmpty( $entry['3'] );

		$this->_reset_context();
	}

	function test_hidden_conditional_unrelated_field_cache_prefill() {
		$this->_reset_context();

		$administrator = $this->_generate_user( 'administrator' );

		wp_set_current_user( $administrator );

		$form = $this->factory->form->import_and_get( 'conditionals.json', 1 );

		// Hydrate the cache (https://github.com/gravityview/GravityView/issues/840#issuecomment-547840611)
		\GFFormsModel::get_field( $form['id'], '1' );
		\GFFormsModel::get_field( $form['id'], '2' );
		\GFFormsModel::get_field( $form['id'], '3' );
		\GFFormsModel::get_field( $form['id'], '4' );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',

			'1' => '2',
			'2' => '2-1',
			'3' => '',
			'4' => 'Processing',
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'user_edit' => true,
			),
			'fields' => array(
				'edit_edit-fields' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '4',
					),
				),
			)
		) );

		$_POST = array(
			'input_4' => 'New',
		);

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( '2', $entry['1'] );
		$this->assertEquals( '2-1', $entry['2'] );
		$this->assertEmpty( $entry['3'] );
		$this->assertEquals( 'New', $entry['4'] );

		$this->_reset_context();
	}

	function test_hidden_conditional_reset_hidden_value_once_more() {
		$this->_reset_context();

		$administrator = $this->_generate_user( 'administrator' );

		wp_set_current_user( $administrator );

		$form = $this->factory->form->import_and_get( 'conditionals.json', 1 );

		// Hydrate the cache (https://github.com/gravityview/GravityView/issues/840#issuecomment-547840611)
		\GFFormsModel::get_field( $form['id'], '1' );
		\GFFormsModel::get_field( $form['id'], '2' );
		\GFFormsModel::get_field( $form['id'], '3' );
		\GFFormsModel::get_field( $form['id'], '4' );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',

			'1' => '2',
			'2' => '2-1',
			'3' => '',
			'4' => 'Processing',
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'user_edit' => true,
			),
			'fields' => array(
				// All fields visible
			)
		) );

		$_POST = array(
			'input_1' => '3',
			'input_2' => '2-1',
			'input_3' => '3-1',
			'input_4' => 'New',
		);

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( '3', $entry['1'] );
		$this->assertEmpty( $entry['2'] );
		$this->assertEquals( '3-1', $entry['3'] );
		$this->assertEquals( 'New', $entry['4'] );

		$this->_reset_context();
	}

	function test_partial_form_after_update() {
		$this->_reset_context();

		$administrator = $this->_generate_user( 'administrator' );

		wp_set_current_user( $administrator );

		$form = $this->factory->form->import_and_get( 'conditionals.json', 1 );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',

			'1' => '2',
			'2' => '2-1',
			'3' => '',
			'4' => 'Processing',
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'user_edit' => true,
			),
			'fields' => array(
				'edit_edit-fields' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '4',
					),
				),
			)
		) );

		$_POST = array(
			'input_1' => '3',
			'input_2' => '2-1',
			'input_3' => '3-1',
			'input_4' => 'New',
		);

		$test = &$this;
		$done_callback = false;
		add_action( 'gform_after_update_entry', $callback = function( $form ) use ( $test, &$done_callback ) {
			$test->assertCount( 4, $form['fields'] );
			$done_callback = true;
		} );

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertTrue( $done_callback );

		remove_action( 'gform_after_update_entry', $callback );

		$this->_reset_context();
	}

	function test_approval_transitions() {
		$this->_reset_context();

		$administrator = $this->_generate_user( 'administrator' );

		wp_set_current_user( $administrator );

		$form = $this->factory->form->import_and_get( 'approval.json', 0 );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',

			'1' => 'hello',
			'2' => 'world',
			'3.1' => 'Approved',
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
		) );

		$_POST = array(
			'input_1' => 'hellow',
			'input_2' => 'orld',
			'input_3_1' => 'Approved',
		);

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( 'hellow', $entry[1] );
		$this->assertEquals( 'orld', $entry[2] );
		$this->assertEquals( 'Approved', $entry['3.1'] );
		$this->assertEquals( GravityView_Entry_Approval_Status::APPROVED, $entry['is_approved'] );

		$this->_reset_context();

		wp_set_current_user( $administrator );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'edit_edit-fields' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '2',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '3',
					),
				),
			),
		) );

		$_POST = array(
			'input_1' => 'hellowo',
			'input_2' => 'rld',
			'input_3_1' => '',
		);

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( 'hellow', $entry[1] );
		$this->assertEquals( 'rld', $entry[2] );
		$this->assertEquals( '', $entry['3.1'] );
		$this->assertEquals( GravityView_Entry_Approval_Status::DISAPPROVED, $entry['is_approved'] );

		$entry['3.1'] = 'Approved';
		GFAPI::update_entry( $entry );
		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );

		$this->_reset_context();

		wp_set_current_user( $administrator );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'edit_edit-fields' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1',
					),
				),
			),
		) );

		$_POST = array(
			'input_1' => 'hellowo',
			'input_2' => 'ld',
		);

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( 'hellowo', $entry[1] );
		$this->assertEquals( 'rld', $entry[2] );
		$this->assertEquals( 'Approved', $entry['3.1'] );
		$this->assertEquals( GravityView_Entry_Approval_Status::APPROVED, $entry['is_approved'] );

		$entry['3.1'] = '';
		GFAPI::update_entry( $entry );
		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::UNAPPROVED );

		$_POST = array(
			'input_1' => 'hellowo',
			'input_2' => 'ld',
		);

		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEquals( 'hellowo', $entry[1] );
		$this->assertEquals( 'rld', $entry[2] );
		$this->assertEquals( '', $entry['3.1'] );
		$this->assertEquals( GravityView_Entry_Approval_Status::UNAPPROVED, $entry['is_approved'] );

		$this->_reset_context();
	}

	public function test_required_upload_field_with_conditional_logic_and_invalid_extension_validation() {
		$this->_reset_context();

		// Create a form with a required file upload field, extension and with conditional logic.
		$form = $this->factory->form->import_and_get( 'upload.json' );

		$form['fields'][0]->conditionalLogic  = [
			'rules'      => [
				[
					'fieldId'  => '2',
					'value'    => 'foo',
					'operator' => 'is',
				],
			],
			'logicType'  => 'all',
			'actionType' => 'show',
		];
		$form['fields'][0]->isRequired        = true;
		$form['fields'][0]->allowedExtensions = 'pdf';

		\GFAPI::update_form( $form );

		// Create a View with 2 fields to test conditional logic.
		$view = $this->factory->view->create_and_get( [
			'form_id'     => $form['id'],
			'template_id' => 'table',
			'fields'      => [
				'edit_edit-fields' => [
					wp_generate_password( 4, false ) => [
						'id' => '1',
					],
					wp_generate_password( 4, false ) => [
						'id' => '2',
					],
				],
			],
		] );

		// Create an entry.
		$administrator = $this->_generate_user( 'administrator' );

		wp_set_current_user( $administrator );

		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', [
			'created_by' => $administrator,
			'form_id'    => $form['id'],
			'2'          => 'bar',
		] );

		$file = tempnam( '/tmp/', 'gvtest_' );
		file_put_contents( $file, 'not PDF' );

		$_FILES = [
			'input_1' => [
				'name'     => 'not_pdf.txt',
				'size'     => 1,
				'tmp_name' => $file,
				'error'    => UPLOAD_ERR_OK,
			],
		];

		$_POST = [
			'input_2' => 'bar',
		];

		[ $output, $render, $entry ] = $this->_emulate_render( $form, $view, $entry );
		$this->assertEquals( 'bar', $entry['2'] );
		$this->assertEquals( '', $entry['1'] ); // The file should not be uploaded because the required field's value is not 'foo';

		$_POST = [
			'input_2' => 'foo',
		];

		[ $output, $render, $entry ] = $this->_emulate_render( $form, $view, $entry );
		$this->assertNotEquals( 'foo', $entry['2'] );
		$this->assertEquals( '', $entry['1'] );
		$this->assertStringContainsString( "gfield_validation_message'>The uploaded file type is not allowed. Must be one of the following: pdf", $output );

		// Reset the context since the previous render failed validation.
		$form['fields'][0]                    = GFFormsModel::get_field( $form['id'], 1 );
		$form['fields'][0]->failed_validation = false;

		add_filter( 'gform_save_field_value', [ $this, '_fake_move_uploaded_file' ], 10, 5 );

		file_put_contents( $file, '%PDF-1.1' );

		$_FILES = [
			'input_1' => [
				'name'     => 'pdf.pdf',
				'size'     => 1,
				'tmp_name' => $file,
				'error'    => UPLOAD_ERR_OK,
			],
		];

		[ $output, $render, $entry ] = $this->_emulate_render( $form, $view, $entry );
		$this->assertEquals( 'foo', $entry['2'] );
		$this->assertNotEmpty( $entry['1'] );

		remove_all_filters( 'gform_save_field_value' );
		$this->_reset_context();
	}


	public function test_validation_of_required_fields_with_conditional_logic() {
		$this->_reset_context();

		$administrator = $this->_generate_user( 'administrator' );

		$form = $this->factory->form->import_and_get( 'standard.json' );

		$entry = $this->factory->entry->import_and_get( 'standard_entry.json', array(
			'created_by' => $administrator,
			'status' => 'active',
			'form_id' => $form['id'],
			'5' => 'text',
			'8' => '',
		) );

		$form['fields'][7]->conditionalLogic = array(
			'rules' => array(
				array(
					'fieldId' => '2.1',
					'value' => 'choice 2',
					'operator' => 'is',
				),
			),
			'logicType' => 'all',
			'actionType' => 'show',
		);
		$form['fields'][7]->isRequired = true;

		\GFAPI::update_form( $form );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				// Excluding field ID 2
				'edit_edit-fields' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '5',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '8',
					),
				),
			),
		) );

		$_POST = array(
			'input_5' => 'updated text',
		);

		wp_set_current_user( $administrator );
		list( $output, $render, $entry ) = $this->_emulate_render( $form, $view, $entry );

		$this->assertEmpty( $entry['8'] );
		$this->assertStringContainsString('This field is required', $output);

		$this->_reset_context();
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

			public function get_meta_value( $key, $feed, $form, $entry ) {
				return '';
			}
		}
	}
}

if ( ! class_exists( 'GravityView_Edit_Entry_Test_Feed' ) ) {
	GFForms::include_feed_addon_framework();

	class GravityView_Edit_Entry_Test_Feed extends GFFeedAddOn {
		public static $processed = array();

		protected $_slug = 'GravityView_Edit_Entry_Test_Feed';

		public function process_feed( $feed, $entry, $form ) {
			self::$processed[] = $entry['id'];
		}

		public static function reset() {
			self::$processed = array();
		}

		public static function get_instance() {
			return new self;
		}
	}

	$feed = new GravityView_Edit_Entry_Test_Feed();
	$feed->setup();

	GFAddon::register( 'GravityView_Edit_Entry_Test_Feed' );
}
