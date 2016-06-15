/**
 * Custom js script at post edit screen
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 *
 * globals jQuery, gvGlobals
 */


jQuery( document ).ready( function( $ ) {

	/**
	 * Set the defaultValue property for select inputs, since they're not set by the DOM. This allows us to check whether they've been changed in insertViewShortcode()
	 * @return {string} Value
	 */
	$( '#select_gravityview_view_form' ).find( 'select' ).prop( 'defaultValue', function () {
		return $( this ).val();
	} );

	/**
	 * Generate the shortcode to insert, and reset the form to default state.
	 */
	function insertViewShortcode() {

		if ( $( "#gravityview_id" ).val() === '' ) {
			alert( gvGlobals.alert_1 );
			$( "#gravityview_view_id" ).focus();
			return false;
		}

		var shortcode = '[gravityview';

		/**
		 * Run through each input and generate shortcode attributes based on their `name`s.
		 */
		$( "#select_gravityview_view_form").find(":input:enabled" ).each( function () {

			var setting_value = '';

			// CHECKBOX or RADIO
			// Checkboxes and Radio inputs have their own `defaultChecked` property
			// so we process them separately
			if ( $( this ).is( ':checkbox' ) || $( this ).is( ':radio' ) ) {

				// If it's not checked and it's not checked by default, don't add the attribute
				if ( (
				     true === $( this ).is( ':checked' )
				     ) && (
				     true === $( this ).prop( 'defaultChecked' )
				     ) ) {
					return;
				}

				// If it's not checked and it's not checked by default, don't add the attribute
				if ( (
				     false === $( this ).is( ':checked' )
				     ) && (
				     false === $( this ).prop( 'defaultChecked' )
				     ) ) {
					return;
				}

				// 1 = checked; 0 = not checked
				setting_value = $( this ).is( ':checked' ) ? '1' : '0';

				// Reset to default
				$( this ).prop( 'checked', $( this ).prop( 'defaultChecked' ) );

			}
			// NOT A CHECKBOX
			// Other inputs have the `defaultValue` DOM property (or they've been set by this script)
			// so we process them next.
			else {

				// It's a drop-down and the value is empty - likely the "Sort by Field" select
				if ( $( this ).is( 'select' ) && $( this ).val() === '' ) {
					return;
				}

				// If the value is the default value, don't add attribute
				if ( $( this ).val() === $( this ).prop( 'defaultValue' ) ) {

					return;

				} else {

					// Get the value
					setting_value = $( this ).val();

					// Reset to default
					$( this ).val( $( this ).prop( 'defaultValue' ) );

				}

			}

			// The shortcode attribute is the input name, without `gravityview_` in front
			var setting_attr = $( this ).prop( 'name' ).replace( /^gravityview_/, '' );

			// Add to the output
			shortcode += ' ' + setting_attr + '="' + setting_value + '"';

		} );

		// Close the shortcode tag
		shortcode += ']';

		window.send_to_editor( shortcode );

		return false;
	}

	//datepicker
	$( '.gv-datepicker' ).datepicker( {
		dateFormat: "yy-mm-dd",

		// Allow users to type in values like "-1 year" or "now"
		constrainInput: false
	} );


	// Select view id -> populate sort fields
	$( "#gravityview_id" ).change( function () {

		var hide_if_js = $( '#select_gravityview_view_form' ).find( '.hide-if-js' );

		if ( $( "#gravityview_id" ).val() === '' ) {
			hide_if_js.fadeOut();
			return;
		}

		// While it's loading, disable the field, remove previous options, and add loading message.
		$( "#gravityview_sort_field" ).prop( 'disabled', 'disabled' ).empty().append( '<option>' + gvGlobals.loading_text + '</option>' );

		var data = {
			action: 'gv_sortable_fields',
			viewid: $( this ).val(),
			nonce: gvGlobals.nonce
		};

		$.post( ajaxurl, data, function ( response ) {
			if ( response ) {
				$( "#gravityview_sort_field" ).empty().append( response ).prop( 'disabled', null );
			}
		} );

		hide_if_js.fadeIn();
	} );

	/**
	 * When showing Thickbox, the full width isn't triggered until closed and re-opened. This fixes that.
	 * Thanks, GF shortcode-ui.js for guidance
	 */
	$( 'body' ).on( 'click', '#add_gravityview', function ( e ) {
		e.preventDefault();

		tb_show( $( this ).attr('title'), $( this ).attr('href'), '' );

		$( '#TB_ajaxContent' ).css( 'padding-bottom', '8px' ); // Fix overflow in Firefox
	});

	// capture form submit -> add shortcode to editor
	$( '#insert_gravityview_view' ).on( 'click', function ( e ) {
		e.preventDefault();
		insertViewShortcode();
		$( '#select_gravityview_view_form' ).find( '.hide-if-js' ).hide();
		return false;
	} );

});
