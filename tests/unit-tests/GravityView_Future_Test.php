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
		/** The future branch of GravityView requires PHP 5.3+ namespaces. */
		if ( version_compare( phpversion(), '5.3' , '<' ) ) {
			$this->markTestSkipped( 'The future code requires PHP 5.3+' );
			return;
		}

		$this->_reset_context();

		parent::setUp();
	}

	function tearDown() {
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

	/**
	 * @covers \GV\Plugin::dir()
	 * @covers \GV\Plugin::url()
	 */
	public function test_plugin_dir_and_url() {
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
	public function test_plugin_is_compatible() {
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

		unset( $GLOBALS['GRAVITYVIEW_TESTS_PHP_VERSION_OVERRIDE'] );
		unset( $GLOBALS['GRAVITYVIEW_TESTS_WP_VERSION_OVERRIDE'] );
		unset( $GLOBALS['GRAVITYVIEW_TESTS_GF_VERSION_OVERRIDE'] );
	}

	/**
	 * @covers \GV\Entry::add_rewrite_endpoint()
	 * @covers \GV\Entry::get_endpoint_name()
	 */
	public function test_entry_endpoint_rewrite_name() {
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
	 * @covers \GV\GF_Entry::by_id()
	 * @covers \GV\GF_Entry::by_slug()
	 */
	public function test_entry_by_slug() {

		$form = $this->factory->form->create_and_get();
		$_entry = $this->factory->entry->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$entry_id = $_entry['id'];

		$entry = \GV\GF_Entry::by_id( 'uno' );
		$this->assertNull( $entry );

		$entry = \GV\GF_Entry::by_id( $entry_id );
		$this->assertEquals( $entry->slug, $entry_id );
		$this->assertEquals( $entry_id, $entry->ID );

		add_filter( 'gravityview_custom_entry_slug', '__return_true' );

		$random = strtolower( wp_generate_password( 8, false ) );
		add_filter( 'gravityview_entry_slug', function( $slug ) use ( $random ) {
			return "sentinel-$random";
		}, 10 );

		/** Updates the slug as a side-effect :( */
		\GravityView_API::get_entry_slug( $entry_id, $_entry );

		$entry = \GV\GF_Entry::by_id( 'uno' );
		$this->assertNull( $entry );

		$entry = \GV\GF_Entry::by_id( "sentinel-$random" );
		$this->assertEquals( $entry->slug, "sentinel-$random" );
		$this->assertEquals( $entry_id, $entry->ID );

		$entry = \GV\GF_Entry::by_slug( "sentinel-$random" );
		$this->assertEquals( $entry->slug, "sentinel-$random" );
		$this->assertEquals( $entry_id, $entry->ID );

		$entry = \GV\GF_Entry::by_slug( $entry_id );
		$this->assertNull( $entry );

		remove_all_filters( 'gravityview_custom_entry_slug' );
		remove_all_filters( 'gravityview_entry_slug' );
	}

	/**
	 * @covers \GV\Entry::get_permalink()
	 */
	public function test_entry_get_permalink() {
		$this->_reset_context();
		$form = $this->factory->form->create_and_get();
		$entry = $this->factory->entry->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();

		global $post;

		/** A standalone View. */
		$post = get_post( $view->ID );
		$expected_url = add_query_arg( array( 'entry' => $entry->ID ), get_permalink( $view->ID ) );
		$this->assertEquals( $expected_url, $entry->get_permalink( $view, $request ) );

		/** With tracking. */
		$_GET = array( 'pagenum' => 1, 'sort' => '4', 'dir' => 'rand' );

		$this->assertEquals( add_query_arg( $_GET, $expected_url ), $entry->get_permalink( $view, $request ) );

		$_GET = array();

		/** An embedded View, sort of. */
		$post = $this->factory->post->create_and_get();

		$expected_url = add_query_arg( array( 'gvid' => $view->ID, 'entry' => $entry->ID ), get_permalink( $post->ID ) );
		$this->assertEquals( $expected_url, $entry->get_permalink( $view, $request ) );

		/** Filters. */
		add_filter( 'gravityview_directory_link', function( $directory ) {
			return 'ooh';
		} );

		$this->assertEquals( add_query_arg( array( 'gvid' => $view->ID, 'entry' => $entry->ID ), 'ooh' ), $entry->get_permalink( $view, $request ) );

		add_filter( 'gravityview/entry/permalink', function( $permalink ) {
			return 'ha';
		} );

		$this->assertEquals( 'ha', $entry->get_permalink( $view, $request ) );

		remove_all_filters( 'gravityview_directory_link' );
		remove_all_filters( 'gravityview/entry/permalink' );

		/** With nice permastruct :) */
		update_option( 'permalink_structure', '/%postname%' );
		$this->assertEquals( get_permalink( $post->ID ) . '/entry/' . $entry->ID . '/?gvid=' . $view->ID, $entry->get_permalink( $view, $request ) );

		unset( $post );
		update_option( 'permalink_structure', '' );
	}

	/**
	 * @covers \GV\Plugin::activate()
	 */
	public function test_plugin_activate() {
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
	public function test_view_collection_add() {
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
	public function test_view_from_post() {
		$post = $this->factory->view->create_and_get();
		$view = \GV\View::from_post( $post );
		$this->assertEquals( $view->ID, $post->ID );

		/** Check forms initialization. */
		$this->assertNotNull( $view->form );

		/** Check fields initialization. */
		foreach ( $view->fields->all() as $field ) {
			$this->assertInstanceOf( '\GV\GF_Field', $field );
			$this->assertEquals( $view->form->ID, $field->form_id );
		}

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
	public function test_view_by_id() {
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
	public function test_view_exists() {
		$data = GravityView_View_Data::getInstance();
		$post = $this->factory->view->create_and_get();

		$this->assertTrue( \GV\View::exists( $post->ID ) );
		$this->assertTrue( $data->view_exists( $post->ID ) );

		$this->assertFalse( \GV\View::exists( $post->ID + 100 ) );
		$this->assertFalse( $data->view_exists( $post->ID + 100 ) );
	}

	/**
	 * @covers \GV\View::offsetExists()
	 * @covers \GV\View::offsetSet()
	 * @covers \GV\View::offsetUnset()
	 * @covers \GV\View::offsetGet()
	 * @covers \GV\View::as_data()
	 */
	public function test_view_data_compat() {
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
	public function test_data_get_views() {
		$post = $this->factory->view->create_and_get();
		$view = \GV\View::by_id( $post->ID );

		$another_post = $this->factory->view->create_and_get();
		$another_view = \GV\View::by_id( $another_post->ID );

		$views = new \GV\View_Collection();
		$views->add( $view );
		$views->add( $another_view );

		\GV\Mocks\Legacy_Context::push( array(
			'views' => $views,
		) );

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

			$admin_bar->add_links();

			wp_set_current_user( 0 );
		}

		{
			\GV\Mocks\Legacy_Context::push( array(
				'post' => $post,
				'view' => $view,
				'in_the_loop' => true,
			) );

			gravityview()->request = new \GV\Mock_Request();
			gravityview()->request->returns['is_view'] = $view;

			$fe = \GravityView_frontend::getInstance();

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
			$and_another_entry = $this->factory->entry->create_and_get( array( 'form_id' => $and_another_view->form->ID, 'status' => 'active' ) );

			gravityview()->request->returns['is_view'] = $and_another_view;

			$fe->setIsGravityviewPostType( true );
			$this->assertContains( 'not allowed to view this content', $fe->render_view( array(
				'id' => $and_another_view->ID,
				'embed_only' => true, /** Check propagation of $passed_args */
			) ) );

			$this->assertContains( 'gv-container-' . $and_another_view->ID, $fe->render_view( array(
				'id' => $and_another_view->ID,
				'embed_only' => false, /** Check propagation of $passed_args */
			) ) );

			gform_update_meta( $and_another_entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );
			gravityview()->request->returns['is_entry'] = \GV\GF_Entry::by_id( $and_another_entry['id'] );

			/**
			 * The back link.
			 */
			$this->assertContains( sprintf( 'data-viewid="%d"', $and_another_view->ID ), $fe->render_view( array(
				'id' => $and_another_view->ID,
				'debug' => true,
			) ) );
		}
	}

	/**
	 * @covers \GV\View_Collection::from_post()
	 * @covers \GV\View_Collection::from_content()
	 * @covers \GV\View_Collection::get()
	 * @covers \GV\View_Collection::contains()
	 * @covers \GravityView_View_Data::maybe_get_view_id()
	 * @covers \GravityView_View_Data::is_valid_embed_id()
	 */
	public function test_view_collection_from_post() {
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

		$this->_reset_context();
		$form = $this->factory->form->create_and_get();
		$entry = $this->factory->entry->create_and_get( array( 'form_id' => $form['id'], 'status' => 'active' ) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$post = $this->factory->post->create_and_get( array( 'post_content' => sprintf( '[gravityview id="%d"]', $view->ID ) ) );

		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );
		$embed_content = sprintf( "\n%s\n", add_query_arg( 'entry', $entry['id'], get_permalink( $view->ID ) ) );
		$this->assertContains( 'table class="gv-table-view-content"', $GLOBALS['wp_embed']->autoembed( $embed_content ) );

		/** Test GravityView_View_Data::is_valid_embed_id regression. */
		$this->assertTrue( GravityView_View_Data::is_valid_embed_id( $post->ID, $view->ID ) );
		$this->assertInstanceOf( '\WP_Error', GravityView_View_Data::is_valid_embed_id( $post->ID, $another_post->ID ) );
		$this->assertInstanceOf( '\WP_Error', GravityView_View_Data::is_valid_embed_id( '', $view->ID ) );
		$this->assertTrue( GravityView_View_Data::is_valid_embed_id( '', $view->ID, true ) );
		$this->assertInstanceOf( '\WP_Error', GravityView_View_Data::is_valid_embed_id( $post->ID, $post->ID ) );

		$this->_reset_context();

		$data = GravityView_View_Data::getInstance();

		/** Test shortcode has all attributes in View regression. */
		$views = $data->maybe_get_view_id( $with_shortcodes );
		$view = $data->get_view( $views[1] );
		$this->assertEquals( $view['atts']['search_field'], 2 );

		$GLOBALS['shortcode_tags']['gravityview'] = $original_shortcode;
	}

	/**
	 * @covers \GV\GF_Form::by_id()
	 */
	public function test_form_gravityforms() {
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
	public function test_form_collection() {
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
	public function test_shortcode_add() {
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
	 * @covers \GV\Shortcode::parse()
	 * @covers \GravityView_View_Data::parse_post_content()
	 */
	public function test_shortcode_parse() {
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
	public function test_core_init() {
		gravityview()->request = new \GV\Frontend_Request();
	}

	/**
	 * @covers \GV\Frontend_Request::is_admin()
	 */
	public function test_default_request_is_admin() {
		$this->assertFalse( gravityview()->request->is_admin() );

		set_current_screen( 'dashboard' );
		$this->assertTrue( gravityview()->request->is_admin() );
		$_script_name = $_SERVER['SCRIPT_NAME'];
		$_SERVER['SCRIPT_NAME'] = '/wp-admin/load-scripts.php';
		$this->assertFalse( gravityview()->request->is_admin() );
		$_SERVER['SCRIPT_NAME'] = $_script_name;
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
		$logic_shortcode = \GVLogic_Shortcode::get_instance();
		$this->assertEquals( $logic_shortcode->shortcode( array( 'if' => 'true', 'is' => 'true' ), 'sentinel' ), 'sentinel' );
		set_current_screen( 'dashboard' );
		$this->assertNull( $logic_shortcode->shortcode( array( 'if' => 'true', 'is' => 'true' ), 'sentinel' ), 'sentinel' );

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
	 * @covers \GV\Frontend_Request::is_search()
	 */
	public function test_frontend_request_is_search() {

		$request = new \GV\Frontend_Request();

		global $post;
		$this->assertFalse( $request->is_view() );

		$view = $this->factory->view->create_and_get();

		$post = $view;
		$this->assertInstanceOf( '\GV\View', $request->is_view() );

		$_GET = array();
		$this->assertFalse( $request->is_search() );

		$_GET = array( 'gv_search' => 'flow' );
		$this->assertTrue( $request->is_search() );

		$_GET = array(
            'gv_search' => 'flow',
            'filter_16' => 'Features+%2F+Enhancements',
        );

		$this->assertTrue( $request->is_search() );

		$_GET = array(
			'gv_search' => '',
			'filter_16' => 'Features+%2F+Enhancements',
		);
		$this->assertTrue( $request->is_search() );

		// TODO: Only count $_GET when in searchable fields

		$_GET = array();
		$_POST = array(
			'gv_search' => '',
			'filter_16' => 'Features+%2F+Enhancements',
		);
		$this->assertFalse( $request->is_search() );

		// Use $_POST instead of $_GET for searches
		add_filter( 'gravityview/search/method', $use_post = function( $method = 'get' ) {
		    return 'post';
        });

		$_POST = array(
			'gv_search' => '',
			'filter_16' => 'Features+%2F+Enhancements',
		);
		$this->assertTrue( $request->is_search() );

		remove_filter( 'gravityview/search/method', $use_post );

		$this->assertFalse( $request->is_search() );

		$post = null;
	}

	public function test_admin_request_is_admin_page() {
		$this->assertFalse( gravityview_is_admin_page() );

		set_current_screen( 'dashboard' );
		$this->assertTrue( \GravityView_Admin::is_admin_page( 'what', 'when' ) );

		$_request = gravityview()->request;
		gravityview()->request = new \GV\Admin_Request();

		$this->assertTrue( gravityview()->request->is_admin() );

		$this->assertFalse( \GravityView_Admin::is_admin_page() );
		$this->assertFalse( \GravityView_Admin::is_admin_page( 'edit.php', 'single' ) );

		$_id = get_current_screen()->id;
		$_post_type = get_current_screen()->post_type;
		get_current_screen()->id = 'gravityview_page_gravityview_settings';
		get_current_screen()->post_type = 'gravityview';

		$this->assertEquals( 'settings', \GravityView_Admin::is_admin_page( 'edit.php', '' ) );

		$this->assertTrue( gravityview()->request->is_admin( 'edit.php', 'settings' ) );
		$this->assertFalse( gravityview()->request->is_admin( 'edit.php', 'zettingz' ) );

		gravityview()->request = $_request;
		get_current_screen()->id = $_id;
		get_current_screen()->post_type = $_post_type;
		set_current_screen( 'frontend' );
	}

	/**
	 * @covers \GV\Frontend_Request::is_admin()
	 * @group ajax
	 */
	public function test_default_request_is_admin_ajax() {
		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		$this->assertFalse( gravityview()->request->is_admin() );
		$this->assertEquals( gravityview()->request->is_admin(), \GravityView_Plugin::is_admin() );

		set_current_screen( 'dashboard' );
		$this->assertFalse( gravityview()->request->is_admin() );
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

		$settings = new \GV\Settings( array( 'one' => 'six' ) );
		$this->assertEquals( $settings->get( 'one' ), 'six' );
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
	}

	/**
	 * @covers \GV\WP_Action_Logger::log()
	 */
	public function test_logging() {
		$this->assertInstanceOf( '\GV\WP_Action_Logger', gravityview()->log );

		$_this = &$this;
		if ( version_compare( phpversion(), '5.4', '>=' ) ) {
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

		$GLOBALS['GRAVITYVIEW_TESTS_PHP_VERSION_OVERRIDE'] = '5.3';

		add_action( 'gravityview_log_error_test', function( $message, $data ) use ( $_this ) {
			$_this->assertEquals( "[critical] Hello, TRAPPIST-1!", $message );
			$_this->assertEquals( $data, array( 'a' => 'b' ) );
		}, 10, 2 );
		gravityview()->log->critical( 'Hello, {world}!', array( 'world' => 'TRAPPIST-1', 'data' => array( 'a' => 'b' ) ) );
		remove_all_actions( 'gravityview_log_error_test' );

		unset( $GLOBALS['GRAVITYVIEW_TESTS_PHP_VERSION_OVERRIDE'] );
	}

	public function test_widget_collection() {
		$configuration = array(
			'header_top' => array(
				wp_generate_password( 4, false ) => array(
					'id' => 'search_bar',
					'search_fields' => '[{"field":"search_all","input":"input_text"}]',
				),
			),
			'header_left' => array(
				wp_generate_password( 4, false ) => array(
					'id' => 'page_info',
				),
			),
			'footer_top' => array(
				wp_generate_password( 4, false ) => array(
					'id' => 'custom_content',
					'content' => 'Here we go again! <b>Now</b>',
				),
			),
			'footer_right' => array(
				wp_generate_password( 4, false ) => array(
					'id' => 'page_links',
				),
			),
		);

		$widgets = \GV\Widget_Collection::from_configuration( $configuration );

		$this->assertEquals( 4, $widgets->count() );

		$footer_widgets = $widgets->by_position( 'footer_*' );
		$this->assertEquals( 2, $footer_widgets->count() );

		$this->assertEquals( 0, $widgets->by_id( 'custom_conten' )->count() );
		$this->assertEquals( 1, $widgets->by_id( 'custom_content' )->count() );

		$this->assertEquals( $configuration, $widgets->as_configuration() );
	}

	/**
	 * @covers \GV\Field_Collection::add()
	 * @covers \GV\Field_Collection::from_configuration()
	 * @covers \GV\Field_Collection::as_configuration()
	 * @covers \GV\Field_Collection::get()
	 * @covers \GV\Field_Collection::by_position()
	 * @covers \GV\Field_Collection::by_visible()
	 * @covers \GV\Field::is_visible()
	 * @covers \GV\Field::as_configuration()
	 * @covers \GV\Field::from_configuration()
	 * @covers \GV\GF_Field::from_configuration()
	 * @covers \GV\Internal_Field::from_configuration()
	 * @covers \GV\Field::update_configuration()
	 * @covers \GravityView_View_Data::get_fields()
	 * @covers ::gravityview_get_directory_fields()
	 * @covers \GVCommon::get_directory_fields()
	 * @covers \GV\Field::get_value()
	 * @covers \GV\Field::get_label()
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

		/** Field configuration and update configuration. */
		$field = \GV\Field::from_configuration(
			array( 'id' => 'custom', 'label' => 'Custom', 'content' => 'Wow!' )
		);

		$this->assertEquals( 'custom', $field->ID );

		$field->update_configuration( array() );

		$this->assertEquals( 'custom', $field->ID );
		$this->assertEquals( 'Wow!', $field->content );
		$this->assertTrue( isset( $field->content ) );
		$this->assertNotEmpty( $field->content );
		$this->assertEmpty( $field->noexists_here_now_then );

		$field->update_configuration( array( 'update' => 'Now!', 'id' => 4 ) );

		$this->assertEquals( 4, $field->ID );
		$this->assertEquals( 'Wow!', $field->content );

		/** Configuration implementations: \GV\Internal_Field */
		$field = \GV\Field::from_configuration( array( 'id' => 'custom' ) );
		$this->assertInstanceOf( '\GV\Internal_Field', $field );
		$this->assertEquals( 'custom', $field->ID );

		/** Configuration implementations: \GV\GF_Field */
		$field = \GV\Field::from_configuration( array( 'id' => 499 ) );
		$this->assertInstanceOf( '\GV\Field', $field );
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$field = \GV\Field::from_configuration( array( 'id' => 1, 'form_id' => $form['id'] ) );
		$this->assertInstanceOf( '\GV\GF_Field', $field );
		$this->assertEquals( 'text', $field->type );
		$field = \GV\Field::from_configuration( array( 'id' => 2, 'form_id' => $form['id'] ) );
		$this->assertInstanceOf( '\GV\GF_Field', $field );
		$this->assertEquals( 'number', $field->type );

		/** Test filter and error condition. */
		add_filter( 'gravityview/field/class', function( $class ) {
			return 'NoExist_ForSure_Really';
		} );
		$field = \GV\Field::from_configuration( array( 'id' => 1 ) );
		$this->assertInstanceOf( '\GV\Field', $field );
		remove_all_filters( 'gravityview/field/class' );

		add_filter( 'gravityview/field/class', function( $class ) {
			return 'stdClass';
		} );
		$field = \GV\Field::from_configuration( array( 'id' => 1 ) );
		$this->assertInstanceOf( '\GV\Field', $field );
		remove_all_filters( 'gravityview/field/class' );

		/** Mass configuration. */
		$fields = \GV\Field_Collection::from_configuration( array(
			'directory_list-title' => array(
				'ffff0001' => array( 'id' => 1, 'form_id' => $form['id'], 'label' => 'Hi there :)' ),
				'ffff0002' => array( 'id' => 2, 'form_id' => $form['id'], 'label' => 'Hi there, too :)' ),
				'ffff0003' => array( 'id' => 'custom', 'only_loggedin_cap' => 'read' ),
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
		$this->assertInstanceOf( '\GV\GF_Field', $fields->get( 'ffff0001' ) );
		$this->assertInstanceOf( '\GV\GF_Field', $fields->get( 'ffff0002' ) );
		$this->assertInstanceOf( '\GV\Internal_Field', $fields->get( 'ffff0003' ) );
		$this->assertInstanceOf( '\GV\Field', $fields->get( 'ffff0004' ) );

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

		add_filter( 'gravityview/field/is_visible', function( $visible, $field ) {
			if ( $field->UID == '000c' )
				return false;
			return $visible;
		}, 10, 2 );

		$visible = $fields->by_visible();
		$this->assertCount( 1, $visible->all() );
		$this->assertNull( $visible->get( '000c' ) );
		$this->assertNotNull( $visible->get( '000b' ) );

		remove_all_filters( 'gravityview/field/is_visible' );

		$user = wp_get_current_user();

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

		\GV\View::_flush_cache();

		$view = \GV\View::from_post( $post );
		$view_data = $view->as_data();
		$non_logged_in_count = count( $view_data['fields']['directory_table-columns'] );

		$this->assertEquals( $logged_in_count - 1, $non_logged_in_count, 'Fields were not hidden for non-logged in view' );
		$this->assertEquals( $logged_in_count, $view->fields->count() );
		$this->assertEquals( $non_logged_in_count, $view->fields->by_visible()->count() );

		remove_all_filters( 'gravityview/configuration/fields' );

		/** Some values, shall we? */
		$fields = $view->fields->by_position( 'directory_table-columns' )->all();

		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $view->form->ID,
			'1' => 'Monsters, Inc.',
			'4' => 'International',
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		/** Uninitialized */
		$field = new \GV\Field();

		$this->assertNull( $field->get_value() );

		add_filter( 'gravityview/field/value', function( $value ) {
			return 'sentinel-2';
		} );
		$this->assertEquals( 'sentinel-2', $field->get_value() );
		remove_all_filters( 'gravityview/field/value' );

		$this->assertNull( $field->get_value() );

		/** Gravity Forms values, please. */
		$field = \GV\GF_Field::by_id( $view->form, '4' );
		$this->assertEquals( 'International', $field->get_value( $view, $view->form, $entry ) );

		add_filter( 'gravityview/field/value', function( $value ) {
			return 'sentinel-4';
		} );
		$this->assertEquals( 'sentinel-4', $field->get_value( $view, null /** \GV\Source */, $entry ) );
		remove_all_filters( 'gravityview/field/value' );

		/** How about internal fields? */
		$field = \GV\Internal_Field::by_id( 'id' );
		$this->assertEquals( $entry->ID, $field->get_value( $view, $view->form, $entry ) );

		add_filter( 'gravityview/field/value', function( $value ) {
			return 'sentinel-6';
		} );
		$this->assertEquals( 'sentinel-6', $field->get_value( $view, null /** \GV\Source */, $entry ) );
		remove_all_filters( 'gravityview/field/value' );

		/** By type? */
		$field = \GV\Internal_Field::by_id( 'id' );
		$this->assertEquals( $entry->ID, $field->get_value( $view, $view->form, $entry ) );

		add_filter( 'gravityview/field/id/value', function( $value ) {
			return 'sentinel-7';
		} );
		$this->assertEquals( 'sentinel-7', $field->get_value( $view, null /** \GV\Source */, $entry ) );
		remove_all_filters( 'gravityview/field/id/value' );

		/** How about labels? Uninitialized first. */
		$field = new \GV\Field();

		$this->assertEmpty( $field->get_label() );

		/** Initialized override. */
		$field->update_configuration( array( 'custom_label' => 'This is a custom label' ) );
		$this->assertEquals( 'This is a custom label', $field->get_label() );

		/** Gravity Forms values, please. */
		$field = \GV\GF_Field::by_id( $view->form, '4' );
		$this->assertEquals( 'Multi select', $field->get_label( $view, $view->form, $entry ) );

		/** Gravity Forms values, with field from view override. */
		$field = \GV\GF_Field::by_id( $view->form, '4' );
		$field->update_configuration( array( 'custom_label' => 'Hobbies <small>Multiselect</small>' ) );
		$this->assertEquals( 'Hobbies <small>Multiselect</small>', $field->get_label( $view, $view->form, $entry ) );

		/** Custom label override and merge tags. */
		$field->update_configuration( array( 'custom_label' => 'This is {entry_id}' ) );
		$this->assertEquals( 'This is ' . $entry->ID, $field->get_label( $view, $view->form, $entry ) );

		/** Internal fields. */
		$field = \GV\Internal_Field::by_id( 'id' );
		$field->update_configuration( array( 'label' => 'ID <small>Entry</small>' ) );
		$this->assertEquals( 'ID <small>Entry</small>', $field->get_label() );

		/** Custom label override and merge tags. */
		$field->update_configuration( array( 'custom_label' => 'This is {entry_id}' ) );
		$this->assertEquals( 'This is ' . $entry->ID, $field->get_label( $view, $view->form, $entry ) );
	}

	/**
	 * @covers \GV\Mocks\GravityView_API_field_value()
	 * @covers \GravityView_API::field_value()
	 */
	public function test_field_value_compat() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form->ID,
			'1' => 'set all the fields!',
			'2' => -100,
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		GravityView_View::getInstance()->setForm( $form->form );

		$field_settings = array(
			'id' => '1',
		);

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = gravityview()->views->get( $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) ) );

		$this->assertEquals( 'set all the fields!', GravityView_API::field_value( $entry->as_entry(), $field_settings ) );

		$field_settings = array(
			'id' => 'custom',
			'content' => 'this is it',
			'wpautop' => true,
		);
		$this->assertEquals( "<p>this is it</p>\n", GravityView_API::field_value( $entry->as_entry(), $field_settings ) );

		/** A more complicated form */
		$form = $this->factory->form->create_and_get();
		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = $this->factory->entry->import_and_get( 'standard_entry.json', array(
			'form_id' => $form->ID,
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		GravityView_View::getInstance()->setForm( $form->form );

		$field_settings = array(
			'id' => '14',
		);
		$this->assertEquals( '<a href="http://apple.com" rel="noopener noreferrer" target="_blank">http://apple.com</a>', GravityView_API::field_value( $entry->as_entry(), $field_settings ) );
	}

	/**
	 * @covers \GV\Template::split_slug()
	 */
	public function test_template_split_slug() {
		$this->assertEquals( \GV\View_Template::split_slug( 'main' ), array( '', 'main' ) );
		$this->assertEquals( \GV\View_Template::split_slug( 'secondary', 'part' ), array( '', 'secondary-part' ) );
		$this->assertEquals( \GV\View_Template::split_slug( 'partial/sub' ), array( 'partial/', 'sub' ) );
		$this->assertEquals( \GV\View_Template::split_slug( 'partial/sub', 'part' ), array( 'partial/', 'sub-part') );
		$this->assertEquals( \GV\View_Template::split_slug( 'partial/fraction/atom', '' ), array( 'partial/fraction/', 'atom' ) );
		$this->assertEquals( \GV\View_Template::split_slug( 'partial/fraction/atom', 'quark' ), array( 'partial/fraction/', 'atom-quark' ) );
	}

	/**
	 * @covers \GV\View_Renderer::render()
	 * @covers \GV\View_Table_Template::render()
	 */
	public function test_frontend_view_renderer_table() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		global $post;

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '1.6',
						'label' => 'Country <small>(Address)</small>',
						'only_loggedin_cap' => 'read',
						'only_loggedin' => true,
					),
				),
			),
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'search_bar',
						'search_fields' => '[{"field":"search_all","input":"input_text"}]',
					),
				),
			),
		) );
		$view = \GV\View::from_post( $post );
		$view->settings->update( array( 'page_size' => 3 ) );
		$entries = new \GV\Entry_Collection();

		$renderer = new \GV\View_Renderer();

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view );

		/** No matching entries... */
		$this->assertEquals( $legacy, $future );
		$this->assertContains( 'No entries match your request', $future );

		/** Some entries */
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => sprintf( 'Some text in a textarea (%s)', wp_generate_password( 12 ) ),
		) );
		$entries->add( \GV\GF_Entry::by_id( $entry['id'] ) );

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view );

		/** One entry */
		$this->assertEquals( $legacy, $future );
		$this->assertContains( 'Some text in a textarea', $future );

		/** Some more */
		foreach ( range( 1, 5 ) as $i ) {
			$entry = $this->factory->entry->create_and_get( array(
				'form_id' => $form['id'],
				'status' => 'active',
				'16' => sprintf( '[%d] Some text in a textarea (%s)', $i, wp_generate_password( 12 ) ),
			) );
			$entries->add( \GV\GF_Entry::by_id( $entry['id'] ) );
		}

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view );

		/** Page one */
		$this->assertEquals( $legacy, $future );
		$this->assertContains( '[5] Some text in a textarea', $future );
		$this->assertNotContains( '[1] Some text in a textarea', $future );

		/** Page two? */
		$_GET = array( 'pagenum' => 2 );

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view );

		$this->assertEquals( $legacy, $future );
		$this->assertContains( '[1] Some text in a textarea', $future );

		/** Some more */
		foreach ( range( 1, 5 ) as $i ) {
			$entry = $this->factory->entry->create_and_get( array(
				'form_id' => $form['id'],
				'status' => 'active',
				'16' => sprintf( '[%d] thisissomemoretext, search me (%s)', $i, wp_generate_password( 12 ) ),
			) );
			$entries->add( \GV\GF_Entry::by_id( $entry['id'] ) );
		}

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view );

		/** Page two */
		$this->assertEquals( $legacy, $future );
		$this->assertContains( '[5] Some text in a textarea', $future );
		$this->assertNotContains( '[1] Some text in a textarea', $future );

		/** Search */
		$_GET = array( 'pagenum' => 1, 'gv_search' => 'thisissomemoretext' );

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view );

		$this->assertEquals( $legacy, $future );
		$this->assertContains( '[5] thisissomemoretext', $future );
		$this->assertNotContains( 'Some text', $future );

		$_GET = array( 'pagenum' => 2, 'gv_search' => 'thisissomemoretext' );

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view );

		$this->assertEquals( $legacy, $future );
		$this->assertContains( '[1] thisissomemoretext', $future );
		$this->assertNotContains( 'Some text', $future );

		$_GET = array( 'pagenum' => 3, 'gv_search' => 'thisissomemoretext' );

		$future = $renderer->render( $view );

		$this->assertContains( 'No entries match your request.', $future );

		/** Hide until searched */
		$view->settings->update( array( 'hide_until_searched' => true ) );

		$_GET = array( 'pagenum' => 1 );

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view );

		$this->assertEquals( $legacy, $future );
		$this->assertContains( 'No entries match your request.', $future );

		$_GET = array( 'pagenum' => 2, 'gv_search' => 'thisissomemoretext' );
		gravityview()->request->returns['is_search'] = true;

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view );

		$this->assertEquals( $legacy, $future );
		$this->assertContains( '[1] thisissomemoretext', $future );
		$this->assertNotContains( 'Country', $future );

		$_GET = array();
		gravityview()->request->returns['is_search'] = false;

		$view->settings->update( array( 'hide_until_searched' => false, 'show_only_approved' => true ) );

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view );

		/** No matching entries... */
		$this->assertEquals( $legacy, $future );
		$this->assertContains( 'No entries match your request.', $future );

		$_entries = $entries->all();
		foreach ( array_rand( $_entries, 5 ) as $entry_num ) {
			gform_update_meta( $_entries[ $entry_num ]->ID, \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );
		}

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view, new \GV\Frontend_Request() );

		/** No matching entries... */
		$this->assertEquals( $legacy, $future );
		$this->assertNotContains( 'No entries match your request.', $future );

		$administrator = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => 'administrator' )
		);

		/** Log in and find our hidden column there... */
		wp_set_current_user( $administrator );

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view );

		/** No matching entries... */
		$this->assertEquals( $legacy, $future );
		$this->assertContains( 'Country', $future );

		/** No configuration */
		$view->fields = new \GV\Field_Collection();

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view );

		$this->assertEquals( $legacy, $future );
		$this->assertContains( 'The Multiple Entries layout has not been configured.', $future );

		wp_set_current_user( -1 );

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view );

		$this->assertEquals( $legacy, $future );
		$this->assertNotContains( 'The Multiple Entries layout has not been configured.', $future );
		$this->assertNotContains( 'Textarea', $future );
	}

	/**
	 * @covers \GV\View_Renderer::render()
	 * @covers \GV\View_List_Template::render()
	 */
	public function test_frontend_view_renderer_list() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		global $post;

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'preset_business_listings',
			'fields' => array(
				'directory_list-title' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '8',
						'label' => 'Name',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'content',
						'label' => 'Content',
					),
				),
				'directory_list-subtitle' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1.6',
						'label' => 'Country <small>(Address)</small>',
						'only_loggedin_cap' => 'read',
						'only_loggedin' => true,
					),
					wp_generate_password( 4, false ) => array(
						'id' => '10',
						'label' => 'Phone',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'content',
						'label' => 'Content',
					),
				),
				'directory_list-image' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '8',
						'label' => 'Name',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'content',
						'label' => 'Content',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '10',
						'label' => 'Phone',
					),
				),
				'directory_list-description' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'content',
						'label' => 'Content',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
				),
				'directory_list-footer-left' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '8',
						'label' => 'Name',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'content',
						'label' => 'Content',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
				),
				'directory_list-footer-right' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '10',
						'label' => 'Phone',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'content',
						'label' => 'Content',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '1.6',
						'label' => 'Country <small>(Address)</small>',
						'only_loggedin_cap' => 'read',
						'only_loggedin' => true,
					),
				)
			),
		) );
		$view = \GV\View::from_post( $post );

		$view->settings->update( array( 'page_size' => 3 ) );

		$entries = new \GV\Entry_Collection();

		\GV\Mocks\Legacy_Context::push( array(
			'post' => $post,
			'view' => $view,
			'entries' => $entries,
			'in_the_loop' => true,
		) );

		$renderer = new \GV\View_Renderer();

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view, new \GV\Frontend_Request() );

		/** Clean up the differences a bit */
		$legacy = str_replace( ' style=""', '', $legacy );
		$legacy = preg_replace( '#>\s*<#', '><', $legacy );
		$future = preg_replace( '#>\s*<#', '><', $future );

		/** No matching entries... */
		$this->assertEquals( $legacy, $future );
		$this->assertContains( 'No entries match your request', $future );

		/** Some entries */
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'8.1' => 'Mr.',
			'8.2' => 'Floaty',
		) );
		$entries->add( \GV\GF_Entry::by_id( $entry['id'] ) );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'10' => '483828428248',
		) );
		$entries->add( \GV\GF_Entry::by_id( $entry['id'] ) );

		\GV\Mocks\Legacy_Context::push( array(
			'post' => $post,
			'view' => $view,
			'entries' => $entries,
			'in_the_loop' => true,
		) );

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view, new \GV\Frontend_Request() );

		/** Clean up the differences a bit */
		$legacy = str_replace( ' style=""', '', $legacy );
		$legacy = preg_replace( '#>\s*<#', '><', $legacy );
		$future = preg_replace( '#>\s*<#', '><', $future );

		$this->assertEquals( $legacy, $future );
	}

	public function test_frontend_widgets() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		global $post;

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
				),
			),
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'search_bar',
						'search_fields' => '[{"field":"search_all","input":"input_text"}]',
					),
				),
				'header_left' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'page_info',
					),
				),
				'footer_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'custom_content',
						'content' => 'Here we go again! <b>Now</b>',
					),
				),
				'footer_right' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'page_links',
					),
				),
			),
		) );

		$view = \GV\View::from_post( $post );
		$view->settings->update( array( 'page_size' => 3 ) );

		$entries = new \GV\Entry_Collection();

		foreach ( range( 1, 5 ) as $i ) {
			$entry = $this->factory->entry->create_and_get( array(
				'form_id' => $form['id'],
				'status' => 'active',
				'16' => wp_generate_password( 12 ),
			) );
			$entries->add( \GV\GF_Entry::by_id( $entry['id'] ) );
		}

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;

		$renderer = new \GV\View_Renderer();

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view );

		$this->assertEquals( $legacy, $future );
		$this->assertContains( 'Search Entries', $future );
		$this->assertContains( 'Displaying 1 - 3 of 5', $future );
		$this->assertContains( "class='page-numbers'", $future );
		$this->assertContains( 'Here we go again! <b>Now</b>', $future );
	}

	/**
	 * @covers \GV\Entry_Renderer::render()
	 * @covers \GV\Entry_Table_Template::render()
	 */
	public function test_entry_renderer_table() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		global $post;

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
		) );
		$view = \GV\View::from_post( $post );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => sprintf( 'Some text in a textarea (%s)', wp_generate_password( 12 ) ),
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;
		gravityview()->request->returns['is_entry'] = $entry;

		$administrator = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => 'administrator' )
		);

		wp_set_current_user( $administrator );

		$renderer = new \GV\Entry_Renderer();
		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $entry, $view );

		$this->assertEquals( $legacy, $future );
		$this->assertContains( 'The Single Entry layout has not been configured', $future );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '1.6',
						'label' => 'Country <small>(Address)</small>',
						'only_loggedin_cap' => 'read',
						'only_loggedin' => true,
					),
				),
			),
			'settings' => array(
				'single_title' => 'Entry ~@{entry_id}@~',
				'back_link_label' => "Let's go back!",
			),
		) );
		$view = \GV\View::from_post( $post );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'1.6' => 'Mexico',
			'16' => sprintf( 'Some text in a textarea (%s)', wp_generate_password( 12 ) ),
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		gravityview()->request->returns['is_view'] = $view;
		gravityview()->request->returns['is_entry'] = $entry;

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $entry, $view );

		$this->assertEquals( $legacy, $future );
		$this->assertContains( 'text in a textarea', $future );

		wp_set_current_user( -1 );

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $entry, $view );

		$this->assertEquals( $legacy, $future );
		$this->assertContains( 'text in a textarea', $future );
		$this->assertContains( 'Let&#039;s go back!', $future );
		$this->assertNotContains( 'Country', $future );


		// Check sorting links
		$view->settings->set( 'sort_columns', '1' );

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $entry, $view );
		$this->assertEquals( $legacy, $future );
		$this->assertContains( 'class="gv-sort', $future );

		// Check sorting links
		$view->settings->set( 'sort_columns', '0' );

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $entry, $view );
		$this->assertEquals( $legacy, $future );
		$this->assertNotContains( 'class="gv-sort', $future );
	}

	public function test_entry_renderer_table_hide_empty() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		global $post;

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '26',
						'label' => 'Product',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '1.6',
						'label' => 'Country <small>(Address)</small>',
					),
				),
			),
			'settings' => array(
				'single_title' => 'Entry ~@{entry_id}@~',
				'back_link_label' => "Let's go back!",
				'hide_empty' => true,
			),
		) );
		$view = \GV\View::from_post( $post );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'1.6' => 'Mexico',
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		gravityview()->request->returns['is_view'] = $view;
		gravityview()->request->returns['is_entry'] = $entry;

		$renderer = new \GV\Entry_Renderer();

		$future = $renderer->render( $entry, $view );

		$this->assertNotContains( 'Textarea', $future, 'This field is empty and should not be displayed.' );
		$this->assertNotContains( 'Product', $future, 'This field is empty and should not be displayed.' );
	}

	/**
	 * @covers \GV\Entry_Renderer::render()
	 * @covers \GV\Entry_List_Template::render()
	 */
	public function test_entry_renderer_list() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		global $post;

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'preset_business_listings',
		) );
		$view = \GV\View::from_post( $post );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => sprintf( 'Some text in a textarea (%s)', wp_generate_password( 12 ) ),
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$administrator = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => 'administrator' )
		);

		wp_set_current_user( $administrator );

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;
		gravityview()->request->returns['is_entry'] = $entry;

		$renderer = new \GV\Entry_Renderer();

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $entry, $view );

		$this->assertEquals( $legacy, $future );
		$this->assertContains( 'The Single Entry layout has not been configured', $future );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'preset_business_listings',
			'fields' => array(
				'single_list-title' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '8',
						'label' => 'Name',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'content',
						'label' => 'Content',
					),
				),
				'single_list-subtitle' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1.6',
						'label' => 'Country <small>(Address)</small>',
						'only_loggedin_cap' => 'read',
						'only_loggedin' => true,
					),
					wp_generate_password( 4, false ) => array(
						'id' => '10',
						'label' => 'Phone',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'content',
						'label' => 'Content',
					),
				),
				'single_list-image' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '8',
						'label' => 'Name',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'content',
						'label' => 'Content',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '10',
						'label' => 'Phone',
					),
				),
				'single_list-description' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'content',
						'label' => 'Content',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
				),
				'single_list-footer-left' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '8',
						'label' => 'Name',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'content',
						'label' => 'Content',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
				),
				'single_list-footer-right' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'content',
						'label' => 'Content',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '1.6',
						'label' => 'Country <small>(Address)</small>',
						'only_loggedin_cap' => 'read',
						'only_loggedin' => true,
					),
				)
			),
			'settings' => array(
				'single_title' => 'Entry ~@{entry_id}@~',
				'back_link_label' => "Let's go back!",
			),
		) );
		$view = \GV\View::from_post( $post );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'1.6' => 'Mexico',
			'16' => sprintf( 'Some text in a textarea (%s)', wp_generate_password( 12 ) ),
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		gravityview()->request->returns['is_view'] = $view;
		gravityview()->request->returns['is_entry'] = $entry;

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $entry, $view );

		$this->assertEquals( $legacy, $future );
		$this->assertContains( 'Country', $future );

		wp_set_current_user( -1 );

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $entry, $view );

		$this->assertEquals( $legacy, $future );
		$this->assertContains( 'Let&#039;s go back!', $future );
		$this->assertContains( 'text in a textarea', $future );
		$this->assertNotContains( 'Country', $future );
	}

	/**
	 * @covers \GV\Field_Renderer::render()
	 */
	public function test_frontend_field_html_renderer() {
		$request = new \GV\Frontend_Request();

		$form = $this->factory->form->import_and_get( 'simple.json' );
		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form->ID,
			'1' => 'this is field one',
			'2' => 42,
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form->ID ) );
		$view = \GV\View::from_post( $view );

		$renderer = new \GV\Field_Renderer();

		if ( false ) {
		/** An unkown field. Test some filters. */
		$field = \GV\Internal_Field::by_id( 'this-does-not-exist' );

		$callbacks = array();

		add_filter( 'gravityview_empty_value', $callbacks []= function( $value ) {
			return 'sentinel-0';
		} );
		$this->assertEquals( 'sentinel-0', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview/field/value/empty', $callbacks []= function( $value ) {
			return 'sentinel-1';
		} );
		$this->assertEquals( 'sentinel-1', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview/template/field/context', $callbacks []= function( $context ) {
			$context->value = $context->display_value = 'This <script> is it';
			return $context;
		} );
		$this->assertEquals( 'This &lt;script&gt; is it', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview_field_entry_value_this-does-not-exist_pre_link', $callbacks []= function( $output ) {
			return 'Yes, it does!! <script>careful</script>';
		} );
		$this->assertEquals( 'Yes, it does!! <script>careful</script>', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview_field_entry_value_this-does-not-exist', $callbacks []= function( $output ) {
			return 'No, it doesn\'t...';
		} );
		$this->assertEquals( 'No, it doesn\'t...', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview_field_entry_value', $callbacks []= function( $output ) {
			return 'I paid for an argument, this is not an argument!';
		} );
		$this->assertEquals( 'I paid for an argument, this is not an argument!', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview/template/field/this-does-not-exist/output', $callbacks []= function( $output ) {
			return '....Yes, it is...';
		} );
		$this->assertEquals( '....Yes, it is...', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview/template/field/output', $callbacks []= function( $output ) {
			return '....... ....No, it is not!';
		} );
		$this->assertEquals( '....... ....No, it is not!', $renderer->render( $field, $view, $form, $entry, $request ) );

		$removed = array(
			remove_filter( 'gravityview_empty_value', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/field/value/empty', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/field/context', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_field_entry_value_this-does-not-exist_pre_link', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_field_entry_value_this-does-not-exist', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_field_entry_value', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/field/this-does-not-exist/output', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/field/output', array_shift( $callbacks ) ),
		);

		$this->assertEmpty( $callbacks );
		$this->assertNotContains( false, $removed );
		}

		$callbacks = array();

		/** Test linking pre/post filtering with deprecated and new filters. */
		add_filter( 'gravityview_field_entry_value_custom_pre_link', $callbacks []= function( $output ) {
			return $output . ', please';
		} );

		add_filter( 'gravityview_field_entry_value_custom', $callbacks []= function( $output ) {
			return $output . ' now!';
		} );

		add_filter( 'gravityview/template/field/output', $callbacks []= function( $output ) {
			return 'Yo, ' . $output;
		}, 4 );

		add_filter( 'gravityview/template/field/output', $callbacks []= function( $output ) {
			return 'Hi! ' . $output;
		} );

		$field = \GV\Internal_Field::by_id( 'custom' );
		$field->content = 'click me now';
		$field->show_as_link = true;
		$field->new_window = true;

		add_filter( 'gravityview_field_entry_link', $callbacks []= function( $html ) {
			return 'Click: ' . $html;
		} );

		add_filter( 'gravityview/entry/permalink', $callbacks []= function( $permalink ) {
			return 'ha';
		} );

		$this->assertEquals( 'Hi! Click: <a href="http://ha" rel="noopener noreferrer" target="_blank">Yo, click me now, please</a> now!', $renderer->render( $field, $view, null, $entry, $request ) );

		$removed = array(
			remove_filter( 'gravityview_field_entry_value_custom_pre_link', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_field_entry_value_custom', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/field/output', array_shift( $callbacks ), 4 ),
			remove_filter( 'gravityview/template/field/output', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_field_entry_link', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/entry/permalink', array_shift( $callbacks ) ),
		);

		$this->assertNotContains( false, $removed );
		$this->assertEmpty( $callbacks );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_address() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'1.1' => 'Address 1<careful>',
			'1.2' => 'Address 2',
			'1.3' => 'City',
			'1.4' => 'State',
			'1.5' => 'ZIP',
			'1.6' => 'Country',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '1.1' );
		$this->assertEquals( 'Address 1&lt;careful&gt;', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '1' );
		$this->assertRegExp( "#^Address 1&lt;careful&gt;<br />Address 2<br />City, State ZIP<br />Country<br/><a href='http://maps.google.com/maps\?q=.*' target='_blank' class='map-it-link'>Map It</a>$#", $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'show_map_link' => false ) );
		$this->assertRegExp( "#^Address 1&lt;careful&gt;<br />Address 2<br />City, State ZIP<br />Country$#", $renderer->render( $field, $view, $form, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_checkbox() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'2.1' => 'Much Better',
			'2.4' => 'yes <careful>',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '2' );
		/**
		 * @todo Not really sure what to do about the XSS here,
		 * as it stems from Gravity Forms, they clean the value up
		 * before saving it into the db but allow HTML through... */
		$this->assertEquals( "<ul class='bulleted'><li>Much Better</li><li>yes <careful></li></ul>", $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '2.1' );
		$this->assertEquals( '<span class="dashicons dashicons-yes"></span>', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview_field_tick', function( $tick ) {
			return '[absofrutely]';
		} );

		$field = \GV\GF_Field::by_id( $form, '2.4' );
		$this->assertEquals( '[absofrutely]', $renderer->render( $field, $view, $form, $entry, $request ) );

		remove_all_filters( 'gravityview_field_tick' );

		$field = \GV\GF_Field::by_id( $form, '2.2' );
		$this->assertEquals( '', $renderer->render( $field, $view, $form, $entry, $request ) );

		/** Change the display type for partial values. */
		$field = \GV\GF_Field::by_id( $form, '2.2' );
		$field->update_configuration( array( 'choice_display' => 'value' ) );
		$this->assertEquals( '', $renderer->render( $field, $view, $form, $entry, $request ) );
		$field->update_configuration( array( 'choice_display' => 'label' ) );
		$this->assertEquals( '', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '2.1' );
		$field->update_configuration( array( 'choice_display' => 'value' ) );
		$this->assertEquals( 'Much Better', $renderer->render( $field, $view, $form, $entry, $request ) );
		$field->update_configuration( array( 'choice_display' => 'label' ) );
		$this->assertEquals( 'Much Better', $renderer->render( $field, $view, $form, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_name() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'8.2' => 'Mr.',
			'8.3' => 'O\'',
			'8.6' => 'Harry <script>1</script>',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '8' );

		$this->assertEquals( 'Mr. O&#039; Harry &lt;script&gt;1&lt;/script&gt;', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '8.2' );
		$this->assertEquals( 'Mr.', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '8.3' );
		$this->assertEquals( 'O&#039;', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '8.6' );
		$this->assertEquals( 'Harry &lt;script&gt;1&lt;/script&gt;', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '8.5' );
		$this->assertEquals( '', $renderer->render( $field, $view, $form, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_number() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'9' => '7982489.23929',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '9' );

		$this->assertEquals( '$7,982,489.24', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'number_format' => true ) );

		$this->assertEquals( '7,982,489.23929', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'decimals' => 3 ) );

		$this->assertEquals( '7,982,489.239', $renderer->render( $field, $view, $form, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_other_entries() {
		$user_1 = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'display_name' => 'John John',
		) );

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $user_1,
			'status' => 'active',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\Internal_Field::by_id( 'other_entries' );

		$this->assertEquals( "<div class=\"gv-no-results\"><p>No entries match your request.</p>\n</div>", $renderer->render( $field, $view, null, $entry, $request ) );

		$entry_anon = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => 0,
			'status' => 'active',
		) );
		$entry_anon = \GV\GF_Entry::by_id( $entry_anon['id'] );

		$this->assertEmpty( $renderer->render( $field, $view, null, $entry_anon, $request ) );

		$field->update_configuration( array( 'no_entries_hide' => true ) );
		$this->assertEmpty( $renderer->render( $field, $view, null, $entry, $request ) );

		$entry_2 = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $user_1,
			'status' => 'active',
		) );
		$entry_2 = \GV\GF_Entry::by_id( $entry_2['id'] );

		$entry_3 = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $user_1,
			'status' => 'active',
		) );
		$entry_3 = \GV\GF_Entry::by_id( $entry_3['id'] );

		$GLOBALS['post'] = get_post( $view->ID );

		$field->update_configuration( array( 'link_format' => 'Entry #{entry_id}', 'after_link' => 'wut' ) );
		$expected = sprintf( '<ul><li><a href="%s">Entry #%d</a><div>wut</div></li><li><a href="%s">Entry #%d</a><div>wut</div></li></ul>',
			esc_attr( $entry_3->get_permalink( $view, $request ) ), $entry_3->ID,
			esc_attr( $entry_2->get_permalink( $view, $request ) ), $entry_2->ID );
		$this->assertEquals( $expected, $renderer->render( $field, $view, null, $entry, $request ) );

		unset( $GLOBALS['post'] );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_source_url() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'source_url' => 'http://gravity<view>.tests/?do<danger>&out=d<anger>',
			'status' => 'active',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\Internal_Field::by_id( 'source_url' );

		$this->assertEquals( 'http://gravityview.tests/?dodanger&out=danger', $renderer->render( $field, $view, null, $entry, $request ) );

		$field->update_configuration( array( 'link_to_source' => true ) );
		$this->assertEquals( '<a href="http://gravityview.tests/?dodanger&amp;out=danger">http://gravity&lt;view&gt;.tests/?do&lt;danger&gt;&amp;out=d&lt;anger&gt;</a>', $renderer->render( $field, $view, null, $entry, $request ) );

		$field->update_configuration( array( 'source_link_text' => '<danger> click {entry_id}' ) );

		/** The danger here is fine, since we support HTML in there. */
		$this->assertEquals( '<a href="http://gravityview.tests/?dodanger&amp;out=danger"><danger> click ' . $entry->ID . '</a>', $renderer->render( $field, $view, null, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_list() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'7' => serialize( array(
				array( 'Column 1' => 'one', 'Column 2' => 'two' ),
				array( 'Column 1' => 'three', 'Column 2' => 'four' ),
			) ),
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '7' );

		$expected = "<table class='gfield_list'><thead><tr><th>Column 1</th>\n";
		$expected .= "<th>Column 2</th>\n</tr></thead>\n<tbody><tr><td>one</td>\n";
		$expected .= "<td>two</td>\n</tr>\n<tr><td>three</td>\n<td>four</td>\n</tr>\n<tbody></table>\n";
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '7.0' );
		$this->assertEquals( "<ul class='bulleted'><li>one</li><li>three</li></ul>", $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '7.1' );
		$this->assertEquals( "<ul class='bulleted'><li>two</li><li>four</li></ul>", $renderer->render( $field, $view, $form, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_phone() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'10' => '93 43A99-392<script>1</script>',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '10' );

		$this->assertEquals( '93 43A99-392&lt;script&gt;1&lt;/script&gt;', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'link_phone' => true ) );

		$output = $renderer->render( $field, $view, $form, $entry, $request );
		$this->assertContains( '<a href="tel:93', $output );
		$this->assertContains( '43A99-392&lt;script&gt;1&lt;/script&gt;">93 43A99-392&lt;script&gt;1&lt;/script&gt;</a>', $output );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_radio() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'11' => '1',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '11' );

		$this->assertEquals( '1', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'choice_display' => 'label' ) );

		$this->assertEquals( 'First Choice', $renderer->render( $field, $view, $form, $entry, $request ) );

		/** @todo There seems to be partial choice support, but not exposed to the UI. Tried testing partial inputs, nada. */
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_select() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'13' => 'f',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '13' );

		$this->assertEquals( 'f', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'choice_display' => 'label' ) );

		$this->assertEquals( 'Female', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview/fields/select/output_label', '__return_false' );

		$this->assertEquals( 'f', $renderer->render( $field, $view, $form, $entry, $request ) );

		remove_all_filters( 'gravityview/fields/select/output_label' );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_textarea() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'16' => 'okay <so> {entry_id} what happens [gvtest_shortcode_t1] here? <script>huh()</script> http://gravityview.co/ <b>beep, I allow it!</b>',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '16' );

		$expected = '<p>okay  {entry_id} what happens [gvtest_shortcode_t1] here? huh() http://gravityview.co/ <b>beep, I allow it!</b></p>' . "\n";
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'trim_words' => 4 ) );

		$expected = sprintf( '<p>okay {entry_id} what happens<a href="%s"> &hellip;</a></p>' . "\n", esc_attr( $entry->get_permalink( $view, $request ) ) );
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'new_window' => true ) );
		$expected = sprintf( '<p>okay {entry_id} what happens<a href="%s" target="_blank"> &hellip;</a></p>' . "\n", esc_attr( $entry->get_permalink( $view, $request ) ) );
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'trim_words' => false, 'make_clickable' => true, 'new_window' => false ) );
		$expected = '<p>okay  {entry_id} what happens [gvtest_shortcode_t1] here? huh() <a href="http://gravityview.co/" rel="nofollow">http://gravityview.co/</a> <b>beep, I allow it!</b></p>' . "\n";
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'new_window' => true ) );
		$expected = '<p>okay  {entry_id} what happens [gvtest_shortcode_t1] here? huh() <a href="http://gravityview.co/" rel="nofollow" target="_blank">http://gravityview.co/</a> <b>beep, I allow it!</b></p>' . "\n";
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview/fields/textarea/allowed_kses', function( $kses ) {
			return array( 'so' => array() );
		} );

		$expected = '<p>okay <so> {entry_id} what happens [gvtest_shortcode_t1] here? huh() <a href="http://gravityview.co/" rel="nofollow" target="_blank">http://gravityview.co/</a> beep, I allow it!</p>' . "\n";
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		remove_all_filters( 'gravityview/fields/textarea/allowed_kses' );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_hidden() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'33' => 'this is <script>hidden</script>',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, 33 );
		$expected = 'this is &lt;script&gt;hidden&lt;/script&gt;';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_password() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'34' => 'if this is ever <script>stored</script>',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, 34 );
		$this->assertEmpty( $renderer->render( $field, $view, $form, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_time() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'17' => '3:12 pm',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '17' );
		$this->assertEquals( '03:12 PM', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'date_display' => 'H:i:s' ) );
		$this->assertEquals( '15:12:00', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '17.1' );
		$this->assertEquals( '03', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '17.2' );
		$this->assertEquals( '12', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '17.3' );
		$this->assertEquals( 'PM', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'date_display' => 'a' ) );
		$this->assertEquals( 'pm', $renderer->render( $field, $view, $form, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_website() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'18' => 'https://gravityview.co/?<script>a</script>=<script>b</script>&1',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '18' );
		$expected = '<a href="https://gravityview.co/?scripta/script=scriptb/script&amp;1" rel="noopener noreferrer" target="_blank">https://gravityview.co/?scripta/script=scriptb/script&1</a>';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'truncatelink' => true ) );
		$expected = '<a href="https://gravityview.co/?scripta/script=scriptb/script&amp;1" rel="noopener noreferrer" target="_blank">gravityview.co</a>';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		/** HTML is allowed. */
		$field->update_configuration( array( 'anchor_text' => '<danger>ok {entry_id}' ) );
		$expected = '<a href="https://gravityview.co/?scripta/script=scriptb/script&amp;1" rel="noopener noreferrer" target="_blank"><danger>ok ' . $entry->ID . '</a>';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'open_same_window' => true ) );
		$expected = '<a href="https://gravityview.co/?scripta/script=scriptb/script&amp;1"><danger>ok ' . $entry->ID . '</a>';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_created_by() {
		$user = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'display_name' => 'John John',
		) );
		update_user_meta( $user, 'custom_field_1', '<oh onload="!">okay</oh>' );
		$user = get_user_by( 'id', $user );

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'1.1' => 'Whatever',
			'created_by' => $user->ID,
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\Internal_Field::by_id( 'created_by' );
		$this->assertEquals( $user->display_name, $renderer->render( $field, $view, null, $entry, $request ) );

		$field->update_configuration( array( 'name_display' => 'ID' ) );
		$this->assertEquals( $user->ID, $renderer->render( $field, $view, null, $entry, $request ) );

		$field->update_configuration( array( 'name_display' => 'custom_field_1' ) );
		$this->assertEquals( esc_html( $user->custom_field_1 ), $renderer->render( $field, $view, null, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_date_created() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'1.1' => 'Whatever',
			'date_created' => '2099-10-11 21:00:12',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\Internal_Field::by_id( 'date_created' );
		$this->assertEquals( 'October 11, 2099', $renderer->render( $field, $view, null, $entry, $request ) );

		$field->update_configuration( array( 'date_display' => 'Y-m-d H:i:s' ) );
		$this->assertEquals( '2099-10-11 21:00:12', $renderer->render( $field, $view, null, $entry, $request ) );

		$field->update_configuration( array( 'date_display' => '<\d\a\n\g\e\r>Y-m-d H:i:s' ) );
		/** This is fine, I guess, as the display format is set by the admin. */
		$this->assertEquals( '<danger>2099-10-11 21:00:12', $renderer->render( $field, $view, null, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_date() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'3' => '2017-05-07',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '3' );
		$this->assertEquals( '05/07/2017', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'date_display' => 'Y-m-d H:i:s' ) );
		$this->assertEquals( '2017-05-07 00:00:00', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'date_display' => '<\d\a\n\g\e\r>Y-m-d H:i:s' ) );
		/** This is fine, I guess, as the display format is set by the admin. */
		$this->assertEquals( '<danger>2017-05-07 00:00:00', $renderer->render( $field, $view, $form, $entry, $request ) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'3' => date( 'Y-m-d', 0 ), /** The beginning. */
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		/** Test filters. */
		$this->assertEquals( '', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview/fields/date/hide_epoch', '__return_false' );
		$this->assertEquals( '<danger>1970-01-01 00:00:00', $renderer->render( $field, $view, $form, $entry, $request ) );

		remove_filter( 'gravityview/fields/date/hide_epoch', '__return_false' );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_email() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'4' => 'support@gravityview.co',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		add_shortcode( 'gvtest_filters_e1', function( $atts ) {
			return 'short';
		} );

		$field = \GV\GF_Field::by_id( $form, '4' );
		$this->assertEquals( '<a href="mailto:support@gravityview.co">support@gravityview.co</a>', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'emailsubject' => 'su<script>bject[gvtest_filters_e1]' ) );
		$this->assertEquals( '<a href="mailto:support@gravityview.co?subject=subjectshort">support@gravityview.co</a>', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'emailbody' => 'su<script>bject[gvtest_filters_e1] space' ) );
		$this->assertEquals( '<a href="mailto:support@gravityview.co?subject=subjectshort&amp;body=subjectshort%20space">support@gravityview.co</a>', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'emailmailto' => false ) );
		$this->assertEquals( 'support@gravityview.co', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview_email_prevent_encrypt', '__return_true' );

		$field->update_configuration( array( 'emailencrypt' => true ) );
		$this->assertEquals( 'support@gravityview.co', $renderer->render( $field, $view, $form, $entry, $request ) );

		remove_filter( 'gravityview_email_prevent_encrypt', '__return_true' );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_entry_approval() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\Internal_Field::by_id( 'entry_approval' );
		$this->assertEquals( '<a href="#" aria-role="button" aria-live="polite" aria-busy="false" class="gv-approval-toggle gv-approval-unapproved" title="Entry not yet reviewed. Click to approve this entry." data-current-status="3" data-entry-slug="' . $entry->ID . '" data-form-id="' . $form->ID . '"><span class="screen-reader-text">Unapproved</span></a>', $renderer->render( $field, $view, null, $entry, $request ) );

		$this->assertEquals( 1, did_action( 'gravityview/field/approval/load_scripts' ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_entry_link() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\Internal_Field::by_id( 'entry_link' );
		$expected = sprintf( '<a href="%s">View Details</a>', esc_attr( $entry->get_permalink( $view, $request ) ) );
		$this->assertEquals( $expected, $renderer->render( $field, $view, null, $entry, $request ) );

		$field->update_configuration( array( 'entry_link_text' => 'Details View' ) );
		$expected = sprintf( '<a href="%s">Details View</a>', esc_attr( $entry->get_permalink( $view, $request ) ) );
		$this->assertEquals( $expected, $renderer->render( $field, $view, null, $entry, $request ) );

		add_filter( 'gravityview_entry_link', function( $output ) {
			return $output . ' &lt; click it!';
		} );

		$expected = sprintf( '<a href="%s">Details View &lt; click it!</a>', esc_attr( $entry->get_permalink( $view, $request ) ) );
		$this->assertEquals( $expected, $renderer->render( $field, $view, null, $entry, $request ) );

		$field->update_configuration( array( 'new_window' => true ) );
		$expected = sprintf( '<a href="%s" rel="noopener noreferrer" target="_blank">Details View &lt; click it!</a>', esc_attr( $entry->get_permalink( $view, $request ) ) );
		$this->assertEquals( $expected, $renderer->render( $field, $view, null, $entry, $request ) );

		remove_all_filters( 'gravityview_entry_link' );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_delete_link() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		/** No permissions */
		$field = \GV\Internal_Field::by_id( 'delete_link' );
		$this->assertEmpty( $renderer->render( $field, $view, null, $entry, $request ) );

		$administrator = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => 'administrator' )
		);
		wp_set_current_user( $administrator );

		$field = \GV\Internal_Field::by_id( 'delete_link' );
		$expected = sprintf( '<a href="%s" onclick="%s">Delete Entry</a>', esc_attr( GravityView_Delete_Entry::get_delete_link( $entry->as_entry(), $view->ID ) ), esc_attr( GravityView_Delete_Entry::get_confirm_dialog() ) );
		$this->assertEquals( $expected, $renderer->render( $field, $view, null, $entry, $request ) );
		$this->assertContains( 'action=delete', $expected );

		$field->update_configuration( array( 'delete_link' => 'Deletes les Entrios' ) );
		$expected = sprintf( '<a href="%s" onclick="%s">Deletes les Entrios</a>', esc_attr( GravityView_Delete_Entry::get_delete_link( $entry->as_entry(), $view->ID ) ), esc_attr( GravityView_Delete_Entry::get_confirm_dialog() ) );
		$this->assertEquals( $expected, $renderer->render( $field, $view, null, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_edit_link() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		/** No permissions */
		$field = \GV\Internal_Field::by_id( 'edit_link' );
		$this->assertEmpty( $renderer->render( $field, $view, null, $entry, $request ) );

		$administrator = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => 'administrator' )
		);
		wp_set_current_user( $administrator );

		$field = \GV\Internal_Field::by_id( 'edit_link' );
		$expected = sprintf( '<a href="%s">Edit Entry</a>', esc_attr( GravityView_Edit_Entry::get_edit_link( $entry->as_entry(), $view->ID ) ) );
		$this->assertEquals( $expected, $renderer->render( $field, $view, null, $entry, $request ) );
		$this->assertContains( 'edit=', $expected );

		$field->update_configuration( array( 'edit_link' => 'Editoriales los Entries', 'new_window' => true ) );
		$expected = sprintf( '<a href="%s" rel="noopener noreferrer" target="_blank">Editoriales los Entries</a>', esc_attr( GravityView_Edit_Entry::get_edit_link( $entry->as_entry(), $view->ID ) ) );
		$this->assertEquals( $expected, $renderer->render( $field, $view, null, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_notes() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		/** No permissions */
		$field = \GV\Internal_Field::by_id( 'notes' );
		$this->assertEmpty( $renderer->render( $field, $view, null, $entry, $request ) );

		$field->update_configuration( array( 'notes' => array( 'view' => true, 'view_loggedout' => true ) ) );
		$this->assertContains( 'There are no notes.', $renderer->render( $field, $view, null, $entry, $request ) );
		$this->assertContains( 'gv-show-notes', $renderer->render( $field, $view, null, $entry, $request ) );

		$administrator = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => 'administrator' )
		);
		wp_set_current_user( $administrator );

		GravityView_Entry_Notes::add_note( $entry->ID, $administrator, 'administrator', 'this <script>1</script> is a note :) {entry_id}' );

		$field = \GV\Internal_Field::by_id( 'notes' );
		$field->update_configuration( array( 'notes' => array( 'view' => true ) ) );
		$this->assertContains( 'gv-has-notes', $renderer->render( $field, $view, null, $entry, $request ) );
		$this->assertContains( 'this &lt;script&gt;1&lt;/script&gt; is a note :) {entry_id}', $renderer->render( $field, $view, null, $entry, $request ) );

		$field->update_configuration( array( 'notes' => array( 'view' => true, 'add' => true ) ) );
		$this->assertContains( 'gv-add-note-submit', $renderer->render( $field, $view, null, $entry, $request ) );

		$field->update_configuration( array( 'notes' => array( 'view' => true, 'delete' => true ) ) );
		$this->assertContains( 'gv-notes-delete', $renderer->render( $field, $view, null, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_custom() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\Internal_Field::by_id( 'custom' );
		$this->assertEquals( '', $renderer->render( $field, $view, null, $entry, $request ) );

		$field->update_configuration( array(
			'content' => 'this is some content <danger>',
		) );
		$this->assertEquals( 'this is some content <danger>', $renderer->render( $field, $view, null, $entry, $request ) );

		$field->update_configuration( array(
			'wpautop' => true,
		) );
		$this->assertEquals( "<p>this is some content <danger></p>\n", $renderer->render( $field, $view, null, $entry, $request ) );

		/** Test filters. */
		add_shortcode( 'gvtest_filters_c1', function( $atts ) {
			return 'w00t';
		} );

		add_filter( 'gravityview/fields/custom/content_before', function( $content ) {
			return "uh, $content, right? {entry_id} [gvtest_filters_c1]";
		} );
		$this->assertEquals( "<p>uh, this is some content <danger>, right? {$entry->ID} w00t</p>\n", $renderer->render( $field, $view, null, $entry, $request ) );

		add_filter( 'gravityview/fields/custom/content_after', function( $content ) {
			return "{$content}Wrong {entry_id}!";
		} );
		$this->assertEquals( "<p>uh, this is some content <danger>, right? {$entry->ID} w00t</p>\nWrong {entry_id}!", $renderer->render( $field, $view, null, $entry, $request ) );

		remove_all_filters( 'gravityview/fields/custom/content_before' );
		remove_all_filters( 'gravityview/fields/custom/content_after' );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_fileupload() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'5' => json_encode( array( 'https://one.jpg', 'https://two.mp3' ) ),
		) );
		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'settings' => array(
				'lightbox' => false,
			),
		) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '5' );

		$output = $renderer->render( $field, $view, $form, $entry, $request );

		$expected = "<ul class='gv-field-file-uploads gv-field-{$form->ID}-5'>";
		$expected .= '<li><a href="http://one.jpg" rel="noopener noreferrer" target="_blank"><img src="http://one.jpg" width="250" class="gv-image gv-field-id-5" /></a></li>';
		$expected .= '<li>';

		$this->assertContains( $expected, $output );
		$this->assertContains( '<audio class="wp-audio-shortcode', $output );
		$this->assertContains( '<source type="audio/mpeg" src="http://two.mp3?_=', $output );
		$this->assertContains( '" /><a href="http://two.mp3">http://two.mp3</a></audio></li></ul>', $output );

		/** No fancy rendering, just links, please? */

		$field->update_configuration( array( 'link_to_file' => true ) );

		$expected = "<ul class='gv-field-file-uploads gv-field-{$form->ID}-5'>";
		$expected .= '<li><a href="http://one.jpg" rel="noopener noreferrer" target="_blank">one.jpg</a></li><li><a href="http://two.mp3" rel="noopener noreferrer" target="_blank">two.mp3</a></li></ul>';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_html() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '6' );

		add_shortcode( 'gvtest_shortcode_h1', function( $atts ) {
			return 'this should not work...';
		} );

		$expected = "This is some content :) {$entry->ID} [gvtest_shortcode_h1]";
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_section() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '12' );

		add_shortcode( 'gvtest_shortcode_s1', function( $atts ) {
			return 'this should not work...';
		} );

		$expected = "Let's see what is up :) {$entry->ID} [gvtest_shortcode_s1]";
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_post() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		$cat_1 = wp_create_category( 'Category 1 <script>2</script>' );
		$cat_2 = wp_create_category( 'Category 6 [gvtest_shortcode_p1] <b>5</b>' );

		foreach ( $form['fields'] as &$field ) {
			/** The post categories is a multi-select thing that needs inputs set. */
			if ( $field->type == 'post_category' ) {
				$field = GFCommon::add_categories_as_choices( $field, '' );
			}
		}

		/** Hack in a jpeg. */
		$filename = tempnam( '/tmp/', 'gvtest_' ). '.jpg';
		$image = "$filename|:|<script>TITLE</script> huh, <b>wut</b>|:|cap<script>tion</script>|:|de's<script>tion</script>";

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'19' => 'What is this? <script>some sort of XSS</script>? :) [gvtest_shortcode_p1]',
			'20' => 'This is some content <script>1</script> <b>who</b> [gvtest_shortcode_p1]',
			'21' => 'This is an excerpt <script>ooh();</script> <b>okay</b> [gvtest_shortcode_p1]',
			'22' => 'tag 1, oh no, <script>1</script>, <b>hi</b>, [gvtest_shortcode_p1]',
			'23.1' => "Be<script>f</script>ore category:$cat_1",
			'23.2' => "Categorized <b>4</b> [gvtest_shortcode_p1]:$cat_2",
			'24' => $image,
			'25' => 'wu<script>t</script> <b>how can this be true?</b> [gvtest_shortcode_p1]',
		) );

		/** From http://web.archive.org/web/20111224041840/http://www.techsupportteam.org/forum/digital-imaging-photography/1892-worlds-smallest-valid-jpeg.html */
		file_put_contents( $filename, implode( '', array_map( 'chr', array_map( 'hexdec', explode( ' ', 'FF D8 FF E0 00 10 4A 46 49 46 00 01 01 01 00 48 00 48 00 00 FF DB 00 43 00 FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF C2 00 0B 08 00 01 00 01 01 01 11 00 FF C4 00 14 10 01 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 FF DA 00 08 01 01 00 01 3F 10' ) ) ) ) );

		global $wpdb;

		if ( version_compare( GFFormsModel::get_database_version(), '2.3-dev-1', '>=' ) ) {
			$wpdb->insert( GFFormsModel::get_entry_meta_table_name(), array(
				'entry_id' => $entry['id'], 'form_id' => $form['id'],
				'meta_key' => '24', 'meta_value' => $image
			) );
		} else {
			$wpdb->insert( GFFormsModel::get_lead_details_table_name(), array(
				'lead_id' => $entry['id'], 'form_id' => $form['id'],
				'field_number' => '24', 'value' => $image
			) );
		}

		$entry['24'] = $image;

		$post = get_post( GFCommon::create_post( $form, $entry ) );

		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		/** Post ID */
		$field = \GV\Internal_Field::by_id( 'post_id' );
		$this->assertEquals( $post->ID, $renderer->render( $field, $view, null, $entry, $request ) );

		$field->update_configuration( array( 'link_to_post' => true ) );
		$expected = sprintf( '<a href="%s">%d</a>', get_permalink( $post->ID ), $post->ID );
		$this->assertEquals( $expected, $renderer->render( $field, $view, null, $entry, $request ) );

		update_option( 'permalink_structure', '/%postname%' );
		$post->post_name = 'hello-привет';
		$post->post_status = 'publish';
		wp_update_post( $post );
		$expected = sprintf( '<a href="%s">%d</a>', get_permalink( $post->ID ), $post->ID );
		$this->assertEquals( $expected, $renderer->render( $field, $view, null, $entry, $request ) );
		update_option( 'permalink_structure', '' );

		/** Post title */
		$post->post_title .= ' realllly';
		wp_update_post( $post );

		add_shortcode( 'gvtest_shortcode_p1', function( $atts ) {
			return 'no no no no no nononon';
		} );

		$the_title_filter = function( $title ) {
			return $title . ' heh';
		};
		add_filter( 'the_title', $the_title_filter );

		$field = \GV\GF_Field::by_id( $form, '19' );
		/** Note: we allow HTML output, filtering the HTML is up to Gravity Forms. */
		$expected = 'What is this? <script>some sort of XSS</script>? :) [gvtest_shortcode_p1]';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'dynamic_data' => true ) );
		/** Note: Gravity Forms saves the data in a filtered way. */
		$expected = 'What is this? some sort of XSS? :) &#091;gvtest_shortcode_p1&#093; realllly heh';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		remove_filter( 'the_title', $the_title_filter );

		/** Post content */
		$post->post_content .= ' are you sure?';
		wp_update_post( $post );

		$the_content_filter = function( $content ) {
			return $content . ' okay';
		};
		add_filter( 'the_content', $the_content_filter );

		$field = \GV\GF_Field::by_id( $form, '20' );
		/** Note: we allow HTML output, filtering the HTML is up to Gravity Forms. */
		$expected = "<p>This is some content <script>1</script> <b>who</b> [gvtest_shortcode_p1]</p>\n";
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'dynamic_data' => true ) );
		/** Note: Gravity Forms saves the data in a filtered way. */
		$expected = "<p>This is some content 1 <b>who</b> &#091;gvtest_shortcode_p1&#093; are you sure?</p>\n okay";
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		remove_filter( 'the_content', $the_content_filter );

		/** Post excerpt */
		$post->post_excerpt .= ' add this';
		wp_update_post( $post );

		$the_excerpt_filter = function( $excerpt ) {
			return $excerpt . ' tack this on';
		};
		add_filter( 'the_excerpt', $the_excerpt_filter );
		$field = \GV\GF_Field::by_id( $form, '21' );
		/** Note: we allow HTML output, filtering the HTML is up to Gravity Forms. */
		$expected = 'This is an excerpt <script>ooh();</script> <b>okay</b> [gvtest_shortcode_p1]';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'dynamic_data' => true ) );
		/** Note: Gravity Forms saves the data in a filtered way. */
		$expected = "<p>This is an excerpt ooh(); <b>okay</b> &#091;gvtest_shortcode_p1&#093; add this</p>\n tack this on";
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		/** Post tags */
		wp_set_post_tags( $post->ID, 'some,more', true );

		$field = \GV\GF_Field::by_id( $form, '22' );
		/** Note: we do not allow HTML output here, they're tags. */
		$expected = 'tag 1, oh no, &lt;script&gt;1&lt;/script&gt;, &lt;b&gt;hi&lt;/b&gt;, [gvtest_shortcode_p1]';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'link_to_term' => true ) );
		$expected = array(
			sprintf( '<a href="%s">tag 1</a>, ', esc_url( get_term_link( 'tag 1', 'post_tag' ) ) ),
			sprintf( '<a href="%s">oh no</a>, ', esc_url( get_term_link( 'oh no', 'post_tag' ) ) ),
			sprintf( '<a href="%s">[gvtest_shortcode_p1]</a>', esc_url( get_term_link( '[gvtest_shortcode_p1]', 'post_tag' ) ) ),
		);
		if ( get_term_by( 'name', '<script>1</script>', 'post_tag' ) ) {
			$expected = array_merge( array(
				sprintf( '<a href="%s"></a>, ', esc_url( get_term_link( get_term_by( 'name', '<script>1</script>', 'post_tag' ), 'post_tag' ) ) ),
				sprintf( '<a href="%s">hi</a>, ', esc_url( get_term_link( get_term_by( 'name', '<b>hi</b>', 'post_tag' ), 'post_tag' ) ) ),
			), $expected );
		}
		foreach ( $expected as $_expected ) {
			$this->assertContains( $_expected, $renderer->render( $field, $view, $form, $entry, $request ) );
		}

		$field->update_configuration( array( 'dynamic_data' => true ) );
		$expected = array(
			sprintf( '<a href="%s" rel="tag">[gvtest_shortcode_p1]</a>', esc_url( get_term_link( '[gvtest_shortcode_p1]', 'post_tag' ) ) ),
			sprintf( '<a href="%s" rel="tag">more</a>, ', esc_url( get_term_link( 'more', 'post_tag' ) ) ),
			sprintf( '<a href="%s" rel="tag">oh no</a>, ', esc_url( get_term_link( 'oh no', 'post_tag' ) ) ),
			sprintf( '<a href="%s" rel="tag">some</a>, ', esc_url( get_term_link( 'some', 'post_tag' ) ) ),
			sprintf( '<a href="%s" rel="tag">tag 1</a>', esc_url( get_term_link( 'tag 1', 'post_tag' ) ) ),
		);
		if ( get_term_by( 'name', '<b>hi</b>', 'post_tag' ) ) {
			$expected []= sprintf( '<a href="%s" rel="tag">hi</a>, ', esc_url( get_term_link( get_term_by( 'name', '<b>hi</b>', 'post_tag' ), 'post_tag' ) ) );
		}

		foreach ( $expected as $_expected ) {
			$this->assertContains( $_expected, $renderer->render( $field, $view, $form, $entry, $request ) );
		}

		$field->update_configuration( array( 'link_to_term' => false ) );
		$expected = explode( ', ', '[gvtest_shortcode_p1], hi, more, oh no, some, tag 1' );
		foreach ( $expected as $_expected ) {
			$this->assertContains( $_expected, $renderer->render( $field, $view, $form, $entry, $request ) );
		}

		/** Post categories */
		$field = \GV\GF_Field::by_id( $form, '23' );
		/** Note: GF does escape category names, but they can come from anywhere. We must escape. */
		$expected = array(
			"<ul class='bulleted'>",
			'<li>Before category</li>',
			'<li>Categorized 4 [gvtest_shortcode_p1]</li>',
			'</ul>',
		);
		foreach ( $expected as $_expected ) {
			$this->assertContains( $_expected, $renderer->render( $field, $view, $form, $entry, $request ) );
		}
		$this->assertEquals( strlen( implode( '', $expected ) ), strlen( $renderer->render( $field, $view, $form, $entry, $request ) ) );

		$field->update_configuration( array( 'link_to_term' => true ) );
		$expected = array(
			sprintf( '<a href="%s">Category 1</a>', esc_url( get_term_link( $cat_1, 'category' ) ) ),
			sprintf( '<a href="%s">Category 6 [gvtest_shortcode_p1] 5</a>', esc_url( get_term_link( $cat_2, 'category' ) ) ),
		);
		foreach ( $expected as $_expected ) {
			$this->assertContains( $_expected, $renderer->render( $field, $view, $form, $entry, $request ) );
		}
		$this->assertEquals( strlen( implode( ', ', $expected ) ), strlen( $renderer->render( $field, $view, $form, $entry, $request ) ) );

		$field->update_configuration( array( 'dynamic_data' => true ) );
		$expected = array(
			sprintf( '<a href="%s" rel="tag">Category 1</a>', esc_url( get_term_link( $cat_1, 'category' ) ) ),
			sprintf( '<a href="%s" rel="tag">Category 6 [gvtest_shortcode_p1] 5</a>', esc_url( get_term_link( $cat_2, 'category' ) ) ),
		);
		foreach ( $expected as $_expected ) {
			$this->assertContains( $_expected, $renderer->render( $field, $view, $form, $entry, $request ) );
		}
		$this->assertEquals( strlen( implode( ', ', $expected ) ), strlen( $renderer->render( $field, $view, $form, $entry, $request ) ) );

		$field->update_configuration( array( 'link_to_term' => false ) );
		$expected = array(
			'Category 1',
			'Category 6 [gvtest_shortcode_p1] 5',
		);
		foreach ( $expected as $_expected ) {
			$this->assertContains( $_expected, $renderer->render( $field, $view, $form, $entry, $request ) );
		}
		$this->assertEquals( strlen( implode( ', ', $expected ) ), strlen( $renderer->render( $field, $view, $form, $entry, $request ) ) );

		/** Post Image */
		$field = \GV\GF_Field::by_id( $form, '24' );
		$expected = '<div class="gv-image"><a class="thickbox" href="' . $filename . '" title="&lt;script&gt;TITLE&lt;/script&gt; huh, &lt;b&gt;wut&lt;/b&gt;"><img src="' . $filename . '" alt="cap&lt;script&gt;tion&lt;/script&gt;" /></a><div class="gv-image-title"><span class="gv-image-label">Title:</span> <div class="gv-image-value">&lt;script&gt;TITLE&lt;/script&gt; huh, &lt;b&gt;wut&lt;/b&gt;</div></div><div class="gv-image-caption"><span class="gv-image-label">Caption:</span> <div class="gv-image-value">cap&lt;script&gt;tion&lt;/script&gt;</div></div><div class="gv-image-description"><span class="gv-image-label">Description:</span> <div class="gv-image-value">de&#039;s&lt;script&gt;tion&lt;/script&gt;</div></div></div>';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'link_to_post' => true ) );
		$expected = '<div class="gv-image"><a href="' . get_permalink( $post->ID ) . '" title="&lt;script&gt;TITLE&lt;/script&gt; huh, &lt;b&gt;wut&lt;/b&gt;"><img src="' . $filename . '" alt="cap&lt;script&gt;tion&lt;/script&gt;" /></a><div class="gv-image-title"><span class="gv-image-label">Title:</span> <div class="gv-image-value">&lt;script&gt;TITLE&lt;/script&gt; huh, &lt;b&gt;wut&lt;/b&gt;</div></div><div class="gv-image-caption"><span class="gv-image-label">Caption:</span> <div class="gv-image-value">cap&lt;script&gt;tion&lt;/script&gt;</div></div><div class="gv-image-description"><span class="gv-image-label">Description:</span> <div class="gv-image-value">de&#039;s&lt;script&gt;tion&lt;/script&gt;</div></div></div>';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'dynamic_data' => true, 'link_to_post' => false, 'show_as_link' => true ) );
		$images = wp_get_attachment_image_src( get_post_thumbnail_id( $entry['post_id'] ), 'large' );
		$expected = '<a href="' . esc_attr( $entry->get_permalink( $view, $request ) ) . '"><div class="gv-image"><a href="" title="&lt;script&gt;TITLE&lt;/script&gt; huh, &lt;b&gt;wut&lt;/b&gt;"><img src="' . $images[0] . '" alt="cap&lt;script&gt;tion&lt;/script&gt;" /></a><div class="gv-image-title"><span class="gv-image-label">Title:</span> <div class="gv-image-value">&lt;script&gt;TITLE&lt;/script&gt; huh, &lt;b&gt;wut&lt;/b&gt;</div></div><div class="gv-image-caption"><span class="gv-image-label">Caption:</span> <div class="gv-image-value">cap&lt;script&gt;tion&lt;/script&gt;</div></div><div class="gv-image-description"><span class="gv-image-label">Description:</span> <div class="gv-image-value">de&#039;s&lt;script&gt;tion&lt;/script&gt;</div></div></div></a>';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		/** @todo: When there's not much else to do, test all the filters in the template! */

		/** Post custom */
		$field = \GV\GF_Field::by_id( $form, '25' );
		$expected = 'wu&lt;script&gt;t&lt;/script&gt; &lt;b&gt;how can this be true?&lt;/b&gt; [gvtest_shortcode_p1]';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );
		/** Note: */
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_product() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'26' => 'productname<script>o</script>|48|30',
			'27' => '4949399',
			'28.1' => 'Op<script>1</script>|48',
			'28.3' => 'Op<script>3</script>|3',
			'29' => '$0.01<script>4</script>',
			'30' => '-32923932',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '26' );
		$expected = 'productname&lt;script&gt;o&lt;/script&gt; ($48.00)';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '26.1' );
		$expected = 'productname&lt;script&gt;o&lt;/script&gt;';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '26.2' );
		$expected = '$48.00';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '26.3' );
		$expected = '30';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		/** Quantity */
		$field = \GV\GF_Field::by_id( $form, '27' );
		$field->update_configuration( array( 'number_format' => true ) );
		$expected = '4,949,399';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		/** Options (checkbox) */
		$field = \GV\GF_Field::by_id( $form, '28' );
		$expected = "<ul class='bulleted'><li>Op<script>1</script> ($48.00)</li><li>Op<script>3</script> ($3.00)</li></ul>";
		/**
		 * @todo Not really sure what to do about the XSS here,
		 * as it stems from Gravity Forms, they clean the value up
		 * before saving it into the db but allow HTML through... */
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '28.1' );
		$expected = '<span class="dashicons dashicons-yes"></span>';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '28.2' );
		$this->assertEmpty( $renderer->render( $field, $view, $form, $entry, $request ) );

		/** Shipping */
		$field = \GV\GF_Field::by_id( $form, '29' );
		$expected = '$0.01';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		/** Total */
		$field = \GV\GF_Field::by_id( $form, '30' );
		$expected = '-$32,923,932.00';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_payment() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\Internal_Field::by_id( 'is_fulfilled' );
		$this->assertEquals( 'Not Fulfilled', $renderer->render( $field, $view, null, $entry, $request ) );

		$field = \GV\Internal_Field::by_id( 'transaction_type' );
		$this->assertEquals( 'One-Time Payment', $renderer->render( $field, $view, null, $entry, $request ) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'is_fulfilled' => 1,
			'transaction_type' => 2,
			'currency' => '<b>1',
			'transaction_id' => 'SA-<script>danger</script>',
			'payment_status' => '<b>sorry</b>',
			'payment_method' => '<ha>1</ha>',
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$field = \GV\Internal_Field::by_id( 'is_fulfilled' );
		$this->assertEquals( 'Fulfilled', $renderer->render( $field, $view, null, $entry, $request ) );

		$field = \GV\Internal_Field::by_id( 'transaction_type' );
		$this->assertEquals( 'Subscription', $renderer->render( $field, $view, null, $entry, $request ) );

		$field = \GV\Internal_Field::by_id( 'currency' );
		$this->assertEquals( '&lt;b&gt;1', $renderer->render( $field, $view, null, $entry, $request ) );

		$field = \GV\Internal_Field::by_id( 'transaction_id' );
		$this->assertEquals( 'SA-&lt;script&gt;danger&lt;/script&gt;', $renderer->render( $field, $view, null, $entry, $request ) );

		$field = \GV\Internal_Field::by_id( 'payment_status' );
		$this->assertEquals( '&lt;b&gt;sorry&lt;/b&gt;', $renderer->render( $field, $view, null, $entry, $request ) );

		$field = \GV\Internal_Field::by_id( 'payment_method' );
		$this->assertEquals( '&lt;ha&gt;1&lt;/ha&gt;', $renderer->render( $field, $view, null, $entry, $request ) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'is_fulfilled' => 1,
			'transaction_type' => 2,
			'currency' => 'EUR',
			'payment_amount' => '-10329.8',
			'payment_date' => '2017-06-02 12:05:00',
			'31.1' => 'XXXXXX2923<script>3</script>',
			'31.2' => 'this should not be shown or stored',
			'31.3' => 'this should not be shown or stored',
			'31.4' => 'Visa<script>44</script>',
			'31.5' => 'this should not be shown or stored',
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$field = \GV\Internal_Field::by_id( 'payment_amount' );
		$this->assertEquals( '-10.329,80 &#8364;', $renderer->render( $field, $view, null, $entry, $request ) );

		$field = \GV\Internal_Field::by_id( 'payment_date' );
		$this->assertEquals( 'June 2, 2017', $renderer->render( $field, $view, null, $entry, $request ) );

		$field->update_configuration( array( 'date_display' => 'Y-m-d\TH:i:s\Z' ) );
		$this->assertEquals( '2017-06-02T12:05:00Z', $renderer->render( $field, $view, null, $entry, $request ) );

		/** Credit card */
		$field = \GV\GF_Field::by_id( $form, '31' );
		$expected = 'Visa44<br />XXXXXX29233';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '31.1' );
		$expected = 'XXXXXX2923&lt;script&gt;3&lt;/script&gt;';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field = \GV\GF_Field::by_id( $form, '31.4' );
		$expected = 'Visa&lt;script&gt;44&lt;/script&gt;';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		/** For security reasons no other fields are shown */
		$field = \GV\GF_Field::by_id( $form, '31.2' );
		$this->assertEmpty( $renderer->render( $field, $view, $form, $entry, $request ) );
		$field = \GV\GF_Field::by_id( $form, '31.3' );
		$this->assertEmpty( $renderer->render( $field, $view, $form, $entry, $request ) );
		$field = \GV\GF_Field::by_id( $form, '31.5' );
		$this->assertEmpty( $renderer->render( $field, $view, $form, $entry, $request ) );
	}

	/**
	 * @covers \GV\Template::push_template_data()
	 * @covers \GV\Template::pop_template_data()
	 */
	public function test_template_push_pop() {
		$template_a = new \GV\Field_HTML_Template( new \GV\Internal_Field() );
		$template_b = new \GV\Field_HTML_Template( new \GV\Internal_Field() );

		global $wp_query;

		$template_a->push_template_data( array( 'hello' => 'world' ) );
		$this->assertEquals( 'world', $wp_query->query_vars['data']->hello );

		$template_b->push_template_data( array( 'bye' => 'moon' ) );
		$this->assertEquals( 'moon', $wp_query->query_vars['data']->bye );

		$template_b->pop_template_data();
		$this->assertEquals( 'world', $wp_query->query_vars['data']->hello );

		$template_a->pop_template_data();
		$this->assertEquals( 'world', $wp_query->query_vars['data']->hello );

		$template_b->push_template_data( array( 'one' => 'of these days' ), 'custom' );
		$template_a->push_template_data( array( 'how' => 'about this' ), 'custom' );

		$this->assertEquals( 'about this', $wp_query->query_vars['custom']->how );

		/** Emulate a destruct */
		unset( $wp_query->query_vars['custom'] );
		unset( $template_a );

		$template_b->pop_template_data( 'custom' );

		$this->assertEquals( 'of these days', $wp_query->query_vars['custom']->one );
	}

	/**
	 * @covers \GV\Frontend_Request::is_view()
	 */
	public function test_frontend_request_is() {
		$request = new \GV\Frontend_Request();

		global $post;
		$this->assertFalse( $request->is_view() );

		$view = $this->factory->view->create_and_get();

		$post = $view;
		$this->assertInstanceOf( '\GV\View', $request->is_view() );

		$post = null;
	}

	/**
	 * @covers \GV\View_Table_Template::the_columns()
	 * @covers \GV\View_Table_Template::the_entry()
	 */
	public function test_view_table() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form->ID,
			'1' => 'set all the fields!',
			'2' => -100,
		) );

		$view = $this->factory->view->create_and_get( array( 'form_id' => $form->ID ) );

		foreach ( range( 1, 10 ) as $i ) {
			$this->factory->entry->import_and_get( 'simple_entry.json', array(
				'form_id' => $form['id'],
				'1' => "this is the $i-numbered entry",
				'2' => $i,
			) );
		}

		add_filter( 'gravityview/configuration/fields', function( $fields ) {
			return array(
				'directory_table-columns' => array(
					array(
						'id' => 1, 'label' => 'This is field one',
					),
					array(
						'id' => 2, 'custom_label' => 'This is field two',
					),
				),
			);
		} );
		$view = \GV\View::by_id( $view->ID );
		remove_all_filters( 'gravityview/configuration/fields' );

		$request = new \GV\Mock_Request();
		gravityview()->request = $request;
		$request->returns['is_view'] = $view;

		$template = new \GV\View_Table_Template( $view, $view->form->entries, $request );

		/** Test the column ouput. */
		$expected = sprintf( '<th id="gv-field-%1$d-1" class="gv-field-%1$d-1"><span class="gv-field-label">This is field one</span></th><th id="gv-field-%1$d-2" class="gv-field-%1$d-2"><span class="gv-field-label">This is field two</span></th>', $view->form->ID );

		ob_start(); $template->the_columns();
		$this->assertEquals( $expected, ob_get_clean() );

		$entries = $view->form->entries->all();

		$attributes = array( 'class' => 'hello-button', 'data-row' => '1' );

		add_filter( 'gravityview/template/table/entry/row/attributes', function( $attributes ) {
			$attributes['onclick'] = 'alert("hello :)");';
			return $attributes;
		} );

		ob_start(); $template->the_entry( $entries[1], $attributes );
		$output = ob_get_clean();
		$this->assertContains( '<tr class="hello-button" data-row="1" onclick="alert(&quot;hello :)&quot;);">', $output );

		remove_all_filters( 'gravityview/template/table/entry/row/attributes' );
	}

	/**
	 * @covers \GV\Mocks\GravityView_API_field_label()
	 * @covers \GravityView_API::field_label()
	 */
	public function test_field_label_compat() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => sprintf( 'Some text in a textarea (%s)', wp_generate_password( 12 ) ),
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		GravityView_View::getInstance()->setForm( $form->form );

		$field_settings = array(
			'id' => '1.6',
			'show_label' => false,
			'label' => 'Country <small>(Address)</small>'
		);

		$GLOBALS['GravityView_API_field_label_override'] = true;
		$expected = GravityView_API::field_label( $field_settings, $entry->as_entry() /** no force */ );
		unset( $GLOBALS['GravityView_API_field_label_override'] );
		$this->assertEquals( $expected, GravityView_API::field_label( $field_settings, $entry->as_entry() /** no force */ ) );
		$this->assertEquals( '', $expected );

		$GLOBALS['GravityView_API_field_label_override'] = true;
		$expected = GravityView_API::field_label( $field_settings, $entry->as_entry(), true );
		unset( $GLOBALS['GravityView_API_field_label_override'] );
		$this->assertEquals( $expected, GravityView_API::field_label( $field_settings, $entry->as_entry(), true ) );
		$this->assertEquals( 'Country', $expected );

		$field_settings = array(
			'id' => 'id',
			'show_label' => true,
			'label' => 'what?',
		);

		$GLOBALS['GravityView_API_field_label_override'] = true;
		$expected = GravityView_API::field_label( $field_settings, $entry->as_entry() /** no force */ );
		unset( $GLOBALS['GravityView_API_field_label_override'] );
		$this->assertEquals( $expected, GravityView_API::field_label( $field_settings, $entry->as_entry() /** no force */ ) );
		$this->assertEquals( 'what?', $expected );

		$field_settings = array(
			'id' => '1',
			'show_label' => true,
			'custom_label' => 'The Real Slim Label',
			'label' => 'what?',
		);

		$GLOBALS['GravityView_API_field_label_override'] = true;
		$expected = GravityView_API::field_label( $field_settings, $entry->as_entry() );
		unset( $GLOBALS['GravityView_API_field_label_override'] );
		$this->assertEquals( $expected, GravityView_API::field_label( $field_settings, $entry->as_entry() ) );
		$this->assertEquals( 'The Real Slim Label', $expected );

		/** The filters. */
		add_filter( 'gravityview_render_after_label', function( $after ) {
			return ', all the other labels are just';
		} );

		add_filter( 'gravityview/template/field_label', function( $label ) {
			return $label . ' imitating';
		} );

		$GLOBALS['GravityView_API_field_label_override'] = true;
		$expected = GravityView_API::field_label( $field_settings, $entry->as_entry() );
		unset( $GLOBALS['GravityView_API_field_label_override'] );
		$this->assertEquals( $expected, GravityView_API::field_label( $field_settings, $entry->as_entry() ) );
		$this->assertEquals( 'The Real Slim Label, all the other labels are just imitating', $expected );

		remove_all_filters( 'gravityview_render_after_label' );
		remove_all_filters( 'gravityview/template/field_label' );

		/** A bail condition. */
		$field_settings = array( 'custom_label' => 'space is the place' );
		$entry = array();

		$this->assertEquals( 'space is the place', GravityView_API::field_label( $field_settings, $entry, true ) );

		/** The filters. */
		add_filter( 'gravityview_render_after_label', function( $after ) {
			return ', okay?';
		} );

		add_filter( 'gravityview/template/field_label', function( $label ) {
			return 'Look, '. $label;
		} );

		$this->assertEquals( 'Look, space is the place, okay?', GravityView_API::field_label( $field_settings, $entry, true ) );

		remove_all_filters( 'gravityview_render_after_label' );
		remove_all_filters( 'gravityview/template/field_label' );
	}

	/**
	 * @covers \GV\Field::get()
	 * @covers \GV\GF_Form::get_field()
	 * @covers \GV\Internal_Source::get_field()
	 * @covers \GV\GF_Field::by_id()
	 * @covers \GV\Internal_Field::by_id()
	 */
	public function test_get_field() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form->ID,
			'1' => 'set all the fields!',
			'2' => -100,
		) );

		/** Invalid cases should not fatal. */
		$this->assertNull( \GV\Field::get( '\GV\No_No_No', '1' ) );
		$this->assertNull( \GV\Field::get( '\GV\Core', array( '1' ) ) );
		$this->assertNull( \GV\GF_Form::get_field() );
		$this->assertNull( \GV\GF_Form::get_field( $form, '1010' ) );

		$field = \GV\GF_Field::get( '\GV\GF_Form', array( $form, '1' ) );
		$this->assertInstanceOf( '\GV\GF_Field', $field );

		$this->assertEquals( 'text', $field->field->type );

		$field = \GV\Internal_Source::get_field( 'custom' );
		$this->assertInstanceOf( '\GV\Internal_Field', $field );
		$this->assertEquals( 'custom', $field->ID );
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
	}

	/**
	 * @covers \GV\Multi_Entry::offsetGet
	 */
	public function test_entry_multi() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form->ID,
			'1' => 'set all the fields!',
			'2' => -100,
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$form2 = $this->factory->form->import_and_get( 'simple.json' );
		$form2 = \GV\GF_Form::by_id( $form2['id'] );
		$entry2 = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form2->ID,
			'1' => 'set all the fields!',
			'2' => -100,
		) );
		$entry2 = \GV\GF_Entry::by_id( $entry2['id'] );

		$multi_entry = \GV\Multi_Entry::from_entries( array(
			$entry, $entry2
		) );

		$this->assertNull( $multi_entry[-1] );

		$this->assertEquals( $entry2, $multi_entry[ $form2->ID ] );
		$this->assertEquals( $entry, $multi_entry[ $form->ID ] );
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

		$view->settings->set( 'sort_field', 'id' );
		$view->settings->set( 'sort_direction', 'desc' );

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
	}

	public function test_mocks_legacy_context() {
		\GV\Mocks\Legacy_Context::reset();

		$this->assertNull( GravityView_frontend::$instance );
		$this->assertNull( GravityView_View_Data::$instance );
		$this->assertNull( GravityView_View::$instance );

		\GV\Mocks\Legacy_Context::pop();

		$form = $this->factory->form->import_and_get( 'simple.json' );
		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form->ID,
			'1' => 'set all the fields!',
			'2' => -100,
		) );
		$view = \GV\View::by_id( $this->factory->view->create( array( 'form_id' => $form->ID ) ) );

		$entries = new \GV\Entry_Collection();
		$entries->add( \GV\GF_Entry::by_id( $entry['id'] ) );

		$views = new \GV\View_Collection();
		$views->add( $view );

		$post = get_post( $view->ID );
		\GV\Mocks\Legacy_Context::push( array(
			'post' => $post,
			'view' => $view,
			'entry' => \GV\GF_Entry::by_id( $entry['id'] ),
			'entries' => $entries,
			'fields'=> $view->fields->by_visible(),
		) );

		$this->assertEquals( array(
			'\GravityView_View::post_id' => $post->ID,
			'\GravityView_frontend::is_gravityview_post_type' => true,
			'\GravityView_frontend::post_has_shortcode' => false,
			'\GravityView_frontend::is_search' => false,
			'\GravityView_frontend::single_entry' => $entry['id'],
			'\GravityView_frontend::entry' => $entry,
			'\GravityView_View::_current_entry' => $entry,
			'\GravityView_frontend::gv_output_data' => \GravityView_View_Data::getInstance(),
			'\GravityView_View::paging' => array( 'offset' => 0, 'page_size' => 20 ),
			'\GravityView_View::sorting' => array( 'sort_field' => 'date_created', 'sort_direction' => 'ASC', 'is_numeric' => false ),
			'wp_actions[loop_start]' => 0,
			'wp_query::in_the_loop' => false,
			'\GravityView_frontend::post_id' => $post->ID,
			'\GravityView_frontend::context_view_id' => $view->ID,
			'\GravityView_View::atts' => $view->settings->as_atts(),
			'\GravityView_View::view_id' => $view->ID,
			'\GravityView_View::back_link_label' => '',
			'\GravityView_View::form' => $view->form->form,
			'\GravityView_View::form_id' => $view->form->ID,
			'\GravityView_View::entries' => array(),
			'\GravityView_View::fields' => $view->fields->by_visible()->as_configuration(),
			'\GravityView_View::context' => 'single',
			'\GravityView_View::total_entries' => 1,
			'\GravityView_View::entries' => array_map( function( $e ) { return $e->as_entry(); }, $entries->all() ),
			'\GravityView_View_Data::views' => $views,
		), \GV\Mocks\Legacy_Context::freeze() );

		$view->settings->update( array( 'back_link_label' => 'Back to #{entry_id}' ) );

		\GV\Mocks\Legacy_Context::push( array(
			'view' => $view,
		) );

		$this->assertEquals( "Back to #{entry_id}", GravityView_View::getInstance()->getBackLinkLabel( false ) );

		\GV\Mocks\Legacy_Context::pop();

		$this->assertEmpty( GravityView_View::getInstance()->getBackLinkLabel() );

		\GV\Mocks\Legacy_Context::reset();
	}

	/**
	 * @covers \GV\Shortcodes\gravityview::callback()
	 * @covers \GravityView_Shortcode::shortcode()
	 */
	public function test_shortcodes_gravityview() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'page_size' => 33,
			),
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
				),
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
				)
			),
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'search_bar',
						'search_fields' => '[{"field":"search_all","input":"input_text"}]',
					),
				),
				'header_left' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'page_info',
					),
				),
				'footer_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'custom_content',
						'content' => 'Here we go again! <b>Now</b>',
					),
				),
			),
		) );
		$view = \GV\View::from_post( $post );

		$entries = array();
		foreach ( range( 1, 8 ) as $i ) {
			$entry = $this->factory->entry->create_and_get( array(
				'form_id' => $form['id'],
				'status' => 'active',
				'16' => sprintf( '[%d] Entry %s', $i, wp_generate_password( 12 ) ),
			) );
			$entries []= \GV\GF_Entry::by_id( $entry['id'] );
		}

		\GV\Mocks\Legacy_Context::push( array(
			'post' => $post,
			'view' => $view,
			'entries' => $view->get_entries( new \GV\Frontend_Request() ),
			'paging' => array(
				'page_size' => 3,
			)
		) );

		$legacy = new GravityView_Shortcode();
		$future = new \GV\Shortcodes\gravityview();

		$args = array(
			'id' => $view->ID,
			'detail' => 'total_entries',
			'page_size' => 3,
		);

		$_GET = array( 'pagenum' => 2 );

		$this->assertEquals( $legacy->shortcode( $args ), $output = $future->callback( $args ) );
		$this->assertEquals( '8', $output );

		$args['detail'] = 'first_entry';

		$this->assertEquals( $legacy->shortcode( $args ), $output = $future->callback( $args ) );
		$this->assertEquals( '1', $output );

		$args['detail'] = 'last_entry';

		$this->assertEquals( $legacy->shortcode( $args ), $output = $future->callback( $args ) );
		$this->assertEquals( '3', $output );

		$args['detail'] = 'page_size';

		$this->assertEquals( $legacy->shortcode( $args ), $output = $future->callback( $args ) );
		$this->assertEquals( '3', $output );

		unset( $args['detail'] );

		$legacy_output = $legacy->shortcode( $args );
		$future_output = $future->callback( $args );

		/** Clean up the differences a bit */
		$legacy_output = str_replace( ' style=""', '', $legacy_output );
		$legacy_output = trim( preg_replace( '#>\s*<#', '><', $legacy_output ) );
		$future_output = trim( preg_replace( '#>\s*<#', '><', $future_output ) );

		$this->assertEquals( $legacy_output, $future_output );
		$this->assertContains( '] Entry ', $future_output );
	}

	/**
	 * @covers \GV\oEmbed::render()
	 */
	public function test_oembed_entry() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'edit_link',
						'label' => 'Edit',
					),
				),
			)
		) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => sprintf( 'Entry %s', wp_generate_password( 12 ) ),
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );

		$future = array( '\GV\oEmbed', 'render' );

		$args = array(
			'matches' => array(
				'slug' => $post->post_name,
				'is_cpt' => 'gravityview',
				'entry_slug' => $entry['id'],
			),
			'attr' => '',
			'url' => add_query_arg( 'entry', $entry['id'], get_permalink( $post->ID ) ),
			'rawattr' => '',
		);

		$administrator = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => 'administrator' )
		);
		wp_set_current_user( $administrator );

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_entry'] = $entry;

		$future_output = call_user_func_array( $future, $args );

		$this->assertContains( 'gravityview-oembed gravityview-oembed-entry gravityview-oembed-entry-' . $entry->ID, $future_output );

		wp_set_current_user( 0 );
		gravityview()->request = new \GV\Frontend_Request();
	}

	public function test_protection_view_content_directory() {
		$post = $this->factory->view->create_and_get();
		$view = \GV\View::by_id( $post->ID );

		$request = new \GV\Mock_Request();
		gravityview()->request = $request;
		$request->returns['is_view'] = $view;

		/** Post password */
		wp_update_post( array( 'ID' => $post->ID, 'post_password' => '123' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'not allowed to view', \GV\View::content( 'what!?' ) );

		/** Private */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'private', 'post_password' => '' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'not allowed to view', \GV\View::content( 'what!?' ) );

		/** Pending */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'pending' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'not allowed to view', \GV\View::content( 'what!?' ) );

		/** Draft */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'draft' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'not allowed to view', \GV\View::content( 'what!?' ) );

		/** Trash */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'trash' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'not allowed to view', \GV\View::content( 'what!?' ) );

		/** Scheduled */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'future', 'post_date_gmt' => '2117-11-10 18:02:56' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'not allowed to view', \GV\View::content( 'what!?' ) );

		/** Regular */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'publish', 'post_date_gmt' => '2017-07-09 00:00:00' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertNotContains( 'not allowed to view', \GV\View::content( 'what!?' ) );

		/** embed_only */
		$view->settings->update( array( 'embed_only' => true ) );
		$request->returns['is_view'] = $view;
		$this->assertContains( 'not allowed to view', \GV\View::content( 'what!?' ) );
	}

	public function test_protection_gravityview_shortcode_directory() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
		) );
		$view = \GV\View::from_post( $post );

		$args = array(
			'id' => $view->ID,
			'detail' => 'total_entries',
			'page_size' => 3,
		);

		$request = new \GV\Mock_Request();
		gravityview()->request = $request;

		$future = new \GV\Shortcodes\gravityview();

		/** Post password */
		wp_update_post( array( 'ID' => $post->ID, 'post_password' => '123' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'content is password protected', $future->callback( $args ) );

		/** Private */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'private', 'post_password' => '' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'not allowed to view', $future->callback( $args ) );

		/** Pending */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'pending' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'not allowed to view', $future->callback( $args ) );

		/** Draft */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'draft' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'not allowed to view', $future->callback( $args ) );

		/** Trash */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'trash' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'not allowed to view', $future->callback( $args ) );

		/** Scheduled */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'future', 'post_date_gmt' => '2117-11-10 18:02:56' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'not allowed to view', $future->callback( $args ) );

		/** Regular */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'publish', 'post_date_gmt' => '2017-07-09 00:00:00' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertNotContains( 'not allowed to view', $future->callback( $args ) );
	}

	public function test_protection_view_content_single() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
		) );
		$view = \GV\View::from_post( $post );
		$view->settings->update( array( 'show_only_approved' => true ) );

		/** Trash */
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'trash',
			'16' => 'hello'
		) );

		$request = new \GV\Mock_Request();
		gravityview()->request = $request;
		$request->returns['is_view'] = $view;
		$request->returns['is_entry'] = \GV\GF_Entry::by_id( $entry['id'] );

		$this->assertContains( 'not allowed to view', \GV\View::content( 'what!?' ) );

		/** Not approved */
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'hello'
		) );

		$request->returns['is_entry'] = \GV\GF_Entry::by_id( $entry['id'] );

		$this->assertContains( 'not allowed to view', \GV\View::content( 'what!?' ) );

		/** Approve */
		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );

		$this->assertNotContains( 'not allowed to view', \GV\View::content( 'what!?' ) );

		/** Slug */
		global $wp_query;
		$wp_query->set( \GV\Entry::get_endpoint_name(), $entry['id'] );

		add_filter( 'gravityview_custom_entry_slug', '__return_true' );

		$request->returns['is_entry'] = \GV\GF_Entry::by_id( $entry['id'] );

		$this->assertContains( 'not allowed to view', \GV\View::content( 'what!?' ) );

		/** Good slug */
		$wp_query->set( \GV\Entry::get_endpoint_name(), gform_get_meta( $entry['id'], 'gravityview_unique_id' ) );

		$this->assertNotContains( 'not allowed to view', \GV\View::content( 'what!?' ) );

		remove_all_filters( 'gravityview_custom_entry_slug' );
	}

	public function test_protection_gravityview_shortcode_single() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'show_only_approved' => true,
			),
		) );
		$view = \GV\View::from_post( $post );

		$future = new \GV\Shortcodes\gravityview();

		/** Trash */
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'trash',
			'16' => 'hello'
		) );

		$request = new \GV\Mock_Request();
		gravityview()->request = $request;
		$request->returns['is_view'] = $view;
		$request->returns['is_entry'] = \GV\GF_Entry::by_id( $entry['id'] );

		$args = array(
			'id' => $view->ID,
			'page_size' => 3,
		);

		$this->assertContains( 'not allowed to view', $future->callback( $args ) );

		/** Not approved */
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'hello'
		) );

		$request->returns['is_entry'] = \GV\GF_Entry::by_id( $entry['id'] );

		$this->assertContains( 'not allowed to view', $future->callback( $args ) );

		/** Approve */
		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );

		$this->assertNotContains( 'not allowed to view', $future->callback( $args ) );

		/** Slug */
		global $wp_query;
		$wp_query->set( \GV\Entry::get_endpoint_name(), $entry['id'] );

		add_filter( 'gravityview_custom_entry_slug', '__return_true' );

		$request->returns['is_entry'] = \GV\GF_Entry::by_id( $entry['id'] );

		$this->assertContains( 'not allowed to view', $future->callback( $args ) );

		/** Good slug */
		$wp_query->set( \GV\Entry::get_endpoint_name(), gform_get_meta( $entry['id'], 'gravityview_unique_id' ) );

		$this->assertNotContains( 'not allowed to view', $future->callback( $args ) );

		remove_all_filters( 'gravityview_custom_entry_slug' );
	}

	public function test_protection_oembed() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'show_only_approved' => true,
			),
		) );
		$view = \GV\View::from_post( $post );

		$future = array( '\GV\oEmbed', 'render' );

		/** Trash */
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'trash',
			'16' => 'hello'
		) );

		$args = array(
			'matches' => array(
				'slug' => $post->post_name,
				'is_cpt' => 'gravityview',
				'entry_slug' => $entry['id'],
			),
			'attr' => '',
			'url' => add_query_arg( 'entry', $entry['id'], get_permalink( $post->ID ) ),
			'rawattr' => '',
		);

		$this->assertContains( 'not allowed to view', call_user_func_array( $future, $args ) );

		/** Not approved */
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'hello'
		) );

		$args = array(
			'matches' => array(
				'slug' => $post->post_name,
				'is_cpt' => 'gravityview',
				'entry_slug' => $entry['id'],
			),
			'attr' => '',
			'url' => add_query_arg( 'entry', $entry['id'], get_permalink( $post->ID ) ),
			'rawattr' => '',
		);

		$this->assertContains( 'not allowed to view', call_user_func_array( $future, $args ) );

		/** Approve */
		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );

		$this->assertNotContains( 'not allowed to view', \GV\View::content( 'what!?' ) );

		/** Post password */
		wp_update_post( array( 'ID' => $post->ID, 'post_password' => '123' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'content is password protected', call_user_func_array( $future, $args ) );

		/** Trash */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'trash' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'not allowed to view', call_user_func_array( $future, $args ) );

		/** Private */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'private', 'post_password' => '' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'not allowed to view', call_user_func_array( $future, $args ) );

		/** Draft */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'draft' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'not allowed to view', call_user_func_array( $future, $args ) );

		/** Pending */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'pending' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'not allowed to view', call_user_func_array( $future, $args ) );

		/** Scheduled */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'future', 'post_date_gmt' => '2117-11-10 18:02:56' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'not allowed to view', call_user_func_array( $future, $args ) );

		/** Regular */
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'publish', 'post_date_gmt' => '2017-07-09 00:00:00' ) );
		$request->returns['is_view'] = \GV\View::by_id( $post->ID );
		$this->assertContains( 'gravityview-oembed-entry', call_user_func_array( $future, $args ) );
	}

	/**
	 * @covers \GV\Wrappers\views::get()
	 */
	public function test_magic_wrappers_views() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
		) );

		$view = \GV\View::from_post( $post );

		/** By ID */
		$this->assertEquals( $view, gravityview()->views->get( $view->ID ) );

		/** From post */
		$this->assertEquals( $view, gravityview()->views->get( $post ) );

		/** From configuration */
		$this->assertEquals( $view, gravityview()->views->get( $view->as_data() ) );

		/** From itself */
		$this->assertEquals( $view, gravityview()->views->get( $view ) );

		/** From context */
		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;

		$this->assertEquals( $view, gravityview()->views->get() );
	}

	public function test_mock_request() {
		$request = new \GV\Mock_Request();
		$request->returns['is_view'] = 9;
		$this->assertEquals( 9, $request->is_view() );
	}

	public function test_utils_get() {
		$a = array( 'hello' => 'world', 'who/is' => 'here', 'who' => array( 'is' => array( 'that' => 'coder' ) ) );
		$this->assertEquals( 'world', \GV\Utils::get( $a, 'hello' ) );
		$this->assertEquals( 'world', \GV\Utils::get( $a, 'hello', 'what?' ) );
		$this->assertEquals( 'what?', \GV\Utils::get( $a, 'world', 'what?' ) );

		/**
		 * Nested.
		 */
		$this->assertEquals( 'here', \GV\Utils::get( $a, 'who/is' ) );
		$this->assertEquals( 'coder', \GV\Utils::get( $a, 'who/is/that' ) );

		/**
		 * Object-like ArrayAccess.
		 */
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form->ID,
			'1' => 'set all the fields!',
			'2' => -100,
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$multi = \GV\Multi_Entry::from_entries( array( $entry ) );

		$this->assertEquals( $entry->ID, \GV\Utils::get( $multi, $form->ID )->ID );

		/**
		 * Object property access.
		 */
		$o = (object)$a;
		$o->who = (object)$o->who;
		$o->who->is = (object)$o->who->is;
		$this->assertEquals( 'world', \GV\Utils::get( $o, 'hello' ) );
		$this->assertEquals( 'world', \GV\Utils::get( $o, 'hello', 'what?' ) );
		$this->assertEquals( 'what?', \GV\Utils::get( $o, 'world', 'what?' ) );

		/**
		 * Nested.
		 */
		$this->assertEquals( 'here', \GV\Utils::get( $o, 'who/is' ) );
		$this->assertEquals( 'coder', \GV\Utils::get( $o, 'who/is/that' ) );
	}

	public function test_license_handler() {
		require_once gravityview()->plugin->dir( 'includes/class-gv-license-handler.php' );

		$settings = $this->getMockBuilder( '\GV\Addon_Settings' )
			->setMethods( array( 'get', 'update' ) )->getMock();

		$settings->method( 'get' )->will( $this->returnValueMap( array(
			array( 'license_key', 'TEST123' ),
		) ) );

		$this->assertSame( \GV_License_Handler::get_instance( $settings ), $handler = \GV\License_Handler::get() );

		$handler->setup_edd();

		add_filter( 'pre_http_request', $callback = function() {
			return array( 'body' => json_encode( array(
				'license' => 'invalid',
				'license_key' => 'TEST',
			) ) );
		} );

		$result = json_decode( $handler->license_call(), true );
		$this->assertEquals( $result['error'], 'capability' );
		$result = json_decode( $handler->license_call( array( 'license' => 'TEST' ), false ), true );
		$this->assertEquals( $result['license'], 'invalid' );

		remove_filter( 'pre_http_request', $callback );
		add_filter( 'pre_http_request', $callback = function() {
			return array( 'body' => json_encode( array(
				'license' => 'valid',
				'license_key' => 'TEST',
				'license_name' => 'Test Suite License',
				'upgrades' => array( 'one' => array( 'name' => 'what', 'price' => 10, 'price_id' => 1, 'url' => 'https://gravityview.co', 'description' => 'testing' ) ),
			) ) );
		} );

		$result = json_decode( $handler->license_call( array( 'license' => 'TEST' ), false ), true );
		$this->assertContains( 'Test Suite License', $result['details'] );

		remove_filter( 'pre_http_request', $callback );
		add_filter( 'pre_http_request', $callback = function() {
			return array( 'body' => json_encode( array() ) );
		} );

		$this->assertEquals( array(), json_decode( $handler->license_call( array( 'license' => 'TEST' ), false ), true ) );

		remove_filter( 'pre_http_request', $callback );
		add_filter( 'pre_http_request', $callback = function() {
			return array( 'body' => json_encode( array(
				'license' => 'valid',
				'license_key' => 'TEST!THIS',
			) ) );
		} );
		$handler->license_call( array( 'license' => 'TEST', 'field_id' => 1, 'update' => 1 ), false );

		remove_filter( 'pre_http_request', $callback );
		add_filter( 'pre_http_request', $callback = function() {
			return array( 'body' => json_encode( array() ) );
		} );

		delete_transient( $handler::status_transient_key );
		$handler->refresh_license_status();

		delete_transient( $handler::status_transient_key );
		delete_transient( 'gv_license_check' );
		$handler->flush_related_plugins_transient();

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		remove_filter( 'pre_http_request', $callback );
		$test = &$this;
		add_filter( 'pre_http_request', $callback = function() use ( $test ) {
			$args = func_get_args();
			$args = $args[0]['body'];
			$test->assertEquals( $args['item_name'], 'GravityView' );
			$test->assertEquals( $args['site_data']['php_version'], phpversion() );
			$test->assertEquals( $args['site_data']['view_count'], wp_count_posts( 'gravityview', 'readable' )->publish );
			$form_counts = \GFFormsModel::get_form_count();
			$test->assertEquals( $args['site_data']['forms_total'], $form_counts['total'] );
			return array( 'body' => json_encode( array() ) );
		} );

		$handler->check_license();
		remove_filter( 'pre_http_request', $callback );

		$this->assertContains( 'Verifying license', $handler->settings_edd_license_activation( false, false ) );
	}

	public function test_addon_settings() {
		$this->assertSame( \GravityView_Settings::get_instance(), $settings = gravityview()->plugin->settings );
		$this->assertEquals( array_keys( $settings->get_default_settings() ), array( 'license_key', 'license_key_response', 'license_key_status', 'support-email', 'no-conflict-mode', 'support_port', 'flexbox_search', 'rest_api', 'beta' ) );

		$this->assertNull( $settings->get( 'not' ) );
		$this->assertEquals( $settings->get( 'not', 'default' ), 'default' );
		$this->assertEquals( $settings->get( 'beta' ), '0' );
		$this->assertEquals( $settings->get_app_settings(), $settings->all() );

		$settings->set( 'beta', '1' );
		$this->assertEquals( $settings->get( 'beta' ), '1' );
		$this->assertEquals( $settings->get( 'license' ), array( 'license' => '', 'status' => '', 'response' => '' ) );

		$settings->set( array(
			'beta' => '3',
			'alpha' => '16'
		) );

		$this->assertEquals( $settings->get( 'beta' ), '3' );
		$this->assertEquals( $settings->get( 'alpha' ), '16' );
		$this->assertEquals( $settings->get( 'support-email' ), get_bloginfo( 'admin_email' ) );

		$settings->update( $expected = array( 'wub' => 'dub' ) );
		$this->assertEquals( $settings->all(), wp_parse_args( $expected, $settings->get_default_settings() ) );

		$this->assertEquals( \GravityView_Settings::getSetting( 'wub' ), 'dub' );

		$settings = new \GV\Addon_Settings();
		$settings->init_admin();
		$settings->init_ajax();
		$settings->add_network_menu();
		$this->assertEquals( $settings->modify_app_settings_menu_title( array( array() ) ), array( array( 'label' => 'GravityView Settings' ) ) );
		$this->assertFalse( $settings->current_user_can_any( array( 'oops' ) ) );

		$this->assertContains( 'delete then', $settings->uninstall_warning_message() );
		$this->assertContains( 'gv-uninstall-form-wrapper', $settings->uninstall_form() );

		$administrator = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => 'administrator' )
		);

		if ( function_exists( 'grant_super_admin' ) ) {
			grant_super_admin( $administrator );
		}
		wp_set_current_user( $administrator );

		ob_start();
		$settings->app_settings_uninstall_tab();
		$this->assertContains( 'ALL GravityView settings will be deleted', ob_get_clean() );

		ob_start();
		$settings->app_settings_tab();
		$this->assertContains( '_gravityview_save_settings_nonce', $tab = ob_get_clean() );
		$this->assertContains( 'Uninstall GravityView', $tab );
		$this->assertNull( $settings->app_settings_title() );
		$this->assertEquals( $settings->app_settings_icon(), '&nbsp;' );

		$settings->scripts();
		$settings->styles();

		$settings->create_app_menu();

		wp_set_current_user( 0 );
	}

	public function test_extension_class() {
		$legacy_ext = new GVFutureTest_Extension_Test_BC();
		$ext = new GVFutureTest_Extension_Test();

		$this->assertFalse( $ext::is_compatible() );
		$this->assertTrue( $legacy_ext::is_compatible() );

		ob_start(); $legacy_ext->admin_notice();
		$this->assertContains( 'requires GravityView Version', ob_get_clean() );

		set_current_screen( 'dashboard' );
		gravityview()->plugin->settings->set( 'license_key_status', 'valid' );
		$ext->settings();
		set_current_screen( 'front' );

		$ext->load_plugin_textdomain();
		$ext->tooltips();
		$ext->add_metabox_tab();
	}

	public function test_widget_class() {
		add_filter( 'gravityview/widget/enable_custom_class', '__return_true' );

		$legacy_w = new GVFutureTest_Widget_Test_BC( 'What is this', 'old-widget', array(), array( 'what' => 'heh' ) );
		$w = new GVFutureTest_Widget_Test( 'This is New', 'new-widget' );

		$this->assertEquals( $legacy_w->get_widget_id(), 'old-widget' );
		$this->assertEquals( $legacy_w->get_setting( 'what' ), 'heh' );
		$this->assertNotEmpty( $w->get_settings() );

		remove_filter( 'gravityview/widget/enable_custom_class', '__return_true' );

		$this->assertNotEmpty( \GV\Widget::get_default_widget_areas() );

		add_filter( 'gravityview_widget_active_areas', $callback = function() { return array( '1' ); } );

		$this->assertEquals( \GV\Widget::get_default_widget_areas(), array( '1' ) );

		add_filter( 'gravityview/widget/active_areas', $callback2 = function() { return array( '2' ); } );

		$this->assertEquals( \GV\Widget::get_default_widget_areas(), array( '2' ) );

		remove_filter( 'gravityview_widget_active_areas', $callback );
		remove_filter( 'gravityview/widget/active_areas', $callback2 );

		$widgets = array_keys( apply_filters( 'gravityview/widgets/register', array() ) );
		$this->assertContains( 'old-widget', $widgets );
		$this->assertContains( 'new-widget', $widgets );

		$this->assertEmpty( apply_filters( 'gravityview_template_widget_options', array() ) );
		$this->assertNotEmpty( apply_filters( 'gravityview_template_widget_options', array(), null, 'old-widget' ) );

		$form = $this->factory->form->create_and_get();
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $view );

		global $post;

		$post = $this->factory->post->create_and_get();
		$post->post_content = sprintf( '[gravityview id="%d"]', $view->ID );

		$w->add_shortcode();
		$this->assertContains( '<strong class="floaty">GravityView</strong>', $w->maybe_do_shortcode( 'okay [gvfuturetest_widget_test] okay' ) );
	}

	public function test_widget_render() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		global $post;

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '1.6',
						'label' => 'Country <small>(Address)</small>',
						'only_loggedin_cap' => 'read',
						'only_loggedin' => true,
					),
				),
			),
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => $widget_id = wp_generate_password( 4, false ) . '-widget',
						'test' => 'foo',
					),
				),
				'footer_right' => array(
					wp_generate_password( 4, false ) => array(
						'id' => $widget_id,
						'test' => 'bar',
					),
				),
			),
		) );

		/** Trigger registration under this ID */
		new GVFutureTest_Widget_Test( 'Widget', $widget_id );

		$view = \GV\View::from_post( $post );


		$renderer = new \GV\View_Renderer();

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;

		$future = $renderer->render( $view );

		$this->assertContains( '<strong class="floaty">GravityViewfoo</strong>', $future );
		$this->assertContains( '<strong class="floaty">GravityViewbar</strong>', $future );
	}

	public function test_template_hooks_compat_table_directory() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		foreach ( range( 1, 5 ) as $i ) {
			$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
				'form_id' => $form['id'],
				'1' => microtime( true ),
				'2' => $i,
			) );
		}

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1',
						'label' => 'Microtime',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '2',
						'label' => 'Index',
					),
				),
			),
		) );
		$view = \GV\View::from_post( $post );

		$test = &$this;
		$callbacks = array();

		add_action( 'gravityview_before', $callbacks []= function( $view_id ) use ( $view, $test ) {
			$test->assertEquals( $view->ID, $view_id );
			echo '{{ gravityview_before }}';
		} );

		add_action( 'gravityview/template/before', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/before }}';
		} );

		add_action( 'gravityview_after', $callbacks []= function( $view_id ) use ( $view, $test ) {
			$test->assertEquals( $view->ID, $view_id );
			echo '{{ gravityview_after }}';
		}, 11 );

		add_action( 'gravityview/template/after', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/after }}';
		}, 11 );

		add_action( 'gravityview_header', $callbacks []= function( $view_id ) use ( $view, $test ) {
			$test->assertEquals( $view->ID, $view_id );
			echo '{{ gravityview_header }}';
		} );

		add_action( 'gravityview/template/header', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/header }}';
		} );

		add_action( 'gravityview_footer', $callbacks []= function( $view_id ) use ( $view, $test ) {
			$test->assertEquals( $view->ID, $view_id );
			echo '{{ gravityview_footer }}';
		} );

		add_action( 'gravityview/template/footer', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/footer }}';
		} );

		add_action( 'gravityview_table_body_before', $callbacks []= function( $gravityview_view ) use ( $view, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			echo '{{ gravityview_table_body_before }}';
		} );

		add_action( 'gravityview/template/table/body/before', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/table/body/before }}';
		} );

		add_action( 'gravityview_table_body_after', $callbacks []= function( $gravityview_view ) use ( $view, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			echo '{{ gravityview_table_body_after }}';
		} );

		add_action( 'gravityview/template/table/body/after', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/table/body/after }}';
		} );

		add_filter( 'gravityview_entry_class', $callbacks []= function( $class, $entry, $gravityview_view ) use ( $view, $form, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			$test->assertEquals( $entry['form_id'], $form['id'] );
			return "$class gravityview_entry_class";
		}, 10, 3 );

		add_filter( 'gravityview/template/table/entry/class', $callbacks []= function( $class, $context ) use ( $view, $form, $test ) {
			$test->assertSame( $context->view, $view );
			$test->assertEquals( $context->entry['form_id'], $form['id'] );
			return "$class gravityview/template/table/entry/class";
		}, 10, 2 );

		add_action( 'gravityview/template/table/cells/before', $callbacks []= function( $context ) use ( $view, $form, $test ) {
			$test->assertSame( $context->view, $view );
			$test->assertEquals( $context->entry['form_id'], $form['id'] );
			echo '{{ gravityview/template/table/cells/before }}';
		} );

		add_action( 'gravityview_table_cells_before', $callbacks []= function( $gravityview_view ) use ( $view, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			echo '{{ gravityview_table_cells_before }}';
		} );

		add_action( 'gravityview/template/table/cells/after', $callbacks []= function( $context ) use ( $view, $form, $test ) {
			$test->assertSame( $context->view, $view );
			$test->assertEquals( $context->entry['form_id'], $form['id'] );
			echo '{{ gravityview/template/table/cells/after }}';
		} );

		add_action( 'gravityview_table_cells_after', $callbacks []= function( $gravityview_view ) use ( $view, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			echo '{{ gravityview_table_cells_after }}';
		} );

		add_filter( 'gravityview/render/container/class', $callbacks []= function( $class, $context ) use ( $view, $test ) {
			$test->assertSame( $context->view, $view );
			$test->assertContains( "gv-container-{$view->ID}", $class );
			return "$class {{ gravityview/render/container/class }}";
		}, 10, 2 );

		$renderer = new \GV\View_Renderer();

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;

		$out = $renderer->render( $view );

		$this->assertStringStartsWith( '{{ gravityview/template/before }}{{ gravityview_before }}', $out );
		$this->assertStringEndsWith( '{{ gravityview/template/after }}{{ gravityview_after }}', $out );

		$this->assertContains( '{{ gravityview/template/header }}{{ gravityview_header }}', $out );
		$this->assertContains( '{{ gravityview/template/footer }}{{ gravityview_footer }}', $out );

		$this->assertContains( '{{ gravityview/template/table/body/before }}{{ gravityview_table_body_before }}', $out );
		$this->assertContains( '{{ gravityview/template/table/body/after }}{{ gravityview_table_body_after }}', $out );

		$this->assertContains( 'class="alt gravityview_entry_class gravityview/template/table/entry/class"', $out );

		$this->assertContains( '{{ gravityview/template/table/cells/before }}{{ gravityview_table_cells_before }}', $out );
		$this->assertContains( '{{ gravityview/template/table/cells/after }}{{ gravityview_table_cells_after }}', $out );

		$this->assertContains( 'gravityviewrendercontainerclass' /** sanitized */, $out );

		$removed = array(
			remove_action( 'gravityview_before', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/before', array_shift( $callbacks ) ),
			remove_action( 'gravityview_after', array_shift( $callbacks ), 11 ),
			remove_action( 'gravityview/template/after', array_shift( $callbacks ), 11 ),
			remove_action( 'gravityview_header', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/header', array_shift( $callbacks ) ),
			remove_action( 'gravityview_footer', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/footer', array_shift( $callbacks ) ),
			remove_action( 'gravityview_table_body_before', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/table/body/before', array_shift( $callbacks ) ),
			remove_action( 'gravityview_table_body_after', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/table/body/after', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_entry_class', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/table/entry/class', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/table/cells/before', array_shift( $callbacks ) ),
			remove_action( 'gravityview_table_cells_before', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/table/cells/after', array_shift( $callbacks ) ),
			remove_action( 'gravityview_table_cells_after', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/render/container/class', array_shift( $callbacks ) ),
		);

		$this->assertNotContains( false, $removed );
		$this->assertEmpty( $callbacks );

		$view->settings->update( array( 'hide_until_searched' => true ) );

		add_action( 'gravityview_table_tr_before', $callbacks []= function( $gravityview_view ) use ( $view, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			echo '{{ gravityview_table_tr_before }}';
		} );

		add_action( 'gravityview/template/table/tr/before', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/table/tr/before }}';
		} );


		add_action( 'gravityview_table_tr_after', $callbacks []= function( $gravityview_view ) use ( $view, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			echo '{{ gravityview_table_tr_after }}';
		} );

		add_action( 'gravityview/template/table/tr/after', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/table/tr/after }}';
		} );

		add_filter( 'gravitview_no_entries_text', $callbacks []= function( $text, $is_search ) {
			return "{{ gravitview_no_entries_text }}$text";
		}, 10, 2 );

		add_filter( 'gravityview/template/text/no_entries', $callbacks []= function( $text, $is_search, $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			return "{{ gravityview/template/text/no_entries }}$text";
		}, 10, 3 );

		add_filter( 'gravityview_render_after_label', $callbacks []= function( $label, $field ) use ( $view, $test ) {
			$test->assertEquals( $field['form_id'], $view->form->ID );
			return "$label{{ gravityview_render_after_label }}";
		}, 10, 4 );

		add_filter( 'gravityview/template/field_label', $callbacks []= function( $label, $field, $form, $entry ) use ( $view, $test ) {
			$test->assertEquals( $form['id'], $view->form->ID );
			$test->assertNull( $entry ); // Headers have no entry
			return "$label{{ gravityview/template/field_label }}";
		}, 10, 4 );

		add_filter( 'gravityview/template/field/label', $callbacks []= function( $label, $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			$test->assertNull( $context->entry ); // Headers have no entry
			return "$label{{ gravityview/template/field/label }}";
		}, 10, 2 );

		$out = $renderer->render( $view );

		$this->assertContains( '{{ gravityview/template/table/tr/before }}{{ gravityview_table_tr_before }}', $out );
		$this->assertContains( '{{ gravityview/template/table/tr/after }}{{ gravityview_table_tr_after }}', $out );

		$this->assertContains( '{{ gravityview/template/text/no_entries }}{{ gravitview_no_entries_text }}', $out );

		$this->assertContains( '{{ gravityview_render_after_label }}{{ gravityview/template/field_label }}{{ gravityview/template/field/label }}', $out );

		$this->assertContains( "gv-container-{$view->ID}", $out );
		$this->assertContains( "gv-container-no-results", $out );

		$removed = array(
			remove_action( 'gravityview_table_tr_before', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/table/tr/before', array_shift( $callbacks ) ),
			remove_action( 'gravityview_table_tr_after', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/table/tr/after', array_shift( $callbacks ) ),
			remove_filter( 'gravitview_no_entries_text', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/text/no_entries', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_render_after_label', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/field_label', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/field/label', array_shift( $callbacks ) ),
		);

		$this->assertNotContains( false, $removed );
		$this->assertEmpty( $callbacks );
	}

	public function test_template_hooks_compat_table_single() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form['id'],
			'1' => microtime( true ),
			'2' => 'who knows, knows who'
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1',
						'label' => 'Microtime',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '2',
						'label' => 'Index',
					),
				),
			),
		) );
		$view = \GV\View::from_post( $post );

		$test = &$this;
		$callbacks = array();

		add_action( 'gravityview_before', $callbacks []= function( $view_id ) use ( $view, $test ) {
			$test->assertEquals( $view->ID, $view_id );
			echo '{{ gravityview_before }}';
		} );

		add_action( 'gravityview/template/before', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/before }}';
		} );

		add_action( 'gravityview_after', $callbacks []= function( $view_id ) use ( $view, $test ) {
			$test->assertEquals( $view->ID, $view_id );
			echo '{{ gravityview_after }}';
		}, 11 );

		add_action( 'gravityview/template/after', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/after }}';
		}, 11 );

		add_action( 'gravityview_header', $callbacks []= function( $view_id ) use ( $view, $test ) {
			$test->assertEquals( $view->ID, $view_id );
			echo '{{ gravityview_header }}';
		} );

		add_action( 'gravityview/template/header', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/header }}';
		} );

		add_action( 'gravityview_footer', $callbacks []= function( $view_id ) use ( $view, $test ) {
			$test->assertEquals( $view->ID, $view_id );
			echo '{{ gravityview_footer }}';
		} );

		add_action( 'gravityview/template/footer', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/footer }}';
		} );

		add_filter( 'gravityview_directory_link', $callbacks []= function( $link, $post_id ) use ( $view, $test ) {
			$test->assertEquals( $view->ID, $post_id );
			return "$link{{ gravityview_directory_link }}";
		}, 10, 2 );

		add_filter( 'gravityview/view/links/directory', $callbacks []= function( $link, $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			return "$link{{ gravityview/view/links/directory }}";
		}, 10, 2 );

		add_filter( 'gravityview_go_back_url', $callbacks []= function( $url ) use ( $view, $test ) {
			$test->assertEquals( $view->ID, \GravityView_View::getInstance()->getViewId() );
			return "$url{{ gravityview_go_back_url }}";
		} );

		add_filter( 'gravityview/template/links/back/url', $callbacks []= function( $url, $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			return "$url{{ gravityview/template/links/back/url }}";
		}, 10, 2 );

		add_filter( 'gravityview_go_back_label', $callbacks []= function( $label ) use ( $view, $test ) {
			$test->assertEquals( $view->ID, \GravityView_View::getInstance()->getViewId() );
			return "$label{{ gravityview_go_back_label }}";
		} );

		add_filter( 'gravityview/template/links/back/label', $callbacks []= function( $label, $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			return "$label{{ gravityview/template/links/back/label }}";
		}, 10, 2 );

		add_filter( 'gravityview/render/container/class', $callbacks []= function( $class, $context ) use ( $view, $test ) {
			$test->assertSame( $context->view, $view );
			$test->assertContains( "gv-container-{$view->ID}", $class );
			return "$class {{ gravityview/render/container/class }}";
		}, 10, 2 );

		add_filter( 'gravityview_render_after_label', $callbacks []= function( $label, $field ) use ( $view, $test ) {
			$test->assertEquals( $field['form_id'], $view->form->ID );
			return "$label{{ gravityview_render_after_label }}";
		}, 10, 4 );

		add_filter( 'gravityview/template/field_label', $callbacks []= function( $label, $field, $form, $_entry ) use ( $view, $entry, $test ) {
			$test->assertEquals( $form['id'], $view->form->ID );
			$test->assertEquals( $_entry['id'], $entry['id'] );
			return "$label{{ gravityview/template/field_label }}";
		}, 10, 4 );

		add_filter( 'gravityview/template/field/label', $callbacks []= function( $label, $context ) use ( $view, $entry, $test ) {
			$test->assertSame( $view, $context->view );
			$test->assertSame( $entry, $context->entry );
			return "$label{{ gravityview/template/field/label }}";
		}, 10, 2 );

		$renderer = new \GV\Entry_Renderer();

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_entry'] = $entry;

		$out = $renderer->render( $entry, $view );

		$this->assertStringStartsWith( '{{ gravityview/template/before }}{{ gravityview_before }}', $out );
		$this->assertStringEndsWith( '{{ gravityview/template/after }}{{ gravityview_after }}', $out );

		$this->assertContains( '{{ gravityview/template/header }}{{ gravityview_header }}', $out );
		$this->assertContains( '{{ gravityview/template/footer }}{{ gravityview_footer }}', $out );

		$this->assertContains( '{{ gravityview_render_after_label }}{{ gravityview/template/field_label }}{{ gravityview/template/field/label }}', $out );

		$this->assertContains( '%20gravityview_directory_link%20%20gravityview/view/links/directory%20', $out );

		$this->assertContains( 'gravityviewrendercontainerclass' /** sanitized */, $out );

		$this->assertContains( "gv-container-{$view->ID}", $out );
		$this->assertNotContains( "gv-container-no-results", $out );

		$removed = array(
			remove_action( 'gravityview_before', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/before', array_shift( $callbacks ) ),
			remove_action( 'gravityview_after', array_shift( $callbacks ), 11 ),
			remove_action( 'gravityview/template/after', array_shift( $callbacks ), 11 ),
			remove_action( 'gravityview_header', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/header', array_shift( $callbacks ) ),
			remove_action( 'gravityview_footer', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/footer', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_directory_link', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/view/links/directory', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_go_back_url', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/links/back/url', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_go_back_label', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/links/back/label', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/render/container/class', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_render_after_label', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/field_label', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/field/label', array_shift( $callbacks ) ),
		);

		$this->assertNotContains( false, $removed );
		$this->assertEmpty( $callbacks );
	}

	public function test_template_hooks_compat_list_directory( $really_directory = true, $save_callback = null ) {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		foreach ( range( 1, 5 ) as $i ) {
			$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
				'form_id' => $form['id'],
				'1' => microtime( true ),
				'2' => $i,
			) );
		}
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$mode = $really_directory ? 'directory' : 'single';

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'preset_business_listings',
			'fields' => array(
				$mode . '_list-title' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1',
						'label' => 'Microtime',
					),
				),
				$mode . '_list-subtitle' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '2',
						'label' => 'Index',
					),
				),
				$mode . '_list-image' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '2',
						'label' => 'Index',
					),
				),
				$mode . '_list-description' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '2',
						'label' => 'Index',
					),
				),
				$mode . '_list-footer-left' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1',
						'label' => 'Microtime',
					),
				),
				$mode . '_list-footer-right' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1',
						'label' => 'Microtime',
					),
				)
			),
		) );
		$view = \GV\View::from_post( $post );

		if ( $mode == 'single' && is_callable( $save_callback ) ) {
			$save_callback( $view, \GV\GF_Entry::by_id( $entry['id'] ) );
		}

		$test = &$this;
		$callbacks = array();

		add_action( 'gravityview_before', $callbacks []= function( $view_id ) use ( $view, $test ) {
			$test->assertEquals( $view->ID, $view_id );
			echo '{{ gravityview_before }}';
		} );

		add_action( 'gravityview/template/before', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/before }}';
		} );

		add_action( 'gravityview_after', $callbacks []= function( $view_id ) use ( $view, $test ) {
			$test->assertEquals( $view->ID, $view_id );
			echo '{{ gravityview_after }}';
		}, 11 );

		add_action( 'gravityview/template/after', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/after }}';
		}, 11 );

		add_action( 'gravityview_header', $callbacks []= function( $view_id ) use ( $view, $test ) {
			$test->assertEquals( $view->ID, $view_id );
			echo '{{ gravityview_header }}';
		} );

		add_action( 'gravityview/template/header', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/header }}';
		} );

		add_action( 'gravityview_footer', $callbacks []= function( $view_id ) use ( $view, $test ) {
			$test->assertEquals( $view->ID, $view_id );
			echo '{{ gravityview_footer }}';
		} );

		add_action( 'gravityview/template/footer', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/footer }}';
		} );

		add_filter( 'gravityview/render/container/class', $callbacks []= function( $class, $context ) use ( $view, $test ) {
			$test->assertSame( $context->view, $view );
			$test->assertContains( "gv-container-{$view->ID}", $class );
			return "$class {{ gravityview/render/container/class }}";
		}, 10, 2 );


		add_filter( 'gravityview_entry_class', $callbacks []= function( $class, $entry, $gravityview_view ) use ( $view, $form, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			$test->assertEquals( $entry['form_id'], $form['id'] );
			return "$class gravityview_entry_class";
		}, 10, 3 );

		add_filter( 'gravityview/template/list/entry/class', $callbacks []= function( $class, $context ) use ( $view, $form, $test ) {
			$test->assertSame( $context->view, $view );
			$test->assertEquals( $context->entry['form_id'], $form['id'] );
			return "$class gravityview/template/list/entry/class";
		}, 10, 2 );

		add_action( 'gravityview_list_body_before', $callbacks []= function( $gravityview_view ) use ( $view, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			echo '{{ gravityview_list_body_before }}';
		} );

		add_action( 'gravityview/template/list/body/before', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/list/body/before }}';
		} );

		add_action( 'gravityview_list_body_after', $callbacks []= function( $gravityview_view ) use ( $view, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			echo '{{ gravityview_list_body_after }}';
		} );

		add_action( 'gravityview/template/list/body/after', $callbacks []= function( $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			echo '{{ gravityview/template/list/body/after }}';
		} );

		add_action( 'gravityview_list_entry_before', $callbacks []= function( $entry, $gravityview_view ) use ( $view, $form, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			$test->assertEquals( $entry['form_id'], $form['id'] );
			echo '{{ gravityview_list_entry_before }}';
		}, 10, 2 );

		add_action( 'gravityview/template/list/entry/before', $callbacks []= function( $context ) use ( $view, $form, $test ) {
			$test->assertSame( $view, $context->view );
			$test->assertEquals( $context->entry['form_id'], $form['id'] );
			echo '{{ gravityview/template/list/entry/before }}';
		} );

		add_action( 'gravityview_list_entry_after', $callbacks []= function( $entry, $gravityview_view ) use ( $view, $form, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			$test->assertEquals( $entry['form_id'], $form['id'] );
			echo '{{ gravityview_list_entry_after }}';
		}, 10, 2 );

		add_action( 'gravityview/template/list/entry/after', $callbacks []= function( $context ) use ( $view, $form, $test ) {
			$test->assertSame( $view, $context->view );
			$test->assertEquals( $context->entry['form_id'], $form['id'] );
			echo '{{ gravityview/template/list/entry/after }}';
		} );

		add_action( 'gravityview_list_entry_title_after', $callbacks []= function( $entry, $gravityview_view ) use ( $view, $form, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			$test->assertEquals( $entry['form_id'], $form['id'] );
			echo '{{ gravityview_list_entry_title_after }}';
		}, 10, 2 );

		add_action( 'gravityview/template/list/entry/title/after', $callbacks []= function( $context ) use ( $view, $form, $test ) {
			$test->assertSame( $view, $context->view );
			$test->assertEquals( $context->entry['form_id'], $form['id'] );
			echo '{{ gravityview/template/list/entry/title/after }}';
		} );

		add_action( 'gravityview_list_entry_title_before', $callbacks []= function( $entry, $gravityview_view ) use ( $view, $form, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			$test->assertEquals( $entry['form_id'], $form['id'] );
			echo '{{ gravityview_list_entry_title_before }}';
		}, 10, 2 );

		add_action( 'gravityview/template/list/entry/title/before', $callbacks []= function( $context ) use ( $view, $form, $test ) {
			$test->assertSame( $view, $context->view );
			$test->assertEquals( $context->entry['form_id'], $form['id'] );
			echo '{{ gravityview/template/list/entry/title/before }}';
		} );

		add_action( 'gravityview_list_entry_content_before', $callbacks []= function( $entry, $gravityview_view ) use ( $view, $form, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			$test->assertEquals( $entry['form_id'], $form['id'] );
			echo '{{ gravityview_list_entry_content_before }}';
		}, 10, 2 );

		add_action( 'gravityview/template/list/entry/content/before', $callbacks []= function( $context ) use ( $view, $form, $test ) {
			$test->assertSame( $view, $context->view );
			$test->assertEquals( $context->entry['form_id'], $form['id'] );
			echo '{{ gravityview/template/list/entry/content/before }}';
		} );

		add_action( 'gravityview_list_entry_content_after', $callbacks []= function( $entry, $gravityview_view ) use ( $view, $form, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			$test->assertEquals( $entry['form_id'], $form['id'] );
			echo '{{ gravityview_list_entry_content_after }}';
		}, 10, 2 );

		add_action( 'gravityview/template/list/entry/content/after', $callbacks []= function( $context ) use ( $view, $form, $test ) {
			$test->assertSame( $view, $context->view );
			$test->assertEquals( $context->entry['form_id'], $form['id'] );
			echo '{{ gravityview/template/list/entry/content/after }}';
		} );

		add_action( 'gravityview_list_entry_footer_after', $callbacks []= function( $entry, $gravityview_view ) use ( $view, $form, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			$test->assertEquals( $entry['form_id'], $form['id'] );
			echo '{{ gravityview_list_entry_footer_after }}';
		}, 10, 2 );

		add_action( 'gravityview/template/list/entry/footer/after', $callbacks []= function( $context ) use ( $view, $form, $test ) {
			$test->assertSame( $view, $context->view );
			$test->assertEquals( $context->entry['form_id'], $form['id'] );
			echo '{{ gravityview/template/list/entry/footer/after }}';
		} );

		add_action( 'gravityview_list_entry_footer_before', $callbacks []= function( $entry, $gravityview_view ) use ( $view, $form, $test ) {
			$test->assertEquals( $gravityview_view->getViewId(), $view->ID );
			$test->assertEquals( $entry['form_id'], $form['id'] );
			echo '{{ gravityview_list_entry_footer_before }}';
		}, 10, 2 );

		add_action( 'gravityview/template/list/entry/footer/before', $callbacks []= function( $context ) use ( $view, $form, $test ) {
			$test->assertSame( $view, $context->view );
			$test->assertEquals( $context->entry['form_id'], $form['id'] );
			echo '{{ gravityview/template/list/entry/footer/before }}';
		} );

		add_filter( 'gravityview_render_after_label', $callbacks []= function( $label, $field ) use ( $view, $test ) {
			$test->assertEquals( $field['form_id'], $view->form->ID );
			return "$label{{ gravityview_render_after_label }}";
		}, 10, 4 );

		add_filter( 'gravityview/template/field_label', $callbacks []= function( $label, $field, $form, $_entry ) use ( $view, $entry, $test, $mode ) {
			$test->assertEquals( $form['id'], $view->form->ID );
			if ( $mode == 'single' ) {
				$test->assertEquals( $_entry['id'], $entry['id'] );
			}
			return "$label{{ gravityview/template/field_label }}";
		}, 10, 4 );

		add_filter( 'gravityview/template/field/label', $callbacks []= function( $label, $context ) use ( $view, $entry, $test, $mode ) {
			$test->assertSame( $view, $context->view );
			if ( $mode == 'single' ) {
				$test->assertEquals( $entry->ID, $context->entry->ID );
			}
			return "$label{{ gravityview/template/field/label }}";
		}, 10, 2 );

		gravityview()->request = new \GV\Mock_Request();

		if ( $mode == 'directory' ) {
			$renderer = new \GV\View_Renderer();
			gravityview()->request->returns['is_view'] = $view;
			$out = $renderer->render( $view );
		} else {
			$renderer = new \GV\Entry_Renderer();
			$entry = \GV\GF_Entry::by_id( $entry['id'] );
			gravityview()->request->returns['is_entry'] = $entry;
			$out = $renderer->render( $entry, $view );
		}

		$this->assertStringStartsWith( '{{ gravityview/template/before }}{{ gravityview_before }}', $out );
		$this->assertStringEndsWith( '{{ gravityview/template/after }}{{ gravityview_after }}', $out );


		if ( $mode == 'directory' ) {
			$this->assertContains( '{{ gravityview/template/header }}{{ gravityview_header }}', $out );
			$this->assertContains( '{{ gravityview/template/footer }}{{ gravityview_footer }}', $out );

			$this->assertContains( '{{ gravityview/template/list/body/before }}{{ gravityview_list_body_before }}', $out );
			$this->assertContains( '{{ gravityview/template/list/body/after }}{{ gravityview_list_body_after }}', $out );

			$this->assertContains( '{{ gravityview/template/list/entry/before }}{{ gravityview_list_entry_before }}', $out );
			$this->assertContains( '{{ gravityview/template/list/entry/after }}{{ gravityview_list_entry_after }}', $out );
			$this->assertContains( '{{ gravityview/template/list/entry/title/before }}{{ gravityview_list_entry_title_before }}', $out );
			$this->assertContains( '{{ gravityview/template/list/entry/title/after }}{{ gravityview_list_entry_title_after }}', $out );
			$this->assertContains( '{{ gravityview/template/list/entry/content/before }}{{ gravityview_list_entry_content_before }}', $out );
			$this->assertContains( '{{ gravityview/template/list/entry/content/after }}{{ gravityview_list_entry_content_after }}', $out );

			$this->assertContains( '{{ gravityview/template/list/entry/footer/before }}{{ gravityview_list_entry_footer_before }}', $out );
			$this->assertContains( '{{ gravityview/template/list/entry/footer/after }}{{ gravityview_list_entry_footer_after }}', $out );

			$this->assertContains( 'gravityview_entry_class gravityviewtemplatelistentryclass', $out );
		}

		$this->assertContains( 'gravityviewrendercontainerclass' /** sanitized */, $out );
		$this->assertNotContains( "gv-container-no-results", $out );

		$removed = array(
			remove_action( 'gravityview_before', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/before', array_shift( $callbacks ) ),
			remove_action( 'gravityview_after', array_shift( $callbacks ), 11 ),
			remove_action( 'gravityview/template/after', array_shift( $callbacks ), 11 ),
			remove_action( 'gravityview_header', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/header', array_shift( $callbacks ) ),
			remove_action( 'gravityview_footer', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/footer', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/render/container/class', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_entry_class', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/list/entry/class', array_shift( $callbacks ) ),
			remove_action( 'gravityview_list_body_before', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/list/body/before', array_shift( $callbacks ) ),
			remove_action( 'gravityview_list_body_after', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/list/body/after', array_shift( $callbacks ) ),
			remove_action( 'gravityview_list_entry_before', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/list/entry/before', array_shift( $callbacks ) ),
			remove_action( 'gravityview_list_entry_after', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/list/entry/after', array_shift( $callbacks ) ),
			remove_action( 'gravityview_list_entry_title_after', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/list/entry/title/after', array_shift( $callbacks ) ),
			remove_action( 'gravityview_list_entry_title_before', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/list/entry/title/before', array_shift( $callbacks ) ),
			remove_action( 'gravityview_list_entry_content_before', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/list/entry/content/before', array_shift( $callbacks ) ),
			remove_action( 'gravityview_list_entry_content_after', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/list/entry/content/after', array_shift( $callbacks ) ),
			remove_action( 'gravityview_list_entry_footer_after', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/list/entry/footer/after', array_shift( $callbacks ) ),
			remove_action( 'gravityview_list_entry_footer_before', array_shift( $callbacks ) ),
			remove_action( 'gravityview/template/list/entry/footer/before', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_render_after_label', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/field_label', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/field/label', array_shift( $callbacks ) ),
		);

		$this->assertNotContains( false, $removed );
		$this->assertEmpty( $callbacks );

		if ( $mode == 'single' ) {
			return $out;
		}

		$view->settings->update( array( 'hide_until_searched' => true ) );

		add_filter( 'gravitview_no_entries_text', $callbacks []= function( $text, $is_search ) {
			return "{{ gravitview_no_entries_text }}$text";
		}, 10, 2 );

		add_filter( 'gravityview/template/text/no_entries', $callbacks []= function( $text, $is_search, $context ) use ( $view, $test ) {
			$test->assertSame( $view, $context->view );
			return "{{ gravityview/template/text/no_entries }}$text";
		}, 10, 3 );

		$out = $renderer->render( $view );

		$this->assertContains( '{{ gravityview/template/text/no_entries }}{{ gravitview_no_entries_text }}', $out );

		$this->assertContains( "gv-container-{$view->ID}", $out );
		$this->assertContains( "gv-container-no-results", $out );

		$removed = array(
			remove_filter( 'gravitview_no_entries_text', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/text/no_entries', array_shift( $callbacks ) ),
		);

		$this->assertNotContains( false, $removed );
		$this->assertEmpty( $callbacks );
	}

	public function test_template_hooks_compat_list_single() {
		$view = null;
		$test = &$this;

		add_filter( 'gravityview_directory_link', $callbacks []= function( $link, $post_id ) use ( &$view, $test ) {
			$test->assertEquals( $view->ID, $post_id );
			return "$link{{ gravityview_directory_link }}";
		}, 10, 2 );

		add_filter( 'gravityview/view/links/directory', $callbacks []= function( $link, $context ) use ( &$view, $test ) {
			$test->assertSame( $view, $context->view );
			return "$link{{ gravityview/view/links/directory }}";
		}, 10, 2 );

		add_filter( 'gravityview_go_back_url', $callbacks []= function( $url ) use ( &$view, $test ) {
			$test->assertEquals( $view->ID, \GravityView_View::getInstance()->getViewId() );
			return "$url{{ gravityview_go_back_url }}";
		} );

		add_filter( 'gravityview/template/links/back/url', $callbacks []= function( $url, $context ) use ( &$view, $test ) {
			$test->assertSame( $view, $context->view );
			return "$url{{ gravityview/template/links/back/url }}";
		}, 10, 2 );

		add_filter( 'gravityview_go_back_label', $callbacks []= function( $label ) use ( &$view, $test ) {
			$test->assertEquals( $view->ID, \GravityView_View::getInstance()->getViewId() );
			return "$label{{ gravityview_go_back_label }}";
		} );

		add_filter( 'gravityview/template/links/back/label', $callbacks []= function( $label, $context ) use ( &$view, $test ) {
			$test->assertSame( $view, $context->view );
			return "$label{{ gravityview/template/links/back/label }}";
		}, 10, 2 );

		$out = $this->test_template_hooks_compat_list_directory( false, function( $_view, $_entry ) use ( &$view ) {
			$view = $_view;
		} );

		$this->assertContains( 'gv-list-single-container', $out );
		$this->assertContains( '%20gravityview_directory_link%20%20gravityview/view/links/directory%20', $out );

		remove_filter( 'gravityview_directory_link', $callbacks[0] );
		remove_filter( 'gravityview/view/links/directory', $callbacks[1] );
		remove_filter( 'gravityview_go_back_url', $callbacks[2] );
		remove_filter( 'gravityview/template/links/back/url', $callbacks[3] );
		remove_filter( 'gravityview_go_back_label', $callbacks[4] );
		remove_filter( 'gravityview/template/links/back/label', $callbacks[5] );
	}

	public function test_hide_empty_filters_compat() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		foreach ( range( 1, 5 ) as $i ) {
			$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
				'form_id' => $form['id'],
				'1' => microtime( true ),
				'2' => '', // Empty
			) );
		}

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'hide_empty' => false,
			),
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1',
						'label' => 'Microtime',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '2',
						'label' => 'Index',
					),
				),
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1',
						'label' => 'Microtime',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '2',
						'label' => 'Index',
					),
				),
			),
		) );
		$view = \GV\View::from_post( $post );

		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$test = &$this;

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_entry'] = $entry;

		/** Single table */
		$renderer = new \GV\Entry_Renderer();
		$this->assertContains( 'Index', $renderer->render( $entry, $view ) );
		$this->assertContains( 'Microtime', $renderer->render( $entry, $view ) );

		add_filter( 'gravityview/render/hide-empty-zone', $filter = function( $hide, $context ) use ( &$test, &$view ) {
			$test->assertSame( $context->view, $view );
			return true;
		}, 10, 2 );

		$this->assertNotContains( 'Index', $renderer->render( $entry, $view ) );
		$this->assertContains( 'Microtime', $renderer->render( $entry, $view ) );

		remove_filter( 'gravityview/render/hide-empty-zone', $filter );

		/** Directory table */

		gravityview()->request->returns['is_view'] = $view;

		$renderer = new \GV\View_Renderer();
		$this->assertContains( 'Index', $renderer->render( $view ) );
		$this->assertContains( 'Microtime', $renderer->render( $view ) );

		add_filter( 'gravityview/render/hide-empty-zone', $filter = function( $hide, $context ) use ( &$test, &$view ) {
			$test->assertSame( $context->view, $view );
			return true;
		}, 10, 2 );

		$id = sprintf( 'gv-field-%d-%d', $form['id'], 2 );
		$this->assertContains( "<td id=\"$id\" class=\"$id\"></td>", $renderer->render( $view ) );
		$this->assertContains( 'Microtime', $renderer->render( $view ) );

		remove_filter( 'gravityview/render/hide-empty-zone', $filter );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'preset_business_listings',
			'settings' => array(
				'hide_empty' => false,
			),
			'fields' => array(
				'directory_list-title' => array(
					wp_generate_password( 4, false ) => array( 'id' => '1', 'label' => 'Microtime' ),
					wp_generate_password( 4, false ) => array( 'id' => '2',	'label' => 'Index' )
				),
				'directory_list-subtitle' => array(
					wp_generate_password( 4, false ) => array( 'id' => '1', 'label' => 'Microtime' ),
					wp_generate_password( 4, false ) => array( 'id' => '2',	'label' => 'Index' )
				),
				'directory_list-image' => array(
					wp_generate_password( 4, false ) => array( 'id' => '1', 'label' => 'Microtime' ),
					wp_generate_password( 4, false ) => array( 'id' => '2',	'label' => 'Index' )
				),
				'directory_list-description' => array(
					wp_generate_password( 4, false ) => array( 'id' => '1', 'label' => 'Microtime' ),
					wp_generate_password( 4, false ) => array( 'id' => '2',	'label' => 'Index' )
				),
				'directory_list-footer-left' => array(
					wp_generate_password( 4, false ) => array( 'id' => '1', 'label' => 'Microtime' ),
					wp_generate_password( 4, false ) => array( 'id' => '2',	'label' => 'Index' )
				),
				'directory_list-footer-right' => array(
					wp_generate_password( 4, false ) => array( 'id' => '1', 'label' => 'Microtime' ),
					wp_generate_password( 4, false ) => array( 'id' => '2',	'label' => 'Index' )
				),

				'single_list-title' => array(
					wp_generate_password( 4, false ) => array( 'id' => '1', 'label' => 'Microtime' ),
					wp_generate_password( 4, false ) => array( 'id' => '2',	'label' => 'Index' )
				),
				'single_list-subtitle' => array(
					wp_generate_password( 4, false ) => array( 'id' => '1', 'label' => 'Microtime' ),
					wp_generate_password( 4, false ) => array( 'id' => '2',	'label' => 'Index' )
				),
				'single_list-image' => array(
					wp_generate_password( 4, false ) => array( 'id' => '1', 'label' => 'Microtime' ),
					wp_generate_password( 4, false ) => array( 'id' => '2',	'label' => 'Index' )
				),
				'single_list-description' => array(
					wp_generate_password( 4, false ) => array( 'id' => '1', 'label' => 'Microtime' ),
					wp_generate_password( 4, false ) => array( 'id' => '2',	'label' => 'Index' )
				),
				'single_list-footer-left' => array(
					wp_generate_password( 4, false ) => array( 'id' => '1', 'label' => 'Microtime' ),
					wp_generate_password( 4, false ) => array( 'id' => '2',	'label' => 'Index' )
				),
				'single_list-footer-right' => array(
					wp_generate_password( 4, false ) => array( 'id' => '1', 'label' => 'Microtime' ),
					wp_generate_password( 4, false ) => array( 'id' => '2',	'label' => 'Index' )
				),
			),
		) );
		$view = \GV\View::from_post( $post );

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_entry'] = $entry;

		/** Single list */
		$renderer = new \GV\Entry_Renderer();
		$this->assertContains( 'Index', $renderer->render( $entry, $view ) );
		$this->assertContains( 'Microtime', $renderer->render( $entry, $view ) );

		add_filter( 'gravityview/render/hide-empty-zone', $filter = function( $hide, $context ) use ( &$test, &$view ) {
			$test->assertSame( $context->view, $view );
			return true;
		}, 10, 2 );

		$this->assertNotContains( 'Index', $renderer->render( $entry, $view ) );
		$this->assertContains( 'Microtime', $renderer->render( $entry, $view ) );

		remove_filter( 'gravityview/render/hide-empty-zone', $filter );

		/** Directory list */

		gravityview()->request->returns['is_view'] = $view;

		$renderer = new \GV\View_Renderer();
		$this->assertContains( 'Index', $renderer->render( $view ) );
		$this->assertContains( 'Microtime', $renderer->render( $view ) );

		add_filter( 'gravityview/render/hide-empty-zone', $filter = function( $hide, $context ) use ( &$test, &$view ) {
			$test->assertSame( $context->view, $view );
			return true;
		}, 10, 2 );

		$id = sprintf( 'gv-field-%d-%d', $form['id'], 2 );
		$this->assertNotContains( 'Index', $renderer->render( $view ) );
		$this->assertContains( 'Microtime', $renderer->render( $view ) );

		remove_filter( 'gravityview/render/hide-empty-zone', $filter );
	}

	public function test_field_output_args_filter_compat() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		foreach ( range( 1, 5 ) as $i ) {
			$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
				'form_id' => $form['id'],
				'1' => microtime( true ),
				'2' => ':)',
			) );
		}

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'hide_empty' => false,
			),
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1',
						'label' => 'Microtime',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '2',
						'label' => 'Index',
					),
				),
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1',
						'label' => 'Microtime',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '2',
						'label' => 'Index',
					),
				),
			),
		) );
		$view = \GV\View::from_post( $post );

		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$test = &$this;

		gravityview()->request = new \GV\Mock_Request();

		/** Directory table */

		gravityview()->request->returns['is_view'] = $view;

		$callbacks = array();

		add_filter( 'gravityview/field_output/args', $callbacks []= function( $args, $passed_args, $context ) use ( $test, &$view ) {
			$test->assertSame( $context->view, $view );
			$args['value'] = 'spAce';
			$args['markup'] .= '[{{ value }}]';
			return $args;
		}, 10, 3 );

		add_filter( 'gravityview/template/field_output/context', $callbacks []= function( $context, $args, $passed_args ) use ( $test, &$view ) {
			$test->assertSame( $context->view, $view );
			$test->assertEquals( $args['value'], 'spAce' );
			$context->field->custom_class = 'sentinel-class';
			return $context;
		}, 10, 3 );

		add_filter( 'gravityview/field_output/pre_html', $callbacks []= function( $markup, $args, $context ) use ( $test, &$view ) {
			$test->assertSame( $context->view, $view );
			$test->assertEquals( $args['value'], 'spAce' );
			return str_replace( '}}', ']]', str_replace( '{{', '[[', "--{{ value }}--|$markup" ) );
		}, 10, 3 );

		add_filter( 'gravityview/field_output/open_tag', $callbacks []= function( $tag, $args, $context ) use ( $test, &$view ) {
			$test->assertSame( $context->view, $view );
			$test->assertEquals( $args['value'], 'spAce' );
			return '[[';
		}, 10, 3 );

		add_filter( 'gravityview/field_output/close_tag', $callbacks []= function( $tag, $args, $context ) use ( $test, &$view ) {
			$test->assertSame( $context->view, $view );
			$test->assertEquals( $args['value'], 'spAce' );
			return ']]';
		}, 10, 3 );

		add_filter( 'gravityview/field_output/context/value', $callbacks []= function( $value, $args, $context ) use ( $test, &$view ) {
			$test->assertSame( $context->view, $view );
			$test->assertEquals( $args['value'], $value );
			return "$value==value==";
		}, 10, 3 );

		add_filter( 'gravityview_field_output', $callbacks []= function( $html, $args, $context ) use ( $test, &$view ) {
			$test->assertSame( $context->view, $view );
			$test->assertEquals( $args['value'], 'spAce' );
			return "{{ gravityview_field_output }}$html";
		}, 10, 3 );

		add_filter( 'gravityview/field_output/html', $callbacks []= function( $html, $args, $context ) use ( $test, &$view ) {
			$test->assertSame( $context->view, $view );
			$test->assertEquals( $args['value'], 'spAce' );
			return "{{ gravityview/field_output/html }}$html";
		}, 10, 3 );

		$renderer = new \GV\View_Renderer();
		$out = $renderer->render( $view );

		$this->assertContains( '[spAce==value==]', $out );
		$this->assertContains( 'sentinel-class', $out );
		$this->assertContains( '--spAce==value==--', $out );
		$this->assertContains( '{{ gravityview_field_output }}', $out );
		$this->assertContains( '{{ gravityview/field_output/html }}', $out );

		$removed = array(
			remove_filter( 'gravityview/field_output/args', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/field_output/context', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/field_output/pre_html', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/field_output/open_tag', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/field_output/close_tag', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/field_output/context/value', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_field_output', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/field_output/html', array_shift( $callbacks ) ),
		);

		$this->assertNotContains( false, $removed );
		$this->assertEmpty( $callbacks );
	}

	public function test_field_value_filters_compat_generic() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$form = \GV\GF_Form::by_id( $form['id'] );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form->ID,
			'template_id' => 'table',
		) );
		$view = \GV\View::from_post( $post );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form->ID,
			'16' => 'hello'
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$request = new \GV\Mock_Request();
		$request->returns['is_view'] = $view;

		/** Simple */
		$template = new \GV\Field_HTML_Template( \GV\Internal_Field::by_id( 'id' ), $view, $view->form, $entry, $request );
		ob_start(); $template->render(); $output = ob_get_clean();
		$this->assertEquals( $entry->ID, $output );

		$template = new \GV\Field_HTML_Template( \GV\GF_Field::by_id( $form, '16' ), $view, $view->form, $entry, $request );
		ob_start(); $template->render(); $output = ob_get_clean();
		$this->assertEquals( '<p>hello</p>', trim( $output ) );

		$callbacks = array();
		$called = array();
		$test = &$this;

		/** Filtering */
		add_filter( 'gravityview_empty_value', $callbacks []= function( $value ) {
			return "$value{{ gravityview_empty_value }}";
		} );

		add_filter( 'gravityview/field/value/empty', $callbacks []= function( $value, $context ) use ( $test, &$view ) {
			$test->assertSame( $context->view, $view );
			return "$value{{ gravityview/field/value/empty }}";
		}, 10, 2 );

		add_filter( 'gravityview/template/field/context', $callbacks []= function( $context ) use ( $test, &$view, &$called ) {
			$test->assertSame( $context->view, $view );
			$called['gravityview/template/field/context'] = true;
			return $context;
		} );

		$template = new \GV\Field_HTML_Template( \GV\Internal_Field::by_id( 'id' ), $view, $view->form, $entry, $request );
		ob_start(); $template->render(); $output = ob_get_clean();
		$this->assertEquals( $entry->ID, $output );

		$template = new \GV\Field_HTML_Template( \GV\GF_Field::by_id( $form, '1.1' ), $view, $view->form, $entry, $request );
		ob_start(); $template->render(); $output = ob_get_clean();
		$this->assertEquals( '{{ gravityview_empty_value }}{{ gravityview/field/value/empty }}', $output );
		$this->assertTrue( $called['gravityview/template/field/context'] );

		add_filter( 'gravityview_field_entry_value_textarea_pre_link', $callbacks []= function( $output, $entry, $field, $field_compat ) {
			return "$output<< gravityview_field_entry_value_textarea_pre_link >>";
		}, 10, 4 );

		add_filter( 'gravityview_field_entry_value_pre_link', $callbacks []= function( $output, $entry, $field, $field_compat ) {
			return "$output<< gravityview_field_entry_value_pre_link >>";
		}, 10, 4 );

		add_filter( 'gravityview_field_entry_link', $callbacks []= function( $output, $permalink, $entry, $field ) {
			return "$output{{ gravityview_field_entry_link }}";
		}, 10, 4 );

		add_filter( 'gravityview/template/field/entry_link', $callbacks []= function( $output, $permalink, $context ) use ( &$test, &$view ) {
			$test->assertSame( $context->view, $view );
			return "$output==gravityview/template/field/entry_link==";
		}, 10, 4 );

		add_filter( 'gravityview_field_entry_value_textarea', $callbacks []= function( $output, $entry, $field, $field_compat ) {
			return "$output{{ gravityview_field_entry_value_textarea }}";
		}, 10, 4 );

		add_filter( 'gravityview_field_entry_value', $callbacks []= function( $output, $entry, $field, $field_compat ) {
			return "$output{{ gravityview_field_entry_value }}";
		}, 10, 4 );

		add_filter( 'gravityview/template/field/textarea/output', $callbacks []= function( $output, $context ) use ( &$test, &$view ) {
			$test->assertSame( $context->view, $view );
			return "$output(__gravityview/template/field/textarea/output__)";
		}, 10, 2 );

		add_filter( 'gravityview/template/field/output', $callbacks []= function( $output, $context ) use ( &$test, &$view ) {
			$test->assertSame( $context->view, $view );
			return "$output(__gravityview/template/field/output__)";
		}, 10, 2 );

		$field = \GV\GF_Field::by_id( $form, '16' );
		$field->show_as_link = true;
		$template = new \GV\Field_HTML_Template( $field, $view, $view->form, $entry, $request );
		ob_start(); $template->render(); $output = ob_get_clean();
		$this->assertContains( "<p>hello</p>\n<< gravityview_field_entry_value_textarea_pre_link >><< gravityview_field_entry_value_pre_link >>", $output );
		$this->assertContains( 'pre_link >></a>{{ gravityview_field_entry_link }}==gravityview/template/field/entry_link==', $output );
		$this->assertContains( '/entry_link=={{ gravityview_field_entry_value_textarea }}{{ gravityview_field_entry_value }}', $output );
		$this->assertContains( 'field_entry_value }}(__gravityview/template/field/textarea/output__)(__gravityview/template/field/output__)', $output );

		$removed = array(
			remove_filter( 'gravityview_empty_value', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/field/value/empty', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/field/context', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_field_entry_value_textarea_pre_link', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_field_entry_value_pre_link', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_field_entry_link', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/field/entry_link', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_field_entry_value_textarea', array_shift( $callbacks ) ),
			remove_filter( 'gravityview_field_entry_value', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/field/textarea/output', array_shift( $callbacks ) ),
			remove_filter( 'gravityview/template/field/output', array_shift( $callbacks ) ),
		);

		$this->assertNotContains( false, $removed );
		$this->assertEmpty( $callbacks );
	}

	public function test_field_value_filters_compat_specific() {
	}

	public function test_basic_table_joins() {
		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_JOINS ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$customers = $this->factory->form->import_and_get( 'simple.json' );
		$orders = $this->factory->form->import_and_get( 'complete.json' );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Ann',
			'2' => 1,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Bob',
			'2' => 2,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Carol',
			'2' => 3,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Derek',
			'2' => 4,
		) );

		$entries = array();

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 4,
			'16' => 'Shoes',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 4,
			'16' => 'Bacon',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 2,
			'16' => 'Book',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 3,
			'16' => 'Keyboard',
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $orders['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $orders['id'],
						'id' => 'id',
						'label' => 'Order ID',
					),
					wp_generate_password( 4, false ) => array(
						// Test without explicit form_id set
						'id' => '16',
						'label' => 'Item',
					),
					wp_generate_password( 4, false ) => array(
						'form_id' => $customers['id'],
						'id' => '1',
						'label' => 'Customer Name',
					),
				),
			),
			'joins' => array(
				array( $orders['id'], '9', $customers['id'], '2' ),
			),
		) );
		$view = \GV\View::from_post( $view );

		$renderer = new \GV\View_Renderer();

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;

		$out = $renderer->render( $view );
		$result = preg_replace( '/\s+/', '', wp_strip_all_tags( $out ) );

		$expected = array(
			'OrderIDItemCustomerName',
			$entries[3]['id'], 'Keyboard', 'Carol',
			$entries[2]['id'], 'Book', 'Bob',
			$entries[1]['id'], 'Bacon', 'Derek',
			$entries[0]['id'], 'Shoes', 'Derek',
			'OrderIDItemCustomerName',
		);

		$this->assertEquals( implode( '', $expected ), $result );
	}

	public function test_basic_list_joins() {
		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_JOINS ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$customers = $this->factory->form->import_and_get( 'simple.json' );
		$orders = $this->factory->form->import_and_get( 'complete.json' );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Ann',
			'2' => 1,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Bob',
			'2' => 2,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Carol',
			'2' => 3,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Derek',
			'2' => 4,
		) );

		$entries = array();

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 4,
			'16' => 'Shoes',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 4,
			'16' => 'Bacon',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 2,
			'16' => 'Book',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 3,
			'16' => 'Keyboard',
		) );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $orders['id'],
			'template_id' => 'preset_business_listings',
			'fields' => array(
				'directory_list-title' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '16',
						'label' => 'Item',
					),
				),
				'directory_list-subtitle' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $orders['id'],
						'id' => 'id',
						'label' => 'Order ID',
					),
				),
				'directory_list-description' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $customers['id'],
						'id' => '1',
						'label' => 'Customer Name',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'custom_content',
						'content' => 'Thank you for your purchase!',
					),
				),
			),
			'joins' => array(
				array( $orders['id'], '9', $customers['id'], '2' ),
			),
		) );
		$view = \GV\View::from_post( $view );

		$renderer = new \GV\View_Renderer();

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;

		$out = $renderer->render( $view );
		$result = preg_replace( '/\s+/', '', wp_strip_all_tags( $out ) );

		$expected = array(
			'Item', 'Keyboard', 'OrderID', $entries[3]['id'], 'CustomerName', 'Carol',
			'Item', 'Book', 'OrderID', $entries[2]['id'], 'CustomerName', 'Bob',
			'Item', 'Bacon', 'OrderID', $entries[1]['id'], 'CustomerName', 'Derek',
			'Item', 'Shoes', 'OrderID', $entries[0]['id'], 'CustomerName', 'Derek',
		);

		$this->assertEquals( implode( '', $expected ), $result );
	}

	public function test_legacy_template_table_joins() {
		$this->_reset_context();

		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_JOINS ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$customers = $this->factory->form->import_and_get( 'simple.json' );
		$orders = $this->factory->form->import_and_get( 'complete.json' );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Ann',
			'2' => 1,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Bob',
			'2' => 2,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Carol',
			'2' => 3,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Derek',
			'2' => 4,
		) );

		$entries = array();

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 4,
			'16' => 'Shoes',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 4,
			'16' => 'Bacon',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 2,
			'16' => 'Book',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 3,
			'16' => 'Keyboard',
		) );

		global $post;
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $orders['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $orders['id'],
						'id' => 'id',
						'label' => 'Order ID',
					),
					wp_generate_password( 4, false ) => array(
						// Test without explicit form_id set
						'id' => '16',
						'label' => 'Item',
					),
					wp_generate_password( 4, false ) => array(
						'form_id' => $customers['id'],
						'id' => '1',
						'label' => 'Customer Name',
					),
				),
			),
			'joins' => array(
				array( $orders['id'], '9', $customers['id'], '2' ),
			),
		) );
		$view = \GV\View::from_post( $post );

		$renderer = new \GV\Legacy_Override_Template( $view );

		$out = $renderer->render( 'table' );
		$result = preg_replace( '/\s+/', '', wp_strip_all_tags( $out ) );

		$expected = array(
			'OrderIDItemCustomerName',
			$entries[3]['id'], 'Keyboard', 'Carol',
			$entries[2]['id'], 'Book', 'Bob',
			$entries[1]['id'], 'Bacon', 'Derek',
			$entries[0]['id'], 'Shoes', 'Derek',
			'OrderIDItemCustomerName',
		);

		$this->assertEquals( implode( '', $expected ), $result );

		$this->_reset_context();
	}

	public function test_legacy_template_list_joins() {
		$this->_reset_context();

		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_JOINS ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$customers = $this->factory->form->import_and_get( 'simple.json' );
		$orders = $this->factory->form->import_and_get( 'complete.json' );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Ann',
			'2' => 1,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Bob',
			'2' => 2,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Carol',
			'2' => 3,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Derek',
			'2' => 4,
		) );

		$entries = array();

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 4,
			'16' => 'Shoes',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 4,
			'16' => 'Bacon',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 2,
			'16' => 'Book',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 3,
			'16' => 'Keyboard',
		) );

		global $post;
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $orders['id'],
			'template_id' => 'preset_business_listings',
			'fields' => array(
				'directory_list-title' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '16',
						'label' => 'Item',
					),
				),
				'directory_list-subtitle' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $orders['id'],
						'id' => 'id',
						'label' => 'Order ID',
					),
				),
				'directory_list-description' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $customers['id'],
						'id' => '1',
						'label' => 'Customer Name',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'custom_content',
						'content' => 'Thank you for your purchase!',
					),
				),
			),
			'joins' => array(
				array( $orders['id'], '9', $customers['id'], '2' ),
			),
		) );
		$view = \GV\View::from_post( $post );

		$renderer = new \GV\View_Renderer();

		$renderer = new \GV\Legacy_Override_Template( $view );

		$out = $renderer->render( 'list' );
		$result = preg_replace( '/\s+/', '', wp_strip_all_tags( $out ) );

		$expected = array(
			'Item', 'Keyboard', 'OrderID', $entries[3]['id'], 'CustomerName', 'Carol',
			'Item', 'Book', 'OrderID', $entries[2]['id'], 'CustomerName', 'Bob',
			'Item', 'Bacon', 'OrderID', $entries[1]['id'], 'CustomerName', 'Derek',
			'Item', 'Shoes', 'OrderID', $entries[0]['id'], 'CustomerName', 'Derek',
		);

		$this->assertEquals( implode( '', $expected ), $result );

		$this->_reset_context();
	}

	public function test_oembed_in_custom_content() {
		$this->_reset_context();

		$form = $this->factory->form->import_and_get( 'simple.json' );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'content' => 'You are here.',
					),
				),
			)
		) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $post );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'content' => $entry->get_permalink( $view ),
						'oembed' => true,
					),
				),
			)
		) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$view = \GV\View::from_post( $post );

		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_entry'] = $entry;

		$renderer = new \GV\Entry_Renderer();

		$this->assertContains( 'You are here.', $renderer->render( $entry, $view ) );

		$this->_reset_context();
	}
}

class GVFutureTest_Extension_Test_BC extends GravityView_Extension {
	protected $_title = 'Legacy Test Extension';
	protected $_version = '9.1.1-BC';
	protected $_item_id = 911;
	protected $_min_gravityview_version = '2.0-dev';
	protected $_min_php_version = '5.3';
}

class GVFutureTest_Extension_Test extends \GV\Extension {
	protected $_title = 'New Test Extension';
	protected $_version = '9.2.1-BC';
	protected $_item_id = 911;
	protected $_min_gravityview_version = '3.0';
	protected $_min_php_version = '7.3.0';

	protected function tab_settings() {
		return array(
			'id' => 'test_settings',
		);
	}
}

class GVFutureTest_Widget_Test_BC extends GravityView_Widget {
}

class GVFutureTest_Widget_Test extends \GV\Widget {
	public function render_frontend( $widget_args, $content = '', $context = '' ) {
		if ( ! $this->pre_render_frontend() ) {
			return;
		}
		?>
			<strong class="floaty">GravityView<?php echo \GV\Utils::get( $widget_args, 'test' ); ?></strong>
		<?php
	}
}
