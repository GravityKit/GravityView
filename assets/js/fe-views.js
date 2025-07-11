/**
 * Custom js script loaded on Views frontend
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 *
 * globals jQuery
 */

jQuery( function ( $ ) {
	var gvFront = {
		init: function () {
			this.datepicker();

			$( '.gv-widget-search' ).each( function () {
				$( this ).attr( 'data-state', $( this ).serialize() );
			} );

			$( '.gv-widget-search' ).on( 'keyup, change', this.form_changed );

			// Logic for the "search entries" field
			$( '.gv-widget-search .gv-search-field-search_all input[type=search]' ).on( 'search', function ( e ) {
				$( e.target ).parents( 'form' ).trigger( 'keyup' );
			} );

			$( '.gv-search-clear' ).on( 'click', this.clear_search );

			$( 'a.gv-sort' ).on( 'click', this.multiclick_sort );

			$( document ).on( 'gform_post_render', this.disable_upload_file_when_limit_reached.bind( this ) );

			$( '#gv-search-advanced-toggle' ).on( 'click', this.toggle_advanced_search );

			this.disable_upload_file_when_limit_reached();

			this.fix_updating_files_after_edit();

			this.number_range();

			this.iframe();

			this.enable_multi_page_entry_edit();
		},

		/**
		 * Multi-page forms can have multiple submit buttons (Previous/Next/Update), and the backend
		 * relies on the button's name and value to determine which page to display next.
		 *
		 * In 2.9.0.1, Gravity Forms refactored the submission process to use
		 * jQuery(form).trigger('submit'), which does not include the clicked button's value.
		 *
		 * To preserve expected behavior, we inject a hidden input with the clicked button’s
		 * name and value before submission.
		 *
		 * @since 2.38.0
		 */
		enable_multi_page_entry_edit: function() {
			$( document ).on( 'gform_post_render', function( event, formId, currentPage ) {
				const $form = $( `#gform_${ formId }` );

				$form.find( 'input[type="submit"][name]' ).each( function() {
					$( this ).on( 'click', function() {
						$form.append( $( '<input>', {
							type: 'hidden',
							name: this.name,
							value: this.value,
						} ) );
					} );
				} );
			} );
		},

		/**
		 * Fix the issue of updating files after edit where the previous value still exists in the uploaded field.
		 */
		fix_updating_files_after_edit: function () {
			if ( window.gform ) {
				// Prevent enabling the single upload input when the field is conditional but already has values.
				gform.addAction( 'gform_post_conditional_logic_field_action', ( form_id, action, target_id ) => {
					if ( action !== 'show' ) {
						return;
					}
					if ( !$( target_id ).is( '.gfield--type-fileupload' ) ) {
						return;
					}

					const input_name = 'input_' + target_id.split( '_' ).pop();
					const existing_files_id = input_name.replace( 'input_', '#preview_existing_files_' );

					// No files, so we don't need to disable the input.
					if ( $( existing_files_id ).children().length === 0 ) {
						return;
					}

					// This might be a single file uploader. Disable that input since we have files.
					const $input = $( target_id ).find( 'input[name=' + input_name + ']' );
					$input.attr( 'disabled', $input[ 0 ].type === 'file' ? 'disabled' : false );
				} );
			}

			$( document ).on( 'gform_post_render', () => {
				$( '.ginput_preview_list' ).each( function () {

					setTimeout( () => {
						if ( $( this ).children().length > 0 ) {
							return;
						}

						const uploader_id = $( this ).attr( 'id' ).replace( 'gform_preview_', 'gform_multifile_upload_' );
						const input_name = 'input_' + uploader_id.split( '_' ).pop();

						const uploader = window?.gfMultiFileUploader?.uploaders[ uploader_id ] || null;
						if ( !uploader ) {
							// This might be a single file uploader. Disable that input since we have files.
							const $input = $( this ).closest( '.gfield' ).find( 'input[name=' + input_name + ']' );
							$input.attr( 'disabled', $input.type === 'file' ? 'disabled' : '' );
							return;
						}

						const $fields_input = $( this ).closest( 'form' ).find( '[name=gform_uploaded_files]' );
						const all_files = JSON.parse( $fields_input.val() || '{}' );
						const input_files = all_files[ input_name ] || [];
						delete all_files[ input_name ];
						$fields_input.val( JSON.stringify( all_files ) ); // Clear out as they will be added through the Uploader.

						// Fake the Uploader files.
						const files = ( input_files ).map( file => {
							file.name = file.uploaded_filename || 'unknown';
							file.id = ( file.temp_filename || '' ).split( '_o_' ).pop().split( '.' ).shift();
							file.status = plupload.DONE;
							file.percent = 100;
							file.gv_is_existing = true;

							return new plupload.File( file );
						} );

						for ( const file of files ) {
							uploader.addFile( file );
						}

					}, 100 );
				} );
			} );
		},

		/**
		 * Sets up event bindings for Gravity Forms multi-file uploaders to enforce limits.
		 * Checks if the necessary GF objects exist before proceeding.
		 * @since 2.30
		 * @since 2.37.1 (Refactored)
		 */
		disable_upload_file_when_limit_reached: function() {
			// Ensure the GF uploader object is available
			if ( typeof gfMultiFileUploader === 'undefined' || typeof gfMultiFileUploader.uploaders === 'undefined' ) {
				return; // Exit if GF object isn't ready
			}

			$.each( gfMultiFileUploader.uploaders, function( index, uploader ) {
				uploader.bind( 'Init', function( up, params ) {
					var data = up.settings;
					var max = parseInt( data.gf_vars.max_files, 10 );
					if ( max === 0 ) {
						return;
					}
					var fieldId = data.multipart_params.field_id;
					var existingFilesCount = $( '#preview_existing_files_' + fieldId ).children().length;
					var limitReached = existingFilesCount >= max;
					gfMultiFileUploader.toggleDisabled( data, limitReached );
				} );

				uploader.bind( 'FilesAdded', function( up, files ) {
					var data = up.settings;
					var max = parseInt( data.gf_vars.max_files, 10 );
					if ( max === 0 ) {
						return;
					}
					var fieldId = data.multipart_params.field_id;
					var formId = data.multipart_params.form_id;
					var newFilesCount = $( '#gform_preview_' + formId + '_' + fieldId ).children().length;
					var existingFilesCount = $( '#preview_existing_files_' + fieldId ).children().length;
					var limitReached = existingFilesCount + newFilesCount >= max;

					$.each( files, function( i, file ) {
						if ( max > 0 && existingFilesCount >= max ) {
							up.removeFile( file );
							$( '#' + file.id ).remove();
							return;
						}

						existingFilesCount++;
					} );

					gfMultiFileUploader.toggleDisabled( data, limitReached );


					// Only show message if max is greater than 1 or limit is reached
					if ( max <= 1 || !limitReached ) {
						return true;
					}


					// Check if message already exists
					if ( $( "#" + up.settings.gf_vars.message_id ).children().length > 0 ) {
						return true;
					}

					$( "#" + up.settings.gf_vars.message_id ).prepend( "<li class='gfield_description gfield_validation_message'>" +
						$('<div/>').text(gform_gravityforms.strings.max_reached).html()
						+
							"</li>" );

					// Announce errors.
					setTimeout( function () {
						wp.a11y.speak( $( "#" + up.settings.gf_vars.message_id ).text() );
					}, 1000 );

				} );

			} );
		},

		/**
		 * Triggered when the search form changes
		 * - Adds 'data-form-changed' attribute to <form> wrapper
		 * - Fades in the Clear button and changes the text to "Reset"
		 *
		 * @param e jQuery Event
		 */
		form_changed: function ( e ) {
			var $form = $( e.target ).hasClass( 'gv-widget-search' ) ? $( e.target ) : $( e.target ).parents( 'form' );

			if ( $form.serialize() === $form.attr( 'data-state' ) ) {
				if ( $form.hasClass( 'gv-is-search' ) ) {
					$( '.gv-search-clear', $( this ) ).text( gvGlobals.clear );
				} else {
					$( '.gv-search-clear', $( this ) ).fadeOut( 100 );
				}
			} else {
				$( '.gv-search-clear', $( this ) ).text( gvGlobals.reset ).fadeIn( 100 );
			}
		},

		/**
		 * - If the form has been changed, the Clear button becomes Reset and reverts the state to form on load
		 * - If the form has not been changed:
		 *        - If there is no existing search result, hide the button
		 *        - If there is a search result, refresh page without $_GET parameters
		 *
		 * @param e jQuery Event
		 * @returns {boolean}
		 */
		clear_search: function ( e ) {
			var $form = $( this ).parents( 'form' );
			var changed = ( $form.attr( 'data-state' ) !== $form.serialize() );

			// Handle an existing search
			if ( $form.hasClass( 'gv-is-search' ) && !changed ) {
				// If there are no changes, submit the form
				return true;
			}

			// If the form has been changed, just reset the data
			if ( changed ) {
				e.preventDefault();

				$form.trigger( 'reset' );

				// If there's now no form field text, hide the reset button
				if ( false === $form.hasClass( 'gv-is-search' ) ) {
					$( '.gv-search-clear', $form ).hide( 100 );
				} else {
					$( '.gv-search-clear', $form ).text( gvGlobals.clear ); // Update the text of the button
				}

				return false;
			}

			return true;
		},

		/**
		 * Generate the datepicker for GV date fields
		 */
		datepicker: function () {
			// If datepicker is loaded
			if ( jQuery.fn.datepicker ) {
				$( '.gv-datepicker' ).each( function () {
					var element = jQuery( this );
					var image = "";
					var showOn = "focus";

					if ( element.hasClass( "datepicker_with_icon" ) ) {
						showOn = "both";
						image = jQuery( '#gforms_calendar_icon_' + this.id ).val();
					}

					gvGlobals.datepicker.showOn = showOn;
					gvGlobals.datepicker.buttonImage = image;
					gvGlobals.datepicker.buttonImageOnly = true;

					// Process custom date formats
					if ( !gvGlobals.datepicker.dateFormat ) {
						var format = "mm/dd/yy";

						if ( element.hasClass( "mdy" ) )
							format = "mm/dd/yy"; else if ( element.hasClass( "dmy" ) )
							format = "dd/mm/yy"; else if ( element.hasClass( "dmy_dash" ) )
							format = "dd-mm-yy"; else if ( element.hasClass( "dmy_dot" ) )
							format = "dd.mm.yy"; else if ( element.hasClass( "ymd_slash" ) )
							format = "yy/mm/dd"; else if ( element.hasClass( "ymd_dash" ) )
							format = "yy-mm-dd"; else if ( element.hasClass( "ymd_dot" ) )
							format = "yy.mm.dd";

						gvGlobals.datepicker.dateFormat = format;
					}

					element.datepicker( gvGlobals.datepicker );
				} );

			}
		},

		/**
		 * When Shift-clicking sorting icons, use multi-sort URL instead of default
		 * @since 2.3
		 */
		multiclick_sort: function ( e ) {
			if ( e.shiftKey ) {
				e.preventDefault();
				location.href = $( this ).data( 'multisort-href' );
			}
		},

		/**
		 * Client side logic to prevent invalid search values.
		 * @since 2.22
		 */
		number_range() {
			$( '.gv-search-number-range' )
				.on( 'change', 'input', function () {
					const $name = $( this ).attr( 'name' );
					const current_type = $name.includes( 'max' ) ? 'max' : 'min';
					const other_type = 'max' === current_type ? 'min' : 'max';
					const $other = $( this )
						.closest( '.gv-search-number-range' )
						.find( 'input[name="' + $name.replace( /(min|max)/, other_type ) + '"]' );

					// Push to end of the stack to avoid timing issues.
					setTimeout( function () {
						if ( $( this ).attr( other_type ) && '' !== $( this ).val() ) {
							const value = parseFloat( $( this ).val() );

							if ( 'max' === current_type	&& value < parseFloat( $( this ).attr( 'min' ) ) ) {
								$( this ).val( $( this ).attr( 'min' ) );
							} else if (	'min' === current_type && value > parseFloat( $( this ).attr( 'max' ) )	) {
								$( this ).val( $( this ).attr( 'max' ) );
							}
						}

						$other.attr( current_type, $( this ).val() );
					}.bind( this ), 2 );
				} )
				.find( 'input' ).trigger( 'change' ); // Initial trigger.
		},

		/**
		 * Listen for messages from the iframe and perform various actions.
		 *
		 * @since 2.29.0
		 */
		iframe: function () {
			window.addEventListener( 'message', function ( event ) {
				if ( event.data?.removeHash ) {
					history.replaceState( null, null, ' ' );
				}

				if ( event.data?.closeFancybox && window.Fancybox ) {
					history.replaceState( null, null, ' ' );

					Fancybox.close();
				}

				if ( event.data?.reloadPage ) {
					location.reload();
					return;
				}

				if ( event.data?.redirectToUrl ) {
					window.location = event.data.redirectToUrl;
				}
			} );
		},

		toggle_advanced_search: function () {
			$( this ).attr( 'aria-expanded', ( _i, val ) => 'true' === val ? 'false' : 'true' );
			$( '#gv-search-advanced' ).toggleClass( 'gv-search-advanced--open', 'true' === $( this ).attr( 'aria-expanded' ) );
		}
	};

	gvFront.init();
} );
