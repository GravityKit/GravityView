<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group cache
 */
class GravityView_Cache_Test extends GV_UnitTestCase {

	public function test_cache() {
		$cache = new GravityView_Cache( array( 1 ) );

		$this->assertEquals( 'gv-cache-f:1-8739602554c7f3241958e3cc9b57fdec', $cache->get_key() );

		$cache->set( 'failure is not an option' );

		$this->assertNull( $cache->get( 'random' ) );

		$this->assertEquals( 'failure is not an option', $cache->get() );

		$cache->delete();

		$this->assertNull( $cache->get(), 'Cache was not deleted (transients object cache)' );
	}

	public function test_cache_multi() {
		$cache = new GravityView_Cache( array( 0, 11, 4 ) );

		$this->assertEquals( 'gv-cache-f:0-f:11-f:4-8739602554c7f3241958e3c', $cache->get_key() );

		$cache->set( 'Go flight.' );

		$cache2 = new GravityView_Cache( array( 4 ) );

		// Simulate an invalidation
		$cache2->delete();

		$this->assertNull( $cache->get(), 'Cache was not deleted by invalidation (transients object cache)' );

		$cache->set( 'Go flight.' );

		$cache3 = new GravityView_Cache( array( 1 ) );

		$this->assertEquals( 'Go flight.', $cache->get() );

		$cache4 = new GravityView_Cache( array( 0 ) );

		$cache4->delete();

		$this->assertNull( $cache->get(), 'Cache was not deleted by invalidation (transients object cache)' );
	}

	/**
	 * @covers GravityView_Cache::entry_updated()
	 * @covers GravityView_Cache::blocklist_add()
	 * @covers GravityView_Cache::in_blocklist()
	 *
	 * @since TODO
	 */
	public function test_edit_entry_after_update_adds_form_to_blocklist() {
		$form  = $this->factory->form->create_and_get();
		$entry = $this->factory->entry->create_and_get(
			array(
				'form_id' => $form['id'],
			)
		);

		$cache = new GravityView_Cache();
		$cache->blocklist_remove( $form['id'] );

		$this->assertFalse( $cache->in_blocklist( $form['id'] ) );

		$hook_name = 'gravityview/edit_entry/after_update';

		if ( false === has_action( $hook_name, array( $cache, 'entry_updated' ) ) ) {
			add_action( $hook_name, array( $cache, 'entry_updated' ), 10, 2 );
		}

		$this->assertNotFalse( has_action( $hook_name, array( $cache, 'entry_updated' ) ) );

		$edit_entry_render = new GravityView_Edit_Entry_Render( GravityView_Edit_Entry::getInstance() );
		$edit_entry_render->entry = $entry;

		$view_id = $this->factory->view->create(
			array(
				'form_id' => $form['id'],
			)
		);
		$original_gv_data = GravityView_View_Data::$instance;
		GravityView_View_Data::$instance = null;
		$gv_data = GravityView_View_Data::getInstance( $view_id );

		try {
			do_action( $hook_name, $form, $entry['id'], $edit_entry_render, $gv_data );
			$this->assertTrue( $cache->in_blocklist( $form['id'] ) );
		} finally {
			$cache->blocklist_remove( $form['id'] );
			GravityView_View_Data::$instance = $original_gv_data;
		}
	}
}
