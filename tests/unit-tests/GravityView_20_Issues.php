<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * Issues uncovered in 2.0
 */
class GV_20_Issues_Test extends GV_UnitTestCase {
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

		$this->assertContains( get_permalink( $post->ID ), $content );
	}

	/**
	 * @since 2.0.6.2
	 */
	function test_gv_age_shortcode() {

		add_shortcode( 'gv_age_1_x', array( $this, '_gv_age_1_x_shortcode' ) );
		add_shortcode( 'gv_age_2_0', array( $this, '_gv_age_2_0_shortcode' ) );

		$this->assertTrue( shortcode_exists( 'gv_age_1_x' ) );
		$this->assertTrue( shortcode_exists( 'gv_age_2_0' ) );

		$form = $this->factory->form->import_and_get( 'complete.json' );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
		) );
		$view = \GV\View::from_post( $post );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'3' => date( 'Y-m-d H:i:s', strtotime( '-366 days' ) ),
		) );

		global $post;
		$post = $this->factory->post->create_and_get();

		$tests = array(
			'[gv_age_1_x entry_id="'.$entry['id'].'" field_id="3" /]' => '1',
			'[gv_age_1_x entry_id="'.$entry['id'].'" field_id="30" /]' => 'Error: No field ID specified.',
			'[gv_age_2_0 entry_id="'.$entry['id'].'" field_id="3" /]' => '1',
			'[gv_age_2_0 entry_id="'.$entry['id'].'" field_id="3" format="%y years" /]' => '1 years',
			'[gv_age_2_0 entry_id="'.$entry['id'].'" field_id="3" format="%y year(s) %m months %d day(s)" /]' => '1 year(s) 0 months 1 day(s)',
			'[gv_age_2_0 entry_id="'.$entry['id'].'" field_id="3" format="%a days" /]' => '366 days',
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
}
