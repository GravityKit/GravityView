/**
 * Renders the view dropdown.
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2024, Katz Web Services, Inc.
 *
 * @since $ver$
 *
 * globals jQuery
 */

( function ( $ ) {
	'use strict';

	/**
	 * Creates a new instance of `ViewDropDown`
	 * @since $ver$
	 * @param {Element} el The original `select` element.
	 * @constructor
	 */
	var ViewDropDown = function ( el ) {
		this.initialized = false;
		this.$el = $( el );
		this.open = false;

		this.init();
		this.renderOptions();
		this.refresh();
	};

	/**
	 * Initializes the dropdown (once).
	 *
	 * It replaces the original `select` with a nicely styled `combobox`, and hooks up the required events.
	 *
	 * @since $ver$
	 */
	ViewDropDown.prototype.init = function () {
		if ( this.initialized ) {
			return;
		}

		var $el = this.$el;
		var dropdown = this;

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
			'	<a target="_blank" href="#">Learn more about view types &amp; layouts</a>' +
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
				if ( $( this ).attr( 'aria-disabled' ) === 'true' ) {
					// allow clicking on links inside the item.)
					if ( 'A' !== e.target.tagName ) {
						e.preventDefault();
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
	 * @since $ver$
	 */
	ViewDropDown.prototype.focus = function () {
		this.select.focus();
	};

	/**
	 * Focuses on the first option.
	 * @since $ver$
	 */
	ViewDropDown.prototype.focusFirst = function () {
		if ( !this.open ) {
			return;
		}

		this.$options_list.find( '.view-dropdown-list-item:first-child' ).focus();
	};

	/**
	 * Focuses on the last option.
	 * @since $ver$
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
	 * @since $ver$
	 */
	ViewDropDown.prototype.focusUp = function () {
		if ( !this.open ) {
			return;
		}

		var $focussed = this.$options_list.find( ':focus' );
		if ( !$focussed.length ) {
			return;
		}

		var $previous = $focussed.prev( '.view-dropdown-list-item' );
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
	 * @since $ver$
	 */
	ViewDropDown.prototype.focusDown = function () {
		if ( !this.open ) {
			return;
		}

		var $focussed = this.$options_list.find( ':focus' );
		if ( !$focussed.length ) {
			return;
		}

		var $next = $focussed.next( '.view-dropdown-list-item' );

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
	 * @since $ver$
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
	 * @since $ver$
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
			true === e.shiftKey ? this.focusUp() : this.focusDown();
		} else if ( e.key === 'Home' ) {
			this.focusFirst();
		} else if ( e.key === 'End' ) {
			this.focusLast();
		}
	};

	/**
	 * Stores the current value on the instance.
	 * @since $ver$
	 */
	ViewDropDown.prototype.storeValue = function () {
		this.$el.data( 'gv-view-value', this.$el.val() );
	};

	/**
	 * Restores a stored value.
	 *
	 * This is used when a confirmation is canceled, and the original value needs to be reset.
	 *
	 * @since $ver$
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
	 * @since $ver$
	 */
	ViewDropDown.prototype.renderOptions = function () {
		var $list = this.$options_list;
		// Clear old values
		$list.html( '' );

		this.$el.find( 'option' ).each( function () {
			var $option = $( this );
			if ( '' === $option.val() ) {
				return;
			}

			var icon = '';
			if ( $option.data( 'icon' ) ) {
				icon = '<img src="' + $option.data( 'icon' ) + '" alt="Icon" />';
			}

			var license = $option.data( 'license' );
			var buy_source = $option.data( 'buy-source' );

			var id = 'view-option-' + ( Math.random() + 1 ).toString( 36 ).substring( 2 );
			var $item = $(
				'<div tabindex="0" id="' + id + '" role="option" aria-selected="false" class="view-dropdown-list-item" aria-disabled="' + $option.is( ':disabled' ) + '" data-value="' + $option.val() + '">' +
				'	<div class="view-dropdown-list-item__icon">' + icon + '</div>' +
				( license
						? ( '<div class="view-dropdown-list-item__license hidden">' +
							'	<svg width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg">' +
							'   	<path d="M4 0C4.24397 0 4.45537 0.169044 4.50899 0.407096L4.77668 1.59548C4.95946 2.40689 5.59313 3.04056 6.40452 3.2233L7.5929 3.49099C7.83096 3.54463 8 3.75603 8 4C8 4.24397 7.83096 4.45537 7.5929 4.50901L6.40452 4.7767C5.59313 4.95944 4.95946 5.59311 4.77668 6.40452L4.50899 7.59291C4.45537 7.83096 4.24397 8 4 8C3.75603 8 3.54463 7.83096 3.49101 7.59291L3.22333 6.40452C3.04054 5.59311 2.40687 4.95944 1.59544 4.7767L0.407082 4.50901C0.169078 4.45537 0 4.24397 0 4C0 3.75603 0.169078 3.54463 0.407082 3.49099L1.59544 3.2233C2.40687 3.04056 3.04054 2.40689 3.22333 1.59548L3.49101 0.407096C3.54463 0.169044 3.75603 0 4 0Z" fill="currentColor"/>' +
							'	</svg>' +
							'	<span>' + license + '</span>' +
							'</div>'
						)
						: ''
				) +
				'	<div class="view-dropdown-list-item__value">' +
				'		<div class="view-dropdown-list-item__label">' + $option.data( 'title' ) + '</div>' +
				'		<div class="view-dropdown-list-item__description">' + $option.data( 'description' ) + '</div>' +
				'	</div>' +
				( buy_source
						? (
							'<div class="view-dropdown-list-item__buy-source hidden">' +
							'	<a href="' + buy_source + '" target="_blank">Upgrade</a>' +
							'</div>'
						)
						: ''
				) +
				'</div>' );

			if ( license ) {
				$item.addClass( 'view-dropdown-list-item--license' );
			}

			$item.data( 'option', $option );

			$list.append( $item );
		} );
	};

	/**
	 * Toggles the open / closed state of the dropdown.
	 *
	 * Puts focus on the first option once opened.
	 *
	 * @since $ver$
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
	 * @since $ver$
	 */
	ViewDropDown.prototype.focusActive = function () {
		this.$options_list.find( 'div.view-dropdown-list-item--active' ).focus()
	}

	/**
	 * Closes the dropdown.
	 * @since $ver$
	 */
	ViewDropDown.prototype.close = function () {
		this.open = false;
		this.refresh();
	};

	/**
	 * Synchronizes the visual state to the underlying data state.
	 * @since $ver$
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
			if ( $( this ).data( 'view-dropdown' ) ) {
				return;
			}

			$( this ).data( 'view-data', new ViewDropDown( this ) );
		}
	} );

	// Initialize any `view-dropdown` elements currently on the page.
	$( function () {
		$( 'select[data-view-dropdown]' ).each( function () {
			$( this ).viewDropdown();
		} );
	} );

} )( jQuery );
