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

		$( document ).on( 'click', '[data-add-row]', function () {
			const $add_row_button = $( this );
			const zone = $add_row_button.data( 'add-row' );
			const template_id = $add_row_button.data( 'template-id' );

			$.post( ajaxurl, {
				action: 'gv_create_row',
				template_id,
				nonce: gvGlobals.nonce,
				zone,
				dataType: 'json'
			} )
				.done( ( response => {
					const result = JSON.parse( response );
					const $row = $( result?.row );
					$row.insertBefore( $add_row_button );

					window?.gvAdminActions?.initTooltips();
					window?.gvAdminActions?.initDroppables( $row );
				} ) );
		} );
	} );
} )( jQuery );
