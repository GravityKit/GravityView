/* global ajaxurl,jQuery,tl_obj */
(function( $ ) {

	'use strict';

	$( document ).ready( function () {

		jconfirm.pluginDefaults.useBootstrap = false;
		jconfirm.pluginDefaults.backgroundDismiss = true;

		/**
		 * TODO: Deprecate
		 **/
		function outputErrorAlert( response, tl_obj ) {

			var settings = {
				icon: 'dashicons dashicons-no',
				title: tl_obj.lang.status.failed.title,
				content: tl_obj.lang.status.failed.content + '<pre>' + JSON.stringify( response ) + '</pre>',
				escapeKey: 'ok',
				type: 'red',
				theme: 'material',
				buttons: {
					ok: {
						text: tl_obj.lang.buttons.ok
					}
				}
			};

			switch ( response.status ) {

				case 404: /** vendor not found */
				settings.title = tl_obj.lang.status.error404.title;
					settings.content = tl_obj.lang.status.error404.content;
					break;

				case 409: /** user already exists */
				settings.title = tl_obj.lang.status.error409.title;
					settings.content = tl_obj.lang.status.error409.content;
					break;

				case 503: /** problem syncing to SaaS */
				settings.title = tl_obj.lang.status.error.title;
					settings.content = tl_obj.lang.status.error.content;
					settings.icon = 'dashicons dashicons-external';
					settings.escapeKey = 'close';
					settings.type = 'orange';
					settings.buttons = {
						goToSupport: {
							text: tl_obj.lang.buttons.go_to_site,
							action: function ( goToSupportButton ) {
								window.open( tl_obj.vendor.support_url, '_blank' );
								return false; // you shall not pass
							},
						},
						close: {
							text: tl_obj.lang.buttons.close
						},
					};
					break;
			}

			$.alert( settings );
		}

		/**
		 * TODO: Deprecate
		 **/
		function outputAccessKey( accessKey, tl_obj ) {

			var settings = {
				icon: 'dashicons dashicons-yes',
				title: tl_obj.lang.status.accesskey.title,
				content: tl_obj.lang.status.accesskey.content + '<pre>' + accessKey + '</pre>',
				escapeKey: 'close',
				type: 'green',
				theme: 'material',
				buttons: {
					goToSupport: {
						text: tl_obj.lang.buttons.go_to_site,
						action: function ( goToSupportButton ) {
							window.open( tl_obj.vendor.support_url, '_blank' );
							return false; // you shall not pass
						},
						btnClass: 'btn-blue',
					},
					revokeAccess: {
						text: tl_obj.lang.buttons.revoke,
						action: function ( revokeAccessButton ){
							window.location.assign( tl_obj.lang.status.accesskey.revoke_link );
						},
					},
					close: {
						text: tl_obj.lang.buttons.close
					}
				}
			};

			$.alert( settings );
		}

		function outputStatus( content, type ){

			var dialogClass = 'tl-' + tl_obj.vendor.namespace + '-auth';
			var responseClass = 'tl-' + tl_obj.vendor.namespace + '-auth__response';

			var $responseDiv = jQuery( '.' + dialogClass ).find( '.' + responseClass );

			if ( 0 == $responseDiv.length ){
				if ( tl_obj.debug ) {
					console.log( responseClass + ' not found');
				}
				return;
			}

			// Reset the class and set the type for contextual styling.
			$responseDiv.attr('class', responseClass).addClass('tl-'+ tl_obj.vendor.namespace + '-auth__response_' + type );
			$responseDiv.text( content );


			/**
			 * Handle buttong actions/labels/etc to it's own function
			 */
			if ( 'error' == type ){
				/**
				 * TODO: Translate string
				 **/
				$( tl_obj.selector ).text('Go to support').removeClass('disabled');
				$( 'body' ).off( 'click', tl_obj.selector );
			}

		}

		function grantAccess( $button ){

			$button.addClass( 'disabled' );

			if ( 'extend' == $button.data('access') ){
				outputStatus( tl_obj.lang.status.extending.content, 'pending' );
			} else {
				outputStatus( tl_obj.lang.status.pending.content, 'pending' );
			}


			var data = {
				'action': 'tl_' + tl_obj.vendor.namespace + '_gen_support',
				'vendor': tl_obj.vendor.namespace,
				'_nonce': tl_obj._nonce,
			};

			if ( tl_obj.debug ) {
				console.log( data );
			}

			var secondStatus = setTimeout( function(){
				outputStatus( tl_obj.lang.status.syncing.content, 'pending' );
			}, 3000 );


			$.post( tl_obj.ajaxurl, data, function ( response ) {

				clearTimeout( secondStatus );

				if ( tl_obj.debug ) {
					console.log( response );
				}

				if ( response.success && typeof response.data == 'object' ) {
					if ( response.data.is_ssl ){
						location.href = tl_obj.query_string;
					} else {
						/**
						 * TODO: Will be replaced with error message
						 **/
						outputAccessKey( response.data.access_key, tl_obj );
					}

					/**
					 * TODO: Removed as we no longer need the button to do popups
					 **/
					if ( response.data.access_key ){
						$( tl_obj.selector ).data('accesskey', response.data.access_key );
					}
				} else {
					outputStatus( tl_obj.lang.status.failed.content + ' ' + response.responseJSON.data.message, 'error' );
				}

			} ).fail( function ( response ) {

				clearTimeout( secondStatus );

				if ( tl_obj.debug ) {
					console.log( response );
				}

				outputStatus( tl_obj.lang.status.failed.content + ' ' + response.responseJSON.data.message, 'error' );

			} ).always( function( response ) {

				if ( ! tl_obj.debug ) {
					return;
				}

				if ( typeof response.data === 'object' ) {
					console.log( 'TrustedLogin support login URL:' );
					console.log( response.data.site_url + '/' + response.data.endpoint + '/' + response.data.identifier );
				}
			});
		}

		/**
		 * TODO: Deprecate
		 **/
		function triggerLoginGeneration() {
			var data = {
				'action': 'tl_' + tl_obj.vendor.namespace + '_gen_support',
				'vendor': tl_obj.vendor.namespace,
				'_nonce': tl_obj._nonce,
			};

			if ( tl_obj.debug ) {
				console.log( data );
			}

			$.post( tl_obj.ajaxurl, data, function ( response ) {

				if ( tl_obj.debug ) {
					console.log( response );
				}

				if ( response.success && typeof response.data == 'object' ) {

					if ( response.data.is_ssl ){
						$.alert( {
							icon: 'dashicons dashicons-yes',
							theme: 'material',
							title: tl_obj.lang.status.synced.title,
							type: 'green',
							escapeKey: 'ok',
							content: tl_obj.lang.status.synced.content,
							buttons: {
								ok: {
									text: tl_obj.lang.buttons.ok
								}
							}
						} );
					} else {
						outputAccessKey( response.data.access_key, tl_obj );
					}

					if ( response.data.access_key ){
						$( tl_obj.selector ).data('accesskey', response.data.access_key );
					}



				} else {
					outputErrorAlert( response, tl_obj );
				}

			} ).fail( function ( response ) {

				outputErrorAlert( response, tl_obj );

			} ).always( function( response ) {

				if ( ! tl_obj.debug ) {
					return;
				}

				if ( typeof response.data == 'object' ) {
					console.log( 'TrustedLogin support login URL:' );
					console.log( response.data.site_url + '/' + response.data.endpoint + '/' + response.data.identifier );
				}
			});
		}

		/**
		 * TODO: Deprecate
		 * No longer show alert.
		 **/
		$( 'body' ).on( 'click', tl_obj.selector, function ( e ) {

			e.preventDefault();

			if ( $( this ).data( 'accesskey' ) ){
				outputAccessKey( $( this ).data( 'accesskey'), tl_obj );
				return false;
			}

			grantAccess( $( this ) );
			return false;

		} );


		$( '#trustedlogin-auth' ).on( 'click', '.tl-toggle-caps', function () {
			$( this ).find( 'span' ).toggleClass( 'dashicons-arrow-down-alt2' ).toggleClass( 'dashicons-arrow-up-alt2' );
			$( this ).next( '.tl-details.caps' ).toggleClass( 'hidden' );
		} );

		var copyTimer = null;

		/**
		 * Used for copy-to-clipboard functionality
		 */
		$( '.tl-' + tl_obj.vendor.namespace + '-auth' ).on( 'click', '#tl-' + tl_obj.vendor.namespace +'-copy', function() {
			var $copyButton = $( this );

			copyToClipboard( $( '.tl-' + tl_obj.vendor.namespace + '-auth__accesskey_field' ).val() );

			$copyButton.text( tl_obj.lang.buttons.copied );

			if ( copyTimer ) {
				clearTimeout( copyTimer );
				copyTimer = null;
			}

			copyTimer = setTimeout( function () {
				$copyButton.text( tl_obj.lang.buttons.copy );
			}, 2000 );
		} );

		function copyToClipboard( copyText ) {

			var $temp = $( '<input>' );
			$( 'body' ).append( $temp );
			$temp.val( copyText ).select();
			document.execCommand( 'copy' );
			$temp.remove()

			if ( tl_obj.debug ) {
				console.log( 'Copied to clipboard', copyText );
			}
		}

	} );

})(jQuery);
