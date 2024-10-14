<?php
/**
 * @file inline-javascript.php
 * @global GravityView_Edit_Entry_Render $object
 */
?><script type="text/javascript">

	function DeleteFile(leadId, fieldId, deleteButton){
		if(confirm('<?php echo esc_js( __( "Would you like to permanently delete this file? 'Cancel' to stop. 'OK' to delete", 'gk-gravityview' ) ); ?>')){
			var fileIndex = jQuery(deleteButton).parent().index();
			var preview_div = jQuery(deleteButton).parents('.gfield--type-fileupload');
			preview_div.find('button.gform_button_select_files').prop("disabled", false);
			var mysack = new sack("<?php echo admin_url( 'admin-ajax.php' ); ?>");
			mysack.execute = 1;
			mysack.method = 'POST';
			mysack.setVar( "action", "rg_delete_file" );
			mysack.setVar( "rg_delete_file", "<?php echo wp_create_nonce( 'rg_delete_file' ); ?>" );
			mysack.setVar( "lead_id", leadId );
			mysack.setVar( "field_id", fieldId );
			mysack.setVar( "file_index", fileIndex );
			mysack.onError = function() { alert('<?php echo esc_js( __( 'Ajax error while deleting field.', 'gk-gravityview' ) ); ?>' )};
			mysack.runAJAX();

			return true;
		}
	}

	function EndDeleteFile(fieldId, fileIndex){
		var previewFileSelector = "#preview_existing_files_" + fieldId + " .ginput_preview";
		var $previewFiles = jQuery(previewFileSelector);
		var rr = $previewFiles.eq(fileIndex);
		$previewFiles.eq(fileIndex).remove();
		var $visiblePreviewFields = jQuery(previewFileSelector);
		if($visiblePreviewFields.length == 0){
			jQuery('#preview_' + fieldId).hide();
			jQuery('#upload_' + fieldId).show('slow');
		}
	}

</script>