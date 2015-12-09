/**
 * Javascript for Entry Approval
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

	var self = {
		'response': { 'status': '' }
	};

	$(function() {
		$( '.toggleApproved' ).on( 'click', self.toggle_approval );
	});

	/**
	 * Toggle a specific entry
	 *
	 * @param e The clicked entry event object
	 * @returns {boolean}
	 */
	self.toggle_approval = function ( e ) {
		e.preventDefault();

		var entry_id = $( this ).attr('data-entry-id');
		var form_id = $( this ).attr('data-form-id');
		var is_approved = $( this ).attr( 'data-approved-status' ).toString();
		var set_approved = ( is_approved === '0' ) ? 'Approved' : '0';

		$( this ).addClass( 'loading' );

		self.update_approval( entry_id, form_id, set_approved, $( this ) );

		console.log( self.response.status );

		return false;
	};

	/**
	 * Update an entry status via AJAX
	 */
	self.update_approval = function ( entry_id, form_id, set_approved, $target ) {

		var data = {
			action: 'gv_update_approved',
			entry_id: entry_id,
			form_id: form_id,
			approved: set_approved,
			nonce: gvApproval.nonce
		};

		$.post( gvApproval.ajaxurl, data, function ( response ) {
			if ( response ) {
				self.response = $.parseJSON( response );
				console.log( self.response );

				if( '0' !== self.response.status ) {
					$target.attr( 'data-approved-status', 'Approved' ).prop( 'title', gvApproval.unapprove_title ).text( gvApproval.text.label_disapprove ).addClass( 'entry_approved' );
				} else {
					$target.attr( 'data-approved-status', '0' ).prop( 'title', gvApproval.approve_title ).text( gvApproval.text.label_approve ).removeClass( 'entry_approved' );
				}

				$target.removeClass( 'loading' );
			}
		});

		return true;
	};

} (jQuery) );
