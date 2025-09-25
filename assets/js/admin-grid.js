( ( $ ) => {
	/**
	 * Enable sortable behaviour for grid rows within the provided context.
	 *
	 * @param {jQuery|HTMLElement|Document} selector Context to search for grids.
	 */
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

					refreshRowReorderButtons( $( this ) );
					if ( ui.sender && ui.sender.length ) {
						refreshRowReorderButtons( ui.sender );
					}
				}
			};

			let connectWith = $( grid ).closest( '.gv-grid' ).data( 'grid-connect' );

			options.start = ( event, ui ) => {
				ui.item.data( 'gv-original-container', getRowContainer( ui.item ) );

				if ( connectWith !== undefined ) {
					$( selector ).find( '[data-grid-connect="' + connectWith + '"]' ).addClass( 'is-receivable' );
				}
			};

			options.stop = ( event, ui ) => {
				if ( connectWith !== undefined ) {
					$( selector ).find( '[data-grid-connect="' + connectWith + '"]' ).removeClass( 'is-receivable' );
				}

				const $item = ui.item;
				const $newContainer = getRowContainer( $item );
				const $original = $item.data( 'gv-original-container' );

				refreshRowReorderButtons( $newContainer );

				if ( $original && $original.length && !$original.is( $newContainer ) ) {
					refreshRowReorderButtons( $original );
				}

				$item.removeData( 'gv-original-container' );
				markUnsavedChanges();
			};

			if ( connectWith !== undefined ) {
				options.connectWith = '[data-grid-connect="' + connectWith + '"] > .gv-grid-rows-container';
			}

			$( grid ).sortable( options );
			refreshRowReorderButtons( $( grid ) );
		} );
	};

	/**
	 * Toggle the Add Row flyout while syncing accessibility attributes.
	 *
	 * @param {jQuery} $container The `.gv-grid-add-row` wrapper.
	 * @param {boolean} isOpen Whether the flyout should be open.
	 */
const setAddRowState = ( $container, isOpen ) => {
	$container.toggleClass( 'open', isOpen );
	$container.find( '.gv-toggle' ).attr( 'aria-expanded', isOpen );
	$container.find( '.gv-grid-row-layouts-wrapper' ).attr( 'aria-hidden', !isOpen );

		const $options = $container.find( '[data-add-row]' );
		if ( isOpen ) {
			$options.each( ( _, option ) => {
				option.removeAttribute( 'tabindex' );
			} );
			return;
		}

	// Prevent hidden layout options from receiving focus.
	$options.attr( 'tabindex', -1 );
};

let lastInteractionWasKeyboard = false;

/**
 * Return the rows container for the provided row element.
 *
 * @param {jQuery} $row The current grid row.
 * @return {jQuery} The wrapping rows container.
	 */
	const getRowContainer = ( $row ) => $row.closest( '.gv-grid-rows-container' );

	/**
	 * Flag the View as having unsaved changes when the helper is available.
	 */
	const markUnsavedChanges = () => {
		window?.viewConfiguration?.setUnsavedChanges?.( true );
	};

	/**
	 * Focus the provided element, delegating to the shared helper when possible.
	 *
	 * @param {jQuery} $element Target element to receive focus.
	 */
	const ensureFocus = ( $element ) => {
		if ( !$element || $element.length === 0 ) {
			return false;
		}

		if ( window?.viewConfiguration?.ensureFocus ) {
			window.viewConfiguration.ensureFocus( $element );
			return document.activeElement === $element[0];
		}

		try {
			$element[0].focus( { preventScroll: true } );
		} catch ( error ) {
			$element.trigger( 'focus' );
		}

		return document.activeElement === $element[0];
	};

	/**
	 * Focus the row container to ensure focus-within styles remain active.
	 *
	 * @param {jQuery} $row Row receiving focus.
	 */
	const focusRowContainer = ( $row ) => {
		if ( !$row || $row.length === 0 ) {
			return;
		}

		try {
			$row.attr( 'tabindex', '-1' );
			$row[0].focus( { preventScroll: true } );
		} catch ( error ) {
			$row.trigger( 'focus' );
		}

		setTimeout( () => {
			$row.removeAttr( 'tabindex' );
		}, 250 );
	};

	/**
	 * Attempt to focus a visible row control in the preferred order.
	 *
	 * @param {jQuery} $row The active row.
	 * @param {'up'|'down'} preferred Preferred control to focus.
	 * @return {boolean} Whether focus was applied to a control.
	 */
	const focusRowControl = ( $row, preferred ) => {
		if ( !$row || $row.length === 0 ) {
			return false;
		}

		const selectors = preferred === 'up'
			? [ '.gv-grid-row-move-up:visible', '.gv-grid-row-move-down:visible' ]
			: [ '.gv-grid-row-move-down:visible', '.gv-grid-row-move-up:visible' ];

		selectors.push( '.gv-grid-row-handle:visible' );
		selectors.push( '.gv-grid-row-delete:visible' );

		for ( let i = 0; i < selectors.length; i++ ) {
			const $target = $row.find( selectors[ i ] ).first();
			if ( $target.length && ensureFocus( $target ) ) {
				return true;
			}
		}

		return false;
	};

	/**
	 * Announce row movement for assistive technology users.
	 *
	 * @param {jQuery} $row The row that moved.
	 */
	const announceRowMove = ( $row ) => {
		const $container = getRowContainer( $row );
		if ( !$container.length ) {
			return;
		}

		const $rows = $container.children( '.gv-grid-row' );
		const index = $rows.index( $row );

		if ( index === -1 ) {
			return;
		}

		let $status = $( '#gv-reorder-status' );
		if ( !$status.length ) {
			$status = $( '<div>', {
				id: 'gv-reorder-status',
				class: 'screen-reader-text',
				'aria-live': 'polite',
				role: 'status'
			} ).appendTo( document.body );
		}

		$status.text( 'Row moved to position ' + ( index + 1 ) + ' of ' + $rows.length + '.' );
	};

	/**
	 * Update visibility of the reorder buttons for a row.
	 *
	 * @param {jQuery} $row Row whose controls should be updated.
	 */
	const updateRowReorderButtons = ( $row ) => {
		const $container = getRowContainer( $row );
		if ( !$container.length ) {
			return;
		}

		const $rows = $container.children( '.gv-grid-row' );
		const index = $rows.index( $row );
		const atTop = index <= 0;
		const atBottom = index === $rows.length - 1;
		const isOnlyRow = $rows.length <= 1;

		const $up = $row.find( '.gv-grid-row-move-up' );
		const $down = $row.find( '.gv-grid-row-move-down' );
		const $handle = $row.find( '.gv-grid-row-handle' );

		const canMoveUp = !isOnlyRow && !atTop;
		const canMoveDown = !isOnlyRow && !atBottom;

		$up
			.attr( 'aria-hidden', canMoveUp ? 'false' : 'true' )
			.attr( 'aria-disabled', ( !canMoveUp ).toString() )
			.prop( 'disabled', !canMoveUp )
			.attr( 'tabindex', canMoveUp ? 0 : -1 )
			.toggle( canMoveUp );

		$down
			.attr( 'aria-hidden', canMoveDown ? 'false' : 'true' )
			.attr( 'aria-disabled', ( !canMoveDown ).toString() )
			.prop( 'disabled', !canMoveDown )
			.attr( 'tabindex', canMoveDown ? 0 : -1 )
			.toggle( canMoveDown );

		if ( $handle.length ) {
			const shouldHideHandle = isOnlyRow || $row.hasClass( 'is-keyboard-nav' );

			$handle.attr( 'aria-hidden', shouldHideHandle ? 'true' : 'false' );
			if ( shouldHideHandle ) {
				$handle.attr( 'tabindex', -1 ).hide();
			} else {
				$handle.removeAttr( 'tabindex' ).show();
			}
		}
	};

	/**
	 * Refresh reorder button visibility for every row in a container.
	 *
	 * @param {jQuery} $container Rows container to refresh.
	 */
	const refreshRowReorderButtons = ( $container ) => {
		if ( !$container || !$container.length ) {
			return;
		}

		$container.children( '.gv-grid-row' ).each( ( _, row ) => {
			updateRowReorderButtons( $( row ) );
		} );
	};

	/**
	 * Move a row while preserving focus and announcing the change.
	 *
	 * @param {jQuery} $row Row to move.
	 * @param {number} direction Negative moves up; positive moves down.
	 * @param {'up'|'down'} preferred Preferred control to focus after move.
	 * @param {jQuery} [$trigger] Control initiating the move.
	 */
	const moveRow = ( $row, direction, preferred, $trigger = undefined ) => {
		if ( !$row || !$row.length ) {
			return;
		}

		const $container = getRowContainer( $row );
		if ( !$container.length ) {
			return;
		}

		const $rows = $container.children( '.gv-grid-row' );
		const index = $rows.index( $row );
		const targetIndex = index + ( direction < 0 ? -1 : 1 );

		if ( targetIndex < 0 || targetIndex >= $rows.length ) {
			return;
		}

		const $focused = $( document.activeElement );
		const hadFocusInside = $focused.length && $.contains( $row[0], $focused[0] );

		if ( direction < 0 ) {
			$row.prev( '.gv-grid-row' ).before( $row );
		} else {
			$row.next( '.gv-grid-row' ).after( $row );
		}

		markUnsavedChanges();

		setTimeout( () => {
			focusRowContainer( $row );
			refreshRowReorderButtons( $container );
			announceRowMove( $row );

			if ( $trigger && $trigger.length && $trigger.is( ':visible' ) && ensureFocus( $trigger ) ) {
				return;
			}

			if ( focusRowControl( $row, preferred ) ) {
				return;
			}

			if ( hadFocusInside && $focused.length && ensureFocus( $focused ) ) {
				return;
			}

			const $fallback = $row.find( '.gv-grid-row-move-up:visible:not([disabled]), .gv-grid-row-move-down:visible:not([disabled]), .gv-grid-row-handle:visible, .gv-grid-row-delete:visible, button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])' )
				.filter( ':visible' )
				.first();

			if ( $fallback.length && ensureFocus( $fallback ) ) {
				return;
			}
		}, 0 );
	};

	/**
	 * Bind DOM listeners for row controls and ensure accessibility hooks stay in sync.
	 */
	const initRowInteractions = () => {
		$( document )
			.on( 'keydown.gv-grid-input', ( event ) => {
				const navigationKeys = [
					'Tab',
					'ArrowUp',
					'ArrowDown',
					'ArrowLeft',
					'ArrowRight',
					'Home',
					'End',
					'Enter',
					' '
				];

				if ( navigationKeys.includes( event.key ) ) {
					lastInteractionWasKeyboard = true;
				}
			} )
			.on( 'pointerdown.gv-grid-input mousedown.gv-grid-input touchstart.gv-grid-input', ( event ) => {
				lastInteractionWasKeyboard = false;

				const $row = $( event.target ).closest( '.gv-grid-row' );
				if ( $row.length ) {
					$row.removeClass( 'is-keyboard-nav' );
					updateRowReorderButtons( $row );
				}
			} )
			.on( 'click', '.gv-grid-row-delete', function () {
				const $row = $( this ).closest( '.gv-grid-row' );
				const $fields = $row.find( '.gv-fields' );
				const $container = getRowContainer( $row );

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
					refreshRowReorderButtons( $container );
					markUnsavedChanges();
					$( document.body ).trigger( 'gravityview/row-removed', $row );
				} );
			} )
			.on( 'click', '.gv-grid-row-move-up', function ( e ) {
				const $button = $( this );

				if ( $button.data( 'gv-skip-click' ) ) {
					$button.removeData( 'gv-skip-click' );
					e.preventDefault();
					e.stopPropagation();
					return;
				}

				e.preventDefault();
				e.stopPropagation();
				ensureFocus( $button );

				moveRow( $button.closest( '.gv-grid-row' ), -1, 'up', $button );
			} )
			.on( 'click', '.gv-grid-row-move-down', function ( e ) {
				const $button = $( this );

				if ( $button.data( 'gv-skip-click' ) ) {
					$button.removeData( 'gv-skip-click' );
					e.preventDefault();
					e.stopPropagation();
					return;
				}

				e.preventDefault();
				e.stopPropagation();
				ensureFocus( $button );

				moveRow( $button.closest( '.gv-grid-row' ), 1, 'down', $button );
			} )
			.on( 'keydown', '.gv-grid-row-move-up, .gv-grid-row-move-down', function ( e ) {
				const $button = $( this );
				const direction = $button.hasClass( 'gv-grid-row-move-up' ) ? -1 : 1;
				const preferred = direction < 0 ? 'up' : 'down';

				if ( e.key === ' ' || e.keyCode === 32 || e.key === 'Enter' || e.keyCode === 13 ) {
					e.preventDefault();
					e.stopPropagation();
					$button.data( 'gv-skip-click', true );
					setTimeout( () => {
						$button.removeData( 'gv-skip-click' );
					}, 250 );

					moveRow( $button.closest( '.gv-grid-row' ), direction, preferred, $button );
					return;
				}

				if ( e.key === 'ArrowUp' || e.keyCode === 38 ) {
					e.preventDefault();
					moveRow( $button.closest( '.gv-grid-row' ), -1, 'up', $button );
				} else if ( e.key === 'ArrowDown' || e.keyCode === 40 ) {
					e.preventDefault();
					moveRow( $button.closest( '.gv-grid-row' ), 1, 'down', $button );
				}
			} )
			.on( 'keydown', '.gv-grid-row-handle', function ( e ) {
				const $handle = $( this );

				if ( e.key === 'ArrowUp' || e.keyCode === 38 ) {
					e.preventDefault();
					moveRow( $handle.closest( '.gv-grid-row' ), -1, 'up', $handle );
				} else if ( e.key === 'ArrowDown' || e.keyCode === 40 ) {
					e.preventDefault();
					moveRow( $handle.closest( '.gv-grid-row' ), 1, 'down', $handle );
				}
			} )
			.on( 'keydown', '.gv-grid-row', function ( e ) {
				if ( e.target !== this ) {
					return;
				}

				if ( e.key === 'ArrowUp' || e.keyCode === 38 ) {
					e.preventDefault();
					moveRow( $( this ), -1, 'up' );
				} else if ( e.key === 'ArrowDown' || e.keyCode === 40 ) {
					e.preventDefault();
					moveRow( $( this ), 1, 'down' );
				}
			} )
			.on( 'focusin', '.gv-grid-row', function () {
				const $row = $( this );
				if ( lastInteractionWasKeyboard ) {
					$row.addClass( 'is-keyboard-nav' );
				}
				refreshRowReorderButtons( getRowContainer( $row ) );
			} )
			.on( 'focusout', '.gv-grid-row', function ( event ) {
				const $row = $( this );
				if ( $row[0] === event.target && $row.has( event.relatedTarget ).length > 0 ) {
					return;
				}
				if ( !$.contains( this, event.relatedTarget ) ) {
					$row.removeClass( 'is-keyboard-nav' );
					updateRowReorderButtons( $row );
				}
			} )
			.on( 'click', '.gv-grid-add-row .gv-toggle', function ( e ) {
				const $add_row = $( this ).closest( '.gv-grid-add-row' );
				const isOpen = !$add_row.hasClass( 'open' );
				setAddRowState( $add_row, isOpen );
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
						setAddRowState( $add_row, false );
					} )
					.done( ( response => {
						const result = JSON.parse( response );
						const $row = $( result?.row );
						const $container = $add_row.closest( '.gv-grid' ).find( '> .gv-grid-rows-container' );

						$row.appendTo( $container );

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

						refreshRowReorderButtons( $container );
						focusRowControl( $row, 'down' );
						markUnsavedChanges();
					} ) );
			} );
	};

	$( () => {
		activateGrid( document );

		$( '.gv-grid-add-row' ).each( ( _, element ) => {
			const $element = $( element );
			setAddRowState( $element, $element.hasClass( 'open' ) );
		} );

		if ( window?.gvAdminActions !== undefined ) {
			window.gvAdminActions.activateGrid = activateGrid;
		}

		initRowInteractions();
	} );
} )( jQuery );
