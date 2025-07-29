<?php

class GV_UnitTestCase extends WP_UnitTestCase {

	/**
	 * @var GV_UnitTest_Factory
	 */
	var $factory;

	/**
	 * @inheritDoc
	 */
	function setUp() : void {
		parent::setUp();

		/* Remove temporary tables which causes problems with GF */
		remove_all_filters( 'query', 10 );

		/* Ensure the database is correctly set up */
		if ( function_exists( 'gf_upgrade' ) ) {
			gf_upgrade()->upgrade_schema();
		}

		GVCommon::clear_cache();

		$this->factory = new GV_UnitTest_Factory( $this );

		if ( version_compare( GFForms::$version, '2.2', '<' ) ) {
			@GFForms::setup_database();
		}
	}

	/**
	 * @inheritDoc
	 */
	function tearDown() : void {
		/** @see https://core.trac.wordpress.org/ticket/29712 */
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Multisite-agnostic way to delete a user from the database.
	 *
	 * @since 1.20 - Included in WP 4.3.0+, so we need to stub for 4.0+
	 */
	public static function delete_user( $user_id ) {
		if ( is_multisite() ) {
			return wpmu_delete_user( $user_id );
		} else {
			return wp_delete_user( $user_id );
		}
	}

	/**
	 * Renders HTML on the commandline nicely, needs lynx.
	 *
	 * @param string $data The data to render.
	 *
	 * @return string The transformed HTML
	 */
	public static function debug_render_html( $data ) {
		$ds = array(
			array( 'pipe', 'r' ),
			array( 'pipe', 'w' ),
		);

		if ( ! is_resource( $handle = proc_open( 'lynx -dump -stdin', $ds, $pipes ) ) ) {
			return $data;
		}

		fwrite( $pipes[0], $data );
		fclose( $pipes[0] );
		$data = stream_get_contents( $pipes[1] );
		fclose( $pipes[1] );
		proc_close( $handle );

		return $data;
	}
}
