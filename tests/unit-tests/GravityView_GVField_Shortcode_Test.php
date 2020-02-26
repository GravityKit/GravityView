<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group shortcode
 */
class GravityView_GVField_Shortcode_Test extends GV_UnitTestCase {
	/**
	 * @covers \GV\Shortcodes\gvfield::callback()
	 */
	public function test_shortcode() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
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
		) );
		$view = \GV\View::from_post( $view );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'hello'
		) );

		$atts = array(
			'view_id' => $view->ID,
			'entry_id' => $entry['id'],
			'field_id' => '16',
		);

		$gvfield = new \GV\Shortcodes\gvfield();

		$this->assertEquals( wpautop( 'hello' ), $gvfield->callback( $atts ) );

		$another_entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'well, o!'
		) );

		/** Test the filters */
		$_this = &$this;
		add_filter( 'gravityview/shortcodes/gvfield/atts', function( $atts ) use ( $_this, $another_entry, $entry ) {
			$_this->assertEquals( $entry['id'], $atts['entry_id'] );
			$atts['entry_id'] = $another_entry['id'];
			return $atts;
		} );

		$this->assertEquals( wpautop( 'well, o!' ), $gvfield->callback( $atts ) );

		add_filter( 'gravityview/shortcodes/gvfield/output', function( $output ) {
			return 'heh, o!';
		} );

		$this->assertEquals( 'heh, o!', $gvfield->callback( $atts ) );

		remove_all_filters( 'gravityview/shortcodes/gvfield/atts' );
		remove_all_filters( 'gravityview/shortcodes/gvfield/output' );

		$atts['field_id'] = 'id';
		$atts['show_as_link'] = true;
		$expected = sprintf( '<a href="%s">%s</a>', esc_attr( \GV\GF_Entry::by_id( $entry['id'] )->get_permalink( $view, new \GV\Mock_Request() ) ), $entry['id'] );
		$this->assertEquals( $expected, $gvfield->callback( $atts ) );

		$and_another_entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16', 'zzzZzz :)',
		) );

		/**
		 * Last/first tests.
		 *
		 * Note to self: first means the latest entry (topmost, first in the list of entries)
		 * last means the other way around.
		 */
		$atts['show_as_link'] = false;

		$atts['entry_id'] = 'first';
		$this->assertEquals( $and_another_entry['id'], $gvfield->callback( $atts ) );

		$atts['entry_id'] = 'last';
		$this->assertEquals( $entry['id'], $gvfield->callback( $atts ) );
	}

	public function test_failures() {
		set_current_screen( 'dashboard' );

		$gvfield = new \GV\Shortcodes\gvfield();
		$this->assertEmpty( $gvfield->callback( array() ) );

		set_current_screen( 'front' );

		$gvfield = new \GV\Shortcodes\gvfield();
		$this->assertEmpty( $gvfield->callback( array() ) );

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
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
		) );
		$view = \GV\View::from_post( $view );

		$atts = array(
			'view_id' => $view->ID,
			'entry_id' => -100,
			'field_id' => '16',
		);

		$this->assertEmpty( $gvfield->callback( $atts ) );

		$atts = array(
			'view_id' => $view->ID,
			'entry_id' => 'last',
			'field_id' => '16',
		);

		$this->assertEmpty( $gvfield->callback( $atts ) );

		$atts = array(
			'view_id' => $view->ID,
			'entry_id' => 'first',
			'field_id' => '16',
		);

		$this->assertEmpty( $gvfield->callback( $atts ) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'hello'
		) );

		$atts = array(
			'view_id' => $view->ID,
			'entry_id' => 'first',
			'field_id' => '1600',
		);

		$this->assertEmpty( $gvfield->callback( $atts ) );
	}

	public function test_sort_direction() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
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
		) );
		$view = \GV\View::from_post( $view );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'hello'
		) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'now'
		) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'goodbye'
		) );

		add_filter( 'gravityview_get_entries', $callback = function( $parameters ) {
			$parameters['sorting']['key'] = 'id';
			$parameters['sorting']['direction'] = 'DESC';
			return $parameters;
		} );

		$atts = array(
			'view_id' => $view->ID,
			'entry_id' => 'last',
			'field_id' => '16',
		);

		$gvfield = new \GV\Shortcodes\gvfield();

		$this->assertEquals( wpautop( 'hello' ), $gvfield->callback( $atts ) );

		$this->assertTrue( remove_filter( 'gravityview_get_entries', $callback ) );
	}
}
