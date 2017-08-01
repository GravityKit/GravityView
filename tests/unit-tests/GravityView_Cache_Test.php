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
}
