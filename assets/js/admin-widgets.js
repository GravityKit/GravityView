/**
 * Javascript functions for GravityView WordPress widgets
 *
 * @package GravityView
 */
jQuery( document).ready(function( $ ) {

	gvWidgets = {

		/**
		 * Register the jQuery triggers
		 */
		init: function () {

			$( document ).on( 'widget-added widget-updated widget-toggled', gvWidgets.modified ).on( 'change refresh', '#widgets-right .gv-recent-entries-select-view', gvWidgets.refreshMergeTags );

			$( document.body ).on( 'click.widgets-toggle', gvWidgets.toggle );

		},

		/**
		 * When a widget is toggled, trigger `widget-toggled` on it
		 */
		toggle: function ( e ) {
			var target = $( e.target );
			if ( target.parents( '.widget-top' ).length && !target.parents( '#available-widgets' ).length ) {

				var widget = target.closest( 'div.widget' );

				// The slideDown function is set to "fast" which is 100ms.
				// This will only work after the slideDown is completed.
				window.setTimeout( function () {
					widget.trigger( 'widget-toggled', widget );
				}, 110 );
			}
		},

		/**
		 * When a widget is added or updated, trigger merge tags loading
		 */
		modified: function ( e, widget ) {

			// Recent Entries widget
			if ( $( widget ).has( '.gv-recent-entries-select-view' ) ) {
				$( widget ).find( '.gv-recent-entries-select-view' ).trigger( 'refresh' );
			}
		},

		/**
		 * Refresh the merge tags for the widget
		 * @param e
		 */
		refreshMergeTags: function ( e ) {
			var view_id = $( this ).val();

			// No View defined
			if ( view_id.length === 0 ) {

				// No View? No merge tags make sense.
				$( '.all-merge-tags' ).remove();

				return;
			}

			var data = {
				action: 'gv_get_view_merge_tag_data',
				view_id: view_id,
				nonce: GVWidgets.nonce
			};

			/**
			 * @see GravityView_Recent_Entries_Widget::ajax_get_view_merge_tag_data() in widgets.php
			 */
			$.post( ajaxurl, data, function ( response ) {
				if ( response ) {

					var parsed = $.parseJSON( response );

					// Set the merge tags for this form
					gf_vars.mergeTags = parsed.mergeTags;

					// Update the form with the new settings to be passed to gfMergeTagsObj()
					window.form = new Form();
					window.form.id = parsed.form.id;
					window.form.fields = parsed.form.fields;
					window.form.title = parsed.form.title;

					// Remove existing merge tags, since otherwise GF will add another
					$( '.all-merge-tags' ).remove();

					// Only init merge tags if the View has been saved and the form hasn't been changed.
					if ( typeof( form ) !== 'undefined' && $( 'body' ).not( '.gv-form-changed' ) ) {

						// Re-init merge tag dropdowns
						window.gfMergeTags = new gfMergeTagsObj( form );

					}

				}
			} );
		} // End refreshMergeTags

	}; // End gvWidgets

	gvWidgets.init();

});