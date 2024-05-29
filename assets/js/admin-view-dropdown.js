/**
 * Renders the view dropdown.
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2024, Katz Web Services, Inc.
 *
 * @since 2.24
 *
 * globals jQuery
 */

( function ( $ ) {
	'use strict';

	/**
	 * Creates a new instance of `ViewDropDown`
	 * @since 2.24
	 * @param {Element} el The original `select` element.
	 * @constructor
	 */
	var ViewDropDown = function ( el ) {
		this.initialized = false;
		this.$el = $( el );
		this.open = false;

		this.init();
		this.renderOptions();
	};

	/**
	 * Initializes the dropdown (once).
	 *
	 * It replaces the original `select` with a nicely styled `combobox`, and hooks up the required events.
	 *
	 * @since 2.24
	 */
	ViewDropDown.prototype.init = function () {
		if ( this.initialized ) {
			return;
		}

		const $el = this.$el;
		const dropdown = this;

		this.storeValue();

		$el.hide();
		$el.wrap( $( '<div class="view-dropdown"></div>' ) );
		this.$wrapper = $el.closest( 'div.view-dropdown' );
		this.$options_wrapper = $( ' <div class="view-dropdown-options"></div>' );
		this.$wrapper.append( this.$options_wrapper );

		if ( this.$el.data( 'scope' ) ) {
			this.$options_wrapper.append( $( '<div class="view-dropdown-options__header"><span>' + this.$el.data( 'scope' ) + ' — ' + this.$el.data( 'label' ) + '</span></div>' ) );
		}
		this.$options_list = $( '<div class="view-dropdown-list"></div>' );
		this.$options_wrapper.append( $( '<div class="view-dropdown-options__body"></div>' ).append( this.$options_list ) );
		this.$options_wrapper.append( $(
			'<div class="view-dropdown-options__footer">' +
			'	<a target="_blank" href="https://docs.gravitykit.com/article/400-what-are-the-differences-between-the-view-types">' +
			'		<span>' + this.$el.data( 'label-learn-more' ) + '</span>' +
			'		<svg width="11" height="10" viewBox="0 0 11 10" fill="none" xmlns="http://www.w3.org/2000/svg">' +
			'			<path d="M1 9.16659L9.33333 0.833252M9.33333 0.833252H1M9.33333 0.833252V9.16659" stroke="#2271B1" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>' +
			'		</svg>' +
			'	</a>' +
			'</div>'
		) );

		this.select = $(
			'<div tabindex="0" role="listbox" class="view-dropdown-select">' +
			'	<div class="view-dropdown-select__value">' +
			'		<div class="view-dropdown-select__value__icon"></div>' +
			'		<span class="view-dropdown-select__value__label">' + this.$el.data( 'label' ) + ':</span>' +
			'		<span class="view-dropdown-select__value__selection"></span>' +
			'	</div>' +
			'	<div class="view-dropdown-select__toggle">' +
			'		<div class="view-dropdown-toggle__chevron"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 12.5L10 7.5L15 12.5" stroke="#667085" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/></svg></div>' +
			'	</div>' +
			'</div>'
		).insertAfter( this.$el );

		// Close select on escape.
		$( document.body )
			.on( 'keyup', function ( e ) {
				if ( dropdown.open && e.key === 'Escape' ) {
					dropdown.close();
				}
			} )
			.on( 'keydown', function ( e ) {
				if ( dropdown.open && !e.altKey && !e.metaKey && !e.ctrlKey && !e.shiftKey ) {
					e.preventDefault();
				}

				// Capture tab, space or enter if we are focussing on the items.
				if ( dropdown.open && [ ' ', 'Enter', 'Tab' ].indexOf( e.key ) > -1 && dropdown.$options_list.find( ':focus' ).length > 0 ) {
					e.preventDefault();
				}

				// Capture enter, arrow up & down and space if we are focussing on the entire select.
				if ( [ 'Enter', ' ', 'ArrowUp', 'ArrowDown' ].indexOf( e.key ) > -1 && dropdown.select.is( document.activeElement ) ) {
					e.preventDefault();
				}
			} )
			.on( 'click', function ( e ) {
				// Close the dropdown if clicked outside the wrapper.
				var is_inside = $.contains( dropdown.$wrapper.get( 0 ), e.target );
				if ( !is_inside && dropdown.open ) {
					e.preventDefault();

					dropdown.close();
				}
			} );

		$( this.select )
			.on( 'click', $.proxy( this.toggle, dropdown ) )
			.on( 'keyup', $.proxy( this.handleKeySelect, this ) );

		$el.on( 'change', $.proxy( this.close, dropdown ) );

		$( this.$options_list )
			.on( 'mousedown', 'div.view-dropdown-list-item[aria-disabled="true"]', function ( e ) {
				e.preventDefault();
			} )
			.on( 'click', 'div.view-dropdown-list-item', function ( e ) {
				const $item = $( this );

				if ( $item.attr( 'aria-disabled' ) === 'true' ) {
					// Allow clicking on links inside the item.
					if ( 'A' !== e.target.tagName ) {
						e.preventDefault();
					}

					if ( undefined !== $( e.target ).data( 'action' ) ) {
						e.preventDefault();

						const action = $( e.target ).data( 'action' );
						$el.trigger( {
							type: 'gravityview/dropdown/' + action,
							target: e.target,
						}, {
							action,
							dropdown,
							option: $item.data( 'option' ).get( 0 ),
						} );
					}

					e.stopPropagation();
					e.stopImmediatePropagation();

					return;
				}

				$el.val( $( this ).data( 'value' ) );
				$el.trigger( 'change' );
				dropdown.focus();
			} )
			.on( 'keyup', 'div.view-dropdown-list-item', $.proxy( this.handleKeyOption, this ) );

		this.initialized = true;
	};

	/**
	 * Relay method for easy focus on the select box.
	 * @since 2.24
	 */
	ViewDropDown.prototype.focus = function () {
		this.select.focus();
	};

	/**
	 * Focuses on the first option.
	 * @since 2.24
	 */
	ViewDropDown.prototype.focusFirst = function () {
		if ( !this.open ) {
			return;
		}

		this.$options_list.find( '.view-dropdown-list-item:first-child' ).focus();
	};

	/**
	 * Focuses on the last option.
	 * @since 2.24
	 */
	ViewDropDown.prototype.focusLast = function () {
		if ( !this.open ) {
			return;
		}

		this.$options_list.find( '.view-dropdown-list-item:last-child' ).focus();
	};

	/**
	 * Places focus on the previous option.
	 *
	 * Will focus on the last option if the current option is first option.
	 *
	 * @since 2.24
	 */
	ViewDropDown.prototype.focusUp = function () {
		if ( !this.open ) {
			return;
		}

		const $focussed = this.$options_list.find( ':focus' );
		if ( !$focussed.length ) {
			return;
		}

		let $previous = $focussed.prev( '.view-dropdown-list-item' );
		if ( $previous.length === 0 ) {
			$previous = this.$options_list.find( '.view-dropdown-list-item:last-child' );
		}

		// Skip over disabled items.
		while ( $previous.attr( 'aria-disabled' ) === 'true' && $previous !== $focussed ) {
			$previous = $previous.prev( '.view-dropdown-list-item' );
		}

		if ( $previous ) {
			$previous.focus();
		}
	};

	/**
	 * Places focus on the next option.
	 *
	 * Will focus on the first option if the current option is last option.
	 *
	 * @since 2.24
	 */
	ViewDropDown.prototype.focusDown = function () {
		if ( !this.open ) {
			return;
		}

		const $focussed = this.$options_list.find( ':focus' );
		if ( !$focussed.length ) {
			return;
		}

		let $next = $focussed.next( '.view-dropdown-list-item' );

		if ( $next.length === 0 ) {
			$next = this.$options_list.find( '.view-dropdown-list-item:first-child' );
		}

		// Skip over disabled items.
		while ( $next.attr( 'aria-disabled' ) === 'true' && $next !== $focussed ) {
			$next = $next.next( '.view-dropdown-list-item' );
			if ( $next.length === 0 ) {
				$next = this.$options_list.find( '.view-dropdown-list-item:first-child' );
			}
		}

		if ( $next ) {
			$next.focus();
		}
	};

	/**
	 * Handles the key events on the select box itself.
	 *
	 * @since 2.24
	 * @param {KeyboardEvent} e The event.
	 */
	ViewDropDown.prototype.handleKeySelect = function ( e ) {
		e.preventDefault();

		if ( [ 'Enter', ' ', 'ArrowUp', 'ArrowDown' ].indexOf( e.key ) > -1 ) {
			this.toggle();
		}
	};

	/**
	 * Handles the key events on an option.
	 *
	 * @since 2.24
	 * @param {KeyboardEvent} e The event.
	 */
	ViewDropDown.prototype.handleKeyOption = function ( e ) {
		e.preventDefault();
		// Either `Enter` or `Space` selects the current option.
		if ( e.key === 'Enter' || e.key === ' ' ) {
			$( e.target ).trigger( 'click' );
			this.close();
		} else if ( e.key === 'ArrowUp' ) {
			this.focusUp();
		} else if ( e.key === 'ArrowDown' ) {
			this.focusDown();
		} else if ( e.key === 'Tab' ) {
			// If `Shift` is used in combination with `Tab` we focus up, otherwise we focus down.
			if ( true === e.shiftKey ) {
				this.focusUp();
			} else {
				this.focusDown();
			}
		} else if ( e.key === 'Home' ) {
			this.focusFirst();
		} else if ( e.key === 'End' ) {
			this.focusLast();
		}
	};

	/**
	 * Stores the current value on the instance.
	 * @since 2.24
	 */
	ViewDropDown.prototype.storeValue = function () {
		this.$el.data( 'gv-view-value', this.$el.val() );
	};

	/**
	 * Restores a stored value.
	 *
	 * This is used when a confirmation is canceled, and the original value needs to be reset.
	 *
	 * @since 2.24
	 */
	ViewDropDown.prototype.restoreValue = function () {
		this.$el.val( this.$el.data( 'gv-view-value' ) );
		// Don't trigger change event again.
		this.refresh();
	};

	/**
	 * Renders all the options based on the original `select` element.
	 *
	 * Can be called again to sync options if the values on the original `select` are updated.
	 *
	 * @since 2.24
	 */
	ViewDropDown.prototype.renderOptions = function () {
		const $list = this.$options_list;
		let placeholders = [];
		// Clear old values
		$list.html( '' );

		const $dropdown = this.$el;
		this.$el.find( 'option' ).each( function () {
			const $option = $( this );
			if ( '' === $option.val() ) {
				return;
			}

			let icon = '';
			if ( $option.data( 'icon' ) ) {
				icon = '<img src="' + $option.data( 'icon' ) + '" alt="Icon" />';
			}

			const action = $option.data( 'action' );

			const id = 'view-option-' + ( Math.random() + 1 ).toString( 36 ).substring( 2 );
			const $item = $(
				'<div' + ( $option.is( ':disabled' ) ? '' : ' tabindex="0"' ) + ' id="' + id + '" role="option" aria-selected="false" class="view-dropdown-list-item" aria-disabled="' + $option.is( ':disabled' ) + '" data-value="' + $option.val() + '">' +
				'	<div class="view-dropdown-list-item__icon">' + icon + '</div>' +
				'	<div class="view-dropdown-list-item__value">' +
				'		<div class="view-dropdown-list-item__label">' + $option.data( 'title' ) +
				( 'activate' === action ? ' <a role="button" data-action="activate" class="view-dropdown-button--pill" href="#">' + ( $dropdown.data( 'label-activate' ) || 'Activate' ) + '</a>' : "" ) +
				( 'install' === action ? ' <a role="button" data-action="install" class="view-dropdown-button--pill" href="#">' + ( $dropdown.data( 'label-install' ) || 'Install' ) + '</a>' : "" ) +
				'		</div>' +
				'		<div class="view-dropdown-list-item__description">' + $option.data( 'description' ) + '</div>' +
				'	</div>' +
				'</div>'
			);

			$item.data( 'option', $option );

			if ( 'buy' === action ) {
				placeholders.push($item);
			} else {
				$list.append( $item );
			}
		} );

		if (placeholders.length > 0) {
			const $available = $(
				'<div class="view-dropdown-list-available">' +
				'	<div class="view-dropdown-list-available__header">' +
				'		<div class="view-dropdown-list-available__heading">' +
				'			<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 0C7.42695 0 7.79691 0.295826 7.89073 0.712418L8.35918 2.79209C8.67906 4.21205 9.78797 5.32097 11.2079 5.64078L13.2876 6.10923C13.7042 6.20309 14 6.57306 14 7C14 7.42694 13.7042 7.79691 13.2876 7.89077L11.2079 8.35922C9.78797 8.67903 8.67906 9.78795 8.35918 11.2079L7.89073 13.2876C7.79691 13.7042 7.42695 14 7 14C6.57305 14 6.2031 13.7042 6.10927 13.2876L5.64082 11.2079C5.32094 9.78795 4.21203 8.67903 2.79203 8.35922L0.712393 7.89077C0.295887 7.79691 0 7.42694 0 7C0 6.57306 0.295887 6.20309 0.712393 6.10923L2.79203 5.64078C4.21203 5.32097 5.32094 4.21205 5.64082 2.79209L6.10927 0.712418C6.2031 0.295826 6.57305 0 7 0Z" fill="white"/></svg>' +
				'			<span>'+ ( $dropdown.data( 'label-available' ) || 'Available' ) + '</span>' +
				'		</div>' +
				'		<div><a target="_blank" rel="nofollow noopener" class="view-dropdown-list-available__upgrade" href="https://www.gravitykit.com/pricing/?utm_campaign=gk_upsells&utm_source=view-editor&utm_content=upgrade">'+ ( $dropdown.data( 'label-upgrade' ) || 'Upgrade' ) +'</a></div>' +
				'	</div>' +
				'	<div class="view-dropdown-list-available__options"></div> ' +
				'</div>'
			);
			$( placeholders ).each( function ( i, el ) {
				$available.find( '.view-dropdown-list-available__options' ).append( el );
			} );

			$list.append( $available );
		}

		this.refresh();
	};

	/**
	 * Toggles the open / closed state of the dropdown.
	 *
	 * Puts focus on the first option once opened.
	 *
	 * @since 2.24
	 */
	ViewDropDown.prototype.toggle = function () {
		this.open = !this.open;
		this.refresh();

		if ( this.open ) {
			this.focusActive();
		}
	};

	/**
	 * Puts focus on the active item.
	 * @since 2.24
	 */
	ViewDropDown.prototype.focusActive = function () {
		this.$options_list.find( 'div.view-dropdown-list-item--active' ).focus();
	};

	/**
	 * Closes the dropdown.
	 * @since 2.24
	 */
	ViewDropDown.prototype.close = function () {
		this.open = false;
		this.refresh();
	};

	/**
	 * Synchronizes the visual state to the underlying data state.
	 * @since 2.24
	 */
	ViewDropDown.prototype.refresh = function () {
		this.$wrapper.toggleClass( 'view-dropdown--open', this.open );

		var value = this.$el.val();
		var $option = this.$el.find( 'option[value="' + value + '"]' );
		var title = $option[ 0 ]?.innerText || 'Select an option';
		var icon = '';

		if ( $option.data( 'icon' ) ) {
			icon = '<img src="' + $option.data( 'icon' ) + '" alt="Icon" />';
		}

		var dropdown = this;

		if ( value ) {
			title = $option.data( 'title' );
			this.$options_list.find( 'div.view-dropdown-list-item' ).each( function () {
				var active = $( this ).data( 'value' ) === value;
				$( this )
					.toggleClass( 'view-dropdown-list-item--active', $( this ).data( 'value' ) === value )
					.attr( 'aria-selected', active ? 'true' : 'false' );

				if ( active ) {
					dropdown.select.attr( 'aria-activedescendant', $( this ).attr( 'id' ) );
				}
			} );
		}

		this.select.find( '.view-dropdown-select__value__selection' ).html( title );
		this.select.find( '.view-dropdown-select__value__icon' ).html( icon );
	};

	// Add a `viewDropdown` method on any element.
	$.fn.extend( {
		'viewDropdown': function () {
			if ( !$( this ).data( 'view-data' ) ) {
				$( this ).data( 'view-data', new ViewDropDown( this ) );
			}

			return $( this ).data( 'view-data' );
		}
	} );

	// Initialize any `view-dropdown` elements currently on the page.
	$( function () {
		$( 'select[data-view-dropdown]' ).each( function () {
			$( this ).viewDropdown();
		} );
	} );

} )( jQuery );
