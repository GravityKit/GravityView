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
		'response': { 'status': '' },

		/**
		 * @var {boolean} True: print console logs; false: don't
		 */
		'debug': false,

		/**
		 * @var {string} jQuery selector used to find if datatables exist
		 */
		'dt_selector': '.gv-datatables',

		/**
		 * @var {string} jQuery selector used to find approval target
		 */
		'selector': '.toggleApproved'
	};

	$(function() {
		self.setup_triggers();
	});

	/**
	 * Checks whether there's DataTables table. If so, uses different trigger.
	 * @returns {void}
	 */
	self.setup_triggers = function(){

		var maybeDT = $( self.dt_selector );

		if ( maybeDT.length > 0 ){
			$( '.gv-datatables' ).on( 'draw.dt', self.add_toggle_approval_trigger );
		} else {
			self.add_toggle_approval_trigger();
		}
	};

	/**
	 * Bind a trigger to the selector element
	 */
	self.add_toggle_approval_trigger = function() {
		$( self.selector ).on( 'click', function( e ) {
			self.toggle_approval( e );
		});
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

		if( self.debug ) {
			console.log( 'toggle_approval', { 'target': e.target, 'is_approved': is_approved });
		}

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
					$target
						.attr( 'data-approved-status', 'Approved' )
						.prop( 'title', gvApproval.text.disapprove_title )
						.text( gvApproval.text.label_disapprove )
						.addClass( 'entry_approved' );
				} else {
					$target
						.attr( 'data-approved-status', '0' )
						.prop( 'title', gvApproval.text.approve_title )
						.text( gvApproval.text.label_approve )
						.removeClass( 'entry_approved' );
				}

				$target.removeClass( 'loading' );
			}
			if( self.debug ) {
				console.log( 'update_approval', { 'data': data, 'response': response });
			}
		});

		return true;
	};

} (jQuery) );
