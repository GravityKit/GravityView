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
		if ( ! defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
			$this->markTestSkipped( 'gravityview() is not being loaded by plugin yet' );
			return;
		}
	}

	/**
	 * Resets the GravityView context, both old and new.
	 */
	private function _reset_context() {
		\GravityView_View_Data::$instance = null;
		\GravityView_frontend::$instance = null;
		gravityview()->request = new \GV\Frontend_Request();
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
	 * @covers \GV\View_Collection::clear()
	 * @covers \GV\View_Collection::merge()
	 */
	function test_view_collection_add() {
		$views = new \GV\View_Collection();
		$view = new \GV\View();

		$views->add( $view );
		$this->assertContains( $view, $views->all() );

		/** Make sure we can only add \GV\View objects into the \GV\View_Collection. */
		$views->add( new stdClass() );
		$this->assertCount( 1, $views->all() );

		$more_views = new \GV\View_Collection();
		$more_views->add( $view );
		$more_views->add( $view );
		$this->assertCount( 2, $more_views->all() );

		$views->merge( $more_views );
		$this->assertCount( 3, $views->all() );

		$views->clear();
		$this->assertCount( 0, $views->all() );
		$this->assertEquals( 0, $views->count() );
	}

	/**
	 * @covers \GV\View::from_post()
	 */
	function test_view_from_post() {
		$post = $this->factory->view->create_and_get();
		$view = \GV\View::from_post( $post );
		$this->assertEquals( $view->ID, $post->ID );

		/** Check forms initialization. */
		$this->assertNotNull( $view->form );

		/** A post of a different post type. */
		$post = $this->factory->post->create_and_get();
		$view = \GV\View::from_post( $post );
		$this->assertNull( $view );

		$view = \GV\View::from_post( null );
		$this->assertNull( $view );
	}

	/**
	 * @covers \GV\View::by_id()
	 */
	function test_view_by_id() {
		$post = $this->factory->view->create_and_get();
		$view = \GV\View::by_id( $post->ID );
		$this->assertEquals( $view->ID, $post->ID );

		/** Check forms initialization. */
		$this->assertNotNull( $view->form );

		/** A post of a different post type. */
		$post = $this->factory->post->create_and_get();
		$this->assertNull( \GV\View::by_id( $post->ID ) );

		/** Disregard global state with a null passed */
		global $post;
		$post = $this->factory->post->create_and_get();

		$this->assertNull( \GV\View::by_id( null ) );

		unset( $post );
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

		$this->_reset_context();
	}

	/**
	 * @covers \GV\View::offsetExists()
	 * @covers \GV\View::offsetSet()
	 * @covers \GV\View::offsetUnset()
	 * @covers \GV\View::offsetGet()
	 * @covers \GV\View::as_data()
	 */
	function test_view_data_compat() {
		$this->_reset_context();

		$post = $this->factory->view->create_and_get();
		$view = \GV\View::by_id( $post->ID );

		/** Limited to the old keys. */
		foreach ( array( 'id', 'view_id', 'form_id', 'template_id', 'atts', 'fields', 'widgets', 'form' ) as $key )
			$this->assertTrue( isset( $view[$key] ) );
		$this->assertFalse( isset( $view['and now, for something completely different...'] ) );

		$this->assertEquals( $post->ID, $view['id'] );
		$this->assertEquals( $post->ID, $view['view_id'] );
		$this->assertEquals( $post->_gravityview_form_id, $view['form_id'] );
		$this->assertSame( $view->form, $view['form'] );
		$this->assertEquals( $post->_gravityview_directory_template, $view['template_id'] );

		/** Immutable! */
		$view['id'] = 9;
		$this->assertEquals( $post->ID, $view['id'] );

		unset( $view['id'] );
		$this->assertEquals( $post->ID, $view['id'] );

		/** Deprecation regressions. */
		$data = \GravityView_View_Data::getInstance();
		$data_view = $data->add_view( $view->ID );
		$this->assertSame( $data_view['id'], $view['id'] );
		$this->assertSame( $data_view['view_id'], $view['view_id'] );

		unset( $GLOBALS['GRAVITYVIEW_TESTS_VIEW_ARRAY_ACCESS_OVERRIDE'] );
		$this->_reset_context();
	}

	/**
	 * Stub \GravityView_View_Data::has_multiple_views() usage around the codebase.
	 *
	 * @covers \GravityView_frontend::set_context_view_id()
	 */
	function test_data_has_multiple_views() {
		$this->_reset_context();

		$post = $this->factory->view->create_and_get();
		$view = \GV\View::by_id( $post->ID );

		$another_post = $this->factory->view->create_and_get();
		$another_view = \GV\View::by_id( $another_post->ID );

		{ /** set_context_view_id */
			$fe = \GravityView_frontend::getInstance();
			$fe->setGvOutputData( \GravityView_View_Data::getInstance() );

			$fe->set_context_view_id();
			$this->assertNull( $fe->get_context_view_id() );

			$fe->set_context_view_id( -5 );
			$this->assertEquals( $fe->get_context_view_id(), -5 );

			$_GET['gvid'] = -7;

			$fe->set_context_view_id();
			$this->assertNull( $fe->get_context_view_id() );

			gravityview()->views->add( $view );
			$fe->set_context_view_id();
			$this->assertEquals( $fe->get_context_view_id(), $view->ID );

			gravityview()->views->add( $view );
			$fe->set_context_view_id();
			$this->assertEquals( $fe->get_context_view_id(), -7 );

			unset( $_GET['gvid'] );
		}

		$this->_reset_context();
	}

	/**
	 * Stub \GravityView_View_Data::get_views() usage around the codebase.
	 *
	 * @covers \GravityView_Admin_Bar::add_links()
	 * @covers \GravityView_Admin_Bar::add_edit_view_and_form_link()
	 * @covers \GravityView_frontend::insert_view_in_content()
	 * @covers \GravityView_frontend::add_scripts_and_styles()
	 * @covers \GravityView_frontend::render_view()
	 */
	function test_data_get_views() {
		$this->_reset_context();

		$post = $this->factory->view->create_and_get();
		$view = \GV\View::by_id( $post->ID );

		$another_post = $this->factory->view->create_and_get();
		$another_view = \GV\View::by_id( $another_post->ID );

		{
			global $wp_admin_bar;
			$admin_bar = new \GravityView_Admin_Bar();

			$wp_admin_bar = $this->getMockBuilder( 'stdClass' )->setMethods( array( 'add_menu' ) )->getMock();
			$wp_admin_bar->expects( $this->exactly( 4 ) )->method( 'add_menu' )
				->withConsecutive(
					array( $this->callback( function ( $subject ) {
						return $subject['id'] == 'gravityview'; /** The GravityView button. */
					} ) ),
					array( $this->callback( function ( $subject ) use ( $view ) {
						return $subject['id'] == 'edit-view-' . $view->ID; /** Edit the first view. */
					} ) ),
					array( $this->callback( function ( $subject ) use ( $view ) {
						return $subject['id'] == 'edit-form-' . $view->form->ID; /** Edit the form (shared by both views). */
					} ) ),
					array( $this->callback( function ( $subject ) use ( $another_view ) {
						return $subject['id'] == 'edit-view-' . $another_view->ID; /** Edit the second view. */
					} ) )
				);

			$administrator = $this->factory->user->create( array(
				'user_login' => md5( microtime() ),
				'user_email' => md5( microtime() ) . '@gravityview.tests',
				'role' => 'administrator' )
			);
			wp_set_current_user( $administrator );

			$this->assertNull( $admin_bar->add_links() ); /** Non-admin, so meh... */

			/** Multiple entries... */
			gravityview()->views->add( $view );
			gravityview()->views->add( $another_view );

			$user = wp_get_current_user();
			$user->add_cap( 'gravityview_full_access' );
			$user->get_role_caps(); // WordPress 4.2 and lower need this to refresh caps

			$admin_bar->add_links();

			wp_set_current_user( 0 );
		}

		{
			$fe = \GravityView_frontend::getInstance();
			global $wp_actions, $wp_query;
			$wp_actions['loop_start'] = 1;
			$wp_query->in_the_loop = true;
			$fe->setIsGravityviewPostType( true );
			$this->assertContains( '<table', $fe->insert_view_in_content( '' ) );

			$fe->add_scripts_and_styles();
		}

		{
			/**
			 * There are two views in there, but let's make sure a view that wasn't called for is still added.
			 * This is a side-effect of the old \GravityView_View_Data::get_view() method.
			 */
			$and_another_post = $this->factory->view->create_and_get();
			$and_another_view = \GV\View::by_id( $and_another_post->ID );
			$and_another_entry = $this->factory->entry->create_and_get( array( 'form_id' => $and_another_view->form->ID ) );

			$fe->setIsGravityviewPostType( true );
			$this->assertContains( 'not allowed to view this content', $fe->render_view( array(
				'id' => $and_another_view->ID,
				'embed_only' => true, /** Check propagation of $passed_args */
			) ) );

			$this->assertContains( 'gv-container-' . $and_another_view->ID, $fe->render_view( array(
				'id' => $and_another_view->ID,
				'embed_only' => false, /** Check propagation of $passed_args */
			) ) );

			$fe->set_context_view_id( $and_another_view->ID );
			$fe->setSingleEntry( $and_another_view->ID );
			$fe->setEntry( $and_another_entry['id'] );

			$this->assertContains( sprintf( 'data-viewid="%d"', $and_another_view->ID ), $fe->render_view( array(
				'id' => $and_another_view->ID,
				'debug' => true,
			) ) );
		}

		$this->_reset_context();
	}

	/**
	 * @covers \GV\View_Collection::from_post()
	 * @covers \GV\View_Collection::from_content()
	 * @covers \GV\View_Collection::get()
	 * @covers \GV\View_Collection::contains()
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
		$this->assertTrue( $views->contains( $view->ID ) );
		$this->assertFalse( $views->contains( -1 ) );

		$another_post = $this->factory->view->create_and_get();

		/** An shortcode-based post. */
		$with_shortcodes = $this->factory->post->create_and_get( array(
			'post_content' => sprintf( '[gravityview id="%d"][gravityview id="%d" search_field="2"]', $post->ID, $another_post->ID )
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

		/** And make sure arrays don't break things. */
		update_post_meta( $with_shortcodes_in_meta->ID, 'invalid_meta_test', array( 'do not even try to parse this' ) );

		$views = \GV\View_Collection::from_post( $with_shortcodes_in_meta );
		$this->assertEmpty( $views->all() );

		$test = $this;

		add_filter( 'gravityview/view_collection/from_post/meta_keys', function( $meta_keys, $post ) use ( $with_shortcodes_in_meta, $test ) {
			$test->assertSame( $post, $with_shortcodes_in_meta );
			return array( 'meta_test', 'invalid_meta_test' );
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

		/** Test GravityView_View_Data::maybe_get_view_id side-effect: calling it adds views to the global scope, hahah :( */
		$this->_reset_context();
		$data = GravityView_View_Data::getInstance();
		$data->maybe_get_view_id( $post );
		$this->assertCount( 1, gravityview()->views->all() );

		/** Test regressions for GravityView_oEmbed::set_vars by calling stuff. */
		$this->_reset_context();
		$this->assertCount( 0, gravityview()->views->all() );
		$form = $this->factory->form->create_and_get();
		$entry = $this->factory->entry->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$post = $this->factory->post->create_and_get( array( 'post_content' => sprintf( '[gravityview id="%d"]', $view->ID ) ) );

		$embed_content = sprintf( "\n%s\n", add_query_arg( 'entry', $entry['id'], get_permalink( $post->ID ) ) );
		$this->assertContains( 'table class="gv-table-view-content"', $GLOBALS['wp_embed']->autoembed( $embed_content ) );
		$this->assertCount( 1, gravityview()->views->all() );

		/** Test GravityView_View_Data::is_valid_embed_id regression. */
		$this->assertTrue( GravityView_View_Data::is_valid_embed_id( $post->ID, $view->ID ) );
		$this->assertInstanceOf( '\WP_Error', GravityView_View_Data::is_valid_embed_id( $post->ID, $another_post->ID ) );
		$this->assertInstanceOf( '\WP_Error', GravityView_View_Data::is_valid_embed_id( '', $view->ID ) );
		$this->assertTrue( GravityView_View_Data::is_valid_embed_id( '', $view->ID, true ) );
		$this->assertInstanceOf( '\WP_Error', GravityView_View_Data::is_valid_embed_id( $post->ID, $post->ID ) );

		/** Test shortcode has all attributes in View regression. */
		$views = $data->maybe_get_view_id( $with_shortcodes );
		$view = $data->get_view( $views[1] );
		$this->assertEquals( $view['atts']['search_field'], 2 );

		/** Test shortcode has all attributes in View regression. */
		$views = $data->maybe_get_view_id( $with_shortcodes );
		$view = $data->get_view( $views[1] );
		$this->assertEquals( $view['atts']['search_field'], 2 );

		$GLOBALS['shortcode_tags']['gravityview'] = $original_shortcode;
		$this->_reset_context();
	}

	/**
	 * Test stubs that work with the old View Data.
	 *
	 * @covers GravityView_frontend::single_entry_title()
	 */
	function test_view_compat() {
		$this->_reset_context();

		$form = $this->factory->form->create_and_get();
		$entry = $this->factory->entry->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$another_form = $this->factory->form->create_and_get();
		$another_entry = $this->factory->entry->create_and_get( array( 'form_id' => $another_form['id'] ) );
		$another_view = $this->factory->view->create_and_get( array( 'form_id' => $another_form['id'] ) );

		$data = GravityView_View_Data::getInstance();
		gravityview()->request->views->add( \GV\View::by_id( $view->ID ) );
		$this->assertCount( 1, gravityview()->views->all() );

		$fe = GravityView_frontend::getInstance();
		$fe->setSingleEntry( $entry['id'] );
		$fe->setEntry( $entry['id'] );
		add_filter( 'gravityview/single/title/out_loop', '__return_true' );
		$fe->setGvOutputData( $data );
		$GLOBALS['post'] = $view;
		$_GET['gvid'] = $view->ID;
		$fe->set_context_view_id();

		gravityview()->views->get( $view->ID )->settings->set( 'single_title', 'hello, world' );

		$this->assertEquals( $fe->single_entry_title( 'sentinel', $view->ID ), 'hello, world' );

		gravityview()->request->views->add( \GV\View::by_id( $another_view->ID ) );
		$this->assertCount( 2, gravityview()->views->all() );

		$fe->setSingleEntry( $another_entry['id'] );
		$fe->setEntry( $another_entry['id'] );
		$GLOBALS['post'] = $another_view;
		$_GET['gvid'] = $another_view->ID;
		$fe->set_context_view_id();

		gravityview()->views->get( $another_view->ID )->settings->set( 'single_title', 'bye, world' );
		$this->assertEquals( $fe->single_entry_title( 'sentinel', $another_view->ID ), 'bye, world' );

		/** Test merge tags */
		gravityview()->views->get( $another_view->ID )->settings->set( 'single_title', '{entry_id}' );
		$this->assertEquals( $fe->single_entry_title( 'sentinel', $another_view->ID ), $another_entry['id'] );

		remove_all_filters( 'gravityview/single/title/out_loop' );
		unset( $GLOBALS['post'] );
		unset( $_GET['gvid'] );
		$this->_reset_context();
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
		$form['hello'] = 'one';
		$this->assertTrue( ! isset( $form['hello'] ) );

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

		/** Make sure we can only add \GV\View objects into the \GV\View_Collection. */
		$forms->add( 'this is not a form' );
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

		$shortcode = \GV\Shortcodes\gravityview::add();
		$this->assertNull( $shortcode );

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
		$this->_reset_context();

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

		$this->_reset_context();
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
		gravityview()->views = null;
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
		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		$this->assertFalse( gravityview()->request->is_admin() );
		$this->assertEquals( gravityview()->request->is_admin(), \GravityView_Plugin::is_admin() );

		set_current_screen( 'dashboard' );
		$this->assertFalse( gravityview()->request->is_admin() );
	}

	/**
	 * @covers \GravityView_View_Data::add_view()
	 * @covers \GV\Mocks\GravityView_View_Data_add_view()
	 *
	 * @covers \GravityView_View_Data::get_view()
	 * @covers \GravityView_View_Data::get_views()
	 * @covers \GravityView_View_Data::has_multiple_views()
	 */
	function test_frontend_request_add_view() {
		$this->_reset_context();

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
		$this->assertEquals( $view['atts'], $_view->settings->as_atts() );

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
		$this->assertEquals( $views, array( $_view->ID => $_view->as_data(), $_another_view->ID => $_another_view->as_data() ) );

		/** Make sure \GravityView_View_Data::get_views == gravityview()->views->all() */
		$this->assertEquals( $data->get_views(), array_combine(
			array_map( function( $view ) { return $view->ID; }, gravityview()->views->all() ),
			array_map( function( $view ) { return $view->as_data(); }, gravityview()->views->all() )
		) );

		/** Make sure \GravityView_View_Data::get_view == gravityview()->views->get() */
		$this->assertEquals( $data->get_view( $_another_view->ID ), gravityview()->request->views->get( $_another_view->ID )->as_data() );
		$this->assertFalse( $data->get_view( -1 ) );

		/** Get view has a side-effect :( it adds a view that it doesn't have... do we emulate this correctly? */
		$this->assertNotEmpty( gravityview()->request->views->all() );
		gravityview()->request = new \GV\Frontend_Request();
		$this->assertEmpty( gravityview()->request->views->all() );
		GravityView_View_Data::$instance = null;
		$data = GravityView_View_Data::getInstance();
		$this->assertEquals( $data->get_view( $_another_view->ID ), gravityview()->request->views->get( $_another_view->ID )->as_data() );
		$this->assertNotNull( gravityview()->request->views->get( $_another_view->ID ) );

		$this->_reset_context();
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
	 * @covers \GV\View_Settings::update()
	 * @covers \GV\View_Settings::as_atts()
	 * @covers \GravityView_View_Data::get_default_arg()
	 * @covers \GravityView_View_Data::get_id_from_atts()
	 */
	public function test_view_settings() {
		$view = new \GV\View();
		$this->assertInstanceOf( '\GV\View_Settings', $view->settings );

		$defaults = \GV\View_Settings::defaults();
		$this->assertNotEmpty( $defaults );

		$settings = new \GV\View_Settings();
		$settings->update( $defaults );
		$this->assertEquals( $defaults, $settings->as_atts() );

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

		/** Update */
		$settings = new \GV\View_Settings();
		$settings->update( $defaults );
		$this->assertEquals( $settings->get( 'test_sentinel' ), '456' );
		$settings->update( array( 'valid_key' => 'this exists', 'test_sentinel' => '789' ) );
		$this->assertEquals( $settings->get( 'test_sentinel' ), '789' );
		$this->assertEquals( $settings->get( 'valid_key' ), 'this exists' );

		/** Regression. */
		$this->assertEquals( \GravityView_View_Data::get_default_arg( 'test_sentinel' ), '456' );
		$setting = \GravityView_View_Data::get_default_arg( 'test_sentinel', true );
		$this->assertEquals( $setting['value'], '456' );
		$atts = $settings->as_atts();
		$this->assertEquals( $atts['test_sentinel'], '789' );

		remove_all_filters( 'gravityview_default_args' );
		remove_all_filters( 'gravityview/view/settings/defaults' );

		/** Dead get_id_from_atts() test assumptions, no actual live code is present in our core... */
		add_filter( 'gravityview/view/settings/defaults', function( $defaults ) {
			$defaults['view_id'] = array( 'value' => '39' );
			return $defaults;
		} );
		$this->assertEquals( 39, \GravityView_View_Data::getInstance()->get_id_from_atts( 'id="40"' ) );
		remove_all_filters( 'gravityview/view/settings/defaults' );
		$this->assertEquals( 40, \GravityView_View_Data::getInstance()->get_id_from_atts( 'id="40"' ) );
		$this->assertEquals( 50, \GravityView_View_Data::getInstance()->get_id_from_atts( 'id="40" view_id="50"' ) );

		$this->_reset_context();
	}

	/**
	 * @covers \GV\WP_Action_Logger::log()
	 */
	public function test_logging() {
		$this->assertInstanceOf( '\GV\WP_Action_Logger', gravityview()->log );

		$_this = &$this;
		add_action( 'gravityview_log_debug_test', function( $message, $data ) use ( $_this ) {
			$_this->assertEquals( "[info, GVFuture_Test->test_logging] Hello, TRAPPIST-1!", $message );
			$_this->assertEquals( $data, array( 'a' => 'b' ) );
		}, 10, 2 );
		gravityview()->log->info( 'Hello, {world}!', array( 'world' => 'TRAPPIST-1', 'data' => array( 'a' => 'b' ) ) );
		remove_all_actions( 'gravityview_log_debug_test' );

		add_action( 'gravityview_log_error_test', function( $message, $data ) use ( $_this ) {
			$_this->assertEquals( "[critical, GVFuture_Test->test_logging] Hello, TRAPPIST-1!", $message );
			$_this->assertEquals( $data, array( 'a' => 'b' ) );
		}, 10, 2 );
		gravityview()->log->critical( 'Hello, {world}!', array( 'world' => 'TRAPPIST-1', 'data' => array( 'a' => 'b' ) ) );

		remove_all_actions( 'gravityview_log_error_test' );
	}

	/**
	 * @covers \GV\Field_Collection::add()
	 * @covers \GV\Field_Collection::from_configuration()
	 * @covers \GV\Field_Collection::as_configuration()
	 * @covers \GV\Field_Collection::get()
	 * @covers \GV\Field_Collection::by_position()
	 * @covers \GV\Field_Collection::by_visible()
	 * @covers \GV\Field::as_configuration()
	 * @covers \GV\Field::from_configuration()
	 * @covers \GravityView_View_Data::get_fields()
	 * @covers ::gravityview_get_directory_fields()
	 * @covers \GVCommon::get_directory_fields()
	 */
	public function test_field_and_field_collection() {
		$fields = new \GV\Field_Collection();
		$field = new \GV\Field();

		$fields->add( $field );
		$this->assertContains( $field, $fields->all() );

		/** Make sure we can only add \GV\Field objects into the \GV\Field_Collection. */
		$fields->add( new stdClass() );
		$this->assertCount( 1, $fields->all() );

		$this->assertEquals( array( 'id', 'label', 'show_label', 'custom_label', 'custom_class', 'only_loggedin', 'only_loggedin_cap', 'search_filter', 'show_as_link' ),
			array_keys( $field->as_configuration() ) );

		$fields = \GV\Field_Collection::from_configuration( array(
			'directory_list-title' => array(
				'ffff0001' => array( 'id' => 1, 'label' => 'Hi there :)' ),
				'ffff0002' => array( 'id' => 2, 'label' => 'Hi there, too :)' ),
				'ffff0003' => array( 'id' => 5, 'only_loggedin_cap' => 'read' ),
			),
			'single_list-title' => array(
				'ffff0004' => array( 'id' => 1, 'label' => 'Hi there :)', 'custom_class' => 'red' ),
			),
		) );
		$this->assertCount( 4, $fields->all() );
		$this->assertEquals( 'red', $fields->get( 'ffff0004' )->custom_class );
		$this->assertEquals( '', $fields->get( 'ffff0003' )->cap ); /** The loggedin wasn't set. */
		$this->assertSame( $fields->by_position( 'directory_list-title' )->get( 'ffff0002' ), $fields->get( 'ffff0002' ) );
		$this->assertCount( 0, $fields->by_position( 'nope' )->all() );
		$this->assertCount( 1, $fields->by_position( 'single_list-title' )->all() );
		$this->assertNull( $fields->by_position( 'nope' )->get( 'ffff0001' ) );

		$this->assertEquals( array( 'directory_list-title', 'single_list-title' ), array_keys( $fields->as_configuration() ) );

		/** Filter by permissions */
		$user = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
		) );

		$fields = \GV\Field_Collection::from_configuration( array(
			'default' => array(
				'000a' => array( 'only_loggedin' => '1', 'only_loggedin_cap' => 'manage_options' ),
				'000b' => array( 'only_loggedin' => '1', 'only_loggedin_cap' => 'read' ),
				'000c' => array( 'only_loggedin' => '0', 'only_loggedin_cap' => 'read' /** Only valid when only_loggedin is set */ ),
			),
		) );

		$visible = $fields->by_visible();
		$this->assertCount( 1, $visible->all() );
		$this->assertNotNull( $visible->get( '000c' ) );

		wp_set_current_user( $user );

		$visible = $fields->by_visible();
		$this->assertCount( 2, $visible->all() );
		$this->assertNotNull( $visible->get( '000c' ) );
		$this->assertNotNull( $visible->get( '000b' ) );

		$user = wp_get_current_user();
		$user->add_cap( 'manage_options' );
		$user->get_role_caps(); // WordPress 4.2 and lower need this to refresh caps

		$visible = $fields->by_visible();
		$this->assertCount( 3, $visible->all() );

		add_filter( 'gravityview/configuration/fields', function( $fields ) {
			foreach ( $fields['directory_table-columns'] as &$field ) {
				if ( $field['label'] == 'Business Name' ) {
					/** Custom parameters */
					$field['sentinel'] = '9148';
				}
			}
			return $fields;
		} );

		/** Back compatibility */
		$post = $this->factory->view->create_and_get();
		$view = \GV\View::from_post( $post );
		$this->assertEquals( $view->fields->as_configuration(), gravityview_get_directory_fields( $view->ID ) );

		/** Test custom getters */
		foreach( $view->fields->by_position( 'directory_table-columns' )->all() as $field ) {
			if ( $field->label == 'Business Name' ) {
				$this->assertEquals( '9148', $field->sentinel );
			}
		}

		/** Regression on \GravityView_View_Data::get_fields() */
		$this->assertEquals( $view->fields->as_configuration(), \GravityView_View_Data::getInstance()->get_fields( $view->ID ) );

		remove_all_filters( 'gravityview/configuration/fields' );

		/** Visible/hidden fields */
		add_filter( 'gravityview/configuration/fields', function( $fields ) {
			foreach ( $fields['directory_table-columns'] as &$field ) {
				if ( $field['label'] == 'Business Name' ) {
					$field['only_loggedin'] = 1;
					$field['only_loggedin_cap'] = 'read';
				}
			}
			return $fields;
		} );

		$view = \GV\View::from_post( $post );
		$view_data = $view->as_data();
		$logged_in_count = count( $view_data['fields']['directory_table-columns'] );

		wp_set_current_user( 0 );

		$view = \GV\View::from_post( $post );
		$view_data = $view->as_data();
		$non_logged_in_count = count( $view_data['fields']['directory_table-columns'] );

		$this->assertEquals( $logged_in_count - 1, $non_logged_in_count, 'Fields were not hidden for non-logged in view' );
		$this->assertEquals( $logged_in_count, $view->fields->count() );
		$this->assertEquals( $non_logged_in_count, $view->fields->by_visible()->count() );

		remove_all_filters( 'gravityview/configuration/fields' );

		$this->_reset_context();
	}

	/**
	 * @covers \GV\Context::__set()
	 * @covers \GV\Context::__get()
	 *
	 * @covers \GV\Field_Value_Context::__set()
	 * @covers \GV\Field_Value_Context::__get()
	 */
	public function test_contexts() {
		$context = new \GV\Context();

		$context->space = 'is the place';
		$this->assertEquals( 'is the place', $context->space );

		$context = new \GV\Field_Value_Context();

		$context->place = 'is the space';
		$this->assertEquals( 'is the space', $context->place );

		/** Make sure we can only set the view to a \GV\View instance. */
		$context->view = 'hello';
		$this->assertNull( $context->view );

		/** Make sure we can only set the form to a \GV\Form instance. */
		$context->form = 'hello';
		$this->assertNull( $context->form );

		/** Make sure we can only set the entry to a \GV\Entry instance. */
		$context->entry = 'hello';
		$this->assertNull( $context->entry );
	}

	/**
	 * @covers \GV\Entry_Collection::filter
	 * @covers \GV\Form::get_entries
	 * @covers \GV\Entry_Collection::count
	 * @covers \GV\Entry_Collection::total
	 * @covers \GV\GF_Entry_Filter::from_search_criteria()
	 * @covers \GV\Entry_Collection::offset
	 * @covers \GV\Entry_Collection::limit
	 * @covers \GV\Entry_Collection::sort
	 * @covers \GV\Entry_Collection::page
	 */
	public function test_entry_collection_and_filter() {
		$this->_reset_context();

		$form = $this->factory->form->import_and_get( 'simple.json' );
		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form->ID,
			'1' => 'set all the fields!',
			'2' => -100,
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form->ID ) );

		$entries = new \GV\Entry_Collection();

		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$entries->add( $entry );
		$this->assertSame( $entries->get( $entry['id'] ), $entry );

		/** Moar!!! */
		foreach ( range( 1, 500 ) as $i ) {
			$this->factory->entry->import_and_get( 'simple_entry.json', array(
				'form_id' => $form['id'],
				'1' => "this is the $i-numbered entry",
				'2' => $i,
			) );
		}

		$this->assertEquals( $form->entries->total(), 501 );
		$this->assertEquals( $form->entries->count(), 0 );

		$filter_1 = \GV\GF_Entry_Filter::from_search_criteria( array( 'field_filters' => array(
			'mode' => 'any', /** OR */
			array( 'key' => '2', 'value' => '200' ),
			array( 'key' => '2', 'value' => '300' ),
		) ) );
		$this->assertEquals( $form->entries->filter( $filter_1 )->count(), 0 );
		$this->assertEquals( $form->entries->filter( $filter_1 )->total(), 2 );

		$filter_2 = \GV\GF_Entry_Filter::from_search_criteria( array( 'field_filters' => array(
			'mode' => 'any', /** OR */
			array( 'key' => '2', 'value' => '150' ),
			array( 'key' => '2', 'value' => '450' ),
		) ) );
		$this->assertEquals( 4, $form->entries->filter( $filter_1 )->filter( $filter_2 )->total() );

		$this->assertCount( 20, $form->entries->all() ); /** The default count... */
		$this->assertCount( 4, $form->entries->filter( $filter_1 )->filter( $filter_2 )->all() );

		/** Try limiting and offsetting (a.k.a. pagination)... */
		$this->assertCount( 5, $form->entries->limit( 5 )->all() );
		$this->assertEquals( 501, $form->entries->limit( 5 )->total() );
		$this->assertEquals( 0, $form->entries->limit( 5 )->count() );

		$entries = $form->entries->limit( 2 )->offset( 0 )->all();
		$this->assertCount( 2, $entries );
		$this->assertEquals( array( $entries[0]['2'], $entries[1]['2'] ), array( '500', '499' ) );

		$entries = $form->entries->limit( 2 )->offset( 6 )->all();
		$this->assertCount( 2, $entries );
		$this->assertEquals( array( $entries[0]['2'], $entries[1]['2'] ), array( '494', '493' ) );

		$entries = $form->entries->limit( 2 )->offset( 6 );
		$entries->fetch();
		$this->assertEquals( $entries->count(), 2 );
		$entries->fetch();
		$this->assertEquals( $entries->count(), 2 );

		$last = $form->entries->limit( 2 )->offset( 6 )->last();
		$this->assertEquals( $last['2'], '493' );

		/** Hey, how about some sorting love? */
		$view = \GV\View::from_post( $view );

		$field = new \GV\Field();
		$field->ID = '2'; /** @todo What about them joins? Should a field have a form link or the other way around? */
		$sort = new \GV\Entry_Sort( $field, \GV\Entry_Sort::ASC );
		$entries = $form->entries->limit( 2 )->sort( $sort )->offset( 18 )->all();

		$this->assertEquals( array( $entries[0]['2'], $entries[1]['2'] ), array( '114', '115' ) );

		/** Pagination */
		$page_1 = $form->entries->limit( 2 )->offset( 1 )->sort( $sort );
		$this->assertEquals( 1, $page_1->current_page );
		$this->assertEquals( $page_1->total(), 500 );

		$entries = $page_1->all();
		$this->assertEquals( array( $entries[0]['2'], $entries[1]['2'] ), array( '1', '10' ) );

		$page_2 = $page_1->page( 2 );
		$this->assertEquals( 2, $page_2->current_page );

		$entries = $page_2->all();
		$this->assertEquals( array( $entries[0]['2'], $entries[1]['2'] ), array( '100', '101' ) );

		$entries = $page_2->page( 3 )->all();
		$this->assertEquals( array( $entries[0]['2'], $entries[1]['2'] ), array( '102', '103' ) );

		/** Numeric sorting, please. */
		$this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form->ID,
			'1' => "this is the floaty-numbered entry",
			'2' => 1.3,
		) );

		$this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form->ID,
			'1' => "this is the floaty-numbered entry",
			'2' => 13,
		) );

		$this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form->ID,
			'1' => "this is the floaty-numbered entry",
			'2' => 0.13,
		) );

		$entries = $form->entries->filter( \GV\GF_Entry_Filter::from_search_criteria( array( 'field_filters' => array(
			'mode' => 'all',
			array( 'key' => '1', 'value' => 'floaty-numbered', 'operator' => 'contains' ),
		) ) ) );

		$field->ID = '2';
		$sort = new \GV\Entry_Sort( $field, \GV\Entry_Sort::ASC, \GV\Entry_Sort::NUMERIC );
		$entries = $entries->sort( $sort )->all();

		$this->assertEquals( '0.13', $entries[0]['2'] );
		$this->assertEquals( '1.3', $entries[1]['2'] );
		$this->assertEquals( '13', $entries[2]['2'] );

		$this->_reset_context();
	}

	/**
	 * @covers \GV\GF_Entry_Filter::as_search_criteria()
	 * @covers \GV\GF_Entry_Filter::merge_search_criteria()
	 */
	public function test_merge_search_criteria() {
		/** Merging Gravity Forms criteria */
		$filter = \GV\GF_Entry_Filter::from_search_criteria( array( 'field_filters' => array(
			'mode' => 'hello',
			array( 'two' ),
		) ) );

		$expected = $filter->as_search_criteria();
		$this->assertEquals( $expected, $filter::merge_search_criteria( array(), $filter->as_search_criteria() ) );
		$this->assertEquals( $expected, $filter::merge_search_criteria( $filter->as_search_criteria(), array() ) );

		$expected['field_filters']['mode'] = 'bye';
		$this->assertEquals( $expected, $filter::merge_search_criteria( $filter->as_search_criteria(), array( 'field_filters' => array( 'mode' => 'bye' ) ) ) );

		$expected['field_filters'] []= array( 'one' );
		$this->assertEquals( $expected, $filter::merge_search_criteria( $filter->as_search_criteria(), array( 'field_filters' => array( 'mode' => 'bye', array( 'one' ) ) ) ) );

		$filter = \GV\GF_Entry_Filter::from_search_criteria( array( 'status' => 'active', 'start_date' => 'today', 'end_date' => 'yesterday' ) );

		$expected = $filter->as_search_criteria();
		$this->assertEquals( $expected, $filter::merge_search_criteria( array(), $filter->as_search_criteria() ) );
		$this->assertEquals( $expected, $filter::merge_search_criteria( $filter->as_search_criteria(), array() ) );

		$expected['status'] = 'inactive';
		$this->assertEquals( $expected, $filter::merge_search_criteria( $filter->as_search_criteria(), array( 'status' => 'inactive' ) ) );

		$expected['start_date'] = '2011';
		$this->assertEquals( $expected, $filter::merge_search_criteria( $filter->as_search_criteria(), array( 'status' => 'inactive', 'start_date' => '2011' ) ) );

		$expected['end_date'] = '2999';
		$this->assertEquals( $expected, $filter::merge_search_criteria( $filter->as_search_criteria(), array( 'status' => 'inactive', 'start_date' => '2011', 'end_date' => '2999' ) ) );
	}

	/**
	 * @covers GravityView_frontend::get_view_entries()
	 * @covers \GV\Mocks\GravityView_frontend_get_view_entries()
	 */
	public function test_get_view_entries_compat() {
		$this->_reset_context();

		$form = $this->factory->form->import_and_get( 'simple.json' );
		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry_1 = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form->ID,
			'1' => 'set all the fields!',
			'2' => -100,
		) );
		$view = \GV\View::by_id( $this->factory->view->create( array( 'form_id' => $form->ID ) ) );

		$entries = GravityView_frontend::get_view_entries( $view->settings->as_atts(), $form->ID );
		$this->assertEquals( 1, $entries['count'] );
		$this->assertEquals( array( 'offset' => 0, 'page_size' => 25 ), $entries['paging'] );
		$this->assertEquals( $entry_1['id'], $entries['entries'][0]['id'] );

		$entry_2 = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form->ID,
			'1' => 'a here goes nothing...',
			'2' => 999,
		) );

		$view->settings->update( array( 'page_size' => 1, 'offset' => 1 ) );

		$entries = GravityView_frontend::get_view_entries( $view->settings->as_atts(), $form->ID );
		$this->assertEquals( 1, $entries['count'] );
		$this->assertEquals( array( 'offset' => 0, 'page_size' => 1 ), $entries['paging'] );
		$this->assertEquals( $entry_1['id'], $entries['entries'][0]['id'] );

		$view->settings->set( 'offset', 0 );
		$view->settings->set( 'page_size', 30 );
		$view->settings->set( 'search_field', '2' );
		$view->settings->set( 'search_value', '999' );

		$entries = GravityView_frontend::get_view_entries( $view->settings->as_atts(), $form->ID );
		$this->assertEquals( 1, $entries['count'] );
		$this->assertEquals( $entry_2['id'], $entries['entries'][0]['id'] );

		$view->settings->set( 'search_field', '' );
		$view->settings->set( 'search_value', '' );

		$view->settings->set( 'sort_field', '1' );
		$view->settings->set( 'sort_direction', 'desc' );
		$entries = GravityView_frontend::get_view_entries( $view->settings->as_atts(), $form->ID );
		$this->assertEquals( 2, $entries['count'] );
		$this->assertEquals( $entry_1['id'], $entries['entries'][0]['id'] );

		/** Test back-compatible filters */
		add_filter( 'gravityview_search_criteria', function( $criteria ) {
			$criteria['search_criteria']['field_filters'] []= array(
				'key' => '1',
				'value' => 'goes',
				'operator' => 'contains',
			);
			return $criteria;
		} );
		$entries = GravityView_frontend::get_view_entries( $view->settings->as_atts(), $form->ID );
		$this->assertEquals( 1, $entries['count'] );
		$this->assertEquals( $entry_2['id'], $entries['entries'][0]['id'] );
		remove_all_filters( 'gravityview_search_criteria' );

		add_filter( 'gravityview_before_get_entries', function( $entries ) {
			return array( 1 );
		} );
		$entries = GravityView_frontend::get_view_entries( $view->settings->as_atts(), $form->ID );
		$this->assertEquals( array( 1 ), $entries['entries'] );
		remove_all_filters( 'gravityview_before_get_entries' );

		add_filter( 'gravityview_entries', function( $entries ) {
			return array( 2 );
		} );
		$entries = GravityView_frontend::get_view_entries( $view->settings->as_atts(), $form->ID );
		$this->assertEquals( array( 2 ), $entries['entries'] );
		remove_all_filters( 'gravityview_entries' );

		$this->_reset_context();
	}
}
