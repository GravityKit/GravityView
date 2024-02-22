<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * Unit tests for {@see GravityView_Delete_Entry}.
 * @since $ver$
 * @group approval
 */
final class GravityView_Delete_Entry_Test extends GV_UnitTestCase {
	/**
	 * Returns the notification events for this extension.
	 * @since $ver$
	 * @return string[]
	 */
	public function notification_events_provider(): array {
		return [
			'trashed' => [ 'gravityview/delete-entry/trashed' ],
			'deleted' => [ 'gravityview/delete-entry/deleted' ],
		];
	}

	/**
	 * @covers       GravityView_Notifications::send_notifications()
	 * @covers       GravityView_Delete_Entry::trigger_notifications()
	 * @dataProvider notification_events_provider
	 */
	public function test_send_notifications( string $event ): void {
		$notification = [
			'name'  => 'Deleted or Trashed',
			'id'    => 1,
			'event' => 'gravityview/delete-entry/deleted',
		];

		$test_form  = $this->factory->form->create_and_get( [ 'notifications' => [ $notification ] ] );
		$test_entry = $this->factory->entry->create_and_get( array( 'form_id' => $test_form['id'] ) );

		$triggered_notifications = [];

		$filter_notification = static function ( $notification ) use ( & $triggered_notifications ) {
			$triggered_notifications[] = $notification;

			return $notification;
		};

		add_filter( 'gform_notification', $filter_notification );

		do_action( $event, $test_entry['id'] );

		remove_filter( 'gform_notification', $filter_notification );

		self::assertSame( [ $notification ], $triggered_notifications );
	}
}
