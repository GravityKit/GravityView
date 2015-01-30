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

	function displayMessage( message, messageClass, container) {

		hideMessage( container, true );

		var messageBox = $('<div class="message ' + messageClass + '" style="display:none;"><p>' + message + '</p></div>');
		$(messageBox).prependTo( container ).slideDown();

		if( messageClass == 'updated' ) {
			messageTimeout = setTimeout( function(){ hideMessage( container, false ); }, 10000);
		}


	}

	function hideMessage( container, messageQueued ){

		var messageBox = $( container ).find('.message');

		if( messageQueued ) {
			$( messageBox ).remove();
		} else {
			$( messageBox ).slideUp( function() {
				$(this).remove();
			});
		}

	}



	// Request entry approve (ajax)
	function updateApproved( entryid, approved, $target) {

		var data = {
			action: 'gv_update_approved',
			entry_id: entryid,
			form_id: gvGlobals.form_id,
			approved: approved,
			nonce: gvGlobals.nonce,
		}

		$.post( ajaxurl, data, function( response ) {
			if( response ) {
				// If there was a successful AJAX request, toggle the checkbox
				$target.removeClass('loading').toggleClass('entry_approved', (approved === 'Approved') );
			}
		});

		return true;

	}




	$(document).ready( function() {

		// add actions to bulk select box
		$("#bulk_action, #bulk_action2").append('<optgroup label="GravityView"><option value="approve-'+ gvGlobals.form_id +'">' + gvGlobals.label_approve +'</option><option value="unapprove-'+ gvGlobals.form_id +'">'+ gvGlobals.label_disapprove +'</option></optgroup>');

		// display update message if any
		if( gvGlobals.bulk_message.length > 0 ) {
			displayMessage( gvGlobals.bulk_message, 'updated', '#lead_form');
		}

		// inject approve/disapprove buttons into the first column of table
		$('thead th.check-column:eq(1), tfoot th.check-column:eq(1)').after('<th scope="col" class="manage-column column-cb check-column gv-approve-column"><a href="'+ gvGlobals.column_link +'" title="'+ gvGlobals.column_title +'"></a></th>');

		$('td:has(img[src*="star"])').after('<td class="gv-approve-column"><a href="#" class="toggleApproved" title="'+ gvGlobals.approve_title +'"></a></td>');

		$('tr:has(input.entry_approved)').find('a.toggleApproved').addClass('entry_approved').prop('title', gvGlobals.unapprove_title );



		$('.toggleApproved').click( function(e) {
			e.preventDefault();

			var entryID = $(this).parent().parent().find( 'th input[type="checkbox"]' ).val();

			$(this).addClass('loading');

			if( $(this).hasClass('entry_approved') ) {
				$(this).prop('title', gvGlobals.approve_title );
				updateApproved( entryID, 0, $(this));
			} else {
				$(this).prop('title', gvGlobals.unapprove_title );
				updateApproved( entryID, 'Approved', $(this));
			}

			return false;

		});


	});

}(jQuery));
