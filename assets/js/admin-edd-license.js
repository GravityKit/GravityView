/*global jQuery, document, ajaxurl */
(function( $ ) {
	"use strict";

	var GV_EDD = {

		message: '',
		license_field: $('#license_key'),
		activate_button : $( '[data-edd_action=activate_license]' ),
		deactivate_button: $( '[data-edd_action=deactivate_license]' ),
		check_button: $( '[data-edd_action=check_license]' ),

		init: function() {

			GV_EDD.message_fadeout();
			GV_EDD.add_status_container();

			$( '.version-info' ).insertBefore('#gform_tab_group');

			$( document )
				.on( 'ready keyup', GV_EDD.license_field, GV_EDD.key_change )
				.on( 'click', ".gv-edd-action", GV_EDD.clicked )
				.on( 'gv-edd-failed gv-edd-invalid', GV_EDD.failed )
				.on( 'gv-edd-valid', GV_EDD.valid )
				.on( 'gv-edd-deactivated', GV_EDD.deactivated )
				.on( 'gv-edd-inactive gv-edd-other', GV_EDD.other );

		},

		/**
		 * Hide the "Settings Updated" message after save
		 */
		message_fadeout: function() {
			setTimeout( function() {
				$('#gform_tab_group #message' ).toggle('scale');
			}, 2000 );
		},

		add_status_container: function() {
			$( GVGlobals.license_box ).insertBefore( GV_EDD.license_field );
		},

		/**
		 * When the license key changes, change the button visibility
		 * @todo refactor- no need having this, plus all the separate methods
		 * @param e
		 */
		key_change: function( e ) {

			//return;
			var license_key = $('#license_key').val();

			var showbuttons = false;
			var hidebuttons = false;

			//buttons.show();

			if( license_key.length > 0 ) {

				switch( $('#license_key_status' ).val() ) {
					case 'valid':
						hidebuttons = $('[data-edd_action=activate_license]' );
						showbuttons = $('[data-edd_action=deactivate_license],[data-edd_action=check_license]' );
						break;
					default:
					case 'deactivated':
					case 'site_inactive':
						hidebuttons = $('[data-edd_action=deactivate_license]' );
						showbuttons = $('[data-edd_action=activate_license],[data-edd_action=check_license]' );
						break;
				}
			} else if ( license_key.length === 0 ) {
				hidebuttons = $('[data-edd_action*=_license]');
			}

			// On load, no animation. Otherwise, 100ms
			var speed = ( e.type === 'ready' ) ? 0 : 'fast';

			if( hidebuttons ) {
				hidebuttons.filter(':visible').fadeOut( speed );
			}
			if( showbuttons ) {
				showbuttons.filter( ':hidden' ).removeClass( 'hide' ).hide().fadeIn( speed );
			}
		},

		/**
		 * Show the HTML of the message
		 * @param {string} selector jQuery selector to replace content with
		 * @param {string} message HTML for new status
		 */
		update_status: function( selector, message ) {
			if( message !== '' ) {
				$( selector ).replaceWith( message ).fadeIn();
			}
		},

		set_pending_message: function( message ) {

			// Hide the license details
			$('.gv-license-details')
				.attr( 'aria-busy', 'true' )
				.find('ul')
					.animate( { opacity: 0.5 }, 1000 );

			$( '#gv-edd-status' )
				.addClass('pending')
				.attr( 'aria-busy', 'true' )
				.html( '<p>' + message + '</p>');
		},

		clicked: function( e ) {
			e.preventDefault();

			var $that = $( this );

			var theData = {
				license: $('#license_key').val(),
				edd_action: $that.attr( 'data-edd_action' ),
				field_id: $that.attr( 'id' ),
			};

			$that.not( GV_EDD.check_button ).addClass('button-disabled');

			$( '#gform-settings,#gform-settings .button').css('cursor', 'wait');

			GV_EDD.set_pending_message( $that.attr('data-pending_text') );

			GV_EDD.post_data( theData );

		},

		/**
		 * Take a string that may be JSON or may be JSON
		 *
		 * @since 1.12
		 * @param {string} string JSON text to attempt to parse
		 * @returns {object} Either JSON-parsed object or object with a message key containing an error message
		 */
		parse_response_json: function( string ) {
			var response_object;

			// Parse valid JSON
			try {

				response_object = $.parseJSON( string );

			} catch( exception ) {

				// The JSON didn't parse most likely because PHP warnings.
				// We attempt to strip out all content up to the expected JSON `{"`
				var second_try = string.replace(/((.|\n)+?){"/gm, "{\"");

				try {

					response_object = $.parseJSON( second_try );

				} catch( e ) {
					
					console.log( '*** \n*** \n*** Error-causing response:\n***\n***\n', string );

					var error_message = 'JSON failed: another plugin caused a conflict with completing this request. Check your browser\'s Javascript console to view the invalid content.';

					response_object = {
						message: '<div id="gv-edd-status" aria-live="polite" class="gv-edd-message inline error"><p>' + error_message + '</p></div>'
					};
				}
			}

			return response_object;
		},

		post_data: function( theData ) {

			$.post( ajaxurl, {
				'action': 'gravityview_license',
				'data': theData
			}, function ( response ) {

				var response_object = GV_EDD.parse_response_json( response );

				GV_EDD.message = response_object.message;

				if( theData.edd_action !== 'check_license' ) {
					$( '#license_key_status' ).val( response_object.license );
					$( '#license_key_response' ).val( JSON.stringify( response_object ) );
					$( document ).trigger( 'gv-edd-' + response_object.license, response_object );
				}

				GV_EDD.update_status( '#gv-edd-status', response_object.message );
				GV_EDD.update_status( '.gv-license-details', response_object.details );

				$( '#gform-settings')
					.css('cursor', 'default')
						.find('.button')
						.css('cursor', 'pointer');
			} );

		},

		valid: function( e ) {
			GV_EDD.activate_button
				.fadeOut( 'medium', function () {
					GV_EDD.activate_button.removeClass( 'button-disabled' );
					GV_EDD.deactivate_button.fadeIn().css( "display", "inline-block" );
				} );
		},

		failed: function( e ) {
			GV_EDD.deactivate_button.removeClass( 'button-disabled' );
			GV_EDD.activate_button.removeClass( 'button-disabled' );
		},

		deactivated: function( e ) {
			GV_EDD.deactivate_button
				.css('min-width', function() {
					return $(this ).width();
				})
				.fadeOut( 'medium', function () {
					GV_EDD.deactivate_button.removeClass( 'button-disabled' );
					GV_EDD.activate_button.fadeIn(function() {
						$(this).css( "display", "inline-block" );
					});
				} );

		},

		other: function( e ) {
			GV_EDD.deactivate_button.fadeOut( 'medium', function () {
				GV_EDD.activate_button
					.removeClass( 'button-disabled' )
					.fadeIn()
					.css( "display", "inline-block" );
			} );
		}
	};

	GV_EDD.init();

})(jQuery);
