<?php
/**
 * @package GravityView
 * @subpackage Gravityview/admin/metaboxes/partials
 * @global $post
 */

global $post;

$embed_only_view_status = gravityview_get_template_setting( $post->ID, 'embed_only' );

/**
 * This is a hack to get the tooltip to display without having to generate the whole anchor text.
 * The generative function should be refactored to allow for access, but it's currently trapped in an abstract method.
 *
 * @see GravityView_FieldType::tooltip()
 *
 * @return string Tooltip HTML.
 */
function gv_get_direct_access_tooltip() {

	add_filter(
		'gform_tooltips',
		function ( $tooltips ) {

			$tooltip  = '<h6>' . esc_html__( 'Direct Access', 'gk-gravityview' ) . '</h6>';
			$tooltip .= wpautop( esc_html__( 'Publicly Accessible: If Visibility is set to Publicly Accessible, anyone with the link can access the View, including search engines and logged-out users.', 'gk-gravityview' ) );
			$tooltip .= wpautop( esc_html__( 'Embedded-Only: The View can only be seen when embedded in other content (such as a Page); it cannot be accessed directly.', 'gk-gravityview' ) );

			$tooltips['direct_access_metabox'] = $tooltip;

			return $tooltips;
		},
		500
	);

	include_once GRAVITYVIEW_DIR . 'includes/admin/field-types/type_text.php';
	$field   = new GravityView_FieldType_text( '', array(), '' );
	$tooltip = $field->tooltip(
		'direct_access_metabox',
		'',
		true,
		array(
			'id'   => '5590376ce4b027e1978eb8d0',
			'type' => 'modal',
		)
	);

	return $tooltip;
}

if ( ! empty( $embed_only_view_status ) ) {
	$direct_access      = 'embed';
	$direct_access_text = __( 'Embed-Only', 'gk-gravityview' );
} else {
	$direct_access      = 'public';
	$direct_access_text = __( 'Public', 'gk-gravityview' );
}

?>
<div class='misc-pub-section misc-pub-section <?php echo $embed_only_view_status ? 'embed-only' : ''; ?>' id='gv-direct-access'>
	<?php
		esc_html_e( 'Direct Access:', 'gk-gravityview' );
	?>

	<span id="gv-direct-access-display">
		<strong data-initial-label="<?php echo esc_attr( $direct_access_text ); ?>"><?php echo esc_html( $direct_access_text ); ?></strong>

		<?php
			echo gv_get_direct_access_tooltip();
		?>
	</span>

	<a href="#gv-direct-access" class="edit-direct-access hide-if-no-js" role="button">
		<span aria-hidden="true"><?php esc_html_e( 'Edit', 'gk-gravityview' ); ?></span>
		<span class="screen-reader-text">
		<?php
			/* translators: Hidden accessibility text. */
			esc_html_e( 'Edit the Direct Access setting', 'gk-gravityview' );
		?>
		</span>
	</a>

	<div id="gv-direct-access-select" class="hide-if-js">

		<input type="radio" name="direct-access-alias" id="gv-direct-access-radio-public" value="public" <?php checked( $direct_access, 'public' ); ?> data-display-label="<?php esc_attr_e( 'Public', 'gk-gravityview' ); ?>" />
		<label for="gv-direct-access-radio-public" class="selectit"><?php esc_html_e( 'Publicly Accessible', 'gk-gravityview' ); ?></label>

		<br/>

		<input type="radio" name="direct-access-alias" id="gv-direct-access-radio-embed" value="embed" <?php checked( $direct_access, 'embed' ); ?> data-display-label="<?php esc_attr_e( 'Embed-Only', 'gk-gravityview' ); ?>" />
		<label for="gv-direct-access-radio-embed" class="selectit"><?php esc_html_e( 'Embedded-Only', 'gk-gravityview' ); ?></label>

		<br/>

		<p>
			<a href="#gv-direct-access" class="save-direct-access hide-if-no-js button"><?php _e( 'OK', 'gk-gravityview' ); ?></a>
			<a href="#gv-direct-access" class="cancel-direct-access hide-if-no-js button-cancel"><?php _e( 'Cancel', 'gk-gravityview' ); ?></a>
		</p>
	</div>
</div>
