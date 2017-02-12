<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * All future tests live here for now...
 *
 * ...at least until the future Test component appears.
 *
 * @group gvfuture
 */
class GVFuture_Test extends GV_UnitTestCase {
	function setUp() {
		parent::setUp();

		/** The future branch of GravityView requires PHP 5.3+ namespaces. */
		if ( version_compare( phpversion(), '5.3' , '<' ) ) {
			$this->markTestSkipped( 'The future code requires PHP 5.3+' );
			return;
		}

		/** Not being loaded by the plugin yet. */
		if ( ! function_exists( 'gravityview' ) ) {
			$this->markTestSkipped( 'gravityview() is not being loaded by plugin yet' );
			return;
		}
	}

	/**
	 * @covers \GV\Plugin::dir()
	 * @covers \GV\Plugin::url()
	 */
	function test_plugin_dir_and_url() {
		$this->assertEquals( GRAVITYVIEW_DIR, gravityview()->plugin->dir() );
		$this->assertStringEndsWith( '/gravityview/test/this.php', strtolower( gravityview()->plugin->dir( 'test/this.php' ) ) );
		$this->assertStringEndsWith( '/gravityview/and/this.php', strtolower( gravityview()->plugin->dir( '/and/this.php' ) ) );

		/** Due to how WP_PLUGIN_DIR is different in test mode, we are only able to check bits of the URL */
		$this->assertStringStartsWith( 'http', strtolower( gravityview()->plugin->url() ) );
		$this->assertStringEndsWith( '/gravityview/', strtolower( gravityview()->plugin->url() ) );
		$this->assertStringEndsWith( '/gravityview/test/this.php', strtolower( gravityview()->plugin->url( 'test/this.php' ) ) );
		$this->assertStringEndsWith( '/gravityview/and/this.php', strtolower( gravityview()->plugin->url( '/and/this.php' ) ) );
	}

	/**
	 * @covers \GV\Plugin::is_compatible()
	 * @covers \GV\Plugin::is_compatible_wordpress()
	 * @covers \GV\Plugin::is_compatible_gravityforms()
	 * @covers \GV\Plugin::is_compatible_php()
	 */
	function test_plugin_is_compatible() {
		/** Under normal testing conditions this should pass. */
		$this->assertTrue( gravityview()->plugin->is_compatible_php() );
		$this->assertTrue( gravityview()->plugin->is_compatible_wordpress() );
		$this->assertTrue( gravityview()->plugin->is_compatible_gravityforms() );
		$this->assertTrue( gravityview()->plugin->is_compatible() );

		/** Simulate various other conditions, including failure conditions. */
		$GLOBALS['GRAVITYVIEW_TESTS_PHP_VERSION_OVERRIDE'] = '7.0.99-hhvm';
		$GLOBALS['GRAVITYVIEW_TESTS_WP_VERSION_OVERRIDE'] = '4.8-alpha-39901';
		$GLOBALS['GRAVITYVIEW_TESTS_GF_VERSION_OVERRIDE'] = '2.1.2.3-alpha';
		$this->assertTrue( gravityview()->plugin->is_compatible_php() );
		$this->assertTrue( gravityview()->plugin->is_compatible_wordpress() );
		$this->assertTrue( gravityview()->plugin->is_compatible_gravityforms() );
		$this->assertTrue( gravityview()->plugin->is_compatible() );

		$GLOBALS['GRAVITYVIEW_TESTS_PHP_VERSION_OVERRIDE'] = '5.2';
		$GLOBALS['GRAVITYVIEW_TESTS_WP_VERSION_OVERRIDE'] = '3.0';
		$GLOBALS['GRAVITYVIEW_TESTS_GF_VERSION_OVERRIDE'] = '1.0';
		$this->assertFalse( gravityview()->plugin->is_compatible_php() );
		$this->assertFalse( gravityview()->plugin->is_compatible_wordpress() );
		$this->assertFalse( gravityview()->plugin->is_compatible_gravityforms() );
		$this->assertFalse( gravityview()->plugin->is_compatible() );

		$GLOBALS['GRAVITYVIEW_TESTS_GF_VERSION_OVERRIDE'] = '2.1.2.3';
		$GLOBALS['GRAVITYVIEW_TESTS_GF_INACTIVE_OVERRIDE'] = true;
		$this->assertFalse( gravityview()->plugin->is_compatible_gravityforms() );
		$this->assertFalse( gravityview()->plugin->is_compatible() );

		/** Cleanup used overrides. */
		unset( $GLOBALS['GRAVITYVIEW_TESTS_PHP_VERSION_OVERRIDE'] );
		unset( $GLOBALS['GRAVITYVIEW_TESTS_WP_VERSION_OVERRIDE'] );
		unset( $GLOBALS['GRAVITYVIEW_TESTS_GF_VERSION_OVERRIDE'] );
		unset( $GLOBALS['GRAVITYVIEW_TESTS_GF_INACTIVE_OVERRIDE'] );

		/** Test deprecations and stubs in the old code. */
		$this->assertTrue( GravityView_Compatibility::is_valid() );
		$this->assertTrue( GravityView_Compatibility::check_php() );
		$this->assertTrue( GravityView_Compatibility::check_wordpress() );
		$this->assertTrue( GravityView_Compatibility::check_gravityforms() );

		$GLOBALS['GRAVITYVIEW_TESTS_PHP_VERSION_OVERRIDE'] = '5.2';
		$this->assertFalse( GravityView_Compatibility::is_valid() );
		$this->assertFalse( GravityView_Compatibility::check_php() );
		$GLOBALS['GRAVITYVIEW_TESTS_WP_VERSION_OVERRIDE'] = '3.0';
		$this->assertFalse( GravityView_Compatibility::check_wordpress() );
	}

	/**
	 * @covers \GV\Entry::add_rewrite_endpoint()
	 * @covers \GV\Entry::get_endpoint_name()
	 */
	function test_entry_endpoint_rewrite_name() {
		$entry_enpoint = array_filter( $GLOBALS['wp_rewrite']->endpoints, function( $endpoint ) {
			return $endpoint === array( EP_ALL, 'entry', 'entry' );
		} );

		$this->assertNotEmpty( $entry_enpoint, 'Single Entry endpoint not registered.' );
		\GV\Entry::add_rewrite_endpoint();
		\GV\Entry::add_rewrite_endpoint();
		GravityView_Post_Types::init_rewrite(); /** Deprecated, but an alias. */
		$this->assertCount( 1, $entry_enpoint, 'Single Entry endpoint registered more than once.' );

		/** Deprecated back-compatibility insurance. */
		$this->assertEquals( \GV\Entry::get_endpoint_name(), GravityView_Post_Types::get_entry_var_name() );

		/** Make sure oEmbed handler registration doesn't error out with \GV\Entry::get_endpoint_name, and works. */
		$this->assertContains( 'gravityview_entry', array_keys( $GLOBALS['wp_embed']->handlers[20000] ), 'oEmbed handler was not registered properly.' );

		/** Make sure is_single_entry works without error, too. Uses \GV\Entry::get_endpoint_name */
		$this->assertFalse( GravityView_frontend::is_single_entry() );
	}

	/**
	 * @covers \GV\Plugin::activate()
	 */
	function test_plugin_activate() {
		/** Trigger an activation. By default, during tests these are not triggered. */
		GravityView_Plugin::activate();
		gravityview()->plugin->activate(); /** Deprecated. */

		$this->assertEquals( get_option( 'gv_version' ), GravityView_Plugin::version );
	}

	/**
	 * @covers \GV\View_Collection::add()
	 */
	function test_view_collection_add() {
		$views = new \GV\View_Collection();
		$view = new \GV\View();

		$views->add( $view );
		$this->assertContains( $view, $views->all() );

		$expectedException = null;
		try {
			/** Make sure we can only add \GV\View objects into the \GV\View_Collection. */
			$views->add( new stdClass() );
		} catch ( \InvalidArgumentException $e ) {
			$expectedException = $e;
		}
		$this->assertInstanceOf( '\InvalidArgumentException', $expectedException );
		$this->assertCount( 1, $views->all() );
	}

	/**
	 * @covers \GV\View::from_post()
	 */
	function test_view_from_post() {
		$post = $this->factory->view->create_and_get();
		$view = \GV\View::from_post( $post );
		$this->assertEquals( $view->ID, $post->ID );

		/** Check forms initialization. */
		$this->assertCount( 1, $view->forms->all() );

		/** A post of a different post type. */
		$post = $this->factory->post->create_and_get();
		$expectedException = null;
		try {
			$view = \GV\View::from_post( $post );
		} catch ( \InvalidArgumentException $e ) {
			$expectedException = $e;
		}
		$this->assertInstanceOf( '\InvalidArgumentException', $expectedException );

		/** Test raised \TypeError in PHP7 when post is not a \WP_Post */
		if ( version_compare( phpversion(), '7.0.x' , '>=' ) ) {
			$expectedException = null;
			try {
				$view = \GV\View::from_post( null );
			} catch ( \TypeError $e ) {
				$expectedException = $e;
			}
			$this->assertInstanceOf( '\TypeError', $expectedException );
		}
	}

	/**
	 * @covers \GV\View::by_id()
	 */
	function test_view_by_id() {
		$post = $this->factory->view->create_and_get();
		$view = \GV\View::by_id( $post->ID );
		$this->assertEquals( $view->ID, $post->ID );

		/** Check forms initialization. */
		$this->assertCount( 1, $view->forms->all() );

		/** A post of a different post type. */
		$post = $this->factory->post->create_and_get();
		$expectedException = null;
		try {
			$view = \GV\View::by_id( $post->ID );
		} catch ( \InvalidArgumentException $e ) {
			$expectedException = $e;
		}
		$this->assertInstanceOf( '\InvalidArgumentException', $expectedException );
	}

	/**
	 * @covers \GV\View::exists()
	 * @covers \GravityView_View_Data::view_exists()
	 */
	function test_view_exists() {
		$data = GravityView_View_Data::getInstance();
		$post = $this->factory->view->create_and_get();

		$this->assertTrue( \GV\View::exists( $post->ID ) );
		$this->assertTrue( $data->view_exists( $post->ID ) );

		$this->assertFalse( \GV\View::exists( $post->ID + 100 ) );
		$this->assertFalse( $data->view_exists( $post->ID + 100 ) );

		GravityView_View_Data::$instance = null; /** Cleanup */
	}

	/**
	 * @covers \GV\View::offsetExists()
	 * @covers \GV\View::offsetSet()
	 * @covers \GV\View::offsetUnset()
	 * @covers \GV\View::offsetGet()
	 */
	function test_view_data_compat() {
		\GravityView_View_Data::$instance = null; /** Reset internal state. */
		$GLOBALS['GRAVITYVIEW_TESTS_VIEW_ARRAY_ACCESS_OVERRIDE'] = 1; /** Suppress test array access test exceptions. */

		$post = $this->factory->view->create_and_get();
		$view = \GV\View::by_id( $post->ID );

		/** Limited to the old keys. */
		foreach ( array( 'id', 'view_id', 'form_id', 'template_id', 'atts', 'fields', 'widgets', 'form' ) as $key )
			$this->assertTrue( isset( $view[$key] ) );
		$this->assertFalse( isset( $view['and now, for something completely different...'] ) );

		$this->assertEquals( $post->ID, $view['id'] );
		$this->assertEquals( $post->ID, $view['view_id'] );
		$this->assertEquals( $post->_gravityview_form_id, $view['form_id'] );
		$this->assertSame( $view->forms->last(), $view['form'] );

		/** Immutable! */
		$expectedException = null;
		try {
			$view['id'] = 9;
		} catch ( \RuntimeException $e ) {
			$expectedException = $e;
		}
		$this->assertInstanceOf( '\RuntimeException', $expectedException );
		$this->assertEquals( $post->ID, $view['id'] );

		$expectedException = null;
		try {
			unset( $view['id'] );
		} catch ( \RuntimeException $e ) {
			$expectedException = $e;
		}
		$this->assertInstanceOf( '\RuntimeException', $expectedException );
		$this->assertEquals( $post->ID, $view['id'] );

		/** Deprecation regressions. */
		$data = \GravityView_View_Data::getInstance();
		$data_view = $data->add_view( $view->ID );
		$this->assertSame( $data_view['id'], $view['id'] );
		$this->assertSame( $data_view['view_id'], $view['view_id'] );

		unset( $GLOBALS['GRAVITYVIEW_TESTS_VIEW_ARRAY_ACCESS_OVERRIDE'] );
		\GravityView_View_Data::$instance = null; /** Reset internal state. */
	}

	/**
	 * @covers \GV\View_Collection::from_post()
	 * @covers \GV\View_Collection::get()
	 * @covers \GravityView_View_Data::maybe_get_view_id()
	 * @covers \GravityView_View_Data::is_valid_embed_id()
	 * @covers \GravityView_oEmbed::set_vars()
	 */
	function test_view_collection_from_post() {
		$original_shortcode = $GLOBALS['shortcode_tags']['gravityview'];
		remove_shortcode( 'gravityview' ); /** Conflicts with existing shortcode right now. */
		\GV\Shortcodes\gravityview::add();

		$post = $this->factory->view->create_and_get();

		$views = \GV\View_Collection::from_post( $post );
		$view = $views->get( $post->ID );
		$this->assertEquals( $view->ID, $post->ID );
		$this->assertNull( $views->get( -1 ) );

		$another_post = $this->factory->view->create_and_get();

		/** An shortcode-based post. */
		$with_shortcodes = $this->factory->post->create_and_get( array(
			'post_content' => sprintf( '[gravityview id="%d"][gravityview id="%d"]', $post->ID, $another_post->ID )
		) );
		$views = \GV\View_Collection::from_post( $with_shortcodes );
		$this->assertCount( 2, $views->all() );

		$view = $views->get( $post->ID );
		$this->assertEquals( $view->ID, $post->ID );

		$view = $views->get( $another_post->ID );
		$this->assertEquals( $view->ID, $another_post->ID );

		/** Test post_meta-stored shortcodes. */
		$with_shortcodes_in_meta = $this->factory->post->create_and_get();
		update_post_meta( $with_shortcodes_in_meta->ID, 'meta_test', sprintf( '[gravityview id="%d"]', $post->ID ) );
		update_post_meta( $with_shortcodes_in_meta->ID, 'another_meta_test', sprintf( '[gravityview id="%d"]', $another_post->ID ) );

		$views = \GV\View_Collection::from_post( $with_shortcodes_in_meta );
		$this->assertEmpty( $views->all() );

		$test = $this;

		add_filter( 'gravityview/view_collection/from_post/meta_keys', function( $meta_keys, $post ) use ( $with_shortcodes_in_meta, $test ) {
			$test->assertSame( $post, $with_shortcodes_in_meta );
			return array( 'meta_test' );
		}, 10, 2 );

		$views = \GV\View_Collection::from_post( $with_shortcodes_in_meta );
		$this->assertCount( 1, $views->all() );
		$view = $views->get( $post->ID );
		$this->assertEquals( $view->ID, $post->ID );

		add_filter( 'gravityview/data/parse/meta_keys', function( $meta_keys, $post_id ) use ( $with_shortcodes_in_meta, $test ) {
			$test->assertEquals( $post_id, $with_shortcodes_in_meta->ID );
			return array( 'another_meta_test' );
		}, 10, 2 );

		$views = \GV\View_Collection::from_post( $with_shortcodes_in_meta );
		$this->assertCount( 1, $views->all() );
		$view = $views->get( $another_post->ID );
		$this->assertEquals( $view->ID, $another_post->ID );

		remove_all_filters( 'gravityview/view_collection/from_post/meta_keys' );
		remove_all_filters( 'gravityview/data/parse/meta_keys' );

		/** How about invalid view IDs? */
		$with_bad_shortcodes = $this->factory->post->create_and_get( array(
			'post_content' => sprintf( '[gravityview id="%d"][gravityview id="%d"]', -$post->ID, -$another_post->ID )
		) );
		$views = \GV\View_Collection::from_post( $with_bad_shortcodes );
		$this->assertCount( 0, $views->all() );

		/** Test regressions in GravityView_View_Data::maybe_get_view_id */
		$data = GravityView_View_Data::getInstance();
		$this->assertEquals( $data->maybe_get_view_id( $post ), $post->ID );
		$this->assertEquals( $data->maybe_get_view_id( array( $post, $another_post ) ), array( $post->ID, $another_post->ID ) );
		$this->assertEquals( $data->maybe_get_view_id( $with_shortcodes ), array( $post->ID, $another_post->ID ) );
		add_filter( 'gravityview/data/parse/meta_keys', function( $meta_keys, $post_id ) {
			return array( 'another_meta_test' );
		}, 10, 2 );
		$this->assertEquals( $data->maybe_get_view_id( $with_shortcodes_in_meta ), $another_post->ID );
		remove_all_filters( 'gravityview/data/parse/meta_keys' );
		$this->assertEquals( $data->maybe_get_view_id( sprintf( '[gravityview id="%d"]', $post->ID ) ), $post->ID );

		/** Test regressions for GravityView_oEmbed::set_vars by calling stuff. */
		$form = $this->factory->form->create_and_get();
		$entry = $this->factory->entry->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$post = $this->factory->post->create_and_get( array( 'post_content' => sprintf( '[gravityview id="%d"]', $view->ID ) ) );

		$embed_content = sprintf( "\n%s\n", add_query_arg( 'entry', $entry['id'], get_permalink( $post->ID ) ) );
		$this->assertContains( 'table class="gv-table-view-content"', $GLOBALS['wp_embed']->autoembed( $embed_content ) );

		/** Test GravityView_View_Data::is_valid_embed_id regression. */
		$this->assertTrue( GravityView_View_Data::is_valid_embed_id( $post->ID, $view->ID ) );
		$this->assertInstanceOf( '\WP_Error', GravityView_View_Data::is_valid_embed_id( $post->ID, $another_post->ID ) );

		$GLOBALS['shortcode_tags']['gravityview'] = $original_shortcode;
		GravityView_frontend::$instance = NULL;
		GravityView_View_Data::$instance = NULL;
	}

	/**
	 * @covers \GV\GF_Form::by_id()
	 */
	function test_form_gravityforms() {
		$_form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $_form['id'] ) );

		$form = \GV\GF_Form::by_id( $_form['id'] );
		$this->assertInstanceOf( '\GV\Form', $form );
		$this->assertInstanceOf( '\GV\GF_Form', $form );

		$this->assertEquals( $form->ID, $_form['id'] );
		$this->assertEquals( $form::$backend, 'gravityforms' );

		/** Array access. */
		$this->assertEquals( $form['id'], $_form['id'] );

		/** Invalid ID. */
		$this->assertNull( \GV\GF_Form::by_id( false ) );
	}

	/**
	 * @covers \GV\Form_Collection::add()
	 * @covers \GV\Form_Collection::get()
	 * @covers \GV\Form_Collection::last()
	 */
	function test_form_collection() {
		$forms = new \GV\Form_Collection();
		$this->assertEmpty( $forms->all() );

		$first_form = $this->factory->form->create_and_get();
		$forms->add( \GV\GF_Form::by_id( $first_form['id'] ) );

		$this->assertSame( $forms->get( $first_form['id'] ), $forms->last() );

		for ( $i = 0; $i < 5; $i++ ) {
			$_form = $this->factory->form->create_and_get();
			$forms->add( \GV\GF_Form::by_id( $_form['id'] ) );
		}
		$this->assertCount( 6, $forms->all() );

		foreach ( $forms->all() as $form ) {
			$this->assertInstanceOf( '\GV\GF_Form', $form );
		}

		$last_form = $forms->get( $_form['id'] );
		$this->assertEquals( $_form['id'], $last_form->ID );

		$_first_form = $forms->get( $first_form['id'] );
		$this->assertEquals( $first_form['id'], $_first_form->ID );

		$this->assertNull( $forms->get( 'this was not added' ) );

		$expectedException = null;
		try {
			/** Make sure we can only add \GV\View objects into the \GV\View_Collection. */
			$forms->add( 'this is not a form' );
		} catch ( \InvalidArgumentException $e ) {
			$expectedException = $e;
		}
		$this->assertInstanceOf( '\InvalidArgumentException', $expectedException );
		$this->assertCount( 6, $forms->all() );

		$this->assertSame( $forms->get( $last_form->ID ), $forms->last() );
	}

	/**
	 * @covers \GV\Shortcode::add()
	 * @covers \GV\Shortcode::remove()
	 */
	function test_shortcode_add() {
		$original_shortcode = $GLOBALS['shortcode_tags']['gravityview'];
		remove_shortcode( 'gravityview' ); /** Conflicts with existing shortcode right now. */
		$shortcode = \GV\Shortcodes\gravityview::add();
		$this->assertInstanceOf( '\GV\Shortcodes\gravityview', $shortcode );
		$this->assertEquals( $shortcode->name, 'gravityview' );
		$this->assertSame( $shortcode, \GV\Shortcodes\gravityview::add() );

		\GV\Shortcodes\gravityview::remove();
		$this->assertFalse( shortcode_exists( 'gravityview' ) );

		add_shortcode( 'gravityview', '__return_false' );

		$expectedException = null;
		try {
			$shortcode = \GV\Shortcodes\gravityview::add();
		} catch ( \ErrorException $e ) {
			$expectedException = $e;
		}
		$this->assertInstanceOf( '\ErrorException', $expectedException );

		$GLOBALS['shortcode_tags']['gravityview'] = $original_shortcode;
	}

	/**
	 * @covers \GV\Shortcode::callback()
	 * @expectedException \BadMethodCallException
	 */
	function test_shortcode_do_not_implemented() {
		\GV\Shortcode::callback( array( 'id' => 1 ) );
	}

	/**
	 * @covers \GV\Shortcode::parse()
	 * @covers \GravityView_View_Data::parse_post_content()
	 */
	function test_shortcode_parse() {
		$original_shortcode = $GLOBALS['shortcode_tags']['gravityview'];
		remove_shortcode( 'gravityview' ); /** Conflicts with existing shortcode right now. */
		\GV\Shortcodes\gravityview::add();

		$shortcodes = \GV\Shortcode::parse( '[gravityview id="1" m="2"]test this[/gravityview]and also[gravityview id="2" one=3]and[noexist]', true );
		$this->assertCount( 2, $shortcodes );

		$this->assertInstanceOf( '\GV\Shortcodes\gravityview', $shortcodes[0] );
		$this->assertEquals( $shortcodes[0]->name, 'gravityview' );
		$this->assertEquals( $shortcodes[0]->atts, array( 'id' => '1', 'm' => '2' ) );
		$this->assertEquals( $shortcodes[0]->content, 'test this' );

		$this->assertInstanceOf( '\GV\Shortcodes\gravityview', $shortcodes[1] );
		$this->assertEquals( $shortcodes[1]->name, 'gravityview' );
		$this->assertEquals( $shortcodes[1]->atts, array( 'id' => '2', 'one' => 3 ) );
		$this->assertEmpty( $shortcodes[1]->content );

		add_shortcode( 'noexist', '__return_false' );

		$shortcodes = \GV\Shortcode::parse( '[gravityview id="1" m="2"]test this[/gravityview]and also[gravityview id="2" one=3]and[noexist]' );
		$this->assertCount( 3, $shortcodes );
		$this->assertEquals( $shortcodes[2]->name, 'noexist' );
		$this->assertEmpty( $shortcodes[2]->atts );
		$this->assertEmpty( $shortcodes[2]->content );

		remove_shortcode( 'noexist' );

		/** Test shortcodes inside shortcode content. */
		add_shortcode( 's1', '__return_false' );
		add_shortcode( 's2', '__return_false' );
		add_shortcode( 's3', '__return_false' );

		$shortcodes = \GV\Shortcode::parse( '[s1][s2][s3][/s2][noexist][/s1]' );

		$this->assertCount( 3, $shortcodes );
		$this->assertEquals( $shortcodes[0]->name, 's1' );
		$this->assertEquals( $shortcodes[1]->name, 's2' );
		$this->assertEquals( $shortcodes[2]->name, 's3' );

		$GLOBALS['shortcode_tags']['gravityview'] = $original_shortcode;

		/** Make sure \GravityView_View_Data::parse_post_content operates in a sane way. */
		GravityView_View_Data::$instance = null; /** Reset just in case. */
		$data = GravityView_View_Data::getInstance();
		$this->assertEquals( -1, $data->parse_post_content( '[gravityview id="-1"]' ) );
		$this->assertEquals( array( -1, -2 ), $data->parse_post_content( '[gravityview id="-1"][gravityview id="-2"]' ) );
		/** The above calls have a side-effect on the data state; make sure it's still intact. */
		$this->assertEquals( $data->get_views(), array() );
	}

	/**
	 * @covers \GV\Core::init()
	 */
	function test_core_init() {
		gravityview()->request = new \GV\Frontend_Request();

		/** Make sure the main \GV\View_Collection is available in both places. */
		$this->assertSame( gravityview()->views, gravityview()->request->views );
		/** And isn't empty... */
		$this->assertEmpty( gravityview()->views->all() );

		/** Can't mutate gravityview()->views */
		$expectedException = null;
		try {
			/** Make sure we can only add \GV\View objects into the \GV\View_Collection. */
			gravityview()->views = null;
		} catch ( \RuntimeException $e ) {
			$expectedException = $e;
		}
		$this->assertInstanceOf( '\RuntimeException', $expectedException );
		$this->assertSame( gravityview()->views, gravityview()->request->views );
		$this->assertEmpty( gravityview()->views->all() );
	}

	/**
	 * @covers \GV\Frontend_Request::is_admin()
	 */
	function test_default_request_is_admin() {
		$this->assertFalse( gravityview()->request->is_admin() );

		set_current_screen( 'dashboard' );
		$this->assertTrue( gravityview()->request->is_admin() );
		set_current_screen( 'front' );

		/** Now make sure old code stubs behave in the same way. */
		$this->assertEquals( gravityview()->request->is_admin(), \GravityView_Plugin::is_admin() );
		set_current_screen( 'front' );
		$this->assertEquals( gravityview()->request->is_admin(), \GravityView_Plugin::is_admin() );

		/** \GravityView_frontend::parse_content returns immediately if is_admin() */
		$fe = \GravityView_frontend::getInstance();
		$restore_GvOutputData = $fe->getGvOutputData(); /** Remember the global state... */
		$fe->setGvOutputData( 'sentinel' );
		$fe->parse_content(); /** Will reset GvOutputData to an emty array. */
		$this->assertNotEquals( 'sentinel', $fe->getGvOutputData() );
		set_current_screen( 'dashboard' );
		$fe = \GravityView_frontend::getInstance();
		$fe->setGvOutputData( 'sentinel' );
		$fe->parse_content(); /** Will not reset GvOutputData to an empty array. */
		$this->assertEquals( 'sentinel', $fe->getGvOutputData() );
		$fe->setGvOutputData( $restore_GvOutputData );

		/** \GravityView_Entry_Link_Shortcode::shortcode short circuits with null if is_admin() */
		set_current_screen( 'front' );
		$entry_link_shortcode = new \GravityView_Entry_Link_Shortcode(); /** And with false if allowed to continue with bad data. */
		$this->assertFalse( $entry_link_shortcode->read_shortcode( array( 'view_id' => 1, 'entry_id' => 1 ) ) );
		set_current_screen( 'dashboard' );
		$this->assertNull( $entry_link_shortcode->read_shortcode( array( 'view_id' => 1, 'entry_id' => 1 ) ) );

		/** \GVLogic_Shortcode::shortcode short circuits as well. */
		set_current_screen( 'front' );
		$logic_shortocde = \GVLogic_Shortcode::get_instance();
		$this->assertEquals( $logic_shortocde->shortcode( array( 'if' => 'true', 'is' => 'true' ), 'sentinel' ), 'sentinel' );
		set_current_screen( 'dashboard' );
		$this->assertNull( $logic_shortocde->shortcode( array( 'if' => 'true', 'is' => 'true' ), 'sentinel' ), 'sentinel' );

		/** \GravityView_Widget::add_shortcode short circuits and adds no tags if is_admin() */
		set_current_screen( 'front' );
		$widget = new \GravityView_Widget( 'test', 1 );
		$widget->add_shortcode();
		$this->assertTrue( shortcode_exists( 'gravityview_widget' ) );
		remove_shortcode( 'gravityview_widget' );
		set_current_screen( 'dashboard' );
		$widget->add_shortcode();
		$this->assertFalse( shortcode_exists( 'gravityview_widget' ) );

		set_current_screen( 'front' );
	}

	/**
	 * @covers \GV\Frontend_Request::is_admin()
	 * @group ajax
	 */
	function test_default_request_is_admin_ajax() {
		if ( ! defined( 'DOING_AJAX' ) )
			define( 'DOING_AJAX', true );

		$this->assertFalse( gravityview()->request->is_admin() );
		$this->assertEquals( gravityview()->request->is_admin(), \GravityView_Plugin::is_admin() );

		set_current_screen( 'dashboard' );
		$this->assertFalse( gravityview()->request->is_admin() );
	}

	/**
	 * @covers \GV\Frontend_Request::parse()
	 */
	function test_frontend_request_parse() {
		// Make sure doesn't break without a global post
		$request = new \GV\Frontend_Request();
		$request->parse( null );
	}

	/**
	 * @covers \GravityView_View_Data::add_view()
	 * @covers \GV\Mocks\GravityView_View_Data_add_view()
	 *
	 * @covers \GravityView_View_Data::get_views)
	 * @covers \GravityView_View_Data::get_views()
	 * @covers \GravityView_View_Data::has_multiple_views()
	 */
	function test_frontend_request_add_view() {
		gravityview()->request = new \GV\Frontend_Request();

		\GravityView_View_Data::$instance = null; /** Reset internal state. */

		/** Try to add a non-existing view. */
		$data = \GravityView_View_Data::getInstance();
		$view = $data->add_view( -1 );
		$this->assertEmpty( gravityview()->views->all() );
		$this->assertFalse( $view );

		/** Add an existing view, not connected to a form. */
		$_view = $this->factory->view->create_and_get( array( 'form_id' => 0 ) );
		$view = $data->add_view( $_view->ID );
		$this->assertEmpty( gravityview()->views->all() );
		$this->assertFalse( $view );

		/** A valid view. */
		$_view = $this->factory->view->create_and_get();
		$view = $data->add_view( $_view->ID );
		$_view = gravityview()->request->views->get( $_view->ID );
		$this->assertCount( 1, gravityview()->views->all() );

		/** Add the same one. Nothing changed, right? */
		$view = $data->add_view( $_view->ID, array( 'sort_direction' => 'RANDOM' ) );
		$this->assertCount( 1, gravityview()->views->all() );

		gravityview()->request = new \GV\Frontend_Request();
		$this->assertCount( 0, gravityview()->views->all() );

		/** Some attributes. */
		$view = $data->add_view( $_view->ID, array( 'sort_direction' => 'RANDOM' ) );
		$_view = gravityview()->request->views->get( $_view->ID );
		$this->assertCount( 1, gravityview()->views->all() );
		$this->assertEquals( $view['atts']['sort_direction'], 'RANDOM' );
		$this->assertEquals( $view['atts'], $_view['atts'] /** Will be deprecated! */ );

		gravityview()->request = new \GV\Frontend_Request();

		/** Try to add an array of non-existing views. */
		$views = $data->add_view( array( -1, -2, -3 ) );
		$this->assertEmpty( gravityview()->views->all() );
		$this->assertEmpty( $views );

		/** Add 2 repeating ones among invalid ones. */
		$_view = $this->factory->view->create_and_get();
		$views = $data->add_view( array( -1, $_view->ID, -3, $_view->ID ) );
		$this->assertCount( 1, gravityview()->views->all() );
		$this->assertCount( 1, $views );
		$this->assertFalse( $data->has_multiple_views() );

		$_another_view = $this->factory->view->create_and_get();
		$views = $data->add_view( array( -1, $_view->ID, -3, $_another_view->ID ) );
		$this->assertCount( 2, gravityview()->views->all() );
		$this->assertCount( 2, $views );
		$this->assertTrue( $data->has_multiple_views() );
		$_view = gravityview()->request->views->get( $_view->ID );
		$_another_view = gravityview()->request->views->get( $_another_view->ID );
		$this->assertEquals( $views, array( $_view->ID => $_view, $_another_view->ID => $_another_view ) );

		/** Make sure \GravityView_View_Data::get_views == gravityview()->views->all() */
		$this->assertEquals( $data->get_views(), array_combine(
			array_map( function( $view ) { return $view->ID; }, gravityview()->views->all() ),
			gravityview()->views->all()
		) );

		/** Make sure \GravityView_View_Data::get_view == gravityview()->views->get() */
		$this->assertEquals( $data->get_view( $_another_view->ID ), gravityview()->request->views->get( $_another_view->ID ) );
		$this->assertFalse( $data->get_view( -1 ) );

		/** Reset it all. */
		gravityview()->request = new \GV\Frontend_Request();
		GravityView_View_Data::$instance = null;
		GravityView_frontend::$instance = null;
	}

	/**
	 * @covers \GV\Settings::set()
	 * @covers \GV\Settings::get()
	 * @covers \GV\Settings::all()
	 */
	public function test_settings_base() {
		$settings = new \GV\Settings();
		$this->assertEmpty( $settings->all() );

		$value = array( 'one' => 'three' );
		$settings->set( 'test', $value );

		$this->assertEquals( $settings->get( 'test' ), $value );
		$this->assertNull( $settings->get( 'noexist' ) );

		$default = 'This is a default value';
		$this->assertEquals( $settings->get( 'no no no no', $default ), $default );

		$this->assertCount( 1, $settings->all() );
	}

	/**
	 * @covers \GV\View_Settings::defaults()
	 * @covers \GravityView_View_Data::get_default_arg()
	 */
	public function test_view_settings() {
		$view = new \GV\View();
		$this->assertInstanceOf( '\GV\View_Settings', $view->settings );

		$defaults = \GV\View_Settings::defaults();
		$this->assertNotEmpty( $defaults );

		/** Details. */
		$detailed = \GV\View_Settings::defaults( true );
		$this->assertEquals( wp_list_pluck( $detailed, 'value', 'id' ), array_values( $defaults ) );

		/** Group. */
		$group = \GV\View_Settings::defaults( true, 'sort' );
		$this->assertEmpty( array_filter( $group, function( $setting ) { return !empty( $setting['group'] ) && $setting['group'] != 'sort'; } ) );

		/** Test old filter. */
		add_filter( 'gravityview_default_args', function( $defaults ) {
			$defaults['test_sentinel'] = '123';
			return $defaults;
		} );

		/** Test new filter. */
		add_filter( 'gravityview/view/settings/defaults', function( $defaults ) {
			$defaults['test_sentinel'] = array( 'value' => '456' );
			return $defaults;
		} );
		$defaults = \GV\View_Settings::defaults();
		$this->assertEquals( $defaults['test_sentinel'], '456' );

		/** Regression. */
		$this->assertEquals( \GravityView_View_Data::get_default_arg( 'test_sentinel' ), '456' );
		$setting = \GravityView_View_Data::get_default_arg( 'test_sentinel', true );
		$this->assertEquals( $setting['value'], '456' );

		remove_all_filters( 'gravityview_default_args' );
		remove_all_filters( 'gravityview/view/settings/defaults' );
	}
}
