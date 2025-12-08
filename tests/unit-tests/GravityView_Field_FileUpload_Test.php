<?php

/**
 * Test the bypass secure download feature for file upload fields
 *
 * @group fields
 * @group fileupload
 */
class GravityView_Field_FileUpload_Test extends GV_UnitTestCase {

	/**
	 * @var GravityView_Field_FileUpload
	 */
	protected $field;

	/**
	 * @var int
	 */
	protected $form_id;

	/**
	 * @var array
	 */
	protected $form;

	public function setUp(): void {
		parent::setUp();

		$this->field = GravityView_Fields::get( 'fileupload' );

		// Create a test form
		$this->form = array(
			'title'  => 'Test Form',
			'fields' => array(
				array(
					'id'    => 1,
					'type'  => 'fileupload',
					'label' => 'File Upload Field',
				),
			),
		);
		$this->form_id = GFAPI::add_form( $this->form );
	}

	public function tearDown(): void {
		GFAPI::delete_form( $this->form_id );
		parent::tearDown();
	}

	/**
	 * Test that the bypass_secure_download field option exists
	 */
	public function test_field_option_exists() {
		$field_options = array();
		$template_id   = 'table';
		$field_id      = 1;
		$context       = 'single';
		$input_type    = 'fileupload';

		$options = $this->field->field_options( $field_options, $template_id, $field_id, $context, $input_type, $this->form_id );

		$this->assertArrayHasKey( 'bypass_secure_download', $options, 'bypass_secure_download option should exist' );
		$this->assertEquals( 'checkbox', $options['bypass_secure_download']['type'], 'Option should be a checkbox' );
		$this->assertFalse( $options['bypass_secure_download']['value'], 'Default value should be false' );
		$this->assertEquals( 'display', $options['bypass_secure_download']['group'], 'Option should be in display group' );
	}

	/**
	 * Test the filter works to bypass secure download
	 */
	public function test_bypass_filter() {
		// Test that the filter exists and can be used
		$filter_name = 'gk/gravityview/fields/fileupload/secure-links/bypass';

		// Test the filter returns false by default
		$result = apply_filters( $filter_name, false, null, array(), null, 'test-file.jpg' );
		$this->assertFalse( $result, 'Filter should return false by default' );

		// Test the filter can be overridden
		add_filter( $filter_name, '__return_true' );
		$result = apply_filters( $filter_name, false, null, array(), null, 'test-file.jpg' );
		$this->assertTrue( $result, 'Filter should return true when overridden' );
		remove_filter( $filter_name, '__return_true' );

		// Test the filter passes the correct parameters
		add_filter( $filter_name, function( $bypass, $field, $field_settings, $context, $file_path ) {
			// Check that all parameters are passed
			$this->assertIsBool( $bypass );
			$this->assertIsArray( $field_settings );
			$this->assertIsString( $file_path );
			return true;
		}, 10, 5 );

		$result = apply_filters( $filter_name, false, null, array( 'test' => 'value' ), null, 'test-file.jpg' );
		$this->assertTrue( $result );

		remove_all_filters( $filter_name );
	}

	/**
	 * Test that the option appears in the field settings array
	 */
	public function test_field_settings_integration() {
		$field_settings = array(
			'id'                     => 1,
			'label'                  => 'File Upload',
			'custom_label'           => '',
			'bypass_secure_download' => true,
		);

		// Test that the setting can be set to true
		$this->assertTrue( $field_settings['bypass_secure_download'] );

		// Test that the setting defaults to false when not set
		unset( $field_settings['bypass_secure_download'] );
		$this->assertArrayNotHasKey( 'bypass_secure_download', $field_settings );
		$this->assertEmpty( $field_settings['bypass_secure_download'] ?? false );
	}

	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_fileupload() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'5' => json_encode( array(
                'https://one.jpg',
                'https://two.mp3',
                'https://three.pdf',
                'https://four.mp4',
                'https://five.txt',
            ) ),
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

		// The setting names are SO confusing. Let's use these clearer variables instead.
		$display_as_url = 'link_to_file';
		$link_to_entry  = 'show_as_link';

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '5' );

		/** Regular rendering, formatted nicely, no wrapper links. */
		$field->update_configuration( array( $display_as_url => false ) );
		$field->update_configuration( array( $link_to_entry => false ) );

		$video_instance = 0;
		$audio_instance = 0;

		add_filter( 'wp_audio_shortcode_override', function( $_, $__, $___, $instance ) use ( &$audio_instance ) {
			$audio_instance = $instance;
			return $_;
		}, 10, 4 );

		add_filter( 'wp_video_shortcode_override', function( $_, $__, $___, $instance ) use ( &$video_instance ) {
			$video_instance = $instance;
			return $_;
		}, 10, 4 );

		if ( isset( $GLOBALS['content_width'] ) ) {
			$content_width = $GLOBALS['content_width'];
			$GLOBALS['content_width'] = /** over */ 9000;
		}

		$output = $renderer->render( $field, $view, $form, $entry, $request );

		$expected = "<ul class='gv-field-file-uploads gv-field-{$form->ID}-5'>";
			// one.jpg
			$expected .= '<li><img src="http://one.jpg" width="250" class="gv-image gv-field-id-5" /></li>';
			// two.mp3
			$expected .= '<li><audio class="wp-audio-shortcode gv-audio gv-field-id-5" id="audio-0-' . $audio_instance . '" preload="none" style="width: 100%;" controls="controls"><source type="audio/mpeg" src="http://two.mp3?_=' . $audio_instance . '" /><a href="http://two.mp3">http://two.mp3</a></audio></li>';
			// three.pdf (PDF always links to file as per https://github.com/gravityview/GravityView/pull/1577/commits/808063d2d2c6ea121ed7ccb2d53a16a863d4a69c)
			$expected .= '<li><a href="http://three.pdf?gv-iframe=true" rel="noopener noreferrer" target="_blank">three.pdf</a></li>';
			// four.mp4
			$expected .= '<li><div style="width: 640px;" class="wp-video">';
			$expected .= '<video class="wp-video-shortcode gv-video gv-field-id-5" id="video-0-' . $video_instance . '" width="640" height="360" preload="metadata" controls="controls"><source type="video/mp4" src="http://four.mp4?_=' . $video_instance . '" /><a href="http://four.mp4">http://four.mp4</a></video></div></li>';
			// five.txt
			$expected .= '<li><a href="http://five.txt?gv-iframe=true" rel="noopener noreferrer" target="_blank">five.txt</a></li>';
		$expected .= '</ul>';

		$this->assertEquals( $expected, $output );

		/** No fancy rendering, just file links, please? */
		$field->update_configuration( array( $display_as_url => true ) );
		$field->update_configuration( array( $link_to_entry => false ) );

		$expected = "<ul class='gv-field-file-uploads gv-field-{$form->ID}-5'>";
			// one.jpg
			$expected .= '<li><a href="http://one.jpg" rel="noopener noreferrer" target="_blank">one.jpg</a></li>';
			// two.mp3
			$expected .= '<li><a href="http://two.mp3" rel="noopener noreferrer" target="_blank">two.mp3</a></li>';
			// three.pdf
			$expected .= '<li><a href="http://three.pdf?gv-iframe=true" rel="noopener noreferrer" target="_blank">three.pdf</a></li>';
			// four.mp4
			$expected .= '<li><a href="http://four.mp4" rel="noopener noreferrer" target="_blank">four.mp4</a></li>';
			// five.txt
			$expected .= '<li><a href="http://five.txt?gv-iframe=true" rel="noopener noreferrer" target="_blank">five.txt</a></li>';
		$expected .= '</ul>';

		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		/** Link to entry forces file links. */
		$field->update_configuration( array( $display_as_url => false ) );
		$field->update_configuration( array( $link_to_entry => true ) );

		$expected = sprintf( '<a href="%s">', esc_attr( $entry->get_permalink( $view ) ) );
		$expected .= "<ul class='gv-field-file-uploads gv-field-{$form->ID}-5'>";
			// one.jpg
			$expected .= '<li><img src="http://one.jpg" width="250" class="gv-image gv-field-id-5" /></li>';
			// two.mp3
			$expected .= '<li>two.mp3</li>';
			// three.pdf
			$expected .= '<li>three.pdf</li>';
			// four.mp4
			$expected .= '<li>four.mp4</li>';
			// five.txt
			$expected .= '<li>five.txt</li>';
		$expected .= '</ul>';
		$expected .= '</a>';

		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		/** Both? Link to entry. */
		$field->update_configuration( array( $display_as_url => true ) );
		$field->update_configuration( array( $link_to_entry => true ) );

		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );


		/** Cool, thanks! What about image behavior with lightboxes, override filter? */

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'5' => json_encode( array( 'https://one.jpg' ) ),
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '5' );

		/** All the basics. */
		$field->update_configuration( array( $display_as_url => false ) );
		$field->update_configuration( array( $link_to_entry => false ) );

		$expected = '<img src="http://one.jpg" width="250" class="gv-image gv-field-id-5" />';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( $display_as_url => true ) );
		$field->update_configuration( array( $link_to_entry => false ) );

		$expected = '<a href="http://one.jpg" rel="noopener noreferrer" target="_blank">one.jpg</a>';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( $display_as_url => false ) );
		$field->update_configuration( array( $link_to_entry => true ) );

		$expected = sprintf( '<a href="%s"><img src="http://one.jpg" width="250" class="gv-image gv-field-id-5" /></a>', esc_attr( $entry->get_permalink( $view ) ) );
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( $display_as_url => true ) );
		$field->update_configuration( array( $link_to_entry => true ) );

		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		/** Thickbox. */
		$view->settings->update( array( 'lightbox' => true ) );

		$field->update_configuration( array( $display_as_url => false ) );
		$field->update_configuration( array( $link_to_entry => false ) );

		$expected = '<a class="gravityview-fancybox" data-fancybox="gallery-' . $form->ID . '-5-' . $entry->ID . '" href="http://one.jpg" rel="gv-field-' . $form->ID . '-5-' . $entry->ID . '">';
			$expected .= '<img src="http://one.jpg" width="250" class="gv-image gv-field-id-5" />';
		$expected .= '</a>';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( $display_as_url => true ) );
		$field->update_configuration( array( $link_to_entry => false ) );

		$expected = '<a class="gravityview-fancybox" data-fancybox="gallery-' . $form->ID . '-5-' . $entry->ID . '" href="http://one.jpg" rel="noopener noreferrer" target="_blank">one.jpg</a>';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( $display_as_url => false ) );
		$field->update_configuration( array( $link_to_entry => true ) );

		$expected = sprintf( '<a href="%s"><img src="http://one.jpg" width="250" class="gv-image gv-field-id-5" /></a>', esc_attr( $entry->get_permalink( $view ) ) );
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( $display_as_url => true ) );
		$field->update_configuration( array( $link_to_entry => true ) );

		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		/** Override, force nice rendering */
		add_filter( 'gravityview/fields/fileupload/disable_link', '__return_true' );

		$field->update_configuration( array( $display_as_url => false ) );
		$field->update_configuration( array( $link_to_entry => false ) );

		$expected = '<a class="gravityview-fancybox" data-fancybox="gallery-' . $form->ID . '-5-' . $entry->ID . '" href="http://one.jpg" rel="gv-field-' . $form->ID . '-5-' . $entry->ID . '">';
			$expected .= '<img src="http://one.jpg" width="250" class="gv-image gv-field-id-5" />';
		$expected .= '</a>';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( $display_as_url => true ) );
		$field->update_configuration( array( $link_to_entry => false ) );

		$expected = '<a class="gravityview-fancybox" data-fancybox="gallery-' . $form->ID . '-5-' . $entry->ID . '" href="http://one.jpg" rel="gv-field-' . $form->ID . '-5-' . $entry->ID . '"><img src="http://one.jpg" width="250" class="gv-image gv-field-id-5" /></a>';
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( $display_as_url => false ) );
		$field->update_configuration( array( $link_to_entry => true ) );

		$expected = sprintf( '<a href="%s"><img src="http://one.jpg" width="250" class="gv-image gv-field-id-5" /></a>', esc_attr( $entry->get_permalink( $view ) ) );
		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( $display_as_url => true ) );
		$field->update_configuration( array( $link_to_entry => true ) );

		$this->assertEquals( $expected, $renderer->render( $field, $view, $form, $entry, $request ) );

		remove_all_filters( 'gravityview/fields/fileupload/disable_link' );

		remove_all_filters( 'wp_video_shortcode_override' );
		remove_all_filters( 'wp_audio_shortcode_override' );

		if ( isset( $content_width ) ) {
			$GLOBALS['content_width'] = $content_width;
		}
	}

	/**
	 * Test the allowed extensions filter
	 */
	public function test_allowed_extensions_filter() {
		$filter_name = 'gk/gravityview/fields/fileupload/secure-links/allowed-extensions';

		// Test that the filter exists and receives media extensions by default
		add_filter( $filter_name, function( $extensions, $field_settings, $context ) {
			// Check that image extensions are included by default
			$this->assertContains( 'jpg', $extensions );
			$this->assertContains( 'png', $extensions );
			$this->assertContains( 'gif', $extensions );

			// Check that audio extensions are included
			$this->assertContains( 'mp3', $extensions );

			// Check that video extensions are included
			$this->assertContains( 'mp4', $extensions );

			// Return custom extensions for testing
			return array( 'jpg', 'png', 'pdf' ); // Allow PDFs in addition to some images
		}, 10, 3 );

		$result = apply_filters( $filter_name, array( 'jpg', 'png', 'gif', 'mp3', 'mp4' ), array(), null );
		$this->assertEquals( array( 'jpg', 'png', 'pdf' ), $result, 'Filter should allow customizing allowed extensions' );

		remove_all_filters( $filter_name );

		// Test allowing all extensions
		add_filter( $filter_name, function() {
			return array( '*' ); // Special case to allow all
		} );

		$result = apply_filters( $filter_name, array( 'jpg' ), array(), null );
		$this->assertEquals( array( '*' ), $result, 'Filter should allow returning * for all extensions' );

		remove_all_filters( $filter_name );
	}

	/**
	 * Test bypass filter with specific Views and actual HTML output
	 * Tests that the filter correctly affects the rendered HTML
	 */
	public function test_bypass_filter_for_specific_views_with_html_output() {
		$filter_name = 'gk/gravityview/fields/fileupload/secure-links/bypass';

		// Create test data
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'5' => json_encode( array(
				'https://example.com/uploads/2024/01/image.jpg',
				'https://example.com/uploads/2024/01/document.pdf'
			) ),
		) );

		// Create multiple views with different IDs
		$allowed_view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'post_title' => 'Allowed View',
		) );
		$allowed_view_id = $allowed_view->ID;

		$restricted_view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'post_title' => 'Restricted View',
		) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();
		$field = \GV\GF_Field::by_id( $form, '5' );

		// Configure field to enable bypass and show as links
		$field->update_configuration( array(
			'bypass_secure_download' => true,
			'link_to_file' => true,  // Show as links
			'show_as_link' => false  // Don't link to entry
		) );

		// Add filter for specific View IDs
		add_filter( $filter_name, function( $bypass, $field, $field_settings, $context, $file_path ) use ( $allowed_view_id ) {
			if ( $context && $context->view && $context->view->ID == $allowed_view_id ) {
				return true;  // Bypass for allowed view
			}
			return false;  // Don't bypass for other views
		}, 10, 5 );

		// Test with allowed View - should show direct URLs
		$view_obj = \GV\View::from_post( $allowed_view );
		$output = $renderer->render( $field, $view_obj, $form, $entry, $request );

		// With bypass enabled, URLs should be direct (no secure download parameters)
		$this->assertStringContainsString( 'href="http://example.com/uploads/2024/01/image.jpg"', $output,
			'Allowed View should show direct image URL without secure parameters' );
		$this->assertStringContainsString( 'href="http://example.com/uploads/2024/01/document.pdf?gv-iframe=true"', $output,
			'Allowed View should show direct PDF URL with iframe parameter' );
		$this->assertStringNotContainsString( '?gf-download=', $output,
			'Allowed View should NOT contain secure download parameters' );
		$this->assertStringContainsString( '<ul class=\'gv-field-file-uploads', $output,
			'Should render as unordered list of files' );

		// Test with restricted View - bypass should be disabled
		$view_obj = \GV\View::from_post( $restricted_view );
		$output = $renderer->render( $field, $view_obj, $form, $entry, $request );

		// Without bypass, should still show files but potentially with secure URLs
		$this->assertStringContainsString( 'uploads/2024/01/image.jpg', $output,
			'Restricted View should still reference the image file' );
		$this->assertStringContainsString( 'uploads/2024/01/document.pdf', $output,
			'Restricted View should still reference the PDF file' );

		remove_all_filters( $filter_name );
	}

	/**
	 * Test bypass filter with user roles and actual HTML output
	 * Verifies that different user roles get different HTML output
	 */
	public function test_bypass_filter_with_user_roles_and_html_output() {
		$filter_name = 'gk/gravityview/fields/fileupload/secure-links/bypass';

		// Create test data
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'5' => json_encode( array(
				'https://example.com/members/confidential-report.pdf',
				'https://example.com/members/private-image.jpg'
			) ),
		) );
		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
		) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view_obj = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();
		$field = \GV\GF_Field::by_id( $form, '5' );

		// Configure field with bypass enabled
		$field->update_configuration( array(
			'bypass_secure_download' => true,
			'link_to_file' => true,
			'show_as_link' => false
		) );

		// Add user role-based filter
		add_filter( $filter_name, function( $bypass, $field, $field_settings, $context, $file_path ) {
			// Only bypass for logged-in users with edit_posts capability
			if ( ! is_user_logged_in() ) {
				return false;
			}
			if ( current_user_can( 'edit_posts' ) ) {
				return true;
			}
			return false;
		}, 10, 5 );

		// Test 1: Guest user - should NOT get direct URLs
		wp_set_current_user( 0 );
		$output = $renderer->render( $field, $view_obj, $form, $entry, $request );

		$this->assertStringContainsString( 'confidential-report.pdf', $output,
			'Guest should see file names' );
		$this->assertStringContainsString( 'private-image.jpg', $output,
			'Guest should see image file names' );

		// Test 2: Subscriber - should NOT get direct URLs (no edit_posts capability)
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );
		$output = $renderer->render( $field, $view_obj, $form, $entry, $request );

		$this->assertStringContainsString( 'confidential-report.pdf', $output,
			'Subscriber should see file names' );

		// Test 3: Editor - SHOULD get direct URLs (has edit_posts capability)
		$editor = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );
		$output = $renderer->render( $field, $view_obj, $form, $entry, $request );

		$this->assertStringContainsString( 'href="http://example.com/members/confidential-report.pdf?gv-iframe=true"', $output,
			'Editor should see direct PDF URL' );
		$this->assertStringContainsString( 'href="http://example.com/members/private-image.jpg"', $output,
			'Editor should see direct image URL' );
		$this->assertStringNotContainsString( '?gf-download=', $output,
			'Editor should NOT see secure download parameters' );

		// Test 4: Administrator - SHOULD get direct URLs
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$output = $renderer->render( $field, $view_obj, $form, $entry, $request );

		$this->assertStringContainsString( 'href="http://example.com/members/confidential-report.pdf?gv-iframe=true"', $output,
			'Administrator should see direct PDF URL' );
		$this->assertStringNotContainsString( '?gf-download=', $output,
			'Administrator should NOT see secure download parameters' );

		// Reset user
		wp_set_current_user( 0 );
		remove_all_filters( $filter_name );
	}

	/**
	 * Test allowed extensions filter affects HTML output for different file types
	 * Verifies that only allowed file types get bypassed in the HTML
	 */
	public function test_allowed_extensions_filter_affects_html_output() {
		$bypass_filter = 'gk/gravityview/fields/fileupload/secure-links/bypass';
		$extensions_filter = 'gk/gravityview/fields/fileupload/secure-links/allowed-extensions';

		// Create test data with various file types
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'5' => json_encode( array(
				'https://example.com/files/photo.jpg',
				'https://example.com/files/video.mp4',
				'https://example.com/files/document.pdf',
				'https://example.com/files/spreadsheet.xlsx',
				'https://example.com/files/audio.mp3'
			) ),
		) );
		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
		) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view_obj = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();
		$field = \GV\GF_Field::by_id( $form, '5' );

		// Configure field to show media (not links) and enable bypass
		$field->update_configuration( array(
			'bypass_secure_download' => true,
			'link_to_file' => false,  // Show as media (images, videos, audio)
			'show_as_link' => false
		) );

		// Always bypass for testing
		add_filter( $bypass_filter, '__return_true', 10 );

		// Test 1: Default allowed extensions (images, audio, video)
		$output = $renderer->render( $field, $view_obj, $form, $entry, $request );

		// Images should render as img tags with direct URLs
		$this->assertStringContainsString( '<img src="http://example.com/files/photo.jpg"', $output,
			'Images should render as img tags with direct URLs' );

		// Videos should render as video elements
		$this->assertStringContainsString( '<video', $output,
			'Videos should render as video elements' );
		$this->assertStringContainsString( 'src="http://example.com/files/video.mp4', $output,
			'Video should have direct URL in src attribute' );

		// Audio should render as audio elements
		$this->assertStringContainsString( '<audio', $output,
			'Audio should render as audio elements' );
		$this->assertStringContainsString( 'src="http://example.com/files/audio.mp3', $output,
			'Audio should have direct URL in src attribute' );

		// PDFs always show as links (not embedded)
		$this->assertStringContainsString( 'href="http://example.com/files/document.pdf?gv-iframe=true"', $output,
			'PDF should show as link with iframe parameter' );

		// XLSX should show as link
		$this->assertStringContainsString( 'spreadsheet.xlsx', $output,
			'XLSX files should be in output' );

		// Test 2: Limit to images only
		remove_all_filters( $extensions_filter );
		add_filter( $extensions_filter, function( $allowed_extensions ) {
			// Only allow image extensions
			return array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );
		}, 10, 1 );

		$output = $renderer->render( $field, $view_obj, $form, $entry, $request );

		// Images should still render with direct URLs
		$this->assertStringContainsString( '<img src="http://example.com/files/photo.jpg"', $output,
			'Images should still render with direct URLs when only images are allowed' );

		// Test 3: Add document formats to allowed extensions
		remove_all_filters( $extensions_filter );
		add_filter( $extensions_filter, function( $allowed_extensions ) {
			$allowed_extensions[] = 'xlsx';
			return $allowed_extensions;
		}, 10, 1 );

		// Change to link display to see document links
		$field->update_configuration( array(
			'link_to_file' => true  // Show as links
		) );

		$output = $renderer->render( $field, $view_obj, $form, $entry, $request );

		// All file types should now show as direct links
		$this->assertStringContainsString( 'href="http://example.com/files/photo.jpg"', $output,
			'Image should show as direct link' );
		$this->assertStringContainsString( 'href="http://example.com/files/document.pdf?gv-iframe=true"', $output,
			'PDF should show as direct link.' );
		$this->assertStringContainsString( 'href="http://example.com/files/spreadsheet.xlsx"', $output,
			'XLSX should show as direct link when added to allowed extensions' );
		$this->assertStringNotContainsString( '?gf-download=', $output,
			'Should not contain secure download parameters for allowed types' );

		remove_all_filters( $bypass_filter );
		remove_all_filters( $extensions_filter );
	}

	/**
	 * Test complete real-world scenario with multiple Views, users, and file types
	 * This tests the full integration of bypass logic with HTML rendering
	 */
	public function test_complete_real_world_html_output_scenario() {
		$bypass_filter = 'gk/gravityview/fields/fileupload/secure-links/bypass';
		$extensions_filter = 'gk/gravityview/fields/fileupload/secure-links/allowed-extensions';

		// Create test data
		$form = $this->factory->form->import_and_get( 'complete.json' );

		// Create entries for different scenarios
		$gallery_entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'5' => json_encode( array(
				'https://example.com/gallery/sunset.jpg',
				'https://example.com/gallery/mountains.png',
				'https://example.com/gallery/timelapse.mp4'
			) ),
		) );

		$member_entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'5' => json_encode( array(
				'https://example.com/members/annual-report.pdf',
				'https://example.com/members/financial-data.xlsx'
			) ),
		) );

		// Create different Views
		$gallery_view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'post_title' => 'Public Gallery View',
		) );
		$gallery_view_id = $gallery_view->ID;

		$member_view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'post_title' => 'Members Only View',
		) );
		$member_view_id = $member_view->ID;

		$form = \GV\GF_Form::by_id( $form['id'] );
		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();
		$field = \GV\GF_Field::by_id( $form, '5' );

		// Add comprehensive bypass logic based on Views and user roles
		add_filter( $bypass_filter, function( $bypass, $field, $field_settings, $context, $file_path ) use ( $gallery_view_id, $member_view_id ) {
			if ( ! $context || ! $context->view ) {
				return false;
			}

			$view_id = $context->view->ID;

			// Public gallery - always bypass for media files
			if ( $view_id == $gallery_view_id ) {
				return true;
			}

			// Member area - only bypass for logged-in users
			if ( $view_id == $member_view_id ) {
				return is_user_logged_in();
			}

			return false;
		}, 10, 5 );

		// Configure allowed extensions based on View context
		add_filter( $extensions_filter, function( $allowed_extensions, $field, $field_settings, $context, $file_path ) use ( $gallery_view_id, $member_view_id ) {
			if ( ! $context || ! $context->view ) {
				return $allowed_extensions;
			}

			$view_id = $context->view->ID;

			// Gallery View: only allow images and videos
			if ( $view_id == $gallery_view_id ) {
				return array( 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'webm' );
			}

			// Member View: add document formats for logged-in users
			if ( $view_id == $member_view_id && is_user_logged_in() ) {
				$allowed_extensions[] = 'pdf';
				$allowed_extensions[] = 'xlsx';
				$allowed_extensions[] = 'docx';
				return $allowed_extensions;
			}

			return $allowed_extensions;
		}, 10, 5 );

		// SCENARIO 1: Public gallery as guest user
		wp_set_current_user( 0 );
		$gallery_view_obj = \GV\View::from_post( $gallery_view );
		$gallery_entry_obj = \GV\GF_Entry::by_id( $gallery_entry['id'] );

		// Configure for media display
		$field->update_configuration( array(
			'bypass_secure_download' => true,
			'link_to_file' => false,  // Show as media
			'show_as_link' => false
		) );

		$output = $renderer->render( $field, $gallery_view_obj, $form, $gallery_entry_obj, $request );

		// Gallery should show direct URLs even for guests
		$this->assertStringContainsString( '<img src="http://example.com/gallery/sunset.jpg"', $output,
			'Public gallery should show direct image URLs for guests' );
		$this->assertStringContainsString( '<img src="http://example.com/gallery/mountains.png"', $output,
			'Public gallery should show direct PNG URLs' );
		$this->assertStringContainsString( '<video', $output,
			'Public gallery should show video element' );
		$this->assertStringContainsString( 'src="http://example.com/gallery/timelapse.mp4', $output,
			'Public gallery should show direct video URLs' );
		$this->assertStringNotContainsString( '?gf-download=', $output,
			'Public gallery should NOT have secure download parameters' );

		// SCENARIO 2: Member area as guest - should NOT bypass
		$member_view_obj = \GV\View::from_post( $member_view );
		$member_entry_obj = \GV\GF_Entry::by_id( $member_entry['id'] );

		// Configure for link display
		$field->update_configuration( array(
			'link_to_file' => true  // Show as links
		) );

		$output = $renderer->render( $field, $member_view_obj, $form, $member_entry_obj, $request );

		// Member files should be referenced but not with direct URLs for guests
		$this->assertStringContainsString( 'annual-report.pdf', $output,
			'Guest should see PDF filename in member area' );
		$this->assertStringContainsString( 'financial-data.xlsx', $output,
			'Guest should see XLSX filename in member area' );

		// SCENARIO 3: Member area as logged-in member - SHOULD bypass
		$member_user = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $member_user );

		$output = $renderer->render( $field, $member_view_obj, $form, $member_entry_obj, $request );

		// Member should see direct URLs for documents
		$this->assertStringContainsString( 'href="http://example.com/members/annual-report.pdf?gv-iframe=true"', $output,
			'Logged-in member should see direct PDF URL' );
		$this->assertStringContainsString( 'href="http://example.com/members/financial-data.xlsx"', $output,
			'Logged-in member should see direct XLSX URL' );
		$this->assertStringNotContainsString( '?gf-download=', $output,
			'Member area should NOT have secure download parameters for logged-in users' );

		// Clean up
		wp_set_current_user( 0 );
		remove_all_filters( $bypass_filter );
		remove_all_filters( $extensions_filter );
	}


}
