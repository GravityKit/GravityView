/*global jQuery, document, redux, ajaxurl */
(function( $ ) {
	"use strict";

	var GV_EDD = {

		message: '',
		last_response: {},
		license_field: $('#license_key'),
		icon: null,
		activate_button : $('[name=edd-activate]'),
		deactivate_button: $('[name=edd-deactivate]'),

		init: function() {

			GV_EDD.message_fadeout();
			GV_EDD.add_message_container();
			GV_EDD.add_icon();

			$( document )
				.on( 'change', GV_EDD.license_field, GV_EDD.key_change )
				.on( 'click', ".gv-edd-action", GV_EDD.clicked )
				.on( 'gv-edd-failed gv-edd-invalid', GV_EDD.failed )
				.on( 'gv-edd-valid', GV_EDD.valid )
				.on( 'gv-edd-deactivated', GV_EDD.deactivated )
				.on( 'gv-edd-inactive gv-edd-other', GV_EDD.other )
				//.on( 'gv-edd-failed gv-edd-valid gv-edd-invalid gv-edd-deactivated gv-edd-inactivate gv-edd-other', GV_EDD.update_message )
				//.on( 'gv-edd-failed gv-edd-valid gv-edd-invalid gv-edd-deactivated gv-edd-inactivate gv-edd-other', GV_EDD.update_icon )
			;
		},

		/**
		 * Hide the "Settings Updated" message after save
		 */
		message_fadeout: function() {
			setTimeout( function() {
				$('#message').slideUp();
			}, 2000 );
		},

		add_message_container: function() {
			$( GVGlobals.license_box ).insertBefore( GV_EDD.license_field );
		},

		add_icon: function() {
			var icon = $('#gaddon-setting-row-license_key td' ).find('.fa');

			if( icon.length === 0 ) {
				icon = $('<i />').insertAfter( GV_EDD.license_field );
			}

			GV_EDD.icon = icon;
		},

		key_change: function( e ) {

			var key = GV_EDD.license_field.val();

			if( key.length !== 0 ) {
				GV_EDD.activate_button.hide().removeClass('hide').fadeIn('medium');
			}
		},

		update_icon: function( response ) {

			if( response.success ) {
				GV_EDD.icon.attr( 'class', 'fa icon-check fa-check gf_valid');
			} else {
				GV_EDD.icon.attr( 'class', 'fa icon-remove fa-times gf_invalid');
			}
		},

		update_message: function( message ) {
			if( message !== '' ) {
				$( '#gv-edd-status' ).replaceWith( message );
			}
		},

		set_pending_message: function( message ) {
			$( '#gv-edd-status' )
				.addClass('pending')
				.html( '<p>' + message + '</p>');
		},

		clicked: function( e ) {
			e.preventDefault();

			var $that = $( this );

			GV_EDD.parent = $that.parentsUntil('tr');
			GV_EDD.activate_button = $( '[name*=edd-activate]', GV_EDD.parent );
			GV_EDD.deactivate_button = $( '[name*=edd-deactivate]', GV_EDD.parent );

			var theData = {
				license: GV_EDD.license_field.val(),
				edd_action: $that.attr( 'data-edd_action' ),
				field_id: $that.attr( 'id' ),
			};

			$that.addClass('button-disabled');
			$( '#gform-settings,#gform-settings .button').css('cursor', 'wait');

			GV_EDD.set_pending_message( $that.attr('data-pending_text') );

			GV_EDD.post_data( theData );

		},

		post_data: function( theData ) {

			$.post( ajaxurl, {
				'action': 'gravityview_license',
				'data': theData
			}, function ( response ) {

				console.log( response );
				response = $.parseJSON( response );

				console.log( response );
				GV_EDD.message = response.message;

				GV_EDD.last_response = response;

				$( '#license_key_status' ).val( response.license );
				$( '#license_key_response' ).val( JSON.stringify( response ) );

				$( document ).trigger( 'gv-edd-' + response.license, response );

				GV_EDD.update_message( response.message );

				GV_EDD.update_icon( response );

				$( '#gform-settings')
					.css('cursor', 'default')
						.find('.button')
						.css('cursor', 'pointer');

				console.log( response );

			} );

		},

		valid: function() {

			GV_EDD.activate_button.fadeOut( 'medium', function () {
				GV_EDD.activate_button.removeClass( 'button-disabled' );
				GV_EDD.deactivate_button.fadeIn().css( "display", "inline-block" );
			} );
		},

		failed: function() {
			GV_EDD.deactivate_button.removeClass( 'button-disabled' );
			GV_EDD.activate_button.removeClass( 'button-disabled' );
		},

		deactivated: function() {

			GV_EDD.deactivate_button
				.fadeOut( 'medium', function () {
					GV_EDD.deactivate_button.removeClass( 'button-disabled' );
					GV_EDD.activate_button.fadeIn().css( "display", "inline-block" );
				} );

		},

		other: function() {
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
