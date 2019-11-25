<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group duplicateentry
 */
class GravityView_Duplicate_Entry_Test extends GV_UnitTestCase {
	/**
	 * Reset the edit entry context.
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
}
