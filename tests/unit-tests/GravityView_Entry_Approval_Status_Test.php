<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @since 1.18
 * @group approval
 * @group approval_status
 */
class GravityView_Entry_Approval_Status_Test extends GV_UnitTestCase {

	/**
	 * @since 1.18
	 * @covers GravityView_Entry_Approval_Status::get_label()
	 * @covers GravityView_Entry_Approval_Status::get_string()
	 * @covers GravityView_Entry_Approval_Status::get_key()
	 * @covers GravityView_Entry_Approval_Status::get_title_attr()
	 */
	public function test_get_values() {

		$statuses = GravityView_Entry_Approval_Status::get_all();

		foreach ( $statuses as $key => $status ) {

			$this->assertEquals( $status['label'], GravityView_Entry_Approval_Status::get_label( $status['value'] ) );
			$this->assertEquals( $status['label'], GravityView_Entry_Approval_Status::get_label( $key ) );
			$this->assertEquals( $status['label'], GravityView_Entry_Approval_Status::get_string( $key, 'label' ) );
			$this->assertEquals( $status['label'], GravityView_Entry_Approval_Status::get_string( $status['value'], 'label' ) );

			$this->assertEquals( $key, GravityView_Entry_Approval_Status::get_key( $status['value'] ) );
			$this->assertEquals( $key, GravityView_Entry_Approval_Status::get_key( $key ) );
			$this->assertEquals( $key, GravityView_Entry_Approval_Status::get_string( $key, 'key' ) );
			$this->assertEquals( $key, GravityView_Entry_Approval_Status::get_string( $status['value'], 'key' ) );

			$this->assertEquals( $status['title'], GravityView_Entry_Approval_Status::get_title_attr( $status['value'] ) );
			$this->assertEquals( $status['title'], GravityView_Entry_Approval_Status::get_title_attr( $key ) );
			$this->assertEquals( $status['title'], GravityView_Entry_Approval_Status::get_string( $key, 'title' ) );
			$this->assertEquals( $status['title'], GravityView_Entry_Approval_Status::get_string( $status['value'], 'title' ) );

			$this->assertEquals( $status['action'], GravityView_Entry_Approval_Status::get_string( $status['value'], 'action' ) );
			$this->assertEquals( $status['action'], GravityView_Entry_Approval_Status::get_string( $key, 'action' ) );

		}
	}

	/**
	 * @since 1.18
	 * @covers GravityView_Entry_Approval_Status::is_valid()
	 */
	public function test_is_valid() {
		$this->assertTrue( GravityView_Entry_Approval_Status::is_valid( GravityView_Entry_Approval_Status::APPROVED ) );
		$this->assertTrue( GravityView_Entry_Approval_Status::is_valid( GravityView_Entry_Approval_Status::DISAPPROVED ) );
		$this->assertTrue( GravityView_Entry_Approval_Status::is_valid( GravityView_Entry_Approval_Status::UNAPPROVED ) );

		$this->assertTrue( GravityView_Entry_Approval_Status::is_valid( 'Approved' ) );
		$this->assertTrue( GravityView_Entry_Approval_Status::is_valid( '1' ) );
		$this->assertTrue( GravityView_Entry_Approval_Status::is_valid( '0' ) );
		$this->assertTrue( GravityView_Entry_Approval_Status::is_valid( '2' ) );
		$this->assertTrue( GravityView_Entry_Approval_Status::is_valid( '3' ) );
		$this->assertTrue( GravityView_Entry_Approval_Status::is_valid( false ) );
		$this->assertTrue( GravityView_Entry_Approval_Status::is_valid( true ) );

		$this->assertFalse( GravityView_Entry_Approval_Status::is_valid( 'asdsad' ) );
		$this->assertFalse( GravityView_Entry_Approval_Status::is_valid( array() ) );
		$this->assertFalse( GravityView_Entry_Approval_Status::is_valid( NULL ) );
		$this->assertFalse( GravityView_Entry_Approval_Status::is_valid() );
	}

	/**
	 * @since 1.18
	 * @covers GravityView_Entry_Approval_Status::maybe_convert_status()
	 * @covers GravityView_Entry_Approval_Status::is_approved()
	 * @covers GravityView_Entry_Approval_Status::is_disapproved()
	 * @covers GravityView_Entry_Approval_Status::is_unapproved()
	 */
	public function test_is_status() {

		$this->assertTrue( GravityView_Entry_Approval_Status::is_approved( GravityView_Entry_Approval_Status::APPROVED ) );
		$this->assertTrue( GravityView_Entry_Approval_Status::is_approved( 'Approved' ) );
		$this->assertTrue( GravityView_Entry_Approval_Status::is_approved( '1' ) );
		$this->assertTrue( GravityView_Entry_Approval_Status::is_approved( true ) );
		$this->assertFalse( GravityView_Entry_Approval_Status::is_approved( false ) );

		$this->assertTrue( GravityView_Entry_Approval_Status::is_disapproved( GravityView_Entry_Approval_Status::DISAPPROVED ) );
		$this->assertTrue( GravityView_Entry_Approval_Status::is_disapproved( '0' ) );
		$this->assertTrue( GravityView_Entry_Approval_Status::is_disapproved( '2' ) );
		$this->assertFalse( GravityView_Entry_Approval_Status::is_disapproved( false ) );
		$this->assertFalse( GravityView_Entry_Approval_Status::is_disapproved( true ) );

		$this->assertTrue( GravityView_Entry_Approval_Status::is_unapproved( GravityView_Entry_Approval_Status::UNAPPROVED ) );
		$this->assertTrue( GravityView_Entry_Approval_Status::is_unapproved( false ) );
		$this->assertFalse( GravityView_Entry_Approval_Status::is_unapproved( '0' ) );
		$this->assertFalse( GravityView_Entry_Approval_Status::is_unapproved( '1' ) );
		$this->assertFalse( GravityView_Entry_Approval_Status::is_unapproved( true ) );

	}

}
