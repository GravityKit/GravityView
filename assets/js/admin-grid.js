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

		$( document )
			.on( 'click', '.gv-grid-add-row .gv-toggle', function ( e ) {
				const $add_row = $( this ).closest( '.gv-grid-add-row' );
				$add_row.toggleClass( 'open' );
				$( this ).attr( 'aria-expanded', $add_row.hasClass( 'open' ) );
			} )
			.on( 'click', '.gv-grid-add-row [data-add-row]', function ( e ) {
				const $add_row_button = $( this );
				const $add_row = $( this ).closest( '.gv-grid-add-row' );

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
						$add_row.removeClass( 'open' );
						$add_row.find( '.gv-toggle' ).attr( 'aria-expanded', false );
					} )
					.done( ( response => {
						const result = JSON.parse( response );
						const $row = $( result?.row );
						$row.insertBefore( $add_row );

						$( document.body ).trigger(
							'gravityview/row-added',
							$row,
							{
								type,
								row_type,
								zone,
								template_id
							}
						);

						window?.gvAdminActions?.initTooltips();
						window?.gvAdminActions?.initDroppables( $row );
					} ) );
			} );
	} );
} )( jQuery );
