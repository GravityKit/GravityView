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
 *
 * globals jQuery, gvGlobals, ajaxurl
 */


(function( $ ) {

	"use strict";

	var self = {};

	/**
	 * Enable Approve/Reject functionality on the Gravity Forms Entries page
	 */
	self.init = function() {

		self.maybeDisplayMessages();

		// Only add Bulk Actions if user has the capability
		if( gvGlobals.add_bulk_action * 1 ) {
			self.addBulkAction();
		}

		// Only support approve/reject if the column is visible
		if( gvGlobals.show_column * 1 ) {

			self.addApprovedColumn();

			self.setInitialApprovedEntries();

			$( '.toggleApproved' ).on( 'click', self.toggleApproved );

		}

	};

	/**
	 * If there are messages, display them
	 */
	self.maybeDisplayMessages = function() {
		// display update message if any
		if ( gvGlobals.bulk_message.length > 0 ) {
			self.displayMessage( gvGlobals.bulk_message, 'updated', '#lead_form' );
		}
	};

	/**
	 * Mark the entries that are approved as approved on load
	 */
	self.setInitialApprovedEntries = function() {
		$( 'tr:has(input.entry_approved)' ).find( 'a.toggleApproved' ).addClass( 'entry_approved' ).prop( 'title', gvGlobals.unapprove_title );
	};

	/**
	 * Add approve/reject options to bulk edit dropdown
	 * @since TODO Converted to using gvGlobals.bulk_actions array, instead of hard-coding options
	 */
	self.addBulkAction = function() {

		// If there are no options, don't add the option group!
		if( 0 === gvGlobals.bulk_actions.length ) { return; }

		var $optgroups = [], $optgroup;

		// Create an <optgroup>
		$.each( gvGlobals.bulk_actions, function ( key ) {

			$optgroup = $('<optgroup />', { 'label': key });

			// Create and add each option to the <optgroup>
			$.each( gvGlobals.bulk_actions[ key ], function ( i ) {
				$optgroup.append( $('<option />', { 'value': gvGlobals.bulk_actions[ key ][ i ].value } ).html( gvGlobals.bulk_actions[ key ][ i ].label ) );
			});

			// Add <optgroup> to the list of groups
			$optgroups.push( $optgroup );
		});

		// Then add the list to 'Bulk action' dropdowns
		$( "#bulk_action, #bulk_action2" ).append( $optgroups );
	};

	/**
	 * Add an Approved column and header in the entries table
	 */
	self.addApprovedColumn = function() {

		/**
		 * inject approve/disapprove buttons into the first column of table
		 */
		$( 'thead th.check-column:eq(1), tfoot th.check-column:eq(1)' ).after( '<th scope="col" class="manage-column column-cb check-column gv-approve-column"><a href="' + gvGlobals.column_link + '" title="' + gvGlobals.column_title + '"></a></th>' );

		/**
		 * Add column for each entry
		 */
		$( 'th.check-column[scope=row]:has(img[src*="star"]),td:has(img[src*="star"])' ).after( '<td class="gv-approve-column"><a href="#" class="toggleApproved" title="' + gvGlobals.approve_title + '"></a></td>' );

	};

	/**
	 * Toggle a specific entry
	 *
	 * @param e The clicked entry event object
	 * @returns {boolean}
	 */
	self.toggleApproved = function ( e ) {
		e.preventDefault();

		var entryID = $( this ).parent().parent().find( 'th input[type="checkbox"]' ).val();

		$( this ).addClass( 'loading' );

		if ( $( this ).hasClass( 'entry_approved' ) ) {
			$( this ).prop( 'title', gvGlobals.approve_title );
			self.updateApproved( entryID, 0, $( this ) );
		} else {
			$( this ).prop( 'title', gvGlobals.unapprove_title );
			self.updateApproved( entryID, 'Approved', $( this ) );
		}

		return false;

	};

	/**
	 * Generate a message and prepend it to the container
	 *
	 * @param message Text to display
	 * @param messageClass (default: updated)
	 * @param container Where to prepend the message (default: #lead_form)
	 */
	self.displayMessage = function ( message, messageClass, container ) {

		self.hideMessage( container, true );

		var messageBox = $( '<div class="message ' + messageClass + '" style="display:none;"><p>' + message + '</p></div>' );
		$( messageBox ).prependTo( container ).slideDown();

		if ( messageClass === 'updated' ) {
			window.setTimeout( function () {
				self.hideMessage( container, false );
			}, 10000 );
		}


	};

	/**
	 * Hide a displayed message
	 * @param container
	 * @param messageQueued
	 */
	self.hideMessage = function ( container, messageQueued ) {

		var messageBox = $( container ).find( '.message' );

		if ( messageQueued ) {
			$( messageBox ).remove();
		} else {
			$( messageBox ).slideUp( function () {
				$( this ).remove();
			} );
		}

	};


	/**
	 * Update an entry status via AJAX
 	 */
	self.updateApproved = function ( entryID, approved, $target ) {

		var data = {
			action: 'gv_update_approved',
			entry_id: entryID,
			form_id: gvGlobals.form_id,
			approved: approved,
			nonce: gvGlobals.nonce
		};

		$.post( ajaxurl, data, function ( response ) {
			if ( response ) {
				// If there was a successful AJAX request, toggle the checkbox
				$target.removeClass( 'loading' ).toggleClass( 'entry_approved', (
				approved === 'Approved'
				) );
			}
		} );

		return true;

	};

	$( self.init );

} (jQuery) );
