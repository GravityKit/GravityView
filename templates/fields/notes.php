<?php
/**
 * Display the name field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

require_once( GFCommon::get_base_path() . '/entry_detail.php' );

$gravityview_view = GravityView_View::getInstance();

$is_editable = $gravityview_view->getCurrentFieldSetting('notes_is_editable', false);

extract( $gravityview_view->getCurrentField() );

?>

<form method="post" class="gv-entry-notes-form">
	<?php wp_nonce_field( 'gforms_update_note', 'gforms_update_note' ) ?>
	<div class="inside">
		<?php
		$notes = RGFormsModel::get_lead_notes( $entry['id'] );

		//getting email values
		$email_fields = GFCommon::get_email_fields( $form );
		$emails = array();

		foreach ( $email_fields as $email_field ) {
			if ( ! empty( $entry[ $email_field->id ] ) ) {
				$emails[] = $entry[ $email_field->id ];
			}
		}
		//displaying notes grid
		$subject = '';
		GFEntryDetail::notes_grid( $notes, $is_editable, $emails, $subject );
		?>
	</div>
</form>