/**
 * Javascript for Entry Notes
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      https://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.17
 *
 * globals jQuery, GVEntryNotes
 */

(function($){

	/**
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
	};

	var gv_entry_notes = {

		init: function () {
			// Allow for multiple on a page.
			$('.gv-notes').each( function () {

				gv_entry_notes.setup_checkboxes( $( this ) );

				$('.gv-notes-toggle', $( this ) ).on('change', gv_entry_notes.toggle_all );

				$('.gv-notes-list', $( this ) ).on( 'submit', gv_entry_notes.delete_notes );
				
				$('.gv-note-email-to', $( this ) ).on('change', gv_entry_notes.email_fields_toggle ).trigger('change');

				$('.gv-note-add', $( this ) )
					.on( 'submit', gv_entry_notes.add_note )
					.find( 'textarea')
						.on( 'keydown', gv_entry_notes.command_enter );

			});
		},

		/**
		 * Add actions for the checkboxes
		 *
		 * @param $container .gv-notes container DOM
		 */
		setup_checkboxes: function( $container ) {

			$( 'input[name="note[]"]', $container )
				.on( 'change', gv_entry_notes.toggle_disable_delete ) // Disable delete button if no checked boxes
				.shiftSelectable() // Enable shift-click
				.filter(':first-child').trigger('change'); // Trigger disable delete on load
		},

		/**
		 * Disable the delete button if there are no checked boxes
		 */
		toggle_disable_delete: function() {
			$container = $( this ).parents('.gv-notes');
			$checkboxes = $( 'input[name="note[]"]', $container );
			$( '.gv-notes-delete', $container ).prop( 'disabled', ( 0 === $checkboxes.filter(':checked').length ) );
		},
		 * Allow Command+Enter to submit new notes. Yummy!
		 *
		 * @see https://davidwalsh.name/command-enter-submit-forms
		 *
		 * @param {jQueryEvent} e
		 */
		command_enter: function( e ) {
			if(e.keyCode == 13 && e.metaKey) {
				$('.gv-entry-note-add').submit();
			}
		},

		toggle_all: function( e ) {
			$container = $( this ).parents('.gv-entry-notes');
			$checkboxes = $( 'input[name="note[]"]', $container );
			$checkboxes.prop("checked", $( this ).prop('checked') );
		},

		delete_notes: function ( e ) {
			e.preventDefault();

			var $container = $( e.target ).parent('.gv-entry-notes');
			var $checked = $( 'input[name="note[]"]:checked', $container );

			// No checked inputs
			if( 0 === $checked.length ) {
				console.log('No notes were checked');
				return false;
			}
			
			if( ! window.confirm( GVEntryNotes.text.delete_confirm ) ) {
				console.log('Just kidding. Please do not delete me!');
				return false;
			}

			var $submit = $container.find('.gv-entry-notes-delete button[type=submit]');

			$.ajax({
				url: GVEntryNotes.ajaxurl,
				isLocal: true,
				method: 'POST',
				beforeSend: function () {
					$container.addClass( 'gv-processing-note' );
					$submit.data( 'value', $submit.html() ).prop( 'disabled', true ).html( GVEntryNotes.text.processing );
				},
				data: {
					action: 'gv_delete_notes',
					data: $( this ).serialize()
				}
			}).done( function( data, textStatus, jqXHR ) {

				$submit.prop('disabled', false ).html( $submit.data( 'value' ) );
				$container.removeClass( 'gv-processing-note' );

				if ( true === data.success ) {

					$checked.parents('tr.gv-entry-note').addClass('gv-entry-note-deleted').animate( {
						"height": "0",
						"opacity": "0"
					}, 'slow', function () {

						$( this ).remove();

						if( 0 === $( 'tr.gv-entry-note', $container ).length ) {
							$container.removeClass('gv-has-notes').addClass('gv-no-notes');
						}

						// After a bulk action is performed, uncheck the "Check all" box
						$container.find( '.gv-notes-toggle' ).prop( 'checked', null );

						gv_entry_notes.setup_checkboxes( $container );
					});

				} else {
					alert( data.data.error );
				}
			});
		},

		add_note: function ( e ) {
			e.preventDefault();

			var $container = $( e.target ).parent('.gv-entry-notes');
			var $submit = $container.find('.gv-add-note-submit');
			var $textarea = $container.find( 'textarea[name=note-content]' );

			if( '' === $textarea.val().trim() )  {
				return;
			}

			$.ajax({
				url: GVEntryNotes.ajaxurl,
				isLocal: true,
				method: 'POST',
				beforeSend: function (  ) {
					$container.addClass( 'gv-processing-note' );
					$textarea.prop('disabled', 'disabled');
					$submit.data( 'value', $submit.html() ).prop( 'disabled', true ).html( GVEntryNotes.text.processing );
				},
				data: {
					action: 'gv_note_add',
					data: $( this ).serialize()
				}
			}).done( function( data, textStatus, jqXHR ) {

				$submit.prop('disabled', false ).html( $submit.data( 'value' ) );
				$textarea.prop('disabled', false );
				$container.removeClass( 'gv-processing-note' );

				if ( true === data.success ) {
					$container.removeClass('gv-no-notes').addClass('gv-has-notes');
					$( data.data.html ).hide().appendTo( $( 'table tbody', $container ) ).fadeIn();
					$textarea.val( '' ); // Clear the existing note comment
					gv_entry_notes.setup_checkboxes( $container );
				} else {
					alert( data.data.error );
				}
			});

			return false;
		}
	};

	gv_entry_notes.init();

})(jQuery);