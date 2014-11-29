<?php

class GravityView_API_Test extends PHPUnit_Framework_TestCase {

	function setUp() {
		parent::setUp();

		// Add form meta table
		GFForms::setup_database();

		$GV = GravityView_Plugin::getInstance();
		$GV->frontend_actions();
	}

	/**
	 * @covers GravityView_API::replace_variables()
	 */
	function test_replace_variables() {

		$entry = array(
			'id' => 5384,
			'form_id' => 123,
			'ip' => '127.0.0.1',
			'source_url' => 'http://example.com/wordpress/?gf_page=preview&id=16',
			'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.78.2 (KHTML, like Gecko) Version/7.0.6 Safari/537.78.2',
			'payment_status' => 'Processing',
			'payment_date' => '2014-08-29 20:55:06',
			'payment_amount' => '0.01',
			'transaction_id' => 'asdfpaoj442gpoagfadf',
			'created_by' => 1,
			'post_id' => 18,
			'status' => 'active',
			'date_created' => '2014-08-29 18:25:39',
		);

		$form = array(
			'id' => 123,
			'title' => 'This is the form title',
		);

		// No match
		$this->assertEquals( 'no bracket', GravityView_API::replace_variables( 'no bracket', $form, $entry ) );

		// Include bracket with nomatch
		$this->assertEquals( '5384 {nomatch}', GravityView_API::replace_variables( '{entry_id} {nomatch}', $form, $entry ) );

		// Match tag, empty value
		$this->assertEquals( '', GravityView_API::replace_variables( '{user:example}', $form, $entry ) );

		// Open matching tag
		$this->assertEquals( '{entry_id', GravityView_API::replace_variables( '{entry_id', $form, $entry ) );

		// Form ID
		$this->assertEquals( $form['id'], GravityView_API::replace_variables( '{form_id}', $form, $entry ) );

		// Form title
		$this->assertEquals( 'Example '.$form['title'], GravityView_API::replace_variables( 'Example {form_title}', $form, $entry ) );

		$this->assertEquals( $entry['post_id'], GravityView_API::replace_variables( '{post_id}', $form, $entry ) );

		$this->assertEquals( date('m/d/Y'), GravityView_API::replace_variables( '{date_mdy}', $form, $entry ) );

		$this->assertEquals( get_option('admin_email'), GravityView_API::replace_variables( '{admin_email}', $form, $entry ) );

		$var_content = '<p>I expect <strong>Entry #{entry_id}</strong> will be in Form #{form_id}</p>';
		$expected_content = '<p>I expect <strong>Entry #5384</strong> will be in Form #123</p>';
		$this->assertEquals( $expected_content, GravityView_API::replace_variables( $var_content, $form, $entry ) );

	}

	/**
	 * @covers GravityView_API::field_class()
	 */
	function test_field_class() {

		$entry = array(
			'id' => 5384,
			'form_id' => 123,
			'ip' => '127.0.0.1',
			'source_url' => 'http://example.com/wordpress/?gf_page=preview&id=16',
			'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.78.2 (KHTML, like Gecko) Version/7.0.6 Safari/537.78.2',
			'payment_status' => 'Processing',
			'payment_date' => '2014-08-29 20:55:06',
			'payment_amount' => '0.01',
			'transaction_id' => 'asdfpaoj442gpoagfadf',
			'created_by' => 1,
			'post_id' => 18,
			'status' => 'active',
			'date_created' => '2014-08-29 18:25:39',
		);

		$form = array(
			'id' => 123,
			'title' => 'This is the form title',
		);

		$field = array(
			'id' => '8'
		);

		$this->assertEquals( 'gv-field-123-8', GravityView_API::field_class( $field, $form, $entry ) );


		$field = array(
			'id' => '8',
			'custom_class' => 'custom-class-{entry_id}'
		);

		// Test the replace_variables functionality
		$this->assertEquals( 'custom-class-5384 gv-field-123-8', GravityView_API::field_class( $field, $form, $entry ) );

		$field['custom_class'] = 'testing,!@@($)*$ 12383';

		// Test the replace_variables functionality
		$this->assertEquals( 'testing 12383 gv-field-123-8', GravityView_API::field_class( $field, $form, $entry ) );

	}

	/**
	 * @uses $this->_override_no_entries_text_output()
	 * @covers GravityView_API::no_results()
	 */
	function test_no_results() {

		global $gravityview_view;

		$gravityview_view = new StdClass();

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


		// Add the filter that modifies output
		add_filter('gravitview_no_entries_text', array( $this, '_override_no_entries_text_output' ), 10, 2);

		// Test to make sure the $is_search parameter is passed correctly
		$this->assertEquals( 'SEARCH override the no entries text output', GravityView_API::no_results( false ) );

		$gravityview_view->curr_search = false;

		// Test to make sure the $is_search parameter is passed correctly
		$this->assertEquals( 'NO SEARCH override the no entries text output', GravityView_API::no_results( false ) );

		// Remove the filter for later
		remove_filter('gravitview_no_entries_text', array( $this, '_override_no_entries_text_output' ));

	}

	function _override_no_entries_text_output( $previous, $is_search = false ) {

		if( $is_search ) {
			return 'SEARCH override the no entries text output';
		} else {
			return 'NO SEARCH override the no entries text output';
		}

	}

	function _get_new_view_id() {

		$view_array = array(
			'post_content' => '',
			'post_type' => 'gravityview',
			'post_status' => 'publish',
		);

		// Add the View
		$view_post_type_id = wp_insert_post( $view_array );

		// Set the form ID
		update_post_meta( $view_post_type_id, '_gravityview_form_id', 123 );

		// Set the View settigns
		update_post_meta( $view_post_type_id, '_gravityview_template_settings', GravityView_View_Data::get_default_args() );

		return $view_post_type_id;

	}

	function test_directory_link( ) {

		$post_array = array(
			'post_content' => 'asdasdsd',
			'post_type' => 'post',
			'post_status' => 'publish',
		);

		$post_id = wp_insert_post( $post_array );

		$view_post_type_id = $this->_get_new_view_id();


		$_GET['pagenum'] = 2;

		$add_pagination = false;
		$this->assertEquals( site_url('?p='.$post_id), GravityView_API::directory_link( $post_id, $add_pagination ) );

		$add_pagination = true;
		$this->assertEquals( site_url('?p='.$post_id.'&pagenum=2'), GravityView_API::directory_link( $post_id, $add_pagination ) );

		// Make sure the cache is working properly
		$this->assertEquals( site_url('?p='.$post_id), wp_cache_get( 'gv_directory_link_'.$post_id ) );

	//
	// Use $gravityview_view data
	//
		global $gravityview_view;
		global $post;

		$post = get_post( $view_post_type_id );

		GravityView_frontend::getInstance()->parse_content();


		$gravityview_view->view_id = $view_post_type_id;


		// Test post_id has been set
		$gravityview_view->post_id = $post_id;

		$this->assertEquals( site_url('?p='.$post_id.'&pagenum=2'), GravityView_API::directory_link() );

		$gravityview_view->post_id = $post_id;

	//
	// TESTING AJAX
	//
		define( 'DOING_AJAX', true );

		// No passed post_id; use $_POST when DOING_AJAX is set
		$this->assertNull( GravityView_API::directory_link() );

		$_POST['post_id'] = $post_id;
		// No passed post_id; use $_POST when DOING_AJAX is set
		$this->assertEquals( site_url('?p='.$post_id.'&pagenum=2'), GravityView_API::directory_link() );

	}

	function test_gravityview_get_current_views() {
		global $post;

		$fe = GravityView_frontend::getInstance();

		// Clear the data so that gravityview_get_current_views() runs parse_content()
		$fe->gv_output_data = NULL;

		$view_post_type_id = $this->_get_new_view_id();
		$post = get_post( $view_post_type_id );

		$current_views = gravityview_get_current_views();

		// When the view is added, the key is set to the View ID and the `id` is also set to that
		$this->assertEquals( $view_post_type_id, $current_views[ $view_post_type_id ]['id'] );

		// Just one View
		$this->assertEquals( 1, sizeof( $current_views ) );

		$second_view_post_type_id = $this->_get_new_view_id();

		$fe->gv_output_data->add_view( $second_view_post_type_id );

		$second_current_views = gravityview_get_current_views();

		// Check to make sure add_view worked properly
		$this->assertEquals( $second_view_post_type_id, $second_current_views[ $second_view_post_type_id ]['id'] );

		// Now two Views
		$this->assertEquals( 2, sizeof( $second_current_views ) );

	}

	/**
	 * @covers gravityview_sanitize_html_class()
	 */
	function test_gravityview_sanitize_html_class() {

		$classes = array(

			// basic
			'example' => gravityview_sanitize_html_class( 'example' ),

			// Don't strip dashes
			'example-dash' => gravityview_sanitize_html_class( 'example-dash' ),

			// Keep spaces
			'example dash' => gravityview_sanitize_html_class( 'example dash' ),

			// Implode with spaces
			'example dash bar' => gravityview_sanitize_html_class( array('example', 'dash', 'bar' ) ),

			// Again, don't strip spaces and implode
			'example-dash bar' => gravityview_sanitize_html_class( array('example-dash', 'bar' ) ),

			// Don't strip numbers or caps
			'Foo Bar0' => gravityview_sanitize_html_class( array('Foo', 'Bar0' ) ),

			// Strip not A-Z a-z 0-9 _ -
			'Foo Bar2_-' => gravityview_sanitize_html_class( 'Foo Bar2!_-' ),
		);

        foreach ( $classes as $expected => $formatted ) {
        	$this->assertEquals( $expected, $formatted );
        }

	}

	/**
	 * @group api
	 */
	function test_gravityview_format_link_DEFAULT() {

		$urls = array(

			// NOT URL
			'asdsadas' => 'asdsadas',

			// Path to root directory
			'http://example.com/example/' => 'example.com',
			'http://example.com/example/1/2/3/4/5/6/7/?example=123' => 'example.com',
			'https://example.com/example/page.html' => 'example.com',

			// No WWW
			'http://example.com' => 'example.com', // http
			'https://example.com' => 'example.com', // https
			'https://example.com/' => 'example.com', // trailing slash
			'https://example.com?example=123' => 'example.com', // no slash qv
			'https://example.com/?example=123' => 'example.com', // trailing slash qv

			// strip WWW
			'http://www.example.com' => 'example.com', // http
			'https://www.example.com' => 'example.com', // https
			'https://www.example.com/' => 'example.com', // trailing slash
			'https://www.example.com?example=123' => 'example.com', // no slash qv
			'https://www.example.com/?example=123' => 'example.com', // trailing slash qv
			'https://www.example.com/?example=123&test<0>=123' => 'example.com', // complex qv

			// strip subdomain
			'http://demo.example.com' => 'example.com', // http
			'https://demo.example.com' => 'example.com', // https
			'https://demo.example.com/' => 'example.com', // trailing slash
			'https://demo.example.com?example=123' => 'example.com', // no slash qv
			'https://demo.example.com/?example=123' => 'example.com', // trailing slash qv

			// Don't strip actual domain when using 2nd tier TLD
			'http://example.ac.za' => 'example.ac.za',
			'http://example.gov.za'=> 'example.gov.za',
			'http://example.law.za'=> 'example.law.za',
			'http://example.school.za'=> 'example.school.za',
			'http://example.me.uk'=> 'example.me.uk',
			'http://example.tm.fr'=> 'example.tm.fr',
			'http://example.asso.fr'=> 'example.asso.fr',
			'http://example.com.fr'=> 'example.com.fr',
			'http://example.telememo.au'=> 'example.telememo.au',
			'http://example.cg.yu'=> 'example.cg.yu',
			'http://example.msk.ru'=> 'example.msk.ru',
			'http://example.irkutsks.ru'=> 'example.irkutsks.ru',
			'http://example.com.ru'=> 'example.com.ru',
			'http://example.sa.au'=> 'example.sa.au',
			'http://example.act.au'=> 'example.act.au',
			'http://example.net.uk'=> 'example.net.uk',
			'http://example.police.uk'=> 'example.police.uk',
			'http://example.plc.uk'=> 'example.plc.uk',
			'http://example.co.uk'=> 'example.co.uk',
			'http://example.gov.uk'=> 'example.gov.uk',
			'http://example.mod.uk'=> 'example.mod.uk',

			// Strip subdomains in 2nd tier TLD
			'http://demo.example.ac.za' => 'example.ac.za',
			'http://demo.example.gov.za'=> 'example.gov.za',
			'http://demo.example.law.za'=> 'example.law.za',
			'http://demo.example.school.za'=> 'example.school.za',
			'http://demo.example.me.uk'=> 'example.me.uk',
			'http://demo.example.tm.fr'=> 'example.tm.fr',
			'http://demo.example.asso.fr'=> 'example.asso.fr',
			'http://demo.example.com.fr'=> 'example.com.fr',
			'http://demo.example.telememo.au'=> 'example.telememo.au',
			'http://demo.example.cg.yu'=> 'example.cg.yu',
			'http://demo.example.msk.ru'=> 'example.msk.ru',
			'http://demo.example.irkutsks.ru'=> 'example.irkutsks.ru',
			'http://demo.example.com.ru'=> 'example.com.ru',
			'http://demo.example.sa.au'=> 'example.sa.au',
			'http://demo.example.act.au'=> 'example.act.au',
			'http://demo.example.net.uk'=> 'example.net.uk',
			'http://demo.example.police.uk'=> 'example.police.uk',
			'http://demo.example.plc.uk'=> 'example.plc.uk',
			'http://demo.example.co.uk'=> 'example.co.uk',
			'http://demo.example.gov.uk'=> 'example.gov.uk',
			'http://demo.example.mod.uk'=> 'example.mod.uk',
		);

        foreach ( $urls as $original => $expected ) {

        	$formatted = gravityview_format_link( $original );

			$this->assertEquals( $expected, $formatted, 'Failed the formatting test' );

        }

	}

	/**
	 * @group api
	 */
	function test_gravityview_format_link_WHEN_FILTER_ROOTONLY_FALSE() {

		// SET FILTER TO FALSE
		add_filter( 'gravityview_anchor_text_rootonly', '__return_false' );

		$urls = array(

			// DO NOT strip subdomains in 2nd tier TLD
			'http://example.com/path/to/webpage' => 'example.com/path/to/webpage',
			'http://example.com/path/to/webpage/' => 'example.com/path/to/webpage/',
			'http://example.com/webpage/?aasdasd=asdasd&asdasdasd=484ignasf' => 'example.com/webpage/',
			'http://example.com/webpage.html' => 'example.com/webpage.html',
		);

		foreach ( $urls as $original => $expected ) {

        	$formatted = gravityview_format_link( $original );

			$this->assertEquals( $expected, $formatted, 'Failed the formatting test' );

        }

        // RETURN FILTER TO TRUE
        add_filter( 'gravityview_anchor_text_rootonly', '__return_true' );

	}

	/**
	 * @group api
	 * @covers gravityview_format_link()
	 */
	function test_gravityview_format_link_WHEN_FILTER_NOSUBDOMAIN_FALSE() {

		// SET FILTER TO FALSE
		add_filter( 'gravityview_anchor_text_nosubdomain', '__return_false' );

		$urls = array(

			// DO NOT strip subdomains in 2nd tier TLD
			'http://demo.example.ac.za' => 'demo.example.ac.za',
			'http://demo.example.gov.za' => 'demo.example.gov.za',
			'http://demo.example.law.za' => 'demo.example.law.za',
			'http://demo.example.school.za' => 'demo.example.school.za',
			'http://demo.example.me.uk' => 'demo.example.me.uk',
			'http://demo.example.tm.fr' => 'demo.example.tm.fr',
			'http://demo.example.asso.fr' => 'demo.example.asso.fr',
			'http://demo.example.com.fr' => 'demo.example.com.fr',
			'http://demo.example.telememo.au' => 'demo.example.telememo.au',
			'http://demo.example.cg.yu' => 'demo.example.cg.yu',
			'http://demo.example.msk.ru' => 'demo.example.msk.ru',
			'http://demo.example.irkutsks.ru' => 'demo.example.irkutsks.ru',
			'http://demo.example.com.ru' => 'demo.example.com.ru',
			'http://demo.example.sa.au' => 'demo.example.sa.au',
			'http://demo.example.act.au' => 'demo.example.act.au',
			'http://demo.example.net.uk' => 'demo.example.net.uk',
			'http://demo.example.police.uk' => 'demo.example.police.uk',
			'http://demo.example.plc.uk' => 'demo.example.plc.uk',
			'http://demo.example.co.uk' => 'demo.example.co.uk',
			'http://demo.example.gov.uk' => 'demo.example.gov.uk',
			'http://demo.example.mod.uk' => 'demo.example.mod.uk',
		);

		foreach ( $urls as $original => $expected ) {

        	$formatted = gravityview_format_link( $original );

			$this->assertEquals( $expected, $formatted, 'Failed the formatting test' );

        }

        // RETURN FILTER TO TRUE
        add_filter( 'gravityview_anchor_text_nosubdomain', '__return_true' );

	}

	/**
	 * @group api
	 */
	function test_gravityview_format_link_WHEN_FILTER_NOQUERYSTRING_FALSE() {

		// SET FILTER TO FALSE
		add_filter( 'gravityview_anchor_text_noquerystring', '__return_false' );

		$urls = array(

			// NOT URL
			'asdsadas' => 'asdsadas',

			// No WWW
			'https://example.com?example=123' => 'example.com?example=123', // no slash qv
			'https://example.com/?example=123' => 'example.com?example=123', // trailing slash qv

			// strip WWW
			'https://www.example.com?example=123' => 'example.com?example=123', // no slash qv
			'https://www.example.com/?example=123' => 'example.com?example=123', // trailing slash qv

			// no subdomain
			'https://demo.example.com?example=123' => 'example.com?example=123', // no slash qv
			'https://demo.example.com/?example=123' => 'example.com?example=123', // trailing slash qv
		);

		foreach ( $urls as $original => $expected ) {

        	$formatted = gravityview_format_link( $original );

			$this->assertEquals( $expected, $formatted, 'Failed the formatting test' );

        }

        // RETURN FILTER TO TRUE
        add_filter( 'gravityview_anchor_text_noquerystring', '__return_true' );
	}
}
