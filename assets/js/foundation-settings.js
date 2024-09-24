/**
 * Javascript for GravityViews Foundation settings.
 *
 * @since $ver$
 */
document.addEventListener( 'DOMContentLoaded', function () {
	// Update all preview element when the corresponding input is changed.
	document.querySelectorAll( '[data-slug-preview]' ).forEach( ( element ) => {
		const default_value = element.dataset.slugDefault ?? 'unknown';

		const input = element
			.closest( '.setting' )
			.querySelector( 'input[name="' + element.dataset.slugPreview + '"]' );

		const update = () => {
			element.innerHTML = ( input.value || default_value ).replace( '{entry_id}', '123' );
		};

		input.addEventListener( 'input', update );
		// Initial trigger.
		update();
	} );
} );
