<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group entry_link_shortcode
 * @group shortcode
 * @since 1.15
 */
class GravityView_Entry_Link_Shortcode_Test extends GV_UnitTestCase {

	/** @type  GravityView_Entry_Link_Shortcode */
	var $object;

	public function setUp() {
		parent::setUp();
		$this->object = new GravityView_Entry_Link_Shortcode;
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
	}

	/**
	 * @covers GravityView_Entry_Link_Shortcode::read_shortcode
	 */
	function _test_read( $view, $entry, $atts ) {

		$link = $this->object->read_shortcode( $atts );

		$gvid = GravityView_View_Data::getInstance()->has_multiple_views() ? '&gvid='.gravityview_get_view_id() : '';

		$this->assertEquals( '<a href="http://example.org/?p='.$atts['post_id'].'&amp;entry='.$atts['entry_id']. esc_attr( $gvid ) . '">View Details</a>', $link, 'no action' );

		$atts['return'] = 'url';
		$link_return_url = $this->object->read_shortcode( $atts );
		$this->assertEquals( 'http://example.org/?p='.$atts['post_id'].'&entry='.$atts['entry_id'] . $gvid, $link_return_url, 'no action, url only' );
	}

	/**
	 * @covers GravityView_Entry_Link_Shortcode::delete_shortcode
	 */
	function _test_delete( $view, $entry, $atts ) {

		// NO CAPS
		$this->factory->user->create_and_set(array( 'user_login' => 'zero', 'role' => 'zero'));

		$zero_link = $this->object->delete_shortcode( $atts );
		$this->assertNull( $zero_link, 'user without caps shouldn\'t see delete link' );

		// ADMIN
		$this->factory->user->create_and_set(array( 'user_login' => 'administrator', 'role' => 'administrator') );

		$delete_entry_delete_link = GravityView_Delete_Entry::get_delete_link( $entry, $view->ID, $atts['post_id'] );

		$atts['return'] = 'html';
		$delete_link = $this->object->delete_shortcode( $atts );
		$atts['action'] = 'delete';
		$delete_link_backward_compat = $this->object->read_shortcode( $atts );
		$this->assertEquals( '<a href="'. esc_url_raw( $delete_entry_delete_link ) .'" onclick="return window.confirm(&#039;Are you sure you want to delete this entry? This cannot be undone.&#039;);">Delete Entry</a>', $delete_link, 'delete link' );
		$this->assertEquals( $delete_link, $delete_link_backward_compat );

		$atts['return'] = 'url';
		$delete_link_return_url = $this->object->delete_shortcode( $atts );
		$this->assertEquals( $delete_entry_delete_link, $delete_link_return_url, 'delete link URL only' );
	}

	/**
	 * @covers GravityView_Entry_Link_Shortcode::edit_shortcode
	 */
	function _test_edit( $view, $entry, $atts ) {

		$nonce_key = GravityView_Edit_Entry::get_nonce_key( $view->ID, $entry['form_id'], $entry['id']  );

		$nonce = wp_create_nonce( $nonce_key );

		$gvid = GravityView_View_Data::getInstance()->has_multiple_views() ? '&gvid='.gravityview_get_view_id() : '';

		$atts['return'] = 'html';
		$edit_link = $this->object->edit_shortcode( $atts );
		$atts['action'] = 'edit';
		$edit_link_backward_compat = $this->object->read_shortcode( $atts );
		$this->assertEquals( $edit_link, $edit_link_backward_compat );
		$this->assertEquals( '<a href="http://example.org/?p='.$atts['post_id'].'&amp;entry='.$atts['entry_id'].esc_attr( $gvid ) .'&amp;page=gf_entries&amp;view=entry&amp;edit='.$nonce.'">Edit Entry</a>', $edit_link, 'edit link' );

		$atts['return'] = 'url';
		$edit_link_return_url = $this->object->edit_shortcode( $atts );
		$this->assertEquals( 'http://example.org/?p='.$atts['post_id'].'&entry='.$atts['entry_id']. $gvid . '&page=gf_entries&view=entry&edit='.$nonce , $edit_link_return_url, 'edit link URL only' );

		$atts['return'] = 'html';
		$atts['link_atts'] = 'target="_blank"&title="check me out!"';
		$edit_link_link_atts = $this->object->edit_shortcode( $atts );
		$this->assertEquals( '<a href="http://example.org/?p='.$atts['post_id'].'&amp;entry='.$atts['entry_id'].esc_attr( $gvid ) .'&amp;page=gf_entries&amp;view=entry&amp;edit='.$nonce . '" target="&quot;_blank&quot;" title="&quot;check me out!&quot;">Edit Entry</a>', $edit_link_link_atts, 'edit link, return html, with link_atts target="_blank"&title="check me out!"' );

		$atts['return'] = 'html';
		$atts['link_atts'] = 'target=_blank&title=check me out!';
		$edit_link_link_atts = $this->object->edit_shortcode( $atts );
		$this->assertEquals( '<a href="http://example.org/?p='.$atts['post_id'].'&amp;entry='.$atts['entry_id']. esc_attr( $gvid ) . '&amp;page=gf_entries&amp;view=entry&amp;edit='.$nonce . '" rel="noopener noreferrer" target="_blank" title="check me out!">Edit Entry</a>', $edit_link_link_atts, 'edit link return html with link atts target=_blank&title=check me out!' );

		$zero = $this->factory->user->create_and_set(array('role' => 'zero'));

		// User without edit entry caps should not be able to see link
		$this->assertNull( $this->object->edit_shortcode( $atts ), 'user with no caps shouldn\'t be able to see link' );
	}
}
