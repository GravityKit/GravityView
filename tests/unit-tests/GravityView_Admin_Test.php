<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group admin
 */
class GravityView_Admin_Test extends GV_UnitTestCase {

	public function test_change_entry_creator_dropdown() {
		$form = $this->factory->form->create_and_get();

		$admin = new GravityView_Change_Entry_Creator();

		$user = $this->factory->user->create( array(
			'user_login' => md5( microtime() ),
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role' => 'subscriber'
		) );

		$count = count_users();
		$total = $count['total_users'];

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $user,
		) );

		$_POST = array(
			'screen_mode' => 'edit',
		);

		add_filter( 'gravityview/get_users/change_entry_creator', $callback = function() use ( $total ) {
			return $total;
		} );

		ob_start();
		$admin->add_select( $form['id'], $entry );
		$select = ob_get_clean();

		$this->assertNotContains( '251-i-only-see-some-users-in-the-change-entry-creator-dropdown', $select );

		remove_filter( 'gravityview/get_users/change_entry_creator', $callback );

		add_filter( 'gravityview/get_users/change_entry_creator', $callback = function() use ( $total ) {
			return $total;
		} );

		$this->assertNotContains( '251-i-only-see-some-users-in-the-change-entry-creator-dropdown', $select );

		remove_filter( 'gravityview/get_users/change_entry_creator', $callback );

		$_POST = array();

		unset( $admin );
	}
}
