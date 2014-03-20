/**
 * Custom js script at post edit screen
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2013, Katz Web Services, Inc.
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
	
	// function to update table on column Approved if visible, after ajax update
	function UpdateApprovedColumns() {
		
		var colIndex = $('th:contains("Approved")').index()-1;
		console.log(colIndex);
		
		$("a.toggleApproved").each( function() {
			
			var tr = $(this).parents('tr');
		
			if( $(this).is('.entry_approved') ) {
				$('td:visible:eq('+ colIndex +')', tr ).html('<i class="fa fa-check gf_valid"></i>');
			} else {
				$('td:visible:eq('+ colIndex +')', tr ).html('');
			}
		});

	}
	
	
	
	// Request entry approve (ajax)
	function UpdateApproved( entryid, approved ) {
	
		var data = {
			action: 'gv_update_approved',
			entry_id: entryid,
			form_id: gvGlobals.form_id,
			approved: approved,
			nonce: gvGlobals.nonce,
		}
			
		$.post( gvGlobals.ajaxurl, data, function( response ) {
			if( response ) {
				
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
			
			$(this).toggleClass('entry_approved');
			
			if( $(this).hasClass('entry_approved') ) {
				$(this).prop('title', gvGlobals.unapprove_title ); 
				UpdateApproved( entryID, 'Approved' );
			} else {
				$(this).prop('title', gvGlobals.approve_title ); 
				UpdateApproved( entryID, 0 );
			}
			
			UpdateApprovedColumns();

			return false;

		});
		
		
	});
 
}(jQuery));
