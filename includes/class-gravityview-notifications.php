<?php

/**
 * Endpoint responsible for firing off notifications.
 * @since $ver$
 * @todo Create a Notifier (Interface and GF implementation) that can be replaced to aid with unit tests.
 */
final class GravityView_Notifications {
	/**
	 * Passes along notification triggers to {@see GFAPI::send_notifications()}
	 *
	 * @internal
	 * @since $ver$
	 *
	 * @param int    $entry_id ID of entry being updated
	 * @param string $event    Hook that triggered the notification. This is used as the key in the GF notifications
	 *                         array.
	 * @param array  $entry    The entry object.
	 */
	public static function send_notifications( int $entry_id = 0, string $event = '', array $entry = [] ): void {
		if ( ! $entry ) {
			$entry = GFAPI::get_entry( $entry_id );
		}

		if ( ! $entry || is_wp_error( $entry ) ) {
			gravityview()->log->error( 'Entry not found at ID #{entry_id}', array( 'entry_id' => $entry_id ) );

			return;
		}

		$form = GVCommon::get_form( $entry['form_id'] );

		if ( ! $form ) {
			gravityview()->log->error(
				'Form not found at ID #{form_id} for entry #{entry_id}',
				[
					'form_id'  => $entry['form_id'],
					'entry_id' => $entry_id
				]
			);

			return;
		}

		GFAPI::send_notifications( $form, $entry, $event );
	}
}
