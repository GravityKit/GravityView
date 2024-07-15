/**
 * Responsible for copying the short codes from the list and edit page.
 * @since 2.21
 */
( function ( $ ) {
	$( function () {
		const shortcode_clipboard = new ClipboardJS( '.gv-shortcode input.code', {
			text: function ( trigger ) {
				return $( trigger ).val();
			}
		} );

		shortcode_clipboard.on('success', function (e) {
			const $el = $( e.trigger ).closest( '.gv-shortcode' ).find( '.copied' );
			$el.show();
			setTimeout( function () {
				$el.fadeOut();
			}, 1000 );
		});

		// ClipBoardJS only listens to the `click` event, so we fake that here for `Enter`.
		$( '.gv-shortcode input.code' ).on( 'keydown', function ( e ) {
			if ( 'Enter' === e.key ) {
				e.preventDefault();
				$( this ).trigger( 'click' );
			}
		} );
	} );
} )( jQuery );
