<?php
/**
 * @package GravityView
 * @subpackage Gravityview/admin/metaboxes/partials
 * @global $post
 */

global $post;
?>
<style>
	#gv-direct-access:before {
		font: normal 20px/1 dashicons;
		speak: never;
		display: inline-block;
		margin-left: -1px;
		padding-right: 3px;
		vertical-align: top;
		-webkit-font-smoothing: antialiased;
		-moz-osx-font-smoothing: grayscale;
	}
	#gv-direct-access:before {
		content: "\f528";
		color: #dba617;
	}
	#gv-direct-access.embed-only:before {
		content: "\f160";
		color: #00a32a;
	}
</style>
<script>
jQuery( function($) {
	let $directAccessSelect, direct_access_html;

	$directAccessSelect = $( '#gv-direct-access-select' );
	direct_access_html = $('#gv-direct-access-display').html();
	// Show the direct access options and hide the toggle button when opened.
	$( '#gv-direct-access .edit-direct-access' ).on( 'click', function ( e ) {
		e.preventDefault();
		if ( $directAccessSelect.is( ':hidden' ) ) {
			$directAccessSelect.slideDown( 'fast', function () {
				$directAccessSelect.find( 'input[type="radio"]' ).first().trigger( 'focus' );
			} );
			$( this ).hide();
		}
	} );
	// Cancel direct access selection area and hide it from view.
	$directAccessSelect.find( '.cancel-direct-access' ).on( 'click', function ( event ) {
		$directAccessSelect.slideUp( 'fast' );
		$( '#gv-direct-access-display' ).html( direct_access_html );
		$( '#gv-direct-access .edit-direct-access' ).show().trigger( 'focus' );

		event.preventDefault();
	} );

	// Set the selected direct access setting as current.
	$directAccessSelect.find('.save-direct-access').on( 'click', function( event ) {
		let directAccessLabel = '',
			checked = false,
			selectedDirectAccess = $directAccessSelect.find( 'input:radio:checked' ).val();

		$directAccessSelect.slideUp('fast');

		$('#gv-direct-access .edit-direct-access').show().trigger( 'focus' );

		switch ( selectedDirectAccess ) {
			case 'public':
				directAccessLabel = 'Public';
				break;
			case 'embed':
				checked = true;
				directAccessLabel = 'Embed-Only';
				break;
		}

		// Update the _actual_ setting in the Permissions tab.
		$( '#gravityview_se_embed_only' ).prop( 'checked', checked );

		// Update the display label.
		$('#gv-direct-access-display').text( directAccessLabel );

		// Update the class on the container to reflect the current setting.
		$('#gv-direct-access').toggleClass('embed-only', checked );
		event.preventDefault();
	});
});
</script>
<?php
	$embed_only_view_status = gravityview_get_template_setting( $post->ID, 'embed_only' );
?>
<div class='misc-pub-section misc-pub-section <?php echo $embed_only_view_status ? 'embed-only' : ''; ?>' id='gv-direct-access'>
	<?php
		esc_html_e( 'Direct Access:', 'gk-gravityview' );
	?>

	<span id="gv-direct-access-display" style="font-weight: bold">
		<?php
		if ( ! empty( $embed_only_view_status ) ) {
			$direct_access          = 'embed';
			$direct_access_text    = __( 'Embed-Only', 'gk-gravityview' );
		} else {
			$direct_access       = 'public';
			$direct_access_text = __( 'Public', 'gk-gravityview' );
		}

		echo esc_html( $direct_access_text );
		?>
	</span>

	<a href="#gv-direct-access" class="edit-direct-access hide-if-no-js" role="button">
		<span aria-hidden="true"><?php esc_html_e( 'Edit', 'gk-gravityview' ); ?></span>
		<span class="screen-reader-text"><?php
			/* translators: Hidden accessibility text. */
			esc_html_e( 'Edit the Direct Access setting', 'gk-gravityview' ); ?>
		</span>
	</a>

	<div id="gv-direct-access-select" class="hide-if-js">

		<input type="radio" name="direct-access-alias" id="gv-direct-access-radio-public" value="public" <?php checked( $direct_access, 'public' ); ?> />
		<label for="gv-direct-access-radio-public" class="selectit"><?php esc_html_e( 'Publicly Accessible', 'gk-gravityview' ); ?></label>

		<br/>

		<input type="radio" name="direct-access-alias" id="gv-direct-access-radio-embedded-only" value="embed" <?php checked( $direct_access, 'embed' ); ?> />
		<label for="gv-direct-access-radio-embedded-only" class="selectit"><?php esc_html_e( 'Embedded-Only', 'gk-gravityview' ); ?></label>

		<br/>

		<p>
			<a href="#gv-direct-access" class="save-direct-access hide-if-no-js button"><?php _e( 'OK', 'gk-gravityview' ); ?></a>
			<a href="#gv-direct-access" class="cancel-direct-access hide-if-no-js button-cancel"><?php _e( 'Cancel', 'gk-gravityview' ); ?></a>
		</p>
	</div>
</div>
