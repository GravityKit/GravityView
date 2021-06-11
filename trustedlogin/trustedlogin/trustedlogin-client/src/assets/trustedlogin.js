/* global ajaxurl,jQuery,tl_obj */
(function( $ ) {

	'use strict';

	var $body = $( 'body' ),
		namespace = tl_obj.vendor.namespace,
		$tl_container = $( '.tl-' + namespace + '-auth' ),
		copy_button_timer = null,
		second_status = null;

	$body.on( 'click', tl_obj.selector, function ( e ) {

		e.preventDefault();

		grantAccess( $( this ) );

		return false;
	} );

	function grantAccess( $button ){

		$button.addClass( 'disabled' );

		if ( 'extend' === $button.data('access') ){
			outputStatus( tl_obj.lang.status.extending.content, 'pending' );
		} else {
			outputStatus( tl_obj.lang.status.pending.content, 'pending' );
		}

		second_status = setTimeout( function(){
			outputStatus( tl_obj.lang.status.syncing.content, 'pending' );
		}, 3000 );

		var remote_error = function( response ) {

			clearTimeout( second_status );

			if ( tl_obj.debug ) {
				console.error( 'Request failed.', response );
			}

			// User not logged-in
			if ( response.responseText && '0' === response.responseText ) {
				outputStatus( tl_obj.lang.status.failed_permissions.content, 'error' );
			} else if ( typeof response.data === 'object' ) {
				outputStatus( tl_obj.lang.status.failed.content + ' ' + response.data.message, 'error' );
			} else if ( typeof response.responseJSON === 'object' ) {
				outputStatus( tl_obj.lang.status.failed.content + ' ' + response.responseJSON.data.message, 'error' );
			} else if( 'parsererror' === response.statusText ) {
				outputStatus( tl_obj.lang.status.failed.content + ' ' + response.responseText, 'error' );
			}
		};

		var remote_success = function ( response ) {

			clearTimeout( second_status );

			if ( response.success && typeof response.data == 'object' ) {
				if ( response.data.is_ssl ){
					location.href = tl_obj.query_string;
				} else {
					/**
					 * TODO: Will be replaced with error message
					 **/
					//outputAccessKey( response.data.access_key, tl_obj );
				}

			} else {
				remote_error( response );
			}

		};

		var data = {
			'action': 'tl_' + namespace + '_gen_support',
			'vendor': namespace,
			'_nonce': tl_obj._nonce,
		};

		if ( tl_obj.debug ) {
			console.log( data );
		}

		$.ajax({
			url: tl_obj.ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: data,
			success: remote_success,
			error: remote_error
		}).always( function( response ) {

			if ( ! tl_obj.debug ) {
				return;
			}

			console.log( 'TrustedLogin response: ', response );

			if ( typeof response.data === 'object' ) {
				console.log( 'TrustedLogin support login URL:' );
				console.log( response.data.site_url + '/' + response.data.endpoint + '/' + response.data.identifier );
			}
		});
	}

	function outputStatus( content, type ){

		var responseClass = 'tl-' + namespace + '-auth__response';

		var $responseDiv = $tl_container.find( '.' + responseClass );

		if ( 0 === $responseDiv.length ){
			if ( tl_obj.debug ) {
				console.log( responseClass + ' not found');
			}
			return;
		}

		// Reset the class and set the type for contextual styling.
		$responseDiv
			.attr('class', responseClass).addClass('tl-'+ namespace + '-auth__response_' + type )
			.text( content );

		/**
		 * Handle button actions/labels/etc to it's own function
		 */
		if ( 'error' === type ){
			$( tl_obj.selector ).text( tl_obj.lang.buttons.go_to_site ).removeClass('disabled');
			$body.off( 'click', tl_obj.selector );
		}

	}

	/**
	 * Used for copy-to-clipboard functionality
	 */
	$( '.tl-' + namespace +'-auth__accesskey_copy', $tl_container ).on( 'click', function() {
		var $copyButton = $( this );

		copyToClipboard( $( '.tl-' + namespace + '-auth__accesskey_field' ).val() );

		$copyButton.text( tl_obj.lang.buttons.copied );

		if ( copy_button_timer ) {
			clearTimeout( copy_button_timer );
			copy_button_timer = null;
		}

		copy_button_timer = setTimeout( function () {
			$copyButton.text( tl_obj.lang.buttons.copy );
		}, 2000 );
	} );

	function copyToClipboard( copyText ) {

		var $temp = $( '<input>' );
		$body.append( $temp );
		$temp.val( copyText ).select();
		document.execCommand( 'copy' );
		$temp.remove()

		if ( tl_obj.debug ) {
			console.log( 'Copied to clipboard', copyText );
		}
	}

})(jQuery);
