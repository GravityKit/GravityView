/**
 * Javascript for Entry Notes
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      https://gravityview.co
 * @copyright Copyright 2016, Katz Web Services, Inc.
 *
 * @since 1.17
 *
 * globals jQuery, GVNotes
 */

(function($){

	/**
	 * Handle adding and deleting notes, showing related messages
	 */
	var gv_notes = {

		/**
		 * The CSS selectors used in this object
		 * @since 1.17
		 */
		selectors: {
			// The wrapper of the note display as well as the Add Note form
			wrapper: '.gv-notes',

			// The form that wraps all editable notes
			bulk_form: '.gv-notes-list',

			// The checkbox that toggles all note checkboxes
			bulk_toggle: '.gv-notes-toggle',

			// The checkbox for each note
			bulk_checkbox: 'input[name="note[]"]',

			// The selector to submit the bulk edit form
			bulk_submit: '.gv-notes-delete',

			// Form containing all Add Note fields, including email fields
			add_note_form: 'form.gv-note-add',

			// The button to submit the Add Note form
			add_note_submit: '.gv-add-note-submit',

			// The content of the note
			add_note_content: 'textarea[name=gv-note-content]',

			// The wrapper for all the email fields
			email_wrapper: '.gv-note-email-container',

			// Wrapper for the Custom Email and Email Subject fields
			email_to_wrapper: '.gv-note-to-container',

			// The select input drop-down to choose "Also email to"
			email_select: '.gv-note-email-to',

			// Wrapper for custom Email To input
			email_to_custom_wrapper: '.gv-note-to-custom-container'
		},
		
		/**
		 * Add all the jQuery actions and hooks
		 * @since 1.17
		 */
		init: function () {
			// Allow for multiple on a page.
			$( gv_notes.selectors.wrapper ).each( function () {

				gv_notes.setup_checkboxes( $( this ) );

				$(gv_notes.selectors.bulk_toggle, $( this ) ).on('change', gv_notes.toggle_all );

				$( gv_notes.selectors.bulk_form, $( this ) ).on( 'submit', gv_notes.delete_notes );
				
				$( gv_notes.selectors.email_select, $( this ) ).on('change', gv_notes.email_fields_toggle ).trigger('change');

				$( gv_notes.selectors.add_note_form, $( this ) )
					.on( 'submit', gv_notes.add_note )
					.find( 'textarea')
						.on( 'keydown', gv_notes.command_enter );

			});
		},

		/**
		 * Add actions for the checkboxes
		 *
		 * @since 1.17
		 *
		 * @param $container .gv-notes container DOM
		 */
		setup_checkboxes: function( $container ) {

			$( gv_notes.selectors.bulk_checkbox, $container )
				.on( 'change', gv_notes.toggle_disable_delete ) // Disable delete button if no checked boxes
				.shiftSelectable() // Enable shift-click
				.filter(':first-child').trigger('change'); // Trigger disable delete on load
		},

		/**
		 * Disable the delete button if there are no checked boxes
		 *
		 * @since 1.17
		 */
		toggle_disable_delete: function() {
			$container = $( this ).parents( gv_notes.selectors.wrapper );
			$checkboxes = $( gv_notes.selectors.bulk_checkbox, $container );
			$( gv_notes.selectors.bulk_submit, $container ).prop( 'disabled', ( 0 === $checkboxes.filter(':checked').length ) );
		},

		/**
		 * Show or hide email fields based on the dropdown
		 *
		 * @since 1.17
		 */
		email_fields_toggle: function( e ) {

			var val = $( this ).val();
			var $email_container = $( e.target ).parents( gv_notes.selectors.wrapper ).find( gv_notes.selectors.email_wrapper );

			$( gv_notes.selectors.email_to_wrapper , $email_container ).toggle( '' !== val );

			$( gv_notes.selectors.email_to_custom_wrapper, $email_container ).toggle( 'custom' === val );
		},

		/**
		 * Allow Command+Enter to submit new notes. Yummy!
		 *
		 * @see https://davidwalsh.name/command-enter-submit-forms
		 * 
		 * @since 1.17
		 *
		 * @param {jQueryEvent} e
		 */
		command_enter: function( e ) {
			if( e.keyCode == 13 && e.metaKey ) {
				$( e.currentTarget ).parents('form.gv-note-add').submit();
			}
		},

		/**
		 * Toggle all checkboxes based on the value of this checkbox
		 *
		 * @since 1.17
		 * 
		 * @param e
		 */
		toggle_all: function( e ) {
			$container = $( this ).parents( gv_notes.selectors.wrapper );
			$checkboxes = $( gv_notes.selectors.bulk_checkbox, $container );
			$checkboxes.prop("checked", $( this ).prop('checked') ).trigger('change');
		},

		/**
		 * Process deleting notes when the Bulk Actions form is submitted
		 * 
		 * @since 1.17
		 * 
		 * @param e
		 * @returns {boolean}
		 */
		delete_notes: function ( e ) {
			e.preventDefault();

			var $container = $( e.target ).parent( gv_notes.selectors.wrapper );
			var $checked = $( gv_notes.selectors.bulk_checkbox, $container ).filter(':checked');

			// No checked inputs
			if( 0 === $checked.length ) {
				console.log('No notes were checked');
				return false;
			}

			if( ! window.confirm( GVNotes.text.delete_confirm ) ) {
				console.log('Just kidding. Please do not delete me!');
				return false;
			}

			var $submit = $container.find( gv_notes.selectors.bulk_submit );

			$.ajax({
				url: GVNotes.ajaxurl,
				isLocal: true,
				method: 'POST',
				beforeSend: function () {
					$container.addClass( 'gv-processing-note' );
					$submit.data( 'value', $submit.html() ).prop( 'disabled', true ).html( GVNotes.text.processing );
				},
				data: {
					action: 'gv_delete_notes',
					data: $( this ).serialize()
				}
			}).done( function( data, textStatus, jqXHR ) {

				// Restore the original text of the button
				$submit.prop('disabled', false ).html( $submit.data( 'value' ) );

				// Remove loading container
				$container.removeClass( 'gv-processing-note' );

				if ( true === data.success ) {

					$checked.parents('tr.gv-note').addClass('gv-note-deleted').animate( {
						"height": "0",
						"opacity": "0"
					}, 'slow', function () {

						$( this ).remove();

						if( 0 === $( 'tr.gv-note', $container ).length ) {
							$container.removeClass('gv-has-notes').addClass('gv-no-notes');
						}

						// After a bulk action is performed, uncheck the "Check all" box
						$container.find( gv_notes.selectors.bulk_toggle ).prop( 'checked', false );

						gv_notes.setup_checkboxes( $container );
					});

				} else {
					gv_notes.show_message( $submit, data.data.error );
				}
			});
		},

		/**
		 * Display a message after performing an action.
		 *
		 * @since 1.17
		 *
		 * @param {jQuery} $insert_after DOM element to insert message after. Default: form button
		 * @param {string} message Message to display. Supports HTML.
		 * @param {bool} is_error Is the message an error message? Default: true.
		 * @returns {void}
		 */
		show_message: function ( $insert_after, message, is_error ) {
			var css_class  = 'gv-note-message';
			var message_class = ( false === is_error ) ? 'gv-note-success' : 'gv-note-error';

			// Create the message container, or if it exists, update it.
			$message = $insert_after.next( '.' + css_class ).length ? $insert_after.next( '.' + css_class ) : $( '<div/>', { class: css_class } );

			$message
				.insertAfter( $insert_after )
				.attr( 'class', css_class + ' ' + message_class )
				.html( message )
				.fadeIn( 'fast' )
				.delay( 3000 )
				.fadeOut( 'fast' )
				.on( 'click', function ( ) {
					$( this ).fadeOut( 'fast' );
				});
		},

		/**
		 * Add a note using AJAX submission
		 *
		 * @since 1.17
		 * 
		 * @param e
		 * @returns {boolean}
		 */
		add_note: function ( e ) {
			e.preventDefault();

			var $container = $( e.target ).parent( gv_notes.selectors.wrapper );
			var $submit = $container.find( gv_notes.selectors.add_note_submit );
			var $inputs = $container.find( ':input' ).not('[type=hidden]');

			if( '' === $container.find( gv_notes.selectors.add_note_content ).val().trim() )  {
				gv_notes.show_message( $submit, GVNotes.text.error_empty_note );
				return;
			}
			
			$.ajax({
				url: GVNotes.ajaxurl,
				isLocal: true,
				method: 'POST',
				beforeSend: function (  ) {
					$container.addClass( 'gv-processing-note' );
					$inputs.prop('disabled', 'disabled');
					$submit.data( 'value', $submit.html() ).html( GVNotes.text.processing );
				},
				data: {
					action: 'gv_note_add',
					data: $( this ).serialize()
				}
			}).done( function( data, textStatus, jqXHR ) {

				$submit.html( $submit.data( 'value' ) );
				$inputs.prop('disabled', false );
				$container.removeClass( 'gv-processing-note' );

				if ( true === data.success ) {
					$container.removeClass('gv-no-notes').addClass('gv-has-notes');
					$( data.data.html ).hide().appendTo( $( 'table tbody', $container ) ).fadeIn();
					gv_notes.setup_checkboxes( $container );
					$inputs.val( '' ).trigger('change'); // Clear the existing note comment, show/hide fields
					gv_notes.show_message( $submit, GVNotes.text.note_added, false );
				} else {
					gv_notes.show_message( $submit, data.data.error );
				}
			});

			return false;
		}
	};

	/**
	 * Enable shift-select
	 * @since 1.17
	 * @see https://gist.github.com/DelvarWorld/3784055
	 */
	$.fn.shiftSelectable = function() {
		var lastChecked,
			$boxes = this;

		$boxes.click(function(evt) {
			if(!lastChecked) {
				lastChecked = this;
				return;
			}

			if(evt.shiftKey) {
				var start = $boxes.index(this),
					end = $boxes.index(lastChecked);
				$boxes.slice(Math.min(start, end), Math.max(start, end) + 1)
					.prop('checked', lastChecked.checked)
					.trigger('change');
			}

			lastChecked = this;
		});

		return this;
	};

	// Initialize after shiftSelectable
	gv_notes.init();

})(jQuery);