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
			
			this.disable_upload_file_when_limit_reached();

			this.fix_updating_files_after_edit();

			this.number_range();
		},

		/**
		 * Fix the issue of updating files after edit where the previous value still exists in the uploaded field.
		 */
		fix_updating_files_after_edit: function(){
			$.each($('.ginput_preview_list'), function(index, element){
				if ($(element).children().length > 0) {
					return true;
				}

				$(element).parents('form').find('[name=gform_uploaded_files]').val('');
			});
		},

		/**
		 * Fix the issue for file upload fields where the button is not disabled and show message for multi upload file.
		 */
		disable_upload_file_when_limit_reached: function(){

			var checkUploaders = setInterval(function() {
				if (typeof gfMultiFileUploader !== 'undefined' && gfMultiFileUploader.uploaders) {
					clearInterval(checkUploaders);
					$.each(gfMultiFileUploader.uploaders, function(index, uploader){
						uploader.bind('Init', function(up, params) {
							var data = up.settings;
							var max = data.gf_vars.max_files;
							var fieldId = data.multipart_params.field_id;
							var existingFilesCount = $('#preview_existing_files_'+fieldId).children().length;
							var limitReached = existingFilesCount >= max;
							gfMultiFileUploader.toggleDisabled(data, limitReached);
						});

						uploader.bind('FilesAdded', function(up, params) {
							var data = up.settings;
							var max = data.gf_vars.max_files;
							var fieldId = data.multipart_params.field_id;
							var formId = data.multipart_params.form_id;
							var newFilesCount = $('#gform_preview_'+formId+'_'+fieldId).children().length;
							var existingFilesCount = $('#preview_existing_files_'+fieldId).children().length;
							var limitReached = existingFilesCount + newFilesCount >= max;
							gfMultiFileUploader.toggleDisabled(data, limitReached);

							// Only show message if max is greater than 1
							if(max <= 1){
								return true;
							}

							// Check if message already exists
							if($("#" + up.settings.gf_vars.message_id).length > 0){
								return true;
							}

							$( "#" + up.settings.gf_vars.message_id ).prepend( "<li class='gfield_description gfield_validation_message'>" +
								$('<div/>').text(gform_gravityforms.strings.max_reached).html()
							 +
								 "</li>" );
							// Announce errors.
							setTimeout(function () {
								wp.a11y.speak( $( "#" + up.settings.gf_vars.message_id ).text() );
							}, 1000 );

						});

					});
				}
			}, 1);
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
		}
	};

	gvFront.init();
} );
