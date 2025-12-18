<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group gvapi
 */
class GravityView_API_Test extends GV_UnitTestCase {

	/**
	 * @var int
	 */
	var $form_id = 0;

	/**
	 * @var array GF Form array
	 */
	var $form = array();

	/**
	 * @var int
	 */
	var $entry_id = 0;

	/**
	 * @var array GF Entry array
	 */
	var $entry = array();

	var $is_set_up = false;

	function setUp() : void {

		parent::setUp();

		$this->form = GV_Unit_Tests_Bootstrap::instance()->get_form();
		$this->form_id = GV_Unit_Tests_Bootstrap::instance()->get_form_id();

		$this->entry = GV_Unit_Tests_Bootstrap::instance()->get_entry();
		$this->entry_id = GV_Unit_Tests_Bootstrap::instance()->get_entry_id();

	}

	/**
	 * @covers ::gv_container_class()
	 */
	public function test_gv_container_class() {


		// Test no View ID and no hide formatting
		GravityView_View::getInstance()->setViewId( 0 );
		GravityView_View::getInstance()->setHideUntilSearched( false );
		GravityView_View::getInstance()->setTotalEntries( 0 );

		// Test $echo parameter TRUE
		ob_start();
		gv_container_class();
		$output = ob_get_clean();

		$this->assertEquals( 'gv-container gv-container-no-results', $output );


		GravityView_View::getInstance()->setEntries( array( array('id'), array('id') ) );
		GravityView_View::getInstance()->setTotalEntries( 2 );

		// Test non-empty View
		ob_start();
		gv_container_class();
		$output = ob_get_clean();

		$this->assertEquals( 'gv-container', $output );

		// Test $echo parameter FALSE
		ob_start();
		$returned_output = gv_container_class( '', false );
		$output = ob_get_clean();
		$this->assertEquals( '', $output, 'Echo was false; there should be no output' );
		$this->assertEquals( 'gv-container', $returned_output );

		// Prevent output
		ob_start();

		$classes = array(
			'gv-container' => gv_container_class(),
			'with-passed-class gv-container' => gv_container_class( 'with-passed-class' ),
			'with-passed-class and-whitespace gv-container' => gv_container_class( '   with-passed-class and-whitespace   ' ),
		);

		foreach ( $classes as $expected => $formatted ) {
			$this->assertEquals( $expected, $formatted, $expected );
		}

		$post = $this->factory->view->create_and_get( array( 'form_id' => $this->form_id ) );
		$view = \GV\View::from_post( $post );
		$view->settings->update( array( 'page_size' => 3 ) );

		$entries = new \GV\Entry_Collection();

		foreach ( range( 1, 5 ) as $i ) {
			$entry = $this->factory->entry->create_and_get( array(
				'form_id' => $this->form_id,
				'status' => 'active',
				'16' => wp_generate_password( 12 ),
			) );
			$entries->add( \GV\GF_Entry::by_id( $entry['id'] ) );
		}

		$context = new \GV\Template_Context();
		$context->request = new \GV\Mock_Request();
		$context->entries = $entries;

		$classes = array(
			'gv-container' => gv_container_class( '', false, $context ),
			'with-passed-class gv-container' => gv_container_class( 'with-passed-class', false, $context ),
			'with-passed-class and-whitespace gv-container' => gv_container_class( '   with-passed-class and-whitespace   ', false, $context ),
		);

		foreach ( $classes as $expected => $formatted ) {
			$this->assertEquals( $expected, $formatted, $expected );
		}

		$context->view = $view;

		$classes = array(
			'gv-container gv-container-' . $view->ID => gv_container_class( '', false, $context ),
			'with-passed-class gv-container gv-container-' . $view->ID => gv_container_class( 'with-passed-class', false, $context ),
			'with-passed-class and-whitespace gv-container gv-container-' . $view->ID => gv_container_class( '   with-passed-class and-whitespace   ', false, $context ),
		);

		foreach ( $classes as $expected => $formatted ) {
			$this->assertEquals( $expected, $formatted, $expected );
		}

		// Test Hide Until Search formatting
		GravityView_View::getInstance()->setHideUntilSearched( true );

		$classes = array(
			'gv-container gv-hidden' => gv_container_class(),
			'with-passed-class gv-container gv-hidden' => gv_container_class( 'with-passed-class' ),
			'with-passed-class and-whitespace gv-container gv-hidden' => gv_container_class( '   with-passed-class and-whitespace   ' ),
		);

		foreach ( $classes as $expected => $formatted ) {
			$this->assertEquals( $expected, $formatted, $expected );
		}

		$context->view->settings->set( 'hide_until_searched', '1' );
		$context->request->returns['is_search'] = false;

		$classes = array(
			'gv-container gv-container-' . $view->ID .' gv-hidden' => gv_container_class( '', false, $context ),
			'with-passed-class gv-container gv-container-' . $view->ID .' gv-hidden' => gv_container_class( 'with-passed-class', false, $context ),
			'with-passed-class and-whitespace gv-container gv-container-' . $view->ID .' gv-hidden' => gv_container_class( '   with-passed-class and-whitespace   ', false, $context ),
		);

		foreach ( $classes as $expected => $formatted ) {
			$this->assertEquals( $expected, $formatted, $expected );
		}

		// Test View ID formatting
		GravityView_View::getInstance()->setViewId( 12 );

		$classes = array(
			'gv-container gv-container-12 gv-hidden' => gv_container_class(),
			'with-passed-class gv-container gv-container-12 gv-hidden' => gv_container_class( 'with-passed-class' ),
			'with-passed-class and-whitespace gv-container gv-container-12 gv-hidden' => gv_container_class( '   with-passed-class and-whitespace   ' ),
		);

		foreach ( $classes as $expected => $formatted ) {
			$this->assertEquals( $expected, $formatted, $expected );
		}

		// Prevent output
		ob_end_clean();
	}

	/**
	 * @covers GravityView_API::replace_variables()
	 * @covers GravityView_Merge_Tags::replace_variables()
	 */
	public function test_replace_variables() {

		$entry = GV_Unit_Tests_Bootstrap::instance()->get_entry();

		$form = GV_Unit_Tests_Bootstrap::instance()->get_form();

		// No match
		$this->assertEquals( 'no bracket', GravityView_API::replace_variables( 'no bracket', $form, $entry ) );

		// Include bracket with nomatch
		$this->assertEquals( $entry['id'] . ' {nomatch}', GravityView_API::replace_variables( '{entry_id} {nomatch}', $form, $entry ) );

		// Match tag, empty value
		$this->assertEquals( '', GravityView_API::replace_variables( '{user:example}', $form, $entry ) );

		// Open matching tag
		$this->assertEquals( '{entry_id', GravityView_API::replace_variables( '{entry_id', $form, $entry ) );

		// Form ID
		$this->assertEquals( $form['id'], GravityView_API::replace_variables( '{form_id}', $form, $entry ) );

		// Form title
		$this->assertEquals( 'Example '.$form['title'], GravityView_API::replace_variables( 'Example {form_title}', $form, $entry ) );

		$this->assertEquals( $entry['post_id'], GravityView_API::replace_variables( '{post_id}', $form, $entry ) );

		$this->assertEquals( date( 'm/d/Y' ), GravityView_API::replace_variables( '{date_mdy}', $form, $entry ) );

		$this->assertEquals( get_option( 'admin_email' ), GravityView_API::replace_variables( '{admin_email}', $form, $entry ) );

		$user = wp_set_current_user( $entry['created_by'] );

		// Test new Roles merge tag
		$this->assertEquals( implode( ', ', $user->roles ), GravityView_API::replace_variables( '{created_by:roles}', $form, $entry ) );

		$user->add_role( 'editor' );

		// Test new Roles merge tag again, with another role.
		$this->assertEquals( implode( ', ', $user->roles ), GravityView_API::replace_variables( '{created_by:roles}', $form, $entry ) );

		$var_content = '<p>I expect <strong>Entry #{entry_id}</strong> will be in Form #{form_id}</p>';
		$expected_content = '<p>I expect <strong>Entry #'.$entry['id'].'</strong> will be in Form #'.$form['id'].'</p>';
		$this->assertEquals( $expected_content, GravityView_API::replace_variables( $var_content, $form, $entry ) );

	}

	/**
	 * @covers GravityView_API::field_class()
	 */
	public function test_field_class() {

		$entry = $this->entry;

		$form = $this->form;

		$field_id = 2;

		$field = GFFormsModel::get_field( $form, $field_id);

		$this->assertEquals( 'gv-field-'.$form['id'].'-'.$field_id, GravityView_API::field_class( $field, $form, $entry ) );

		$field['custom_class'] = 'custom-class-{entry_id}';

		// Test the replace_variables functionality
		$this->assertEquals( 'custom-class-'.$entry['id'].' gv-field-'.$form['id'].'-'.$field_id, GravityView_API::field_class( $field, $form, $entry ) );

		$field['custom_class'] = 'testing,!@@($)*$ 12383';

		// Test the replace_variables functionality
		$this->assertEquals( 'testing 12383 gv-field-'.$form['id'].'-'.$field_id, GravityView_API::field_class( $field, $form, $entry ) );

		unset( $field['custom_class'] );
	}

	/**
	 * @group entry_link
	 * @covers GravityView_API::entry_link_html()
	 */
	public function test_entry_link_html() {

		global $post;

		$user = $this->factory->user->create_and_set( array( 'role' => 'administrator' ) );
		$form = $this->factory->form->create_and_get();
		$post = $this->factory->view->create_and_get();
		$entry = $this->factory->entry->create_and_get( array(
			'created_by' => $user->ID,
			'form_id' => $form['id'],
		) );

		$this->assertFalse( is_wp_error( $entry ), 'There was an error creating the $entry object. Skipping test' . print_r( $entry, true ) );

		GravityView_View::getInstance()->setPostId( $post->ID );

		// Set the post for the base URL for the entry link
		setup_postdata( $post );

		$this->assertNull( GravityView_API::entry_link_html( array() ) );

		$expected_url = site_url( sprintf('?gravityview=%s&amp;entry=%s', $post->post_name, $entry['id'] ) );

		$this->assertEquals( sprintf( '<a href="%s">%s</a>', $expected_url, 'Link Text' ), GravityView_API::entry_link_html( $entry, 'Link Text' ) );

		// Don't escape HTML
		$this->assertEquals( sprintf( '<a href="%s">%s</a>', $expected_url, 'Ampersands & Quotes " \'' ), GravityView_API::entry_link_html( $entry, 'Ampersands & Quotes " \'' ) );

		$this->assertEquals( sprintf( '<a href="%s" title="Expected Title">%s</a>', $expected_url, 'Link Text' ), GravityView_API::entry_link_html( $entry, 'Link Text', 'title=Expected Title' ) );

		// Invalid attribute shouldn't be included
		$this->assertEquals( sprintf( '<a href="%s">%s</a>', $expected_url, 'Link Text' ), GravityView_API::entry_link_html( $entry, 'Link Text', 'invalid=true' ) );


	}

	/**
	 * @group entry_link
	 * @since 2.10
	 * @covers gv_get_query_args()
	 */
	public function test_gv_get_query_args() {

		$_GET = array();

		$this->assertEquals( array(), gv_get_query_args() );

		$_GET = array( 'entry_id' => '1234' );
		$this->assertEquals( array(), gv_get_query_args(), 'Should have ignored reserved args' );

		$_GET = array( 'not_reserved' => '1234' );
		$this->assertEquals( $_GET, gv_get_query_args(), 'Should have returned $_GET verbatim; not reserved' );

		add_filter( 'gravityview/api/reserved_query_args', $add_not_reserved = function( $args ) {
			$args[] = 'not_reserved';
			return $args;
		} );

		$_GET = array( 'not_reserved' => '1234' );
		$this->assertEquals( array(), gv_get_query_args(), 'Should have been blocked by adding `not_reserved` to reserved args using the filter.' );

		remove_filter( 'gravityview/api/reserved_query_args', $add_not_reserved );

		$_GET = array( 'example' => 'anjela%27s%2c%20inc' );
		$this->assertEquals( array( 'example' => "anjela's, inc" ), gv_get_query_args(), 'Should have decoded URL args.' );

		$_GET = array( 'example' => '<script>Example</script>' );
		$this->assertEquals( array( 'example' => "<script>Example</script>" ), gv_get_query_args(), 'Should not have stripped or sanitized. That\'s for later in the cycle.' );

		$_GET = array( 'gv_search' => 'testing', 'gv_start' => '2020-02-02', 'gv_end' => '2020-02-02', 'gv_id' => '1', 'gv_by' => '3', 'mode' => 'all' );
		$this->assertEquals( array(), gv_get_query_args(), 'Search Bar should define search parameters as reserved.' );

		$_GET = array();
	}

	/**
	 * @group back_link
	 * @covers ::gravityview_back_link()
	 * @since TODO
	 */
	public function test_gravityview_back_link_hidden_behavior() {
		$form = $this->factory->form->create_and_get();
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$context = new \GV\Template_Context();
		$context->view = \GV\View::from_post( $view );

		// Test hidden behavior returns null
		$context->view->settings->update( array( 'back_link_behavior' => 'hidden' ) );
		$this->assertNull( gravityview_back_link( $context ), 'Hidden back link behavior should return null' );
	}

	/**
	 * @group back_link
	 * @covers ::gravityview_back_link()
	 * @since TODO
	 */
	public function test_gravityview_back_link_directory_returns_directory_link() {
		$form = $this->factory->form->create_and_get();
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$context = new \GV\Template_Context();
		$context->view = \GV\View::from_post( $view );

		// Directory behavior should return a URL (directory link)
		$context->view->settings->update( array( 'back_link_behavior' => 'directory' ) );

		// Capture the generated URL via filter before template processing
		$captured_url = '';
		add_filter( 'gravityview/template/links/back/url', function( $href ) use ( &$captured_url ) {
			$captured_url = $href;
			return ''; // Return empty to bypass template requirements
		}, 5 );

		gravityview_back_link( $context );

		// Directory behavior should return a valid URL (gv_directory_link output)
		$this->assertNotEmpty( $captured_url, 'Directory behavior should return a directory link URL' );
		$this->assertStringContainsString( 'http', $captured_url, 'Directory behavior should return a valid URL' );

		remove_all_filters( 'gravityview/template/links/back/url' );
	}

	/**
	 * @group back_link
	 * @covers ::gravityview_back_link()
	 * @since TODO
	 */
	public function test_gravityview_back_link_previous_uses_referer() {
		$form = $this->factory->form->create_and_get();
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$context = new \GV\Template_Context();
		$context->view = \GV\View::from_post( $view );

		// Test previous behavior with valid same-site referer
		$context->view->settings->update( array( 'back_link_behavior' => 'previous' ) );

		// Set up a valid referer (same site)
		$_SERVER['HTTP_REFERER'] = home_url( '/custom-page/?param=value' );

		// Capture the generated URL via filter before template processing
		$captured_url = '';
		add_filter( 'gravityview/template/links/back/url', function( $href ) use ( &$captured_url ) {
			$captured_url = $href;
			return ''; // Return empty to bypass template requirements
		}, 5 );

		gravityview_back_link( $context );

		// wp_get_referer() validates and returns same-site referers
		$this->assertStringContainsString( '/custom-page/', $captured_url, 'previous behavior should use HTTP_REFERER for same-site URLs' );

		// Clean up
		unset( $_SERVER['HTTP_REFERER'] );
		remove_all_filters( 'gravityview/template/links/back/url' );
	}

	/**
	 * @group back_link
	 * @covers ::gravityview_back_link()
	 * @since TODO
	 */
	public function test_gravityview_back_link_previous_rejects_external_referer() {
		$form = $this->factory->form->create_and_get();
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$context = new \GV\Template_Context();
		$context->view = \GV\View::from_post( $view );

		// Test previous behavior with external referer (should be rejected by wp_get_referer)
		$context->view->settings->update( array( 'back_link_behavior' => 'previous' ) );

		// Set up an external referer (should be rejected)
		$_SERVER['HTTP_REFERER'] = 'https://malicious-site.com/phishing';

		// Capture the generated URL via filter before template processing
		$captured_url = '';
		add_filter( 'gravityview/template/links/back/url', function( $href ) use ( &$captured_url ) {
			$captured_url = $href;
			return ''; // Return empty to bypass template requirements
		}, 5 );

		gravityview_back_link( $context );

		// wp_get_referer() should reject external URLs and fall back to directory link
		$this->assertStringNotContainsString( 'malicious-site.com', $captured_url, 'previous behavior should reject external referers' );

		// Clean up
		unset( $_SERVER['HTTP_REFERER'] );
		remove_all_filters( 'gravityview/template/links/back/url' );
	}

	/**
	 * @group back_link
	 * @covers ::gravityview_back_link()
	 * @since TODO
	 */
	public function test_gravityview_back_link_filter() {
		$form = $this->factory->form->create_and_get();
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$context = new \GV\Template_Context();
		$context->view = \GV\View::from_post( $view );

		// Capture to verify filter receives URL, then modify
		$filter_received_url = false;
		add_filter( 'gravityview/template/links/back/url', function( $href ) use ( &$filter_received_url ) {
			$filter_received_url = ! empty( $href );
			return ''; // Return empty to bypass template requirements
		}, 5 );

		gravityview_back_link( $context );

		$this->assertTrue( $filter_received_url, 'Filter should receive back link URL' );

		remove_all_filters( 'gravityview/template/links/back/url' );
	}

	/**
	 * @group back_link
	 * @covers ::gravityview_back_link()
	 * @since 2.31
	 */
	public function test_gravityview_back_link_gv_back_invalid_post_id() {
		$form = $this->factory->form->create_and_get();
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$context = new \GV\Template_Context();
		$context->view = \GV\View::from_post( $view );

		$context->view->settings->update( array( 'back_link_behavior' => 'previous' ) );

		// Set gv_back to a non-existent post ID.
		$_GET['gv_back'] = 999999999;

		// Set a valid referer as fallback.
		$_SERVER['HTTP_REFERER'] = home_url( '/fallback-page/' );

		// Capture the generated URL via filter.
		$captured_url = '';
		add_filter( 'gravityview/template/links/back/url', function( $href ) use ( &$captured_url ) {
			$captured_url = $href;
			return '';
		}, 5 );

		gravityview_back_link( $context );

		// Should fall back to referer since gv_back post doesn't exist.
		$this->assertStringContainsString( '/fallback-page/', $captured_url, 'Invalid gv_back post ID should fall back to referer' );
		$this->assertStringNotContainsString( '999999999', $captured_url, 'Invalid post ID should not appear in URL' );

		// Clean up.
		unset( $_GET['gv_back'], $_SERVER['HTTP_REFERER'] );
		remove_all_filters( 'gravityview/template/links/back/url' );
	}

	/**
	 * @group back_link
	 * @covers ::gravityview_back_link()
	 * @since 2.31
	 */
	public function test_gravityview_back_link_gv_back_valid_post_id() {
		$form = $this->factory->form->create_and_get();
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		// Create a page to use as the gv_back target.
		$page = $this->factory->post->create_and_get( array(
			'post_type'  => 'page',
			'post_title' => 'Embedded View Page',
			'post_name'  => 'embedded-view-page',
		) );

		$context = new \GV\Template_Context();
		$context->view = \GV\View::from_post( $view );

		$context->view->settings->update( array( 'back_link_behavior' => 'previous' ) );

		// Set gv_back to a valid post ID.
		$_GET['gv_back'] = $page->ID;

		// Set a different referer (should be ignored in favor of gv_back).
		$_SERVER['HTTP_REFERER'] = home_url( '/different-page/' );

		// Capture the generated URL via filter.
		$captured_url = '';
		add_filter( 'gravityview/template/links/back/url', function( $href ) use ( &$captured_url ) {
			$captured_url = $href;
			return '';
		}, 5 );

		gravityview_back_link( $context );

		// Should use gv_back post permalink, not the referer.
		// Check for page_id parameter since test environment may not have pretty permalinks.
		$this->assertStringContainsString( 'page_id=' . $page->ID, $captured_url, 'Valid gv_back post ID should use that post permalink' );
		$this->assertStringNotContainsString( 'different-page', $captured_url, 'Referer should be ignored when gv_back is valid' );

		// Clean up.
		unset( $_GET['gv_back'], $_SERVER['HTTP_REFERER'] );
		remove_all_filters( 'gravityview/template/links/back/url' );
	}

	/**
	 * Test that gv_back works when HTTP referer is completely missing.
	 *
	 * Real-world scenario: Browser privacy settings strip referer, or user
	 * bookmarked the Single Entry page directly.
	 *
	 * @group back_link
	 * @covers ::gravityview_back_link()
	 * @since 2.31
	 */
	public function test_gravityview_back_link_gv_back_no_referer() {
		$form = $this->factory->form->create_and_get();
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		// Create a page that "embedded" the View.
		$embedding_page = $this->factory->post->create_and_get( array(
			'post_type'  => 'page',
			'post_title' => 'My Embedded View Page',
		) );

		$context = new \GV\Template_Context();
		$context->view = \GV\View::from_post( $view );
		$context->view->settings->update( array( 'back_link_behavior' => 'previous' ) );

		// Set gv_back but NO referer (simulating privacy browser or direct bookmark).
		$_GET['gv_back'] = $embedding_page->ID;
		unset( $_SERVER['HTTP_REFERER'] );

		$captured_url = '';
		add_filter( 'gravityview/template/links/back/url', function( $href ) use ( &$captured_url ) {
			$captured_url = $href;
			return '';
		}, 5 );

		gravityview_back_link( $context );

		// Should use gv_back even without referer.
		$this->assertStringContainsString( 'page_id=' . $embedding_page->ID, $captured_url, 'gv_back should work even when HTTP referer is missing' );

		unset( $_GET['gv_back'] );
		remove_all_filters( 'gravityview/template/links/back/url' );
	}

	/**
	 * Test that directory behavior returns View directory link.
	 *
	 * Real-world scenario: GitHub #830 - "After edit/delete an entry keep the search results"
	 * User wants to return to the Multiple Entries directory.
	 *
	 * Note: Full search filter preservation requires integration testing since it depends
	 * on WordPress query processing. This test verifies the directory URL is generated.
	 *
	 * @group back_link
	 * @covers ::gravityview_back_link()
	 * @since 2.31
	 */
	public function test_gravityview_back_link_directory_returns_view_link() {
		$form = $this->factory->form->create_and_get();
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$context = new \GV\Template_Context();
		$context->view = \GV\View::from_post( $view );
		$context->view->settings->update( array( 'back_link_behavior' => 'directory' ) );

		$captured_url = '';
		add_filter( 'gravityview/template/links/back/url', function( $href ) use ( &$captured_url ) {
			$captured_url = $href;
			return '';
		}, 5 );

		gravityview_back_link( $context );

		// Directory behavior should return a URL containing the View's slug.
		$this->assertStringContainsString( 'gravityview', $captured_url, 'Directory behavior should return View directory link' );
		$this->assertNotEmpty( $captured_url, 'Directory behavior should generate a URL' );

		remove_all_filters( 'gravityview/template/links/back/url' );
	}

	/**
	 * Test that gv_back is NOT added when viewing View on its own CPT page.
	 *
	 * Real-world scenario: User visits /view/my-view/ directly (not embedded).
	 * No gv_back should be added since there's no embedding page.
	 *
	 * @group back_link
	 * @group entry_link
	 * @covers GravityView_API::entry_link()
	 * @since 2.31
	 */
	public function test_entry_link_no_gv_back_on_view_cpt_page() {
		global $post;

		$user = $this->factory->user->create_and_set( array( 'role' => 'administrator' ) );
		$form = $this->factory->form->create_and_get();
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$entry = $this->factory->entry->create_and_get( array(
			'created_by' => $user->ID,
			'form_id'    => $form['id'],
		) );

		// Set global $post to the View itself (not an embedding page).
		$post = $view;
		setup_postdata( $post );

		GravityView_View::getInstance()->setPostId( $view->ID );
		GravityView_View::getInstance()->setViewId( $view->ID );

		$entry_url = GravityView_API::entry_link( $entry, $view->ID, true, $view->ID );

		// Should NOT contain gv_back when on View's own page.
		$this->assertStringNotContainsString( 'gv_back', $entry_url, 'Entry link should not have gv_back when on View CPT page (not embedded)' );

		wp_reset_postdata();
	}

	/**
	 * Test that gv_back IS added when View is embedded in a regular page.
	 *
	 * Real-world scenario: Most common support issue - View embedded via shortcode
	 * in a page, Back Link should return to that page.
	 *
	 * @group back_link
	 * @group entry_link
	 * @covers GravityView_API::entry_link()
	 * @since 2.31
	 */
	public function test_entry_link_adds_gv_back_when_embedded() {
		global $post;

		$user = $this->factory->user->create_and_get( array( 'role' => 'administrator' ) );
		$form = $this->factory->form->create_and_get();
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$entry = $this->factory->entry->create_and_get( array(
			'created_by' => $user->ID,
			'form_id'    => $form['id'],
		) );

		// Create a regular page that embeds the View.
		$embedding_page = $this->factory->post->create_and_get( array(
			'post_type'    => 'page',
			'post_title'   => 'Page With Embedded View',
			'post_content' => '[gravityview id="' . $view->ID . '"]',
		) );

		// Set global $post to the embedding page (simulating shortcode render context).
		$post = $embedding_page;
		setup_postdata( $post );

		GravityView_View::getInstance()->setPostId( $embedding_page->ID );
		GravityView_View::getInstance()->setViewId( $view->ID );

		$entry_url = GravityView_API::entry_link( $entry, $embedding_page->ID, true, $view->ID );

		// Should contain gv_back with embedding page ID.
		$this->assertStringContainsString( 'gv_back=' . $embedding_page->ID, $entry_url, 'Entry link should have gv_back when View is embedded in a page' );

		wp_reset_postdata();
	}

	/**
	 * Test gv_back with non-numeric value is ignored.
	 *
	 * Real-world scenario: Malicious or malformed URL with non-numeric gv_back.
	 *
	 * @group back_link
	 * @covers ::gravityview_back_link()
	 * @since 2.31
	 */
	public function test_gravityview_back_link_gv_back_non_numeric_ignored() {
		$form = $this->factory->form->create_and_get();
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$context = new \GV\Template_Context();
		$context->view = \GV\View::from_post( $view );
		$context->view->settings->update( array( 'back_link_behavior' => 'previous' ) );

		// Set gv_back to non-numeric value.
		$_GET['gv_back'] = 'malicious-script';
		$_SERVER['HTTP_REFERER'] = home_url( '/safe-fallback/' );

		$captured_url = '';
		add_filter( 'gravityview/template/links/back/url', function( $href ) use ( &$captured_url ) {
			$captured_url = $href;
			return '';
		}, 5 );

		gravityview_back_link( $context );

		// Should fall back to referer, ignoring non-numeric gv_back.
		$this->assertStringContainsString( '/safe-fallback/', $captured_url, 'Non-numeric gv_back should be ignored' );
		$this->assertStringNotContainsString( 'malicious', $captured_url, 'Malicious gv_back value should not appear in URL' );

		unset( $_GET['gv_back'], $_SERVER['HTTP_REFERER'] );
		remove_all_filters( 'gravityview/template/links/back/url' );
	}

	/**
	 * Test that hidden behavior still returns null (no Back Link shown).
	 *
	 * Real-world scenario: User wants to hide the Back Link entirely.
	 *
	 * @group back_link
	 * @covers ::gravityview_back_link()
	 * @since 2.31
	 */
	public function test_gravityview_back_link_hidden_returns_null() {
		$form = $this->factory->form->create_and_get();
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$context = new \GV\Template_Context();
		$context->view = \GV\View::from_post( $view );
		$context->view->settings->update( array( 'back_link_behavior' => 'hidden' ) );

		// Even with gv_back set, hidden should return null.
		$_GET['gv_back'] = 999;

		$result = gravityview_back_link( $context );

		$this->assertNull( $result, 'Hidden behavior should return null regardless of gv_back' );

		unset( $_GET['gv_back'] );
	}

	/**
	 * @group entry_link
	 * @covers GravityView_API::entry_link()
	 */
	public function test_entry_link() {

		$user = $this->factory->user->create_and_set( array( 'role' => 'administrator' ) );
		$form = $this->factory->form->create_and_get();
		$form2 = $this->factory->form->create_and_get();
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view2 = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$entry = $this->factory->entry->create_and_get( array(
			'created_by' => $user->ID,
			'form_id' => $form['id'],
		) );
		$entry2 = $this->factory->entry->create_and_get( array(
			'created_by' => $user->ID,
			'form_id' => $form2['id'],
		) );

		$multi_entry = \GV\Multi_Entry::from_entries( array(
			\GV\GF_Entry::from_entry( $entry ), \GV\GF_Entry::from_entry( $entry2 )
		) );

		GravityView_View::getInstance()->setPostId( $view->ID );

		$href = GravityView_API::entry_link( $entry, $view->ID );

		$this->assertEquals( site_url('?gravityview='.$view->post_name.'&entry='.$entry['id'] ), $href );

		$_GET = array( 'entry' => '1746472' );
		$href = GravityView_API::entry_link( $entry, $view->ID );
		$this->assertEquals( site_url('?gravityview='.$view->post_name.'&entry='.$entry['id'] ), $href, 'Reserved $_GET args should have been ignored by gv_get_query_args()' );

		$_GET = array( 'fortune' => 'brave' );
		$href = GravityView_API::entry_link( $entry, $view->ID );
		$this->assertEquals( site_url('?gravityview='.$view->post_name.'&fortune=brave&entry='.$entry['id'] ), $href, '$_GET args should have been added but weren\'t.' );

		add_filter( 'gravityview/entry_link/add_query_args', '__return_false' );

		$href = GravityView_API::entry_link( $entry, $view->ID );

		$this->assertEquals( site_url('?gravityview='.$view->post_name.'&entry='.$entry['id'] ), $href, 'Filter should have prevented $_GET args from being added' );

		$_GET = array();

		remove_filter( 'gravityview/entry_link/add_query_args', '__return_false' );

		$post_with_embeds = $this->factory->post->create_and_get( array( 'post_content' => '[gravityview id="' . $view->ID .'"] and then [gravityview id="' . $view2->ID .'"]') );

		GravityView_View::getInstance()->setPostId( $post_with_embeds->ID );
		GravityView_View::getInstance()->setViewId( $view->ID );

		$href = GravityView_API::entry_link( $entry, $post_with_embeds->ID );

		// Reproduces GH#1190
		$this->assertEquals( site_url('?p='.$post_with_embeds->ID .'&entry='.$entry['id'] . '&gvid=' . $view->ID ), $href );

		GravityView_View::getInstance()->setViewId( $view2->ID );
		$href = GravityView_API::entry_link( $entry, $post_with_embeds->ID );

		$this->assertEquals( site_url('?p='.$post_with_embeds->ID .'&entry='.$entry['id'] . '&gvid=' . $view2->ID ), $href );

		$post_with_single_embed = $this->factory->post->create_and_get( array( 'post_content' => '[gravityview id="' . $view->ID .'"]') );

		GravityView_View::getInstance()->setPostId( $post_with_single_embed->ID );

		$href = GravityView_API::entry_link( $entry, $post_with_single_embed->ID );

		$this->assertEquals( site_url('?p='.$post_with_single_embed->ID .'&entry='.$entry['id'] ), $href );

		$href = GravityView_API::entry_link( $multi_entry->as_entry(), $post_with_single_embed->ID );
		$this->assertEquals( site_url('?p='.$post_with_single_embed->ID .'&entry='.$entry['id'] . ',' . $entry2['id'] ), $href );

		add_filter( 'gravityview_custom_entry_slug', '__return_true' );

		$href = GravityView_API::entry_link( $multi_entry->as_entry(), $post_with_single_embed->ID );
		$entry1_slug = GravityView_API::get_entry_slug( $entry['id'] );
		$entry2_slug = GravityView_API::get_entry_slug( $entry2['id'] );
		$this->assertEquals( site_url('?p='.$post_with_single_embed->ID .'&entry='.$entry1_slug . ',' . $entry2_slug ), $href );

		remove_filter( 'gravityview_custom_entry_slug', '__return_true' );
	}

	/**
	 * @uses GravityView_API_Test::_override_no_entries_text_output()
	 * @covers GravityView_API::no_results()
	 */
	public function test_no_results() {

		global $gravityview_view;

		$gravityview_view = GravityView_View::getInstance();

		$gravityview_view->curr_start = false;
		$gravityview_view->curr_end = false;
		$gravityview_view->curr_search = false;

		// Not in search by default
		$this->assertEquals( 'No entries match your request.', GravityView_API::no_results( false ) );
		$this->assertEquals( '<p>No entries match your request.</p>'."\n", GravityView_API::no_results( true ) );
		// Pretend we're in search
		$gravityview_view->curr_search = true;

		$this->assertEquals( 'This search returned no results.', GravityView_API::no_results( false ) );
		$this->assertEquals( '<p>This search returned no results.</p>'."\n", GravityView_API::no_results( true ) );

		$context = new \GV\Template_Context();
		$context->request = new \GV\Mock_Request();
		$context->request->returns['is_search'] = true;
		$context->view = new \GV\View();
		$this->assertEquals( 'This search returned no results.', GravityView_API::no_results( false, $context ) );
		$context->view->settings->set( 'no_search_results_text', '' ); // When empty, use default
		$this->assertEquals( 'This search returned no results.', GravityView_API::no_results( false, $context ) );
		$context->view->settings->set( 'no_search_results_text', 'NO ENTRIES <strong>IN</strong> <example>THIS</example> SEARCH' );
		$this->assertEquals( 'NO ENTRIES <strong>IN</strong> <example>THIS</example> SEARCH', $context->view->settings->get( 'no_search_results_text' ) );
		$this->assertEquals( 'NO ENTRIES <strong>IN</strong> THIS SEARCH', GravityView_API::no_results( false, $context ) );
		$this->assertEquals( '<p>NO ENTRIES <strong>IN</strong> THIS SEARCH</p>' . "\n", GravityView_API::no_results( true, $context ) );

		$context->request->returns['is_search'] = false;
		$context->view = new \GV\View();
		$this->assertEquals( 'No entries match your request.', GravityView_API::no_results( false, $context ) );
		$context->view->settings->set( 'no_results_text', '' ); // When empty, use default
		$this->assertEquals( 'No entries match your request.', GravityView_API::no_results( false, $context ) );
		$context->view->settings->set( 'no_results_text', 'NO ENTRIES <strong>IN</strong> <example>NOT</example> SEARCH' );
		$this->assertEquals( 'NO ENTRIES <strong>IN</strong> <example>NOT</example> SEARCH', $context->view->settings->get( 'no_results_text' ) );
		$this->assertEquals( 'NO ENTRIES <strong>IN</strong> NOT SEARCH', GravityView_API::no_results( false, $context ) );
		$this->assertEquals( '<p>NO ENTRIES <strong>IN</strong> NOT SEARCH</p>' . "\n", GravityView_API::no_results( true, $context ) );

		// Add the filter that modifies output
		add_filter( 'gravitview_no_entries_text', array( $this, '_override_no_entries_text_output' ), 10, 2 );

		// Test to make sure the $is_search parameter is passed correctly
		$this->assertEquals( 'SEARCH <example>override</example> the no entries text output', GravityView_API::no_results( false ), 'HTML should be allowed from filters' );

		$gravityview_view->curr_search = false;

		// Test to make sure the $is_search parameter is passed correctly
		$this->assertEquals( 'NO SEARCH <example>override</example> the no entries text output', GravityView_API::no_results( false ), 'HTML should be allowed from filters' );

		// Remove the filter for later
		remove_filter( 'gravitview_no_entries_text', array( $this, '_override_no_entries_text_output' ) );
	}

	public function _override_no_entries_text_output( $previous, $is_search = false ) {

		if ( $is_search ) {
			return 'SEARCH <example>override</example> the no entries text output';
		} else {
			return 'NO SEARCH <example>override</example> the no entries text output';
		}

	}

	public function _get_new_view_id() {
		return $this->factory->view->create_object( array(
			'form_id' => $this->form_id
		) );
	}

	/**
	 * @covers ::gravityview_get_current_views()
	 * @group get_current_views
	 * @internal Make sure this test is above the test_directory_link() test so that one doesn't pollute $post
	 */
	public function test_gravityview_get_current_views() {

		$fe = GravityView_frontend::getInstance();

		$fe->setIsGravityviewPostType( false );
		$fe->setPostHasShortcode( false );
		$fe->setPostId( null );
		$fe->setIsSearch( false );

		GravityView_View_Data::$instance = NULL;
		$fe->setGvOutputData( NULL );

		global $post;

		$view_post_type_id = $this->_get_new_view_id();
		$post = get_post( $view_post_type_id );

		$this->assertEquals( $view_post_type_id, $post->ID, 'The post was not properly created' );

		$current_views = gravityview_get_current_views();

		// Check if the view post is set
		$this->assertTrue( isset( $current_views[ $view_post_type_id ] ), 'The $current_views array didn\'t have a value set at $post->ID key of ' . $view_post_type_id );

		// When the view is added, the key is set to the View ID and the `id` is also set to that
		$this->assertEquals( $view_post_type_id, $current_views[ $view_post_type_id ]['id'] );

		// Just one View
		$this->assertEquals( 1, count( $current_views ) );

		$second_view_post_type_id = $this->_get_new_view_id();

		$fe->gv_output_data->add_view( $second_view_post_type_id );

		$second_current_views = gravityview_get_current_views();

		// Check to make sure add_view worked properly
		$this->assertEquals( $second_view_post_type_id, $second_current_views[ $second_view_post_type_id ]['view_id'] );

		// Now two Views
		$this->assertEquals( 2, count( $second_current_views ) );

		GravityView_View_Data::$instance = NULL;
	}

	/**
	 * @group field_width
	 * @covers GravityView_API::field_width()
	 */
	public function test_field_width() {

		$field = array();

		// Empty $field['width'] returns NULL
		$width = GravityView_API::field_width( $field );
		$this->assertNull( $width );

		// Default: convert to %
		$field['width'] = 10;
		$width = GravityView_API::field_width( $field );
		$this->assertEquals( '10%', $width );

		// Limit to 100% when using default % formatting
		$field['width'] = 200;
		$width = GravityView_API::field_width( $field );
		$this->assertEquals( '100%', $width );

		// Check other formats
		$format = '%dpx';
		$field['width'] = 200;
		$width = GravityView_API::field_width( $field, $format );
		$this->assertEquals( '200px', $width );

		$format = '%d';
		$field['width'] = 500000;
		$width = GravityView_API::field_width( $field, $format );
		$this->assertEquals( '500000', $width );
	}

	/**
	 * Prevent `wpautop()` from being applied to View Zones
	 *
	 * @param array $args Associative array; `field` and `form` is required.
	 * @param array $passed_args Original associative array with field data. `field` and `form` are required.
	 */
	function _filter_test_gravityview_field_output_args( $args = array(), $passed_args = array() ) {

		$args['wpautop'] = false;

		return $args;
	}

	/**
	 * @covers ::gv_directory_link()
	 * @covers GravityView_API::directory_link()
	 */
	public function test_directory_link( ) {
		$post_array = array(
			'post_content' => 'asdasdsd',
			'post_type' => 'post',
			'post_status' => 'publish',
		);

		$post_id = wp_insert_post( $post_array );

		$view_post_type_id = $this->_get_new_view_id();

		$_GET['pagenum'] = 2;

		$add_pagination = false;
		$this->assertEquals( site_url( '?p=' . $post_id ), GravityView_API::directory_link( $post_id, $add_pagination ) );

		$add_pagination = true;
		$this->assertEquals( site_url( '?p=' . $post_id . '&pagenum=2' ), GravityView_API::directory_link( $post_id, $add_pagination ) );

		//
		// Use $gravityview_view data
		//
		global $gravityview_view;
		global $post;

		$post = get_post( $view_post_type_id );

		GravityView_frontend::getInstance()->parse_content();

		$gravityview_view->setViewId( $view_post_type_id );

		// Test post_id has been set
		$gravityview_view->setPostId( $post_id );

		/* TODO - fix this assertion */
		$this->assertEquals( site_url( '?p=' . $post_id . '&pagenum=2' ), GravityView_API::directory_link() );
	}

	/**
	 * @covers ::gv_directory_link()
	 * @covers GravityView_API::directory_link()
	 *
	 * @group ajax
	 */
	public function test_directory_link_ajax() {
		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		$post_array = array(
			'post_content' => 'asdasdsd',
			'post_type' => 'post',
			'post_status' => 'publish',
		);
		$post_id = wp_insert_post( $post_array );
		$_GET['pagenum'] = 2;
		$_POST['post_id'] = $post_id;
		// No passed post_id; use $_POST when DOING_AJAX is set
		$this->assertEquals( site_url( '?p=' . $post_id . '&pagenum=2' ), GravityView_API::directory_link() );
	}

}
