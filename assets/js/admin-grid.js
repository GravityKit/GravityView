( ( $ ) => {
	$( () => {
		$( document ).find( '.gv-grid' ).sortable( {
			handle: '.gv-grid-row-handle',
			items: '> .gv-grid-row.is-draggable',
			distance: 2,
			revert: 75,
			placeholder: 'grid-row-placeholder',
			forcePlaceholderSize: true,
		} );

		$( '.gv-grid-add-row' )
			.on( 'click', '.gv-toggle', function ( e ) {
				$( e.delegateTarget ).toggleClass( 'open' );
			} )
			.on( 'click', '[data-add-row]', function ( e ) {
				const $add_row_button = $( this );

				const zone = $add_row_button.data( 'add-row' );
				const template_id = $add_row_button.data( 'template-id' );
				const type = $add_row_button.data( 'type' );
				const row_type = $add_row_button.data( 'row-type' );

				$.post( ajaxurl, {
					action: 'gv_create_row',
					template_id,
					nonce: gvGlobals.nonce,
					zone,
					type,
					row_type,
					dataType: 'json'
				} )
					.always( () => $( e.delegateTarget ).removeClass( 'open' ) )
					.done( ( response => {
						const result = JSON.parse( response );
						const $row = $( result?.row );
						$row.insertBefore( $( e.delegateTarget ) );

						window?.gvAdminActions?.initTooltips();
						window?.gvAdminActions?.initDroppables( $row );
					} ) );
			} );
	} );
} )( jQuery );
