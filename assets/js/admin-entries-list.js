/**
 * Custom js script at post edit screen
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
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
		if ( gvGlobals.show_column * 1 ) {
			self.addApprovedColumn();

			self.setInitialApprovedEntries();

			self.setupTippy();

			$( '.toggleApproved' ).on( 'click', self.toggleApproved );
		}
	};

	self.setupTippy = function() {

		/**
		 * Little helper function to add the .selected class the current value
		 * @param element
		 * @param status 1, 2, or 3
		 */
		var gv_select_status = function( element, status ) {
			$( element )
				.find('a').removeClass('selected').off().end()
				.find('a[data-approved="' + status + '"]').addClass('selected');
		};

		tippy( '.toggleApproved', {
			interactive: true,
			arrow: true,
			arrowType: 'round',
			theme: 'light-border',
			content: gvGlobals.status_popover_template,
			placement: gvGlobals.status_popover_placement,
			onShow: function( showEvent ) {
				var $entry_element = $( showEvent.reference );
				var current_status = parseInt( $entry_element.attr( 'data-current-status' ), 10 );

				var onClickHandler = function( linkClickEvent ) {
					linkClickEvent.preventDefault();

					var new_status = parseInt( $( linkClickEvent.target ).attr( 'data-approved' ), 10 );
					var entry_id = $entry_element.parent().parent().find( 'th input[type="checkbox"]' ).val();
					var new_class_and_title = self.getClassAndTitleFromApprovalStatus( new_status );

					$entry_element
						.addClass( 'loading' )
						.prop( 'title', new_class_and_title[ 1 ] )
						.attr( 'data-current-status', new_status );

					self.updateApproved( entry_id, new_status, $entry_element );

					gv_select_status( showEvent.popper, new_status );
				};

				document.gvStatusKeyPressHandler = function( keyPressEvent ) {
					keyPressEvent.preventDefault();

					// Support keypad when using more modern browsers
					var key = keyPressEvent.key || keyPressEvent.keyCode;

					if ( 'Escape' === key || 'Esc' === key ) {
						showEvent.popper._tippy.hide();
						return;
					}

					if ( -1 === [ '1', '2', '3' ].indexOf( key ) ) {
						return;
					}

					$( showEvent.popper )
						.find( 'a[data-approved="' + key + '"]' ).trigger('click');
				};

				$( document ).on( 'keyup', document.gvStatusKeyPressHandler );

				$( showEvent.popper ).on( 'click', onClickHandler );

				gv_select_status( showEvent.popper, current_status );
			},
			onHide: function ( hideEvent ) {
				$( hideEvent.popper ).off('click');
				$( document ).off( 'keyup', document.gvStatusKeyPressHandler );
			}
		} );

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
	 * Return CSS class and title associated with approval status
	 * @returns {array}
	 */
	self.getClassAndTitleFromApprovalStatus = function( status ) {
		var css_class, title;

		if ( parseInt( status, 10 ) === parseInt( gvGlobals.status_unapproved, 10 ) ) {
			css_class = 'unapproved';
			title = gvGlobals.unapprove_title;
		} else if ( parseInt( status, 10 ) === parseInt( gvGlobals.status_approved, 10 ) ) {
			css_class = 'approved';
			title = gvGlobals.disapprove_title;
		} else {
			css_class = 'disapproved';
			title = gvGlobals.approve_title;
		}

		return [ css_class, title ];
	};

	/**
	 * Mark the entries that are approved as approved on load
	 * See GravityView_Admin_ApproveEntries::add_entry_approved_hidden_input() for where input comes from
	 */
	self.setInitialApprovedEntries = function() {

		$( 'tr:has(input.entry_approval)' ).each( function() {

			var $input = $( 'input.entry_approval', $( this ) );
			var class_and_title = self.getClassAndTitleFromApprovalStatus( $input.val() );

			$( this ).find( 'a.toggleApproved' )
				.addClass( class_and_title[ 0 ] )
				.prop( 'title', class_and_title[ 1 ] )
				.attr( 'data-current-status', $input.val() );
		} );
	};

	/**
	 * Add approve/reject options to bulk edit dropdown
	 * @since 1.16.3 Converted to using gvGlobals.bulk_actions array, instead of hard-coding options
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
		$( "#bulk_action, #bulk_action2, #bulk-action-selector-top, #bulk-action-selector-bottom" ).append( $optgroups );
	};

	/**
	 * Add an Approved column and header in the entries table
	 */
	self.addApprovedColumn = function() {

		// Don't add column if there are no entries yet.
		if( $( 'tbody tr', '#lead_form' ).length === 1 && $( 'tbody tr td', '#lead_form' ).length === 1 ) {
			return;
		}

		var link = '<a href="' + gvGlobals.column_link + '" title="' + gvGlobals.column_title + '"></a>';

		// No link to sort by value? Show a span instead
		if( 0 === gvGlobals.column_link.length ) {
			link = '<span title="' + gvGlobals.column_title + '"></span>';
		}

		/**
		 * inject approve/disapprove buttons into the first column of table
		 */
		$( 'thead th.check-column:eq(1), tfoot th.check-column:eq(1), thead .column-is_starred, tfoot .column-is_starred' ).after( '<th scope="col" class="manage-column column-cb gv-approve-column column-is_approved">' + link + '</th>' );

		/**
		 * Add column for each entry
		 */
		$( 'th.check-column[scope=row]:has(img[src*="star"]),td:has(img[src*="star"]),tbody th.column-is_starred' ).after( '<th scope="row" class="column-is_approved gv-approve-column"><a href="#" class="toggleApproved" title="' + gvGlobals.approve_title + '"></a></th>' );

	};

	/**
	 * Toggle a specific entry
	 *
	 * @param e The clicked entry event object
	 * @returns {boolean}
	 */
	self.toggleApproved = function ( e ) {
		e.preventDefault();

		var entryID = $( this ).parent().parent().find( 'th input[type="checkbox"]' ).val(),
			title,
			status;

		// When holding down option/control, unapprove the entry
		if ( e.altKey ) {
			e.preventDefault(); // Prevent browser takeover
			// When holding down option+shift, disapprove the entry
			if ( e.shiftKey ) {
				status = gvGlobals.status_disapproved;
				title = gvGlobals.disapprove_title;
			} else {
				status = gvGlobals.status_unapproved;
				title = gvGlobals.unapprove_title;
			}
		} else if ( $( this ).hasClass( 'approved' ) ) {
			title = gvGlobals.approve_title;
			status = gvGlobals.status_disapproved;
		} else {
			title = gvGlobals.disapprove_title;
			status = gvGlobals.status_approved;
		}

		$( this )
			.addClass( 'loading' )
			.prop( 'title', title )
			.attr( 'data-current-status', status );

		self.updateApproved( entryID, status, $( this ) );

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
			entry_slug: entryID,
			form_id: gvGlobals.form_id,
			approved: approved,
			nonce: gvGlobals.nonce,
			admin_nonce: gvGlobals.admin_nonce
		};

		$.post( ajaxurl, data, function ( response ) {
			if ( response ) {

				$target.removeClass( 'loading' );

				if( response.success ) {

					var approved_increment    = $target.hasClass( 'approved' ) ? -1 : 0,
							disapproved_increment = $target.hasClass( 'disapproved' ) ? -1 : 0,
							unapproved_increment  = $target.hasClass( 'unapproved' ) ? -1 : 0;

					$target.removeClass( 'approved unapproved disapproved' );

					switch ( parseInt( approved, 10 ) ) {
						case parseInt( gvGlobals.status_approved, 10 ):
							$target.addClass( 'approved' );
							approved_increment++;
							break;
						case parseInt( gvGlobals.status_disapproved, 10 ):
							$target.addClass( 'disapproved' );
							disapproved_increment++;
							break;
						case parseInt( gvGlobals.status_unapproved, 10 ):
							$target.addClass( 'unapproved' );
							unapproved_increment++;
							break;
					}

					// Update the entry filter count
					window.UpdateCount( "gv_approved_count", approved_increment );
					window.UpdateCount( "gv_disapproved_count", disapproved_increment );
					window.UpdateCount( "gv_unapproved_count", unapproved_increment );

				} else {
					alert( response.data[0].message );
				}
			}
		} );

		return true;

	};

	$( self.init );

} (jQuery) );
