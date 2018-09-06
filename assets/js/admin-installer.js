/* global ajaxurl,jQuery */
/**
 * Custom JS for extensions & plugins page
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2018, Katz Web Services, Inc.
 *
 * @since 2.0.x
 */

( function ( $ ) {

	var adminInstaller = {

		/**
		 * Add click events to activate/deactivate/install buttons
		 */
		init: function () {

			$( '.gv-admin-installer-container' ).on( 'click', 'a.button:not(.disabled)', function ( e ) {

				e.preventDefault();

				var item = $( this ).parent(), status = $( this ).attr( 'data-status' ),
					pluginPath = $( this ).attr( 'data-plugin-path' ),
					installUrl = $( this ).attr( 'href' );

				var performAction = function () {
					$( '.gv-admin-installer-container a.button' ).addClass( 'disabled' );
					$( item ).find( '.spinner' ).show();

					switch ( status ) {
						case 'active':
							return adminInstaller.deactivate( pluginPath, item );
						case 'inactive':
							return adminInstaller.activate( pluginPath, item );
						case 'notinstalled':
							return adminInstaller.install( installUrl );
					}
				};

				$.when( performAction() )
					.always( function () {

						$( item ).find( '.spinner' ).hide();
						$( '.gv-admin-installer-container a.button' ).removeClass( 'disabled' );
					} )
					.fail( function ( error ) {

						$( '.gv-admin-installer-notice' ).show().find( 'p' ).text( error );

						$( 'html, body' ).animate( {
							scrollTop: $( '.wrap' ).offset().top
						}, 1000 );
					} );
			} );

		},

		/**
		 * Activate extension via Ajax POST request
		 *
		 * @param {string} pluginPath WP's plugin path
		 * @param {Object} item DOM element with extension data
		 *
		 * @returns {Promise}
		 */
		activate: function ( pluginPath, item ) {
			var defer = $.Deferred();

			$.post( ajaxurl, {
				'action': 'gravityview_admin_installer_activate',
				'data': { path: pluginPath }
			}, function ( response ) {

				if ( ! response.success ) {
					defer.reject( response.data.error );
				}

				adminInstaller.update_item( item,  'active', gvAdminInstaller.activeStatusLabel, gvAdminInstaller.deactivateActionLabel );

				defer.resolve();
			} ).fail( function () {

				defer.reject( gvAdminInstaller.activateErrorLabel );
			} );

			return defer.promise();
		},

		/**
		 * Deactivate extension via Ajax POST request
		 *
		 * @param {string} pluginPath WP's plugin path
		 * @param {Object} item DOM element with extension data
		 *
		 * @returns {Promise}
		 */
		deactivate: function ( pluginPath, item ) {
			var defer = $.Deferred();

			$.post( ajaxurl, {
				'action': 'gravityview_admin_installer_deactivate',
				'data': { path: pluginPath }
			}, function ( response ) {
				if ( !response.success ) {
					defer.reject( response.data.error );
				}

				adminInstaller.update_item( item,  'inactive', gvAdminInstaller.inactiveStatusLabel, gvAdminInstaller.activateActionLabel );

				defer.resolve();
			} ).fail( function () {

				defer.reject( gvAdminInstaller.deactivateErrorLabel );
			} );

			return defer.promise();
		},

		/**
		 * Updates item with new status labels, text, and button action labels
		 *
		 * @param {Object} item DOM element with extension data
		 * @param {string} status Status key ("active", "inactive" or "notinstalled")
		 * @param {string} status_label The label shown for the extension status
		 * @param {string} button_text The text of the extension button
		 */
		update_item: function ( item, status, status_label, button_text ) {
			$( item ).find( 'div.status' ).removeClass( 'active inactive notinstalled' ).addClass( status ).find( '.status-label' ).text( status_label );
			$( item ).find( 'a.button' ).attr( 'data-status', status );
			$( item ).find( 'a.button span.title' ).text( button_text );
		},

		/**
		 * Install extension via redirect installation page
		 *
		 * @returns {Promise}
		 */
		install: function ( installUrl ) {
			var defer = $.Deferred();

			window.location.href = installUrl;

			return defer.promise();
		}
	};

	adminInstaller.init();

}( jQuery ) );
