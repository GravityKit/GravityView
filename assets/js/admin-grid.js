( ( $ ) => {
	$( () => {
		$( document ).find( '.gv-grid' ).sortable( {
			handle: '.gv-grid-row-handle',
			items: '> .gv-grid-row.is-sortable',
			distance: 2,
			revert: 75,
			placeholder: 'grid-row-placeholder',
			forcePlaceholderSize: true,
		} );

		$( document ).on( 'click', '.gv-grid-row-delete', function () {
			const $row = $( this ).closest( '.gv-grid-row' );
			const $fields = $row.find( '.gv-fields' );

			if (
				$fields.length > 0
				&& !confirm( $( this ).data( 'confirm' ) )
			) {
				return;
			}

			$row.fadeOut( 'fast', () => {
				$fields.each( function () {
					$( this ).remove();
					$( document.body ).trigger( 'gravityview/field-removed', $( this ) );
				} );

				$row.remove();
				$( document.body ).trigger( 'gravityview/row-removed', $row );
			} );
		} );

		$( '.gv-grid-add-row' )
			.on( 'click', '.gv-toggle', function ( e ) {
				$( e.delegateTarget ).toggleClass( 'open' );
				$( this ).attr( 'aria-expanded', $( e.delegateTarget ).hasClass( 'open' ) );
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
					.always( () => {
						$( e.delegateTarget ).removeClass( 'open' )
						$( e.delegateTarget ).find( '.gv-toggle' ).attr( 'aria-expanded', false );
					} )
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
