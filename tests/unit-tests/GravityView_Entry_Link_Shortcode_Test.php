<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group entry_link_shortcode
 * @group shortcode
 * @since 1.15
 */
class GravityView_Entry_Link_Shortcode_Test extends GV_UnitTestCase {

	/** @type  \GV\Shortcodes\gv_entry_link */
	var $object;

	public function setUp() : void {
		parent::setUp();
		$this->object = new \GV\Shortcodes\gv_entry_link;
	}

	/**
	 * @covers GravityView_Entry_Link_Shortcode::__construct
	 * @covers GravityView_Entry_Link_Shortcode::add_hooks
	 */
	public function test_add_hooks() {
		$this->assertTrue( shortcode_exists('gv_entry_link') );
		$this->assertTrue( shortcode_exists('gv_edit_entry_link') );
		$this->assertTrue( shortcode_exists('gv_delete_entry_link') );
	}

	/**
	 * @covers GravityView_Entry_Link_Shortcode::shortcode
	 */
	public function test_shortcode() {

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

		$post_title = new WP_UnitTest_Generator_Sequence( __METHOD__ . ' %s' );
		$post_id = $this->factory->post->create(array(
			'post_title' => $post_title->next(),
			'post_content' => sprintf( '[gravityview id="%d"]', $view->ID ),
		));

		$atts = array(
			'post_id' => $post_id,
			'entry_id' => $entry['id'],
			'view_id' => $view->ID,
		);

		$this->_test_read( $view, $entry, $atts );
		$this->_test_edit( $view, $entry, $atts );
		$this->_test_delete( $view, $entry, $atts );

		$this->_test_embed( $view, $entry, $atts );
	}

	/**
	 * @since 2.0.9
	 */
	function _test_embed( $view, $entry, $atts ) {

		$post_title = new WP_UnitTest_Generator_Sequence( __METHOD__ . ' %s' );
		$post_id = $this->factory->post->create(array(
			'post_title' => $post_title->next(),
			'post_content' => sprintf( '[gv_entry_link action="read" entry_id="%d" view_id="%d"]', $atts['entry_id'], $view->ID ),
		));

		$post = get_post( $post_id );

		$post_content = apply_filters( 'the_content', $post->post_content );

		// Link to the View
		$this->assertEquals( '<a href="http://example.org/?gravityview=' . $view->post_name . '&amp;entry='. $atts['entry_id'] .'">View Details</a>', trim( $post_content ), 'Embedded failed' );

		$post_content = apply_filters( 'the_content', sprintf( '[gv_entry_link action="read" entry_id="%d" view_id="%d" post_id="%d"]', $atts['entry_id'], $view->ID, $post_id ) );

		// Link to the Embed
		$this->assertEquals( '<a href="http://example.org/?p='. $post_id .'&amp;entry='. $atts['entry_id'] .'">View Details</a>', trim( $post_content ), 'Embedded failed' );

		$post_content = apply_filters( 'the_content', sprintf( '[gv_entry_link entry_id="%d" post_id="%d"]', $atts['entry_id'], $view->ID, $post_id ) );
		$this->assertEquals( '', trim( $post_content ), 'You should need to have a View ID at least.' );

		$post_content = apply_filters( 'the_content', sprintf( '[gv_entry_link view_id="%d" post_id="%d"]', $atts['entry_id'], $view->ID, $post_id ) );
		$this->assertEquals( '', trim( $post_content ), 'You need an Entry ID when you are not inside a View' );
	}

	/**
	 * @covers GravityView_Entry_Link_Shortcode::read_shortcode
	 */
	function _test_read( $view, $entry, $atts ) {

		$link = $this->object->callback( $atts, '', 'gv_entry_link' );

		$gvid = GravityView_View_Data::getInstance()->has_multiple_views() ? '&gvid='.gravityview_get_view_id() : '';

		$this->assertEquals( '<a href="http://example.org/?p='.$atts['post_id'].'&amp;entry='.$atts['entry_id']. esc_attr( $gvid ) . '">View Details</a>', $link, 'no action' );

		$atts['return'] = 'url';
		$link_return_url = $this->object->callback( $atts, '', 'gv_entry_link' );
		$this->assertEquals( 'http://example.org/?p='.$atts['post_id'].'&entry='.$atts['entry_id'] . $gvid, $link_return_url, 'no action, url only' );
	}

	/**
	 * @covers GravityView_Entry_Link_Shortcode::delete_shortcode
	 */
	function _test_delete( $view, $entry, $atts ) {

		// NO CAPS
		$this->factory->user->create_and_set(array( 'user_login' => 'zero', 'role' => 'zero'));

		$zero_link = $this->object->callback( $atts, '', 'gv_delete_entry_link' );
		$this->assertNull( $zero_link, 'user without caps shouldn\'t see delete link' );

		// ADMIN
		$this->factory->user->create_and_set(array( 'user_login' => 'administrator', 'role' => 'administrator') );

		$delete_entry_delete_link = GravityView_Delete_Entry::get_delete_link( $entry, $view->ID, $atts['post_id'] );

		$atts['return'] = 'html';
		$delete_link = $this->object->callback( $atts, '', 'gv_delete_entry_link' );
		$atts['action'] = 'delete';
		$delete_link_backward_compat = $this->object->callback( $atts, '', 'gv_entry_link' );
		$this->assertEquals( '<a href="'. esc_url_raw( $delete_entry_delete_link ) .'" onclick="return window.confirm(&#039;Are you sure you want to delete this entry? This cannot be undone.&#039;);">Delete Entry</a>', $delete_link, 'delete link' );
		$this->assertEquals( $delete_link, $delete_link_backward_compat );

		$atts['return'] = 'url';
		$delete_link_return_url = $this->object->callback( $atts, '', 'gv_delete_entry_link' );
		$this->assertEquals( $delete_entry_delete_link, $delete_link_return_url, 'delete link URL only' );
	}

	/**
	 * @covers GravityView_Entry_Link_Shortcode::edit_shortcode
	 */
	function _test_edit( $view, $entry, $atts ) {

		$nonce_key = GravityView_Edit_Entry::get_nonce_key( $view->ID, $entry['form_id'], $entry['id']  );

		$nonce = wp_create_nonce( $nonce_key );

		$gvid = '&gvid='.$view->ID;

		$atts['return'] = 'html';
		$edit_link = $this->object->callback( $atts, '', 'gv_edit_entry_link' );
		$atts['action'] = 'edit';
		$edit_link_backward_compat = $this->object->callback( $atts, '', 'gv_entry_link' );
		$this->assertEquals( $edit_link, $edit_link_backward_compat );
		$this->assertEquals( '<a href="http://example.org/?p='.$atts['post_id'].'&amp;entry='.$atts['entry_id'] . '&amp;edit='.$nonce. esc_attr( $gvid ) . '">Edit Entry</a>', $edit_link, 'edit link' );

		$atts['return'] = 'url';
		$edit_link_return_url = $this->object->callback( $atts, '', 'gv_edit_entry_link' );
		$this->assertEquals( 'http://example.org/?p='.$atts['post_id'].'&entry='.$atts['entry_id'] . '&edit='.$nonce . $gvid, $edit_link_return_url, 'edit link URL only' );

		$atts['return'] = 'html';
		$atts['link_atts'] = 'target="_blank"&title="check me out!"';
		$edit_link_link_atts = $this->object->callback( $atts, '', 'gv_edit_entry_link' );
		$this->assertEquals( '<a href="http://example.org/?p='.$atts['post_id'].'&amp;entry='.$atts['entry_id'] .'&amp;edit='.$nonce . esc_attr( $gvid ) . '" target="&quot;_blank&quot;" title="&quot;check me out!&quot;">Edit Entry</a>', $edit_link_link_atts, 'edit link, return html, with link_atts target="_blank"&title="check me out!"' );

		$atts['return'] = 'html';
		$atts['link_atts'] = 'target=_blank&title=check me out!';
		$edit_link_link_atts = $this->object->callback( $atts, '', 'gv_edit_entry_link' );
		$this->assertEquals( '<a href="http://example.org/?p='.$atts['post_id'].'&amp;entry='.$atts['entry_id'] . '&amp;edit='.$nonce . esc_attr( $gvid ) . '" rel="noopener noreferrer" target="_blank" title="check me out!">Edit Entry</a>', $edit_link_link_atts, 'edit link return html with link atts target=_blank&title=check me out!' );

		global $post;
		$post = get_post( $atts['post_id'] );

		\GV\Mocks\Legacy_Context::push( array( 'view' => \GV\View::from_post( $view ), 'entry' => \GV\GF_Entry::from_entry( $entry ), 'post' => $post ) );

		$edit_link_no_atts = $this->object->callback( array(), '', 'gv_edit_entry_link' );
		$gv_back = '&amp;' . GravityView_API::BACK_LINK_PARAM . '=' . $atts['post_id'];
		$this->assertEquals( '<a href="http://example.org/?gravityview='.$view->post_name.'&amp;entry='.$atts['entry_id'] . $gv_back . '&amp;edit='.$nonce.esc_attr( $gvid ).'">Edit Entry</a>', $edit_link_no_atts, 'edit link no atts' );

		add_filter( 'gravityview_custom_entry_slug', '__return_true' );
		$edit_link_no_atts_custom_slug = $this->object->callback( array(), '', 'gv_edit_entry_link' );
		$entry_slug = GravityView_API::get_entry_slug( $entry['id'], $entry );
		$this->assertEquals( '<a href="http://example.org/?gravityview='.$view->post_name.'&amp;entry='.$entry_slug . $gv_back . '&amp;edit='.$nonce.esc_attr( $gvid ).'">Edit Entry</a>', $edit_link_no_atts_custom_slug, 'edit link no atts custom slug' );
		remove_filter( 'gravityview_custom_entry_slug', '__return_true' );

		\GV\Mocks\Legacy_Context::pop();

		$this->_reset_context();

		$zero = $this->factory->user->create_and_set(array('role' => 'zero'));

		// User without edit entry caps should not be able to see link
		$this->assertNull( $this->object->callback( $atts, '', 'gv_edit_entry_link' ), 'user with no caps shouldn\'t be able to see link' );

		$this->_reset_context();
	}

	/**
	 * @group gv_entry_link
	 */
	public function test_entry_link_in_custom_content() {
		$this->_reset_context();

		$form = $this->factory->form->import_and_get( 'simple.json' );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'content' => '[gv_entry_link]No Attributes Defined[/gv_entry_link]',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'content' => '[gv_entry_link entry_id="{entry_id}"]Only Entry ID[/gv_entry_link]',
					),
				),
			),
			'settings' => $settings,
		) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
		) );
		$view = \GV\View::from_post( $post );

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;

		$renderer = new \GV\View_Renderer();

		$rendered_view = $renderer->render( $view );



		$this->assertStringContainsString( 'entry='.$entry['id'].'">No Attributes Defined', $rendered_view );
		$this->assertStringContainsString( 'entry='.$entry['id'].'">Only Entry ID', $rendered_view );

		$post = $this->factory->post->create_and_get(array(
			'post_content' => sprintf( '[gravityview id="%d"]', $view->ID )
		));

		$administrator = $this->factory->user->create( array(
				'user_login' => md5( microtime() ),
				'user_email' => md5( microtime() ) . '@gravityview.tests',
				'role' => 'administrator' )
		);

		if ( function_exists( 'grant_super_admin' ) ) {
			grant_super_admin( $administrator );
		}
		wp_set_current_user( $administrator );

		$this->_reset_context();
	}

	/**
	 * Resets the GravityView context, both old and new.
	 */
	private function _reset_context() {
		\GV\Mocks\Legacy_Context::reset();
		gravityview()->request = new \GV\Frontend_Request();

		global $wp_query, $post;

		$wp_query = new WP_Query();
		$post = null;

		\GV\View::_flush_cache();

		set_current_screen( 'front' );
		wp_set_current_user( 0 );
	}
}
