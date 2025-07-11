<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * Issues uncovered in 2.0
 */
class GV_20_Issues_Test extends GV_UnitTestCase {
	function setUp() : void {
		$this->_reset_context();

		parent::setUp();
	}

	function tearDown() : void {
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

	public function test_search_widget_embedded() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

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

		$entries = array();
		foreach ( range( 1, 8 ) as $i ) {
			$entry = $this->factory->entry->create_and_get( array(
				'form_id' => $form['id'],
				'status' => 'active',
				'16' => sprintf( '[%d] Entry %s', $i, wp_generate_password( 12 ) ),
			) );
			$entries []= \GV\GF_Entry::by_id( $entry['id'] );
		}

		global $post;
		$post = $this->factory->post->create_and_get();
		$post->post_content = sprintf( '[gravityview id="%d"]', $view->ID );

		$content = apply_filters( 'the_content', $post->post_content );

		$this->assertStringContainsString( get_permalink( $post->ID ), $content );
	}

	/**
	 * @since 2.0.6.2
	 */
	function test_gv_age_shortcode() {
		$this->markTestSkipped('Flaky test; temporarily disable');

		add_shortcode( 'gv_age_1_x', array( $this, '_gv_age_1_x_shortcode' ) );
		add_shortcode( 'gv_age_2_0', array( $this, '_gv_age_2_0_shortcode' ) );

		$this->assertTrue( shortcode_exists( 'gv_age_1_x' ) );
		$this->assertTrue( shortcode_exists( 'gv_age_2_0' ) );

		$form = $this->factory->form->import_and_get( 'complete.json' );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
		) );
		$view = \GV\View::from_post( $post );

		$year_and_one_day_ago = '366 days';

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'3' => date( 'Y-m-d H:i:s', strtotime( "-{$year_and_one_day_ago}" ) ),
		) );

		global $post;
		$post = $this->factory->post->create_and_get();

		$tests = array(
			'[gv_age_1_x entry_id="'.$entry['id'].'" field_id="3" /]' => '1',
			'[gv_age_1_x entry_id="'.$entry['id'].'" field_id="30" /]' => 'Error: No field ID specified.',
			'[gv_age_2_0 entry_id="'.$entry['id'].'" field_id="3" /]' => '1',
			'[gv_age_2_0 entry_id="'.$entry['id'].'" field_id="3" format="%y years" /]' => '1 years',
			'[gv_age_2_0 entry_id="'.$entry['id'].'" field_id="3" format="%y year(s) %m months %d day(s)" /]' => '1 year(s) 0 months 1 day(s)',
			'[gv_age_2_0 entry_id="'.$entry['id'].'" field_id="3" format="%a days" /]' => $year_and_one_day_ago,
			'[gv_age_2_0 entry_id="'.$entry['id'].'" field_id="30" /]' => 'Error: Field value not specified.',
			'[gv_age_2_0 entry_id="'.$entry['id'].'" field_id="30" hide_errors="1" /]' => '',
			'[gv_age_2_0 entry_id="9999999" /]' => 'Error: Entry not found',
			'[gv_age_2_0 entry_id="9999999" field_id="30" hide_errors="1" /]' => '',
		);

		$context = array(
			'view' => $view,
			'field' => \GV\Internal_Field::by_id('custom'),
			'entries' => new \GV\Entry_Collection(),
			'entry' => GV\GF_Entry::from_entry( $entry ),
			'request' => gravityview()->request,
			'post' => $post,
		);

		\GravityView_View::getInstance()->setCurrentEntry( $entry );

		\GV\Mocks\Legacy_Context::push( $context );

		foreach ( $tests as $shortcode => $expected ) {
			$post->post_content = $shortcode;
			$content = apply_filters( 'the_content', $post->post_content );
			$this->assertEquals( $expected, trim( $content ), $shortcode );
		}

		$this->_reset_context();
	}

	/**
	 * Test prior version of the shortcode to make sure the context setting works
	 *
	 * @link https://gist.githubusercontent.com/zackkatz/b99a61b3830c42b9504a72c6c62e829e/raw/e45f0f7b88ea95c2987a56f7488ada2ff306d501/gv_age.php
	 * @param $atts
	 * @param null $content
	 *
	 * @return int|string
	 */
	function _gv_age_1_x_shortcode( $atts = array(), $content = null ) {
		global $gravityview_view;

		extract( $gravityview_view->field_data ); // create a $entry variable with current entry data array
		extract( shortcode_atts(
				array(
					'field_id' => '',
				), $atts )
		);
		$birth_date = $entry[ $field_id ];
		if ( empty( $birth_date ) ) {
			return 'Error: No field ID specified.';
		} else {
			$from = new DateTime( $birth_date );
			$to   = new DateTime( 'today' );
			$age  = $from->diff( $to )->y;
		}

		return $age;
	}

	/**
	 * @link https://gist.githubusercontent.com/zackkatz/b99a61b3830c42b9504a72c6c62e829e/raw/f5e17d31eb4e631b408d8c4393e856f7e9296148/gv_age.php
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	function _gv_age_2_0_shortcode( $atts ) {

		$defaults = array(
			'field_id'    => '',
			'entry_id'    => '',
			'format'      => '%y',
			'hide_errors' => ''
		);

		$atts = shortcode_atts( $defaults, $atts, 'gv_age' );

		$entry = GFAPI::get_entry( $atts['entry_id'] );

		if ( ! $entry || is_wp_error( $entry ) ) {
			return empty( $atts['hide_errors'] ) ? 'Error: Entry not found' : '';
		}

		if ( empty( $entry[ $atts['field_id'] ] ) ) {
			return empty( $atts['hide_errors'] ) ? 'Error: Field value not specified.' : '';
		}

		$from = new DateTime( $entry[ $atts['field_id'] ] ); // Birth date
		$to   = new DateTime( 'now' );

		$interval = $from->diff( $to );

		return $interval->format( $atts['format'] ); // Default format is years ('%y')
	}

	function test_shortcode_search_value_search_filter() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;

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
				),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => sprintf( 'yes-yes-yes Entry %s', wp_generate_password( 12 ) ),
		) );
		$entries []= \GV\GF_Entry::by_id( $entry['id'] );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => sprintf( 'no-no-no Entry %s', wp_generate_password( 12 ) ),
		) );
		$entries []= \GV\GF_Entry::by_id( $entry['id'] );

		global $post;
		$post = $this->factory->post->create_and_get();
		$post->post_content = sprintf( '[gravityview id="%d" search_filter="16" search_value="no-no-no"]', $view->ID );

		$content = apply_filters( 'the_content', $post->post_content );

		$this->assertStringContainsString( 'no-no-no', $content );
		$this->assertStringNotContainsString( 'yes-yes-yes', $content );
	}

	/**
	 * @link https://github.com/gravityview/GravityView/issues/1117
	 */
	public function test_merge_tags_in_labels() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '16',
						'custom_label' => 'Textarea with entry {E:16}',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'custom_label' => 'Entry {date_created:timestamp} ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'custom_label' => 'Label: {date_created:timestamp} Entry: {E:16}',
						'content' => 'Content: {date_created:timestamp} Entry: {E:16}',
					),
				),
			),
		) );
		$view = \GV\View::from_post( $post );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'date_created' => '1970-05-23 21:21:18',
			'status' => 'active',
			'16' => sprintf( 'Just some entry %s', wp_generate_password( 12 ) ),
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$renderer = new \GV\Entry_Renderer();

		$output = $renderer->render( $entry, $view );

		$this->assertStringContainsString( 'Content: 12345678 Entry: Just some entry', $output );
		$this->assertStringContainsString( 'Textarea with entry Just some entry', $output );
		$this->assertStringContainsString( 'Label: 12345678 Entry: Just some entry', $output );
	}

	/**
	 * https://github.com/gravityview/GravityView/issues/1124
	 */
	public function test_hide_until_searched_widgets() {
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
			'settings' => array(
				'hide_until_searched' => true,
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

		add_filter( 'gravityview/view/anchor_id', '__return_false' );
		add_filter( 'gravityview/widget/search/append_view_id_anchor', '__return_false' );

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;

		$renderer = new \GV\View_Renderer();

		$legacy = \GravityView_frontend::getInstance()->insert_view_in_content( '' );
		$future = $renderer->render( $view );

		$this->assertEquals( $legacy, $future );
		$this->assertStringContainsString( 'Search Entries', $future );
		$this->assertStringContainsString( 'Here we go again! <b>Now</b>', $future );

		remove_all_filters( 'gravityview/view/anchor_id' );
		remove_all_filters( 'gravityview/widget/search/append_view_id_anchor' );
	}

	/**
	 * https://github.com/gravityview/GravityView/issues/1137
	 */
	public function test_view_in_view_embedded() {
		$this->_reset_context();
		$form         = $this->factory->form->import_and_get( 'simple.json' );
		$another_form = $this->factory->form->import_and_get( 'simple.json' );

		$entry = $this->factory->entry->create_and_get( array(
			'status' => 'active',
			'form_id' => $form['id'],
			'1' => 'this is an entry',
		) );

		$another_entry = $this->factory->entry->create_and_get( array(
			'status' => 'active',
			'form_id' => $another_form['id'],
			'1' => 'this is an another entry',
		) );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'content' => 'Embed this view!',
					),
				),
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'content' => 'Embed this view!',
					),
				),
			),
			'settings' => $settings,
		) );

		$another_view = $this->factory->view->create_and_get( array(
			'form_id' => $another_form['id'],
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'content' => '[gravityview id="' . $view->ID . '"]',
					),
				),
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'content' => '[gravityview id="' . $view->ID . '"]',
					),
				),
			),
			'settings' => $settings,
		) );

		$form          = \GV\GF_Form::by_id( $form['id'] );
		$entry         = \GV\GF_Entry::by_id( $entry['id'] );
		$view          = \GV\View::from_post( $view );
		$another_form  = \GV\GF_Form::by_id( $another_form['id'] );
		$another_entry = \GV\GF_Entry::by_id( $another_entry['id'] );
		$another_view  = \GV\View::from_post( $another_view );

		$future = new \GV\Shortcodes\gravityview();

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = false;

		$args = array(
			'id' => $another_view->ID,
		);

		$this->assertStringContainsString( 'Embed this view', $future->callback( $args ) );

		global $post;

		$post = $this->factory->post->create_and_get( array( 'post_content' => '[gravityview id="' . $another_view->ID . '"]' ) );

		gravityview()->request->returns['is_entry'] = $another_entry;

		$this->assertStringContainsString( 'Embed this view', $future->callback( $args ) );

		$this->_reset_context();
	}

	/**
	 * https://github.com/gravityview/GravityView/issues/1148
	 */
	public function test_is_approved_field_values() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'is_approved',
						'unapproved_label' => '',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'is_approved',
						'unapproved_label' => 'Nicht bestätigt',
					),
				),
			),
		) );
		$view = \GV\View::from_post( $post );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$renderer = new \GV\Entry_Renderer();

		$output = $renderer->render( $entry, $view );

		$this->assertStringContainsString( '<span class="gv-approval-unapproved">Unapproved</span>', $output );
		$this->assertStringContainsString( '<span class="gv-approval-unapproved">Nicht bestätigt</span>', $output );
	}

	/**
	 * https://secure.helpscout.net/conversation/603701583/15492/
	 */
	public function test_gravityview_entries_pass_count_by_reference() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
		) );
		$view = \GV\View::from_post( $post );

		list( $entries, $paging, $count ) = \GV\Mocks\GravityView_frontend_get_view_entries( array( 'id' => $view->ID ), $form['id'], array(
			'paging' => array( 'current_page' => 1, 'offset' => 0, 'page_size' => 25 ),
		), 0 );

		$this->assertEquals( array(
			array(), array( 'offset' => 0, 'page_size' => 25 ), 0
		), array( $entries, $paging, $count ) );

		add_filter( 'gravityview_before_get_entries', $before_callback = function( $entries, $criteria, $parameters, &$count ) {
			$count = 10;
			return array();
		}, 10, 4 );

		list( $entries, $paging, $count ) = \GV\Mocks\GravityView_frontend_get_view_entries( array( 'id' => $view->ID ), $form['id'], array(
			'paging' => array( 'current_page' => 1, 'offset' => 0, 'page_size' => 25 ),
		), 10 );

		$this->assertTrue( remove_filter( 'gravityview_before_get_entries', $before_callback ) );

		add_filter( 'gravityview_entries', $before_callback = function( $entries, $criteria, $parameters, &$count ) {
			$count = 11;
			return array();
		}, 10, 4 );

		list( $entries, $paging, $count ) = \GV\Mocks\GravityView_frontend_get_view_entries( array( 'id' => $view->ID ), $form['id'], array(
			'paging' => array( 'current_page' => 1, 'offset' => 0, 'page_size' => 25 ),
		), 11 );

		$this->assertTrue( remove_filter( 'gravityview_entries', $before_callback ) );
	}



	/**
	 * @group field_html
	 */
	public function test_frontend_field_html_number_comma_type() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'9' => '9999.99',
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '9' );

		$field->field->numberFormat = 'decimal_comma';

		$this->assertEquals( '9999,99', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'number_format' => true ) );

		$this->assertEquals( '9.999,99', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'decimals' => 3 ) );

		$this->assertEquals( '9.999,990', $renderer->render( $field, $view, $form, $entry, $request ) );

		$field->update_configuration( array( 'number_format' => false ) );

		$this->assertEquals( '9999.990', $renderer->render( $field, $view, $form, $entry, $request ) );
	}


	/**
	 * https://secure.helpscout.net/conversation/673812806/16937/
	 */
	public function test_fileupload_download_link_index_php_detection() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		$upload_url = GFFormsModel::get_upload_url( $form['id'] );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'5' => json_encode( $files = array( $upload_url . '/one.jpg', $upload_url . '/two.mp3' ) ),
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
		$field->update_configuration( array( 'link_to_file' => false ) );
		$field->update_configuration( array( 'show_as_link' => false ) );

		$files[0] = $field->field->get_download_url( $files[0] );
		$files[1] = $field->field->get_download_url( $files[1] );

		$this->assertStringContainsString( 'index.php', $files[0] );
		$this->assertStringContainsString( 'one.jpg', $files[0] );
		$this->assertStringContainsString( 'index.php', $files[1] );
		$this->assertStringContainsString( 'two.mp3', $files[1] );

		$output = $renderer->render( $field, $view, $form, $entry, $request );

		$expected = "<ul class='gv-field-file-uploads gv-field-{$form->ID}-5'>";
		$expected .= '<li><img src="' . $files[0] . '" width="250" class="gv-image gv-field-id-5" /></li>';
		$expected .= '<li>';

		$this->assertStringContainsString( $expected, $output );
		$this->assertStringContainsString( '<audio class="wp-audio-shortcode', $output );
		$this->assertStringContainsString( '<source type="audio/mpeg" src="' . esc_attr( $files[1] ) . '&_=', $output );
		$this->assertStringContainsString( '" /><a href="' . esc_attr( $files[1] ). '">' . esc_html( $files[1] ) .  '</a></audio></li></ul>', $output );
	}

	public function test_fileupload_download_link_lightbox() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$upload_url = GFFormsModel::get_upload_url( $form['id'] );

		$file = $upload_url . '/one.jpg';

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'5' => json_encode( array( $file ) ),
		) );
		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'settings' => array(
				'lightbox' => true,
			),
		) );

		$form = \GV\GF_Form::by_id( $form['id'] );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view = \GV\View::from_post( $view );

		$request = new \GV\Frontend_Request();
		$renderer = new \GV\Field_Renderer();

		$field = \GV\GF_Field::by_id( $form, '5' );
		$field->update_configuration( array( 'link_to_file' => false ) );
		$field->update_configuration( array( 'show_as_link' => false ) );

		$output = $renderer->render( $field, $view, $form, $entry, $request );

		$secure_link = $field->field->get_download_url($file);

		$expected = sprintf(
			'<a class="gravityview-fancybox" data-fancybox="%s" href="%s" rel="gv-field-%d-5-%d"><img src="' . $secure_link . '" width="250" class="gv-image gv-field-id-5" /></a>',
			'gallery-' . sprintf( "%s-%s-%s", $form->ID, $field->ID, $entry->get_slug() ),
			esc_attr( $secure_link ),
			$form->ID,
			$entry->ID
		);

		$this->assertEquals( $expected, $output );

	}

	/**
	 * https://gravityview.slack.com/archives/C91HX67RV/p1539807639000200
	 */
	public function test_entry_by_non_unique_slug() {

		$form1 = $this->factory->form->create_and_get();
		$form2 = $this->factory->form->create_and_get();
		$entry1 = $this->factory->entry->create_and_get( array( 'form_id' => $form1['id'] ) );
		$entry2 = $this->factory->entry->create_and_get( array( 'form_id' => $form2['id'] ) );

		add_filter( 'gravityview_custom_entry_slug', '__return_true' );

		add_filter( 'gravityview_entry_slug', function( $slug ) {
			return "non-unique";
		}, 10 );

		/** Updates the slug as a side-effect :( */
		\GravityView_API::get_entry_slug( $entry1['id'], $entry1 );
		\GravityView_API::get_entry_slug( $entry2['id'], $entry2 );

		$entry = \GV\GF_Entry::by_id( 'non-unique', $form1['id'] );
		$this->assertEquals( $entry1['id'], $entry->ID );

		$entry = \GV\GF_Entry::by_id( 'non-unique', $form2['id'] );
		$this->assertEquals( $entry2['id'], $entry->ID );

		remove_all_filters( 'gravityview_custom_entry_slug' );
		remove_all_filters( 'gravityview_entry_slug' );
	}
}
