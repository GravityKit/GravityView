/**
 * Javascript for Entry Approval
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
		 * @var {string} The class added and removed based on whether entry is approved
		 */
		'css_classes': {
			'approved': 'gv-approval-approved',
			'unapproved': 'gv-approval-unapproved',
			'disapproved': 'gv-approval-disapproved',
			'loading': 'gv-approval-loading'
		},

		/**
		 * @var {string} jQuery selector used to find approval target
		 */
		'selector': '.gv-approval-toggle'
	};

	$(function() {
		self.setup_triggers();
	});

	/**
	 * Checks whether there's DataTables table. If so, uses different trigger.
	 * @returns {void}
	 */
	self.setup_triggers = function(){

		// Only continue if the script has been properly localized
		if ( ! window.gvApproval ) {
			return;
		}

		var maybeDT = $( self.dt_selector );

		if ( maybeDT.length > 0 ){
			$( '.gv-datatables' ).on( 'draw.dt', self.add_toggle_approval_trigger );

			$( window ).on( 'gravityview-datatables/event/responsive', self.add_toggle_approval_trigger );
		} else {
			self.add_toggle_approval_trigger();
		}
	};

	/**
	 * Bind a trigger to the selector element
	 * @since 2.3.1
	 */
	self.add_toggle_approval_trigger = function() {

		/**
		 * Little helper function to add the .selected class the current value
		 * @param element
		 * @param status 1, 2, or 3
		 */
		var gv_select_status = function( element, status ) {
			$( element )
				.find('a').removeClass('selected').end()
				.find('a[data-approved="' + status + '"]').addClass('selected');
		};

		tippy( self.selector, {
			interactive: true,
			arrow: true,
			arrowType: 'round',
			theme: 'light-border',
			content: gvApproval.status_popover_template,
			placement: gvApproval.status_popover_placement,
			onShow: function( showEvent ) {
				var $entry_element = $( showEvent.reference );
				var current_status = parseInt( $entry_element.attr( 'data-current-status' ), 10 );

				var onClickHandler = function( linkClickEvent ) {
					linkClickEvent.preventDefault();

					var new_status = parseInt( $( linkClickEvent.target ).attr( 'data-approved' ), 10 );

					$entry_element._newStatus = new_status;
					self.toggle_approval( linkClickEvent, $entry_element );

					gv_select_status( showEvent.popper, new_status );
				};

				/**
				 * Needs to be defined here so we can pass it showEvent.popper
				 *
				 * @param {Event} keyPressEvent
				 */
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

					$( showEvent.popper ).find( 'a[data-approved="' + key + '"]' ).trigger('click');
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

		$( self.selector ).on( 'click', function( e ) {
			e.preventDefault();

			if ( $( e.target ).hasClass( self.css_classes.loading ) ) {
				if ( self.debug ) {
					console.log( 'add_toggle_approval_trigger', 'Cannot toggle approval while approval is pending.' );
				}
				return false;
			}
			self.toggle_approval( e );
		} );
	};

	/**
	 * Toggle a specific entry
	 *
	 * @param e The clicked entry event object
	 * @param {jQuery} $target If passed, the clicked element passed from tippy.js
	 * @returns {boolean}
	 */
	self.toggle_approval = function( e, $target ) {
		e.preventDefault();

		if ( $target && $target._newStatus ) {
			var $link = $target;
			var new_status = $target._newStatus;
		} else {
			var $link = $( e.target ).is( 'span' ) ? $( e.target ).parent() : $( e.target );
			var new_status = self.get_new_status( e, $link.attr( 'data-current-status' ) );
		}
		var entry_slug = $link.attr( 'data-entry-slug' );
		var form_id = $link.attr( 'data-form-id' );

		if ( self.debug ) {
			console.log( 'toggle_approval', { 'target': e.target, 'current_approval_value': $link.attr( 'data-current-status' ), 'new_status': new_status } );
		}

		$link.addClass( self.css_classes.loading );

		self.update_approval( entry_slug, form_id, new_status, $link );

		return false;
	};

	/**
	 * Get the new status value that should be used when clicking the link, based on current value
	 *
	 * @param {Event} e
	 * @param {string|int} old_status Old status value
	 *
	 * @returns {int}
	 */
	self.get_new_status = function( e, old_status ) {
		var new_status;

		// When holding down option/control, unapprove the entry
		if ( e.altKey ) {
			e.preventDefault(); // Prevent browser takeover

			// When holding down option+shift, disapprove the entry
			if ( e.shiftKey ) {
				return gvApproval.status.disapproved.value;
			}

			return gvApproval.status.unapproved.value;
		}


		// The `+ ""` code converts the value to a string, without requiring `.toString()`
		switch( old_status + "" ) {
			case gvApproval.status.approved.value + "":
				new_status = gvApproval.status.disapproved.value;
				break;
			default:
				new_status = gvApproval.status.approved.value;
				break;
		}

		return new_status;
	};

	/**
	 * Update an entry status via AJAX
	 */
	self.update_approval = function ( entry_slug, form_id, set_approved, $target ) {

		var data = {
			action: 'gv_update_approved',
			entry_slug: entry_slug,
			form_id: form_id,
			approved: set_approved,
			nonce: gvApproval.nonce
		};

		var css_class, new_status;

		$target.attr( 'aria-busy', true );

		$.post( gvApproval.ajaxurl, data, function ( response ) {
			if( response.success ) {

				switch( response.data.status ) {
					case gvApproval.status.approved.value:
						new_status = gvApproval.status.approved;
						css_class = self.css_classes.approved;
						break;
					case gvApproval.status.disapproved.value:
						new_status = gvApproval.status.disapproved;
						css_class = self.css_classes.disapproved;
						break;
					case gvApproval.status.unapproved.value:
						new_status = gvApproval.status.unapproved;
						css_class = self.css_classes.unapproved;
						break;
				}

				$target
					.prop( 'title', new_status.title )
					.attr( 'data-current-status', response.data.status )
					.removeClass( self.css_classes.disapproved )
					.removeClass( self.css_classes.approved )
					.removeClass( self.css_classes.unapproved )
					.addClass( css_class )
					.find('span')
						.text( new_status.label );

			} else if( '0' !== response ) {
				if( self.debug ) {
					console.error( 'AJAX Error', response );
				}
				alert( response.data[0].message );
			}

			$target.attr( 'aria-busy', false ).removeClass( self.css_classes.loading );

			if( self.debug ) {
				console.log( 'update_approval', { 'data': data, 'response': response });
			}
		});

		return true;
	};

} (jQuery) );
