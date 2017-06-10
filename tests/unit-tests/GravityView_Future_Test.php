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
		\GV\Mocks\Legacy_Context::reset();
		gravityview()->request = new \GV\Frontend_Request();
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
	 * @covers \GV\Entry::get_permalink()
	 */
	public function test_entry_get_permalink() {
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

		$this->_reset_context();
	}

	/**
	 * @covers \GV\View::offsetExists()
	 * @covers \GV\View::offsetSet()
	 * @covers \GV\View::offsetUnset()
	 * @covers \GV\View::offsetGet()
	 * @covers \GV\View::as_data()
	 */
	public function test_view_data_compat() {
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
	 * Stub \GravityView_View_Data::get_views() usage around the codebase.
	 *
	 * @covers \GravityView_Admin_Bar::add_links()
	 * @covers \GravityView_Admin_Bar::add_edit_view_and_form_link()
	 * @covers \GravityView_frontend::insert_view_in_content()
	 * @covers \GravityView_frontend::add_scripts_and_styles()
	 * @covers \GravityView_frontend::render_view()
	 */
	public function test_data_get_views() {
		if ( defined( 'GRAVITYVIEW_FUTURE_CORE_ALPHA_LOADED' ) ) {
			$this->markTestSkipped( 'The alpha future does no longer care' );
		}

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
		$entry = $this->factory->entry->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$post = $this->factory->post->create_and_get( array( 'post_content' => sprintf( '[gravityview id="%d"]', $view->ID ) ) );

		$embed_content = sprintf( "\n%s\n", add_query_arg( 'entry', $entry['id'], get_permalink( $post->ID ) ) );
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
		$this->_reset_context();
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
	 * @covers \GV\Shortcode::callback()
	 * @expectedException \BadMethodCallException
	 */
	public function test_shortcode_do_not_implemented() {
		\GV\Shortcode::callback( array( 'id' => 1 ) );
	}

	/**
	 * @covers \GV\Shortcode::parse()
	 * @covers \GravityView_View_Data::parse_post_content()
	 */
	public function test_shortcode_parse() {
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
	 * @covers \GV\Frontend_Request::is_search()
	 */
	function test_frontend_request_is_search() {
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

		/** Custom label override and merge tags. */
		$field->update_configuration( array( 'custom_label' => 'This is {entry_id}' ) );
		$this->assertEquals( 'This is ' . $entry->ID, $field->get_label( $view, $view->form, $entry ) );

		/** Internal fields. */
		$field = \GV\Internal_Field::by_id( 'id' );
		$field->update_configuration( array( 'label' => 'ID' ) );
		$this->assertEquals( 'ID', $field->get_label() );

		/** Custom label override and merge tags. */
		$field->update_configuration( array( 'custom_label' => 'This is {entry_id}' ) );
		$this->assertEquals( 'This is ' . $entry->ID, $field->get_label( $view, $view->form, $entry ) );

		$this->_reset_context();
	}

	/**
	 * @covers \GV\Mocks\GravityView_API_field_value()
	 * @covers \GravityView_API::field_value()
	 */
	public function test_field_value_compat() {
		$this->_reset_context();

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

		$GLOBALS['GravityView_API_field_value_override'] = true;
		$this->assertEquals( 'set all the fields!', GravityView_API::field_value( $entry->as_entry(), $field_settings ) );
		unset( $GLOBALS['GravityView_API_field_value_override'] );
		$this->assertEquals( 'set all the fields!', GravityView_API::field_value( $entry->as_entry(), $field_settings ) );

		$field_settings = array(
			'id' => 'custom',
			'content' => 'this is it',
			'wpautop' => true,
		);
		$GLOBALS['GravityView_API_field_value_override'] = true;
		$this->assertEquals( "<p>this is it</p>\n", GravityView_API::field_value( $entry->as_entry(), $field_settings ) );
		unset( $GLOBALS['GravityView_API_field_value_override'] );
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
		$GLOBALS['GravityView_API_field_value_override'] = true;
		$expected = GravityView_API::field_value( $entry->as_entry(), $field_settings );
		unset( $GLOBALS['GravityView_API_field_value_override'] );
		$this->assertEquals( "$expected", GravityView_API::field_value( $entry->as_entry(), $field_settings ) );

		$this->_reset_context();
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
	 */
	public function test_frontend_view_renderer() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form['id'],
			/** Fields, more complex entries may have hundreds of fields defined in the JSON file. */
			'1' => 'this is field one',
			'2' => 102,
		) );
		$post = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $post );

		$renderer = new \GV\View_Renderer();

		/** Password protection. */
		wp_update_post( array( 'ID' => $view->ID, 'post_password' => '123' ) );
		$this->assertContains( 'content is password protected', $renderer->render( $view, new \GV\Frontend_Request() ) );
		wp_update_post( array( 'ID' => $view->ID, 'post_password' => '' ) );
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

		/** An unkown field. Test some filters. */
		$field = \GV\Internal_Field::by_id( 'this-does-not-exist' );

		add_filter( 'gravityview_empty_value', function( $value ) {
			return 'sentinel-0';
		} );
		$this->assertEquals( 'sentinel-0', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview/field/value/empty', function( $value ) {
			return 'sentinel-1';
		} );
		$this->assertEquals( 'sentinel-1', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview/template/field/data', function( $data ) {
			$data['value'] = $data['display_value'] = 'This <script> is it';
			return $data;
		} );
		$this->assertEquals( 'This &lt;script&gt; is it', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview_field_entry_value_this-does-not-exist_pre_link', function( $output ) {
			return 'Yes, it does!! <script>careful</script>';
		} );
		$this->assertEquals( 'Yes, it does!! <script>careful</script>', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview_field_entry_value_this-does-not-exist', function( $output ) {
			return 'No, it doesn\'t...';
		} );
		$this->assertEquals( 'No, it doesn\'t...', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview_field_entry_value', function( $output ) {
			return 'I paid for an argument, this is not an argument!';
		} );
		$this->assertEquals( 'I paid for an argument, this is not an argument!', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview/field/this-does-not-exist/output', function( $output ) {
			return '....Yes, it is...';
		} );
		$this->assertEquals( '....Yes, it is...', $renderer->render( $field, $view, $form, $entry, $request ) );

		add_filter( 'gravityview/field/output', function( $output ) {
			return '....... ....No, it is not!';
		} );
		$this->assertEquals( '....... ....No, it is not!', $renderer->render( $field, $view, $form, $entry, $request ) );

		remove_all_filters( 'gravityview_empty_value' );
		remove_all_filters( 'gravityview/field/value/empty' );
		remove_all_filters( 'gravityview/template/field/data' );
		remove_all_filters( 'gravityview_field_entry_value_this-does-not-exist_pre_link' );
		remove_all_filters( 'gravityview_field_entry_value_this-does-not-exist' );
		remove_all_filters( 'gravityview_field_entry_value' );
		remove_all_filters( 'gravityview/field/this-does-not-exist/output' );
		remove_all_filters( 'gravityview/field/output' );

		/** Test linking pre/post filtering with deprecated and new filters. */
		add_filter( 'gravityview_field_entry_value_custom_pre_link', function( $output ) {
			return $output . ', please';
		} );

		add_filter( 'gravityview_field_entry_value_custom', function( $output ) {
			return $output . ' now!';
		} );

		add_filter( 'gravityview/field/output', function( $output ) {
			return 'Yo, ' . $output;
		}, 4 );

		add_filter( 'gravityview/field/output', function( $output ) {
			return 'Hi! ' . $output;
		} );

		$field = \GV\Internal_Field::by_id( 'custom' );
		$field->content = 'click me now';
		$field->show_as_link = true;
		$field->new_window = true;

		add_filter( 'gravityview_field_entry_link', function( $html ) {
			return 'Click: ' . $html;
		} );

		add_filter( 'gravityview/entry/permalink', function( $permalink ) {
			return 'ha';
		} );

		$this->assertEquals( 'Hi! Click: <a href="http://ha" rel="noopener noreferrer" target="_blank">Yo, click me now, please</a> now!', $renderer->render( $field, $view, null, $entry, $request ) );

		remove_all_filters( 'gravityview_field_entry_value_custom_pre_link' );
		remove_all_filters( 'gravityview_field_entry_value_custom' );
		remove_all_filters( 'gravityview_field_entry_link' );
		remove_all_filters( 'gravityview/entry/permalink' );
		remove_all_filters( 'gravityview/field/output' );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_address() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_checkbox() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_name() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_number() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_other_entries() {
		$this->_reset_context();

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

		$field->update_configuration( array( 'link_format' => 'Entry #{entry_id}', 'page_size' => 1, 'after_link' => 'wut' ) );
		$expected = sprintf( '<ul><li><a href="%s">Entry #%d</a><div>wut</div></li></ul>', esc_attr( $entry_3->get_permalink( $view, $request ) ), $entry_3->ID );
		$this->assertEquals( $expected, $renderer->render( $field, $view, null, $entry, $request ) );

		unset( $GLOBALS['post'] );

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_source_url() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_list() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_phone() {
		$this->_reset_context();

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

		$this->assertEquals( '<a href="tel:93%2043A99-392&lt;script&gt;1&lt;/script&gt;">93 43A99-392&lt;script&gt;1&lt;/script&gt;</a>', $renderer->render( $field, $view, $form, $entry, $request ) );

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_radio() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_select() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_textarea() {
		$this->_reset_context();

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'16' => 'okay <so> {entry_id} what happens [gvtest_shortcode_t1] here? <script>huh()</script> http://gravityview.co/',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '16' );

		$expected = '<p>okay &lt;so&gt; {entry_id} what happens [gvtest_shortcode_t1] here? &lt;script&gt;huh()&lt;/script&gt; http://gravityview.co/</p>' . "\n";
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'trim_words' => 4 ) );
		/** This is a bad test, no entry link can be determined and the space is trimmed from the &hellip; */
		$this->assertEquals( '<p>okay &lt;so&gt; {entry_id} what&hellip;</p>' . "\n", $renderer->render( $field, $view, $form, $entry, $request ) );
		GravityView_View::getInstance()->setViewId( $view->ID );
		$expected = sprintf( '<p>okay &lt;so&gt; {entry_id} what<a href="%s"> &hellip;</a></p>' . "\n", esc_attr( $entry->get_permalink( $view, $request ) ) );
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'new_window' => true ) );
		$expected = sprintf( '<p>okay &lt;so&gt; {entry_id} what<a href="%s" target="_blank"> &hellip;</a></p>' . "\n", esc_attr( $entry->get_permalink( $view, $request ) ) );
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'trim_words' => false, 'make_clickable' => true, 'new_window' => false ) );
		$expected = '<p>okay &lt;so&gt; {entry_id} what happens [gvtest_shortcode_t1] here? &lt;script&gt;huh()&lt;/script&gt; <a href="http://gravityview.co/" rel="nofollow">http://gravityview.co/</a></p>' . "\n";
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'new_window' => true ) );
		$expected = '<p>okay &lt;so&gt; {entry_id} what happens [gvtest_shortcode_t1] here? &lt;script&gt;huh()&lt;/script&gt; <a href="http://gravityview.co/" rel="nofollow" target="_blank">http://gravityview.co/</a></p>' . "\n";
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_hidden() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_password() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_time() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_website() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_created_by() {
		$this->_reset_context();

		$user = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'display_name' => 'John John',
		) );
		update_user_meta( $user, 'custom_field_1', '<oh onload="!">okay</oh>' );
		$user = get_user_by( 'ID', $user );

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_date_created() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_date() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_email() {
		$this->_reset_context();

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

		add_filter( 'gravityview/fields/email/prevent_encrypt', '__return_true' );

		$field->update_configuration( array( 'emailencrypt' => true ) );
		$this->assertEquals( 'support@gravityview.co', $renderer->render( $field, $view, $form, $entry, $request ) );

		remove_filter( 'gravityview/fields/email/prevent_encrypt', '__return_true' );

		$this->assertContains( 'Email hidden; Javascript is required.', $renderer->render( $field, $view, $form, $entry, $request ) );
		$this->assertNotContains( 'support@gravityview.co', $renderer->render( $field, $view, $form, $entry, $request ) );

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_entry_approval() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_entry_link() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_delete_link() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_edit_link() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_notes() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_custom() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_fileupload() {
		$this->_reset_context();

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'5' => json_encode( array( 'https://one.jpg', 'https://two.mp3' ) ),
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();


		$field = \GV\GF_Field::by_id( $form, '5' );

		/** Still haunted by good ol' global state :( */
		GravityView_View::getInstance()->setCurrentField( array(
			'field' => $field->field,
			'field_settings' => $field->as_configuration(),
			'entry' => $entry->as_entry(),
			'field_value' => $field->get_value( $view, $form, $entry ),
		) );

		$expected = "<ul class='gv-field-file-uploads gv-field-{$form->ID}-5'>";
		$expected .= '<li><a href="http://one.jpg" rel="noopener noreferrer" target="_blank"><img src="http://one.jpg" width="250" class="gv-image gv-field-id-5" /></a></li>';
		$expected .= '<li><!--[if lt IE 9]><script>document.createElement(\'audio\');</script><![endif]-->' . "\n" . '<audio class="wp-audio-shortcode gv-audio gv-field-id-5" id="audio-0-1" preload="none" style="width: 100%;" controls="controls"><source type="audio/mpeg" src="http://two.mp3?_=1" /><a href="http://two.mp3">http://two.mp3</a></audio></li>';
		$expected .= '</ul>';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		/** No fancy rendering, just links, please? */

		$field->update_configuration( array( 'link_to_file' => true ) );

		/** Still haunted by good ol' global state :( */
		GravityView_View::getInstance()->setCurrentField( array(
			'field' => $field->field,
			'field_settings' => $field->as_configuration(),
			'entry' => $entry->as_entry(),
			'field_value' => $field->get_value( $view, $form, $entry ),
		) );

		$expected = "<ul class='gv-field-file-uploads gv-field-{$form->ID}-5'>";
		$expected .= '<li><a href="http://one.jpg" rel="noopener noreferrer" target="_blank">one.jpg</a></li><li><a href="http://two.mp3" rel="noopener noreferrer" target="_blank">two.mp3</a></li></ul>';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_html() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_section() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_post() {
		$this->_reset_context();

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

		$wpdb->insert( GFFormsModel::get_lead_details_table_name(), array(
			'lead_id' => $entry['id'], 'form_id' => $form['id'],
			'field_number' => '24', 'value' => $image
		) );

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
			sprintf( '<a href="%s"></a>, ', esc_url( get_term_link( get_term_by( 'name', '<script>1</script>', 'post_tag' ), 'post_tag' ) ) ),
			sprintf( '<a href="%s">hi</a>, ', esc_url( get_term_link( get_term_by( 'name', '<b>hi</b>', 'post_tag' ), 'post_tag' ) ) ),
			sprintf( '<a href="%s">[gvtest_shortcode_p1]</a>', esc_url( get_term_link( '[gvtest_shortcode_p1]', 'post_tag' ) ) ),
		);
		foreach ( $expected as $_expected ) {
			$this->assertContains( $_expected, $renderer->render( $field, $view, $form, $entry, $request ) );
		}
		$this->assertEquals( strlen( implode( '', $expected ) ), strlen( $renderer->render( $field, $view, $form, $entry, $request ) ) );

		$field->update_configuration( array( 'dynamic_data' => true ) );
		$expected = array(
			sprintf( '<a href="%s" rel="tag">[gvtest_shortcode_p1]</a>, ', esc_url( get_term_link( '[gvtest_shortcode_p1]', 'post_tag' ) ) ),
			sprintf( '<a href="%s" rel="tag">hi</a>, ', esc_url( get_term_link( get_term_by( 'name', '<b>hi</b>', 'post_tag' ), 'post_tag' ) ) ),
			sprintf( '<a href="%s" rel="tag">more</a>, ', esc_url( get_term_link( 'more', 'post_tag' ) ) ),
			sprintf( '<a href="%s" rel="tag">oh no</a>, ', esc_url( get_term_link( 'oh no', 'post_tag' ) ) ),
			sprintf( '<a href="%s" rel="tag">some</a>, ', esc_url( get_term_link( 'some', 'post_tag' ) ) ),
			sprintf( '<a href="%s" rel="tag">tag 1</a>', esc_url( get_term_link( 'tag 1', 'post_tag' ) ) ),
		);
		foreach ( $expected as $_expected ) {
			$this->assertContains( $_expected, $renderer->render( $field, $view, $form, $entry, $request ) );
		}
		$this->assertEquals( strlen( implode( '', $expected ) ), strlen( $renderer->render( $field, $view, $form, $entry, $request ) ) );

		$field->update_configuration( array( 'link_to_term' => false ) );
		$expected = '[gvtest_shortcode_p1], hi, more, oh no, some, tag 1';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

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
			sprintf( '<a href="%s">Category 1</a>', esc_url( get_term_link( $cat_1 ) ) ),
			sprintf( '<a href="%s">Category 6 [gvtest_shortcode_p1] 5</a>', esc_url( get_term_link( $cat_2 ) ) ),
		);
		foreach ( $expected as $_expected ) {
			$this->assertContains( $_expected, $renderer->render( $field, $view, $form, $entry, $request ) );
		}
		$this->assertEquals( strlen( implode( ', ', $expected ) ), strlen( $renderer->render( $field, $view, $form, $entry, $request ) ) );

		$field->update_configuration( array( 'dynamic_data' => true ) );
		$expected = array(
			sprintf( '<a href="%s" rel="tag">Category 1</a>', esc_url( get_term_link( $cat_1 ) ) ),
			sprintf( '<a href="%s" rel="tag">Category 6 [gvtest_shortcode_p1] 5</a>', esc_url( get_term_link( $cat_2 ) ) ),
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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_product() {
		$this->_reset_context();

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

		$this->_reset_context();
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_payment() {
		$this->_reset_context();

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

		$this->_reset_context();
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
	 * @covers \GV\Frontend_Request::output()
	 * @covers \GV\Frontend_Request::is_view()
	 */
	public function test_frontend_request() {
		$request = new \GV\Frontend_Request();

		global $post;

		$this->assertEquals( $request->output( 'just some regular content' ), 'just some regular content' );

		$_this = &$this;
		add_filter( 'gravityview/request/output/views', function( $views ) use ( $_this ) {
			$_this->assertCount( 0, $views->all() );
			return $views;
		} );

		$this->assertFalse( $request->is_view() );
		$request->output( '' );

		remove_all_filters( 'gravityview/request/output/views' );

		$view = $this->factory->view->create_and_get();
		$with_shortcode = $this->factory->post->create_and_get( array(
			'post_content' => '[gravityview id="' . $view->ID . '"]'
		) );

		add_filter( 'gravityview/request/output/views', function( $views ) use ( $_this, $view ) {
			$_this->assertCount( 1, $views->all() );
			$_this->assertEquals( $view->ID, $views->last()->ID );
			return $views;
		} );

		$post = $with_shortcode;
		$this->assertFalse( $request->is_view() );
		$request->output( '' );

		$post = $view;
		$this->assertTrue( $request->is_view() );
		$request->output( '' );

		$post = null;
		$request->output( '[gravityview id="' . $view->ID . '"]' );

		remove_all_filters( 'gravityview/request/output/views' );

		/** A post password is required. */
		$post = get_post( wp_update_post( array( 'ID' => $view->ID, 'post_password' => '123' ) ) );
		$this->assertEquals( $request->output( 'sentinel_1' ), 'sentinel_1' );
		$post = get_post( wp_update_post( array( 'ID' => $view->ID, 'post_password' => null ) ) );

		/** Not directly accessible. */
		add_filter( 'gravityview_direct_access', '__return_false' );
		$this->assertContains( 'not allowed to view this', $request->output( '' ) );
		remove_all_filters( 'gravityview_direct_access' );

		add_filter( 'gravityview/request/output/direct', '__return_false' );
		$this->assertContains( 'not allowed to view this', $request->output( '' ) );
		remove_all_filters( 'gravityview/request/output/direct' );

		/** Embed-only */
		add_filter( 'gravityview/request/output/views', function( $views ) use ( $_this, $view ) {
			$views->get( $view->ID )->settings->set( 'embed_only', true );
			return $views;
		} );
		$this->assertContains( 'not allowed to view this', $request->output( '' ) );
		remove_all_filters( 'gravityview/request/output/views' );

		/** A broken view with no form. */
		delete_post_meta( $post->ID, '_gravityview_form_id' );
		$this->assertEquals( $request->output( 'sentinel' ), 'sentinel' );

		$administrator = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => 'administrator' )
		);
		wp_set_current_user( $administrator );
		$this->assertContains( 'View is not configured properly', $request->output( '' ) );

		$this->_reset_context();
	}

	/**
	 * @covers \GV\View_Table_Template::the_columns()
	 * @covers \GV\View_Table_Template::the_entry()
	 */
	public function test_view_table() {
		$this->_reset_context();

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

		$template = new \GV\View_Table_Template( $view, $view->form->entries, new \GV\Frontend_Request() );

		/** Test the column ouput. */
		$expected = sprintf( '<th id="gv-field-%1$d-1" class="gv-field-%1$d-1"><span class="gv-field-label">A Text Field</span></th><th id="gv-field-%1$d-2" class="gv-field-%1$d-2"><span class="gv-field-label">This is field two</span></th>', $view->form->ID );

		ob_start(); $template->the_columns();
		$this->assertEquals( $expected, ob_get_clean() );

		$entries = $view->form->entries->all();

		$attributes = array( 'class' => 'hello-button', 'data-row' => '1' );

		add_filter( 'gravityview/entry/row/attributes', function( $attributes ) {
			$attributes['onclick'] = 'alert("hello :)");';
			return $attributes;
		} );

		ob_start(); $template->the_entry( $entries[1], $attributes );
		$output = ob_get_clean();
		$this->assertContains( '<tr class="hello-button" data-row="1" onclick="alert(&quot;hello :)&quot;);">', $output );

		$this->_reset_context();
	}

	/**
	 * @covers \GV\Mocks\GravityView_API_field_label()
	 * @covers \GravityView_API::field_label()
	 */
	public function test_field_label_compat() {
		$this->_reset_context();

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
			'show_label' => false,
			'label' => 'what?',
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
		$this->assertEquals( 'A Text Field', $expected );

		$field_settings = array(
			'id' => '1',
			'show_label' => true,
			'label' => 'what?',
		);

		$GLOBALS['GravityView_API_field_label_override'] = true;
		$expected = GravityView_API::field_label( $field_settings, $entry->as_entry() /** no force */ );
		unset( $GLOBALS['GravityView_API_field_label_override'] );
		$this->assertEquals( $expected, GravityView_API::field_label( $field_settings, $entry->as_entry() /** no force */ ) );
		$this->assertEquals( 'A Text Field', $expected );

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

		$this->_reset_context();
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

		\GV\Mocks\Legacy_Context::push( array(
			'view' => $view,
		) );

		$this->assertEquals( array(
			'\GravityView_View::atts' => $view->settings->as_atts(),
			'\GravityView_View::view_id' => $view->ID,
			'\GravityView_View::back_link_label' => '',
			'\GravityView_View::form' => $view->form->form,
			'\GravityView_View::form_id' => $view->form->ID,
			'\GravityView_View::entries' => array(),
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
}
