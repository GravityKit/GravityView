<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group gravityviewview
 * @since 1.15.2
 */
class GravityView_View_Test extends GV_UnitTestCase {

	/**
	 * @covers GravityView_View::__get()
	 */
	function test__get() {

		$not_defined = GravityView_View::getInstance()->definitely_not_defined;
		$this->assertNull( $not_defined );

		GravityView_View::getInstance()->setContext('wahoo!');
		$this->assertEquals( 'wahoo!', GravityView_View::getInstance()->getContext() );
	}

	/**
	 * @covers GravityView_View::setBackLinkLabel
	 * @covers GravityView_View::getBackLinkLabel
	 * @covers GravityView_View::setForm
	 * @covers GravityView_View::setCurrentEntry
	 */
	function test_BackLinkLabel() {

		// Test replace_variables
		$form = $this->factory->form->create_and_get();
		$entry = $this->factory->entry->create_and_get( array('form_id' => $form['id'] ));
		GravityView_View::getInstance()->setCurrentEntry( $entry );
		GravityView_View::getInstance()->setForm( $form );
		GravityView_View::getInstance()->setBackLinkLabel( '{form_id} {entry_id}' );
		$this->assertEquals( "{$form['id']} {$entry['id']}", GravityView_View::getInstance()->getBackLinkLabel() );


		// Test shortcode replacement
		add_shortcode( 'return_empty_string', '__return_empty_string' );
		GravityView_View::getInstance()->setBackLinkLabel( '[return_empty_string]' );
		$this->assertEquals( '', GravityView_View::getInstance()->getBackLinkLabel() );

	}

	/**
	 * @covers GravityView_View::setTotalEntries
	 * @covers GravityView_View::getTotalEntries
	 */
	function test_TotalEntries() {

		$GV = GravityView_View::getInstance();

		// Test no entries
		$GV->setTotalEntries( 0 );
		$this->assertEquals( 0, $GV->getTotalEntries() );

		$GV->setTotalEntries( 'non-numeric' );
		$this->assertEquals( 0, $GV->getTotalEntries() );

		// INT
		$GV->setTotalEntries( 10000 );
		$this->assertEquals( 10000, $GV->getTotalEntries() );

		// FLOAT
		$GV->setTotalEntries( 100.00 );
		$this->assertEquals( 100, $GV->getTotalEntries() );

		// STRING
		$GV->setTotalEntries( '100' );
		$this->assertEquals( 100, $GV->getTotalEntries() );
	}

	/**
	 * @covers GravityView_View::getPaginationCounts
	 * @covers GravityView_View::getPaging
	 * @covers GravityView_View::setPaging
	 */
	function test_getPaginationCounts() {

		$GV = GravityView_View::getInstance();


		// Test no entries
		$GV->setTotalEntries( 0 );
		$counts = $GV->getPaginationCounts();
		$this->assertEquals( array(), $counts );


		$total_entries = 100;
		$offset = 25;
		$page_size = 25;

		$GV->setTotalEntries( $total_entries );

		/** @see GravityView_frontend::get_view_entries */
		$paging = array(
			'offset' => $offset,
			'page_size' => $page_size,
		);

		$GV->setPaging( $paging );

		$this->assertEquals( $paging, $GV->getPaging() );

		$this->assertEquals( array( 'first' => 26, 'last' => 50, 'total' => 100 ), $GV->getPaginationCounts() );
	}

	/**
	 * @covers GravityView_View::add_id_specific_templates
	 */
	function test_add_id_specific_templates() {

		GravityView_View::getInstance()->setFormId( 123 );
		GravityView_View::getInstance()->setViewId( 45 );
		GravityView_View::getInstance()->setPostId( 6789 );

		$templates_before = array( 'table-header.php' );
		$templates = GravityView_View::getInstance()->add_id_specific_templates( $templates_before, 'table', 'header' );

		$expected = array(
			'form-123-table-header.php',
			'view-45-table-header.php',
			'page-6789-table-header.php',
			'table-header.php',
		);

		$this->assertEquals( $expected, $templates );
	}

}
