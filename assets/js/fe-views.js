/**
 * Custom js script loaded on Views frontend
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 *
 * globals jQuery
 */


jQuery(document).ready( function( $ ) {

	var gvFront = {

		init: function () {

			this.cookies();
			this.datepicker();

			$( '.gv-widget-search' ).on( 'keypress change', this.form_changed );

			$( '.gv-search-clear' ).on( 'click', this.clear_search );

		},

		/**
		 * Triggered when the search form changes
		 * - Adds 'data-form-changed' attribute to <form> wrapper
		 * - Fades in the Clear button and changes the text to "Reset"
		 *
		 * @param e jQuery Event
		 */
		form_changed: function ( e ) {

			// Only trigger change on characters, not Shift or Command/Alt
			if ( e.type === 'keypress' && (
				e.which === 0 || e.ctrlKey || e.metaKey || e.altKey
				) ) {
				return;
			}

			$( this ).attr( 'data-form-changed', '1' );

			$( '.gv-search-clear', $( this ) ).text( gvGlobals.reset ).fadeIn( 100 );
		},

		/**
		 * - If the form has been changed, the Clear button becomes Reset and reverts the state to form on load
		 * - If the form has not been changed:
		 *        - If there is no existing search result, hide the button
		 *        - If there is a search result, refresh page without $_GET parameters
		 *
		 * @param e jQuery Event
		 * @returns {boolean}
		 */
		clear_search: function ( e ) {

			var $form = $( this ).parents( 'form' );
			var changed = ( $form.attr( 'data-form-changed' ) === '1' );

			// Handle an existing search
			if ( $form.hasClass( 'gv-is-search' ) ) {

				// If there are no changes, submit the form
				if ( !changed ) {
					return true;
				}

			}

			// If the form has been changed, just reset the data
			if ( changed ) {
				e.preventDefault();

				$form.trigger( 'reset' );

				$form.attr( 'data-form-changed', null ) // Clear the changed status
					.find( '.gv-search-clear' ).text( gvGlobals.clear ); // Update the text of the button

				// If there's now no form field text, hide the reset button
				if ( false === $form.hasClass( 'gv-is-search' ) ) {
					$( '.gv-search-clear', $form ).hide( 100 );
				}

				return false;
			}

			return true;
		},

		/**
		 * Generate the datepicker for GV date fields
		 */
		datepicker: function () {

			// If datepicker is loaded
			if ( jQuery.fn.datepicker ) {

				$( '.gv-datepicker' ).each( function () {
					var element = jQuery( this );
					var image = "";
					var showOn = "focus";

					if ( element.hasClass( "datepicker_with_icon" ) ) {
						showOn = "both";
						image = jQuery( '#gforms_calendar_icon_' + this.id ).val();
					}

					gvGlobals.datepicker.showOn = showOn;
					gvGlobals.datepicker.buttonImage = image;
					gvGlobals.datepicker.buttonImageOnly = true;

					// Process custom date formats
					if ( !gvGlobals.datepicker.dateFormat ) {

						var format = "mm/dd/yy";

						if ( element.hasClass( "mdy" ) )
							format = "mm/dd/yy"; else if ( element.hasClass( "dmy" ) )
							format = "dd/mm/yy"; else if ( element.hasClass( "dmy_dash" ) )
							format = "dd-mm-yy"; else if ( element.hasClass( "dmy_dot" ) )
							format = "dd.mm.yy"; else if ( element.hasClass( "ymd_slash" ) )
							format = "yy/mm/dd"; else if ( element.hasClass( "ymd_dash" ) )
							format = "yy-mm-dd"; else if ( element.hasClass( "ymd_dot" ) )
							format = "yy.mm.dd";

						gvGlobals.datepicker.dateFormat = format;
					}

					element.datepicker( gvGlobals.datepicker );
				} );

			}
		},

		cookies: function () {
			if ( $( "#gravityview_back_link" ).length > 0 ) {
				gvFront.backGetCookie();
			} else if ( $( ".gravityview-view-id" ).length > 0 ) {
				$( ".gravityview-view-id" ).each( gvFront.backSetCookie );
			}
		},

		// Set the back link cookie
		backSetCookie: function () {
			var viewId = $( this ).val();
			$.cookie( 'gravityview_back_link_' + viewId, window.location.href, { path: gvGlobals.cookiepath } );
		},

		// Get the back link cookie and replace the back link href
		backGetCookie: function () {
			var viewId = $( "#gravityview_back_link" ).attr( 'data-viewid' );
			if ( $.cookie( 'gravityview_back_link_' + viewId ) !== null ) {
				$( "#gravityview_back_link" ).attr( 'href', $.cookie( 'gravityview_back_link_' + viewId ) );
			}
		}

	};

	gvFront.init();

});
