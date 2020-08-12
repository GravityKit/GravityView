<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * PHP-side filtering and sorting.
 * https://github.com/gravityview/GravityView/issues/1381
 * @group filter
 */
class GravityView_PHPFilterSort_Test extends GV_UnitTestCase {
	public function test_it_all() {
        $form = $this->factory->form->import_and_get( 'complete.json' );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;
		$settings['offset'] = 2;
		$settings['page_size'] = 5;

        $view = \GV\View::from_post( $this->factory->view->create_and_get( array(
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
                        'id' => 'custom',
                        'label' => 'Filter, this!',
						'content' => '[GravityView_PHPFilterSort_Test_test_it_all]'
                    ),
                ),
            ),
            'widgets' => array(),
            'settings' => $settings,
        ) ) );

		foreach ( range( 1, 25 ) as $i ) {
		    $this->factory->entry->create_and_get( array(
				'form_id' => $form['id'],
				'status' => 'active',
				'16' => $s = sprintf( '[%d] Some text in a textarea (%s)', $i, wp_generate_password( 12 ) ),
			) );
		}

		/**
		 * Add a filter.
		 */
		add_filter( 'gravityview/entries/filter', $filter = function( $entry, $view, $request ) {
			$field = \GV\GF_Field::by_id( $view->form, '16' );
			if ( ! preg_match( '#^\[.*?[379].*?\]#', $field->get_value( $view, $view->form, $entry ) ) ) {
				return $entry;
			}
			return null;
		}, 10, 3 );

        $entries = $view->get_entries( new GV\Frontend_Request() );

		$this->assertEquals( 16, $entries->total() );
		$this->assertEquals( 5, count( $entries = $entries->all() ) );

		$field = \GV\GF_Field::by_id( $view->form, '16' );

		$this->assertContains( '[22]', $field->get_value( $view, $view->form, $entries[0] ) );
		$this->assertContains( '[21]', $field->get_value( $view, $view->form, $entries[1] ) );
		$this->assertContains( '[20]', $field->get_value( $view, $view->form, $entries[2] ) );
		$this->assertContains( '[18]', $field->get_value( $view, $view->form, $entries[3] ) );
		$this->assertContains( '[16]', $field->get_value( $view, $view->form, $entries[4] ) );

		/**
		 * Add a sort.
		 */
		add_filter( 'gravityview/entries/sort', $sort = function( $compare, $entry1, $entry2, $view, $request ) {
			$field = \GV\GF_Field::by_id( $view->form, '16' );

			$v1 = $field->get_value( $view, $view->form, $entry1 );
			$v2 = $field->get_value( $view, $view->form, $entry2 );

			if ( preg_match( '#^\[.1\]#', $v1 ) ) {
				return 1;
			}

			if ( preg_match( '#^\[.2\]#', $v2 ) ) {
				return -1;
			}

			return 0;
		}, 10, 5 );

		$_GET['pagenum'] = 2;

        $entries = $view->get_entries( new GV\Frontend_Request() );

		$this->assertEquals( 16, $entries->total() );
		$this->assertEquals( 5, count( $entries = $entries->all() ) );

		$this->assertContains( '[10]', $field->get_value( $view, $view->form, $entries[0] ) );
		$this->assertContains( '[12]', $field->get_value( $view, $view->form, $entries[1] ) );
		$this->assertContains( '[14]', $field->get_value( $view, $view->form, $entries[2] ) );
		$this->assertContains( '[15]', $field->get_value( $view, $view->form, $entries[3] ) );
		$this->assertContains( '[16]', $field->get_value( $view, $view->form, $entries[4] ) );

		$this->assertTrue( remove_filter( 'gravityview/entries/filter', $filter ) );
		$this->assertTrue( remove_filter( 'gravityview/entries/sort', $sort ) );

		$_GET = array();
	}
}
