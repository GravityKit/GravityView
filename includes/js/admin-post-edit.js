/**
 * Custom js script at post edit screen
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */


(function( $ ) {

	function InsertViewShortcode(){
		var view_id = $("#gravityview_view_id").val();

		if( view_id === '' ) {
			alert( gvGlobals.alert_1 );
			$("#gravityview_view_id").focus();
			return false;
		}

		var shortcode = '[gravityview id="' + view_id +'"';

		//page size
		var page_size = parseInt( $("#gravityview_page_size").val() );
		if( page_size > 0 && page_size != 25 ) {
			shortcode += ' page_size="' + page_size + '"';
		}

		//show only approved
		var show_only_approved = $("#gravityview_only_approved").prop('checked');
		if( show_only_approved === true ) {
			shortcode += ' show_only_approved="1"';
		}

		// sorting
		var sort_field = $("#gravityview_sort_field").val();
		if( '' !== sort_field ) {
			var sort_direction = $("#gravityview_sort_direction").val();
			shortcode += ' sort_field="' + sort_field + '"' + ' sort_direction="' + sort_direction + '"';
		}

		// date filtering
		var start_date = $("#gravityview_start_date").val();
		if( '' !== start_date ) {
			shortcode += ' start_date="' + start_date + '"';
		}
		var end_date = $("#gravityview_end_date").val();
		if( '' !== end_date ) {
			shortcode += ' end_date="' + end_date + '"';
		}


		shortcode += ']';
		//var win = window.dialogArguments || opener || parent || top;
		window.send_to_editor( shortcode );
		return false;
	}



	$(document).ready( function() {

		//datepicker
		$('.gv-datepicker').datepicker({ dateFormat: "yy-mm-dd" });


		// Select view id -> populate sort fields
		$("#gravityview_view_id").change( function() {

			$("#gravityview_sort_field").empty();

			var data = {
				action: 'gv_sortable_fields',
				viewid: $(this).val(),
				nonce: gvGlobals.nonce,
			};

			$.post( gvGlobals.ajaxurl, data, function( response ) {
				if( response ) {
					$("#gravityview_sort_field").append( response );
				}
			});

		});


		// capture form submit -> add shortcode to editor
		$('#insert_gravityview_view').on( 'click', function(e) {
			e.preventDefault();
			InsertViewShortcode();
			return false;
		});




	});

}(jQuery));
