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
	}, maybeDT;

	$(function() {
		maybeDT = $('.gv-datatables');
		self.dtCheck( maybeDT );
	});

	/**
	 * Check if the DataTables Extension is in use
	 * @param maybeDT
	 */
	self.dtCheck = function( maybeDT ){

		if (maybeDT.length !== 0){
			$(maybeDT).on( 'draw.dt', function () {
				$( '.toggleApproved' ).on( 'click', function( e ) {
					self.toggle_approval(e);
				});
			});
		} else {
			$( '.toggleApproved' ).on( 'click', function( e ) {
				self.toggle_approval(e);
			});
		}
	};

	/**
	 * Toggle a specific entry
	 *
	 * @param e The clicked entry event object
	 * @returns {boolean}
	 */
	self.toggle_approval = function ( e ) {
		e.preventDefault();

		var entry_id = $( e.target ).attr('data-entry-id');
		var form_id = $( e.target ).attr('data-form-id');
		var is_approved = $( e.target ).attr( 'data-approved-status').toString();
		var set_approved = ( is_approved === '' || is_approved === '0' ) ? 'Approved' : '0';

		console.log(is_approved);

		$( this ).addClass( 'loading' );

		self.update_approval( entry_id, form_id, set_approved, $( e.target ) );

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

				if( '0' !== self.response.status ) {
					$target.attr( 'data-approved-status', 'Approved' ).prop( 'title', gvApproval.text.disapprove_title ).text( gvApproval.text.label_disapprove ).addClass( 'entry_approved' );
				} else {
					$target.attr( 'data-approved-status', '0' )
							.prop( 'title', gvApproval.text.approve_title ).text( gvApproval.text.label_approve ).removeClass( 'entry_approved' );
				}

				$target.removeClass( 'loading' );
			}
		});

		return true;
	};

} (jQuery) );
