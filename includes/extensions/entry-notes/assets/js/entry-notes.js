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
					.attr('checked', lastChecked.checked)
					.trigger('change');
			}

			lastChecked = this;
		});
	};

	var gv_entry_notes = {

		init: function () {
			// Allow for multiple on a page.
			$('.gv-entry-notes').each( function () {

				$( this ).find('input[type="checkbox"]').shiftSelectable();
				$( this).find('.gv-notes-toggle').on('change', gv_entry_notes.toggle_all );

				$('.gv-entry-notes-list').on( 'submit', gv_entry_notes.delete_notes );

				$('.gv-entry-note-add')
					.on( 'submit', gv_entry_notes.add_note )
					.find( 'textarea')
						.on( 'keydown', gv_entry_notes.command_enter );

			});
		},

		/**
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

			// Not Delete
			if( '' === $('#bulk_action').val() ) {
				console.log('Delete was not selected');
				return false;
			}

			$container = $( this ).parents('.gv-entry-notes');
			$notes_form = $container.find('.gv-entry-notes-list');
			$submit = $container.find('.gv-entry-notes-bulk-action input[type=submit]');

			$checked = $( 'input[name="note[]"]:checked', $notes_form );

			// No checked inputs
			if( 0 === $checked.length ) {
				console.log('No notes were checked');
				return false;
			}

			$.ajax({
				url: ajaxurl,
				isLocal: true,
				method: 'POST',
				beforeSend: function () {
					$container.addClass( 'gv-processing-note' );
					$submit.attr( 'data-value', $submit.html() ).prop('disabled', 'disabled').html( GVEntryNotes.text.processing );
				},
				data: {
					action: 'gv_delete_notes',
					data: $( this ).serialize()
				}
			}).done( function( data, textStatus, jqXHR ) {

				$submit.prop('disabled', null ).html( $submit.attr( 'data-value' ) );
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
					});

				} else {
					alert( data.data.error );
				}
			});
		},

		add_note: function ( e ) {
			e.preventDefault();

			$container = $( this ).parents('.gv-entry-notes');
			$notes_form = $container.find('.gv-entry-notes-list');
			$submit = $container.find('.gv-add-note-submit');
			$textarea = $( this ).find( '#gv-entry-note-content' );

			$.ajax({
				url: ajaxurl,
				isLocal: true,
				method: 'POST',
				beforeSend: function (  ) {
					$container.addClass( 'gv-processing-note' );
					$notes_table = $notes_form.find( 'table.entry-detail-notes' );
					$textarea.prop('disabled', 'disabled');
					$submit.attr( 'data-value', $submit.html() ).prop('disabled', 'disabled').html( GVEntryNotes.text.processing );
				},
				data: {
					action: 'gv_add_note',
					data: $( this ).serialize()
				}
			}).done( function( data, textStatus, jqXHR ) {

				$submit.prop('disabled', null ).html( $submit.attr( 'data-value' ) );
				$textarea.prop('disabled', null );
				$container.removeClass( 'gv-processing-note' );

				if ( true === data.success ) {

					$container.removeClass('gv-no-notes').addClass('gv-has-notes');

					$( data.data.html ).hide().appendTo( $notes_table ).fadeIn();
					$textarea.val( '' ); // Clear the existing note comment
				} else {
					alert( data.data.error );
				}
			});

			return false;
		}
	};

	gv_entry_notes.init();

})(jQuery);