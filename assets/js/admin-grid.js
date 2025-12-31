( ( $ ) => {
	/**
	 * Grid row reordering accessibility utilities
	 * @since 2.48
	 */
	const gridReorder = {
		/**
		 * Get the rows container for a given row
		 * @since 2.48
		 */
		getRowContainer: function( $row ) {
			return $row.closest( '.gv-grid-rows-container' );
		},

		/**
		 * Mark the View as having unsaved changes
		 * @since 2.48
		 */
		markUnsavedChanges: function() {
			if ( window?.viewConfiguration?.setUnsavedChanges ) {
				window.viewConfiguration.setUnsavedChanges( true );
			}
		},

		/**
		 * Update visibility of move up/down buttons based on row position
		 * @since 2.48
		 */
		updateRowReorderButtons: function( $row ) {
			const $container = gridReorder.getRowContainer( $row );
			const $siblings = $container.children( '.gv-grid-row.is-sortable' );
			const index = $siblings.index( $row );
			const $up = $row.find( '.gv-grid-row-move-up' );
			const $down = $row.find( '.gv-grid-row-move-down' );
			const atTop = index <= 0;
			const atBottom = index === $siblings.length - 1;

			$up.attr( 'aria-hidden', atTop ? 'true' : 'false' ).toggle( ! atTop );
			$down.attr( 'aria-hidden', atBottom ? 'true' : 'false' ).toggle( ! atBottom );
		},

		/**
		 * Refresh all row reorder buttons in a container
		 * @since 2.48
		 */
		refreshRowReorderButtons: function( $container ) {
			if ( ! $container || ! $container.length ) {
				$container = $( '.gv-grid-rows-container' );
			}

			$container.children( '.gv-grid-row.is-sortable' ).each( function() {
				gridReorder.updateRowReorderButtons( $( this ) );
			} );
		},

		/**
		 * Announce row position change to screen readers
		 * @since 2.48
		 */
		announceRowMove: function( $row ) {
			try {
				const $container = gridReorder.getRowContainer( $row );
				const $siblings = $container.children( '.gv-grid-row.is-sortable' );
				const index = $siblings.index( $row );
				let $status = $( '#gv-row-reorder-status' );

				if ( $status.length === 0 ) {
					$status = $( '<div/>', {
						id: 'gv-row-reorder-status',
						'class': 'screen-reader-text',
						'aria-live': 'polite',
						role: 'status'
					} ).appendTo( document.body );
				}

				$status.text( 'Row moved to position ' + ( index + 1 ) + ' of ' + $siblings.length + '.' );
			} catch ( e ) {
				// Silent failure for screen reader announcements
			}
		},

		/**
		 * Ensure focus on an element with retries
		 * @since 2.48
		 */
		ensureFocus: function( $el ) {
			if ( ! $el || ! $el.length ) {
				return false;
			}

			let attempts = 0;
			const maxAttempts = 5;
			const tryFocus = function() {
				attempts++;
				if ( ! $el.is( ':visible' ) ) {
					if ( attempts < maxAttempts ) {
						return setTimeout( tryFocus, 0 );
					}
					return;
				}

				try {
					$el[ 0 ].focus( { preventScroll: true } );
				} catch ( e ) {
					$el.trigger( 'focus' );
				}

				const ok = document.activeElement === $el[ 0 ];
				if ( ! ok && attempts < maxAttempts ) {
					setTimeout( tryFocus, 0 );
				}
			};

			tryFocus();
			return true;
		},

		/**
		 * Focus the row to ensure visibility of reorder controls
		 * @since 2.48
		 */
		focusRowContainer: function( $row ) {
			if ( ! $row || ! $row.length ) {
				return;
			}

			try {
				$row.attr( 'tabindex', '-1' );
				$row[ 0 ].focus( { preventScroll: true } );
				setTimeout( function() {
					$row.removeAttr( 'tabindex' );
				}, 250 );
			} catch ( e ) {
				// Silent failure
			}
		},

		/**
		 * Focus the preferred reorder control button
		 * @since 2.48
		 */
		focusRowControl: function( $row, preferred ) {
			let sel = preferred === 'up' ? '.gv-grid-row-move-up' : '.gv-grid-row-move-down';
			let $btn = $row.find( sel ).filter( ':visible' );

			if ( ! $btn.length ) {
				sel = preferred === 'up' ? '.gv-grid-row-move-down' : '.gv-grid-row-move-up';
				$btn = $row.find( sel ).filter( ':visible' );
			}

			if ( $btn.length ) {
				$btn.trigger( 'focus' );
				return true;
			}

			return false;
		},

		/**
		 * Move a row up or down in the container
		 * @since 2.48
		 */
		moveRow: function( $row, delta, preferred, $usedBtn ) {
			if ( ! $row || ! $row.length || ! $row.hasClass( 'is-sortable' ) ) {
				return;
			}

			const $container = gridReorder.getRowContainer( $row );
			if ( ! $container.length ) {
				return;
			}

			const $siblings = $container.children( '.gv-grid-row.is-sortable' );
			const index = $siblings.index( $row );
			const newIndex = index + ( delta < 0 ? -1 : 1 );

			if ( newIndex < 0 || newIndex >= $siblings.length ) {
				return;
			}

			const $focused = $( document.activeElement );
			const hadFocusInside = $.contains( $row[ 0 ], $focused[ 0 ] );

			if ( delta < 0 ) {
				$row.prev( '.gv-grid-row.is-sortable' ).before( $row );
			} else {
				$row.next( '.gv-grid-row.is-sortable' ).after( $row );
			}

			gridReorder.markUnsavedChanges();

			// Defer updates and focus to next tick to ensure DOM settled
			setTimeout( function() {
				// Ensure :focus-within for visibility of reorder controls
				gridReorder.focusRowContainer( $row );
				gridReorder.updateRowReorderButtons( $row );
				gridReorder.announceRowMove( $row );

				// Update sibling buttons too
				gridReorder.refreshRowReorderButtons( $container );

				// Prefer re-focusing the exact button used if still visible
				if ( $usedBtn && $usedBtn.length && $usedBtn.is( ':visible' ) ) {
					gridReorder.ensureFocus( $usedBtn );
				} else if ( ! gridReorder.focusRowControl( $row, preferred ) ) {
					if ( hadFocusInside && $focused && $focused.length && $.contains( $row[ 0 ], $focused[ 0 ] ) ) {
						gridReorder.ensureFocus( $focused );
					} else {
						const $t = $row.find( '.gv-grid-row-move-up:visible, .gv-grid-row-move-down:visible, button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])' ).filter( ':visible' ).first();
						if ( $t.length ) {
							gridReorder.ensureFocus( $t );
						}
					}
				}
			}, 0 );
		}
	};

	const activateGrid = ( selector ) => {
		$( selector ).find( '.gv-grid > .gv-grid-rows-container' ).each( ( i, grid ) => {
			let options = {
				handle: '> .gv-grid-row-actions > .gv-grid-row-handle',
				items: '> .gv-grid-row.is-sortable',
				cancel: 'input, textarea, select, option', // Exclude 'button' so handle button works
				distance: 2,
				revert: 75,
				placeholder: 'grid-row-placeholder',
				forcePlaceholderSize: true,
				stop: function( event, ui ) {
					gridReorder.markUnsavedChanges();
					gridReorder.refreshRowReorderButtons( $( this ) );
					gridReorder.announceRowMove( ui.item );
				},
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
				options.stop = function( event, ui ) {
					$( selector ).find( '[data-grid-connect="' + connectWith + '"]' ).removeClass( 'is-receivable' );
					gridReorder.markUnsavedChanges();
					gridReorder.refreshRowReorderButtons( $( this ) );
					gridReorder.announceRowMove( ui.item );
				};
			}

			$( grid ).sortable( options );
		} );
	};

	$( () => {
		activateGrid( document );

		if ( window?.gvAdminActions !== undefined ) {
			window.gvAdminActions.activateGrid = activateGrid;
			window.gvAdminActions.gridReorder = gridReorder;
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

		// Handle keyboard events for non-button grid row actions
		$( document ).on( 'keydown', '.gv-grid-row-action:not(button)', function ( e ) {
			// Trigger click on Enter or Space key for non-button elements only.
			// Native buttons already handle keyboard activation.
			if ( e.key === 'Enter' || e.key === ' ' ) {
				e.preventDefault();
				$( this ).trigger( 'click' );
			}
		} );

		// Row reorder: Move up button click handler
		$( document ).on( 'click', '.gv-grid-row-move-up', function ( e ) {
			const $btn = $( this );
			if ( $btn.data( 'gv-skip-click' ) ) {
				$btn.removeData( 'gv-skip-click' );
				e.preventDefault();
				e.stopPropagation();
				return;
			}

			e.preventDefault();
			e.stopPropagation();
			gridReorder.moveRow( $btn.closest( '.gv-grid-row' ), -1, 'up', $btn );
		} );

		// Row reorder: Move down button click handler
		$( document ).on( 'click', '.gv-grid-row-move-down', function ( e ) {
			const $btn = $( this );
			if ( $btn.data( 'gv-skip-click' ) ) {
				$btn.removeData( 'gv-skip-click' );
				e.preventDefault();
				e.stopPropagation();
				return;
			}

			e.preventDefault();
			e.stopPropagation();
			gridReorder.moveRow( $btn.closest( '.gv-grid-row' ), 1, 'down', $btn );
		} );

		// Row reorder: Keyboard navigation for move buttons
		$( document ).on( 'keydown', '.gv-grid-row-move-up, .gv-grid-row-move-down', function ( e ) {
			const $btn = $( this );
			const isUp = $btn.hasClass( 'gv-grid-row-move-up' );

			if ( e.key === ' ' || e.keyCode === 32 || e.key === 'Enter' || e.keyCode === 13 ) {
				// Prevent native click firing later; run our move now
				e.preventDefault();
				e.stopPropagation();
				$btn.data( 'gv-skip-click', true );
				setTimeout( function () {
					$btn.removeData( 'gv-skip-click' );
				}, 250 );

				if ( isUp ) {
					gridReorder.moveRow( $btn.closest( '.gv-grid-row' ), -1, 'up', $btn );
				} else {
					gridReorder.moveRow( $btn.closest( '.gv-grid-row' ), 1, 'down', $btn );
				}
				return;
			}

			// Arrow key support
			if ( e.key === 'ArrowUp' || e.keyCode === 38 ) {
				e.preventDefault();
				gridReorder.moveRow( $btn.closest( '.gv-grid-row' ), -1, 'up', $btn );
			} else if ( e.key === 'ArrowDown' || e.keyCode === 40 ) {
				e.preventDefault();
				gridReorder.moveRow( $btn.closest( '.gv-grid-row' ), 1, 'down', $btn );
			}
		} );

		// Row reorder: Update button visibility on focus
		$( document ).on( 'focusin', '.gv-grid-row.is-sortable', function () {
			gridReorder.updateRowReorderButtons( $( this ) );
		} );

		// Initialize row reorder buttons on page load
		gridReorder.refreshRowReorderButtons();

		// Refresh buttons when rows are added
		$( document.body ).on( 'gravityview/row-added', function ( event, $row ) {
			if ( $row && $row.length ) {
				const $container = gridReorder.getRowContainer( $row );
				gridReorder.refreshRowReorderButtons( $container );
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
							.end()
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
