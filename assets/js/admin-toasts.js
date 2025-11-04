/**
 * GravityView Toast Notification System
 *
 * Foundation-inspired toast notifications for user feedback.
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      https://www.gravitykit.com
 * @copyright Copyright 2024, Katz Web Services, Inc.
 *
 * @since 2.43
 */

/* global jQuery */

(function( $ ) {
	'use strict';

	/**
	 * Toast notification constants
	 *
	 * @since 2.43
	 */
	var DEFAULTS = {
		/**
		 * Default toast display duration in milliseconds.
		 * 7.5 seconds provides enough time to read a typical message without being intrusive.
		 * Based on average reading speed of 200-250 words per minute.
		 */
		DURATION: 7500,

		/**
		 * CSS transition duration for fade-out animation in milliseconds.
		 * 300ms matches the CSS transition timing in admin-toasts.scss for smooth animations.
		 */
		FADE_DURATION: 300,

		/**
		 * Initial progress bar width percentage.
		 * Progress bar animates from 0% to 100% over the toast duration.
		 */
		PROGRESS_START: '0%',

		/**
		 * SVG icon dimensions in pixels.
		 * 20x20px provides clear visibility without overwhelming the toast message.
		 */
		ICON_SIZE: 20,

		/**
		 * SVG viewBox dimensions.
		 * Standard 24x24 viewBox provides optimal scaling for the 20x20px display size.
		 */
		VIEWBOX_SIZE: 24
	};

	/**
	 * Toast notification system
	 *
	 * @since 2.43
	 */
	window.GVToast = {
		container: null,

		/**
		 * Initialize toast container
		 *
		 * @since 2.43
		 */
		init: function() {
			if ( this.container ) {
				return;
			}

			this.container = $( '<div class="gv-toast-container"></div>' );
			$( 'body' ).append( this.container );
		},

		/**
		 * Show a toast notification
		 *
		 * @since 2.43
		 *
		 * @param {string} message - The message to display
		 * @param {string} type - The type of toast (success, error, warning)
		 * @param {number} duration - How long to show the toast in milliseconds (default: 7500)
		 */
		show: function( message, type, duration ) {
			this.init();

			duration = duration || DEFAULTS.DURATION;

			var iconMap = {
			success: '<svg xmlns="http://www.w3.org/2000/svg" width="' + DEFAULTS.ICON_SIZE + '" height="' + DEFAULTS.ICON_SIZE + '" viewBox="0 0 ' + DEFAULTS.VIEWBOX_SIZE + ' ' + DEFAULTS.VIEWBOX_SIZE + '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
				error: '<svg xmlns="http://www.w3.org/2000/svg" width="' + DEFAULTS.ICON_SIZE + '" height="' + DEFAULTS.ICON_SIZE + '" viewBox="0 0 ' + DEFAULTS.VIEWBOX_SIZE + ' ' + DEFAULTS.VIEWBOX_SIZE + '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
				warning: '<svg xmlns="http://www.w3.org/2000/svg" width="' + DEFAULTS.ICON_SIZE + '" height="' + DEFAULTS.ICON_SIZE + '" viewBox="0 0 ' + DEFAULTS.VIEWBOX_SIZE + ' ' + DEFAULTS.VIEWBOX_SIZE + '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>'
			};

			var $toast = $(
				'<div class="gv-toast gv-toast-' + type + '">' +
					'<div class="gv-toast-content">' +
						'<span class="gv-toast-icon">' + iconMap[type] + '</span>' +
						'<span class="gv-toast-message">' + message + '</span>' +
					'</div>' +
					'<div class="gv-toast-progress"></div>' +
				'</div>'
			);

			this.container.append( $toast );

			// Trigger reflow to enable CSS transition
			$toast[0].offsetHeight;

			// Show toast
			$toast.addClass( 'gv-toast-show' );

			// Set progress bar animation
			var $progress = $toast.find( '.gv-toast-progress' );
			$progress.css( {
				'width': DEFAULTS.PROGRESS_START,
				'transition-duration': duration + 'ms'
			} );

			// Auto-hide after duration
			setTimeout( function() {
				$toast.removeClass( 'gv-toast-show' );
				setTimeout( function() {
					$toast.remove();
				}, DEFAULTS.FADE_DURATION );
			}, duration );
		},

		/**
		 * Show success toast
		 *
		 * @since 2.43
		 *
		 * @param {string} message - The message to display
		 * @param {number} duration - How long to show the toast in milliseconds
		 */
		success: function( message, duration ) {
			this.show( message, 'success', duration );
		},

		/**
		 * Show error toast
		 *
		 * @since 2.43
		 *
		 * @param {string} message - The message to display
		 * @param {number} duration - How long to show the toast in milliseconds
		 */
		error: function( message, duration ) {
			this.show( message, 'error', duration );
		},

		/**
		 * Show warning toast
		 *
		 * @since 2.43
		 *
		 * @param {string} message - The message to display
		 * @param {number} duration - How long to show the toast in milliseconds
		 */
		warning: function( message, duration ) {
			this.show( message, 'warning', duration );
		}
	};

})( jQuery );
