<?php
/**
 * @file inline-javascript.php
 * @global GravityView_Edit_Entry_Render $object
 */
?><script type="text/javascript">

	function DeleteFile(leadId, fieldId, deleteButton){
		if(confirm('<?php echo esc_js( __( "Would you like to permanently delete this file? 'Cancel' to stop. 'OK' to delete", 'gk-gravityview' ) ); ?>')){
			var fileIndex = jQuery(deleteButton).parent().index();
			var preview_div = jQuery(deleteButton).closest('.gfield--type-fileupload');
			preview_div.find('button.gform_button_select_files').prop("disabled", false);

			EndDeleteFile( fieldId, fileIndex );
			return true;
		}
	}

	function EndDeleteFile(fieldId, fileIndex){
		var previewFileSelector = "#preview_existing_files_" + fieldId + " .ginput_preview";
		var $previewFiles = jQuery(previewFileSelector);

		const $preview_div = $previewFiles.closest('.gfield--type-fileupload');
		const $input_field = $preview_div.find( 'input[name="input_' + fieldId + '"]' );
		const files = JSON.parse( $input_field.val() );
		files.splice( fileIndex, 1 );
		$input_field.val( JSON.stringify( files ) );

		$previewFiles.eq(fileIndex).remove();
		var $visiblePreviewFields = jQuery(previewFileSelector);
		if($visiblePreviewFields.length == 0){
			jQuery('#preview_' + fieldId).hide();
			jQuery('#upload_' + fieldId).show('slow');
		}
	}

</script>
