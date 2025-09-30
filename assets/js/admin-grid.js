( ( $ ) => {
	const activateGrid = ( selector ) => {
		$( selector ).find( '.gv-grid > .gv-grid-rows-container' ).each( ( i, grid ) => {
			let options = {
				handle: '> .gv-grid-row-actions > .gv-grid-row-handle',
				items: '> .gv-grid-row.is-sortable',
				distance: 2,
				revert: 75,
				placeholder: 'grid-row-placeholder',
				forcePlaceholderSize: true,
				receive: function ( event, ui ) {
					const sender_area = ui.sender.closest( '.gv-grid' ).data( 'grid-context' );
					const receiver_area = $( this ).closest( '.gv-grid' ).data( 'grid-context' );

					ui.item.attr( 'data-context', receiver_area );
					ui.item.find( '[data-context]' ).attr( 'data-context', receiver_area );
					ui.item.find( '[data-areaid]' ).attr( 'data-areaid', ( _, area_id ) => {
						return area_id.replace( sender_area + '_', receiver_area + '_' );
					} );

					ui.item.find( '[name*="[' + sender_area + '_"]' ).each( function () {
						const name = $( this ).attr( 'name' );
						$( this ).attr( 'name', name.replace( '[' + sender_area + '_', '[' + receiver_area + '_' ) );
					} );
				}
			};

			let connectWith = $( grid ).closest( '.gv-grid' ).data( 'grid-connect' );
			if ( connectWith !== undefined ) {
				options.connectWith = '[data-grid-connect="' + connectWith + '"] > .gv-grid-rows-container';
				options.start = () => {
					$( selector ).find( '[data-grid-connect="' + connectWith + '"]' ).addClass( 'is-receivable' );
				};
				options.stop = () => {
					$( selector ).find( '[data-grid-connect="' + connectWith + '"]' ).removeClass( 'is-receivable' );
				};
			}

			$( grid ).sortable( options );
		} );
	};

	$( () => {
		activateGrid( document );

		if ( window?.gvAdminActions !== undefined ) {
			window.gvAdminActions.activateGrid = activateGrid;
		}

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

		// Handle keyboard events for grid row actions
		$( document ).on( 'keydown', '.gv-grid-row-action', function ( e ) {
			// Trigger click on Enter or Space key
			if ( e.key === 'Enter' || e.key === ' ' ) {
				e.preventDefault();
				$( this ).trigger( 'click' );
			}
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
						$add_row
							.removeClass( 'open' )
							.find( '.gv-toggle' )
								.attr( 'aria-expanded', false )
							.find( 'button' )
								.attr( 'tabindex', '-1' );
					} )
					.done( ( response => {
						const result = JSON.parse( response );
						const $row = $( result?.row );

						$row.appendTo( $add_row.closest( '.gv-grid' ).find( '> .gv-grid-rows-container' ) );

						$row.find( 'button' )
							.attr( 'tabindex', null )
							.first().trigger( 'focus' );

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
