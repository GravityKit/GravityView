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
<style>
	table.entry-detail-view {
		margin-bottom: 16px
	}

	table.entry-detail-view td.lastrow {
		border-bottom: none !important;
	}

	td.entry-view-section-break {
		font-size: 14px;
		font-weight: bold;
		background-color: #EEE;
		border-bottom: 1px solid #DFDFDF;
		padding: 7px;
	}

	td.entry-view-field-name {
		font-weight: bold;
		background-color: #EAF2FA;
		border-bottom: 1px solid #FFF;
		line-height: 1.5;
		padding: 7px;
	}

	td.entry-view-field-value {
		border-bottom: 1px solid #DFDFDF;
		padding: 7px 7px 7px 40px;
		line-height: 1.8;
	}

	td.entry-view-field-value p {
		text-align: left;
	}

	td.entry-view-field-value ul.bulleted {
		margin-left: 12px;
	}

	td.entry-view-field-value ul.bulleted li {
		list-style-type: disc;
	}

	.gv-entry-notes-form .button {
		display: inline-block;
		text-decoration: none;
		font-size: 13px;
		line-height: 26px;
		height: 28px;
		margin: 0;
		padding: 0 10px 1px;
		cursor: pointer;
		border-width: 1px;
		border-style: solid;
		-webkit-appearance: none;
		-webkit-border-radius: 3px;
		border-radius: 3px;
		white-space: nowrap;
		-webkit-box-sizing: border-box;
		-moz-box-sizing: border-box;
		box-sizing: border-box;
		
		-webkit-box-shadow: inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08);
		box-shadow: inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08);
		vertical-align: top;

		width: auto!important;
	}

	.gv-entry-notes-form table {
		-webkit-box-shadow: none;
		box-shadow: none;
		table-layout:fixed
		border: 1px solid #e5e5e5;
		-webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04);
		box-shadow: 0 1px 1px rgba(0,0,0,.04);
		border-spacing: 0;
		width: 100%;
		clear: both;
		margin: 0;
	}
	.gv-entry-notes-form .check-column {
		width: 2.2em;
		padding: 6px 0 25px;
		vertical-align: top;
	}

	div.note-avatar {
		width: 48px;
		height: 48px;
		float: left;
		margin-right: 6px;
	}

	h6.note-author {
		font-weight: bold;
		font-size: 1.1em;
		line-height: 1;
		margin: 0;
		padding: 0;
	}

	p.note-email {
		line-height: 1.3;
		margin: 0 !important;
		padding: 0 !important;
		text-align: left;
	}

	div.detail-note-content {
		margin: 1.8em 1em 1.8em 0;
		padding: 1.8em;
		position: relative;
		line-height: 1.8em;
		border-left: 4px solid #E6DB55;
		background-color: #FFFBCC;
	}

	div.detail-note-content p {
		line-height: 30px;
	}

	div.detail-note-content.gforms_note_success{
		background-color: #ECFCDE;
		border: 1px solid #A7C886;

	}
	div.detail-note-content.gforms_note_error{
		background-color: #FFEBE8;
		border: 1px solid #CC0000;

	}

	.entry-detail-notes textarea {
		height: auto!important;
	}
</style>
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