/**
 * SelectWoo initialization for multiselect fields in the View editor.
 *
 * @since 2.35
 */
( function( $ ) {
	'use strict';

	/**
	 * Initialize SelectWoo on multiselect elements with the .gv-selectwoo class.
	 */
	function initSelectWoo() {
		$( '.gv-selectwoo' ).each( function() {
			var $select = $( this );

			// Skip if already initialized.
			if ( $select.hasClass( 'select2-hidden-accessible' ) ) {
				return;
			}

			$select.selectWoo( {
				containerCssClass: 'gv-selectwoo-container',
				dropdownCssClass: 'gv-multiselect-dropdown',
				width: '100%',
				placeholder: $select.data( 'placeholder' ) || '',
				allowClear: true,
			} );
		} );
	}

	// Add ARIA labels on dropdown open for accessibility.
	$( document ).on( 'select2:open', '.gv-selectwoo', function() {
		var $select = $( this );
		var labelText = $( 'label[for="' + $select.attr( 'id' ) + '"] .gv-label' ).text() || '';

		$( '.gv-multiselect-dropdown input.select2-search__field' ).attr( {
			'aria-label': labelText + ' - ' + GVMultiselect.language.search_placeholder,
			'placeholder': GVMultiselect.language.search_placeholder
		} );
	} );

	// Initialize on document ready.
	$( document ).ready( initSelectWoo );

	// Re-initialize when field settings dialogs open or fields are added.
	$( document ).on( 'gravityview/field-settings/opened gravityview/field-added', function() {
		setTimeout( initSelectWoo, 100 );
	} );

}( jQuery ) );
