<?php
/**
 * @package GravityView
 * @subpackage Gravityview/admin/metaboxes/views
 * @global $post
 */
global $post;

// View template settings
$current_settings = gravityview_get_template_settings( $post->ID );

?>

<table class="form-table striped">
	<tr style="vertical-align: top;">
		<td colspan="2">
			<p><strong class="is-label"><?php esc_html_e( 'Available Placeholders', 'gk-gravityview' ); ?></strong></p>
			<?php echo wpautop( strtr(
				esc_html__( 'You may use these placeholders to write CSS and JavaScript that only affects this View: [PLACEHOLDERS_LIST]', 'gk-gravityview' ),
				[
					'[PLACEHOLDERS_LIST]' => sprintf(
						'<ul class="ul-disc"><li><code>VIEW_SELECTOR</code> — %1$s</li><li><code>VIEW_ID</code> — %2$s</li><li><code>GF_FORM_ID</code> — %3$s</li></ul>',
						sprintf(
							/* translators: %s is an example CSS selector like .gv-container.gv-container-123 */
							esc_html__( 'A CSS selector targeting this View (e.g., %s)', 'gk-gravityview' ),
							'<code>' . ( get_the_ID() ? sprintf( '.gv-container.gv-container-%d', get_the_ID() ) : '.gv-container.gv-container-123' ) . '</code>'
						),
						esc_html__( 'The View ID number', 'gk-gravityview' ),
						esc_html__( 'The connected form ID', 'gk-gravityview' )
					),
				]
			) ); ?>
			<p><?php
				printf(
					/* translators: %1$s and %2$s are code examples showing placeholder replacement */
					esc_html__( 'Example: %1$s becomes %2$s', 'gk-gravityview' ),
					'<code>VIEW_SELECTOR h3 { }</code>',
					'<code>' . ( get_the_ID() ? sprintf( '.gv-container.gv-container-%d', get_the_ID() ) : '.gv-container.gv-container-123' ) . ' h3 { }</code>'
				);
			?></p>
			<p><a href="https://docs.gravitykit.com/article/246-adding-custom-css-to-your-website#:~:text=Available%20placeholders" rel="external"><?php
				esc_html_e( 'Read more about the placeholders.', 'gk-gravityview' );
			?><span class="screen-reader-text"> <?php esc_html_e( 'This link opens in a new window.', 'gk-gravityview' ); ?></span></a></p>
		</td>
	</tr>
<?php

	/**
	 * @since 1.15.2
	 */
	GravityView_Render_Settings::render_setting_row( 'custom_css', $current_settings );

	/**
	 * @since  2.5
	 */
	GravityView_Render_Settings::render_setting_row( 'custom_javascript', $current_settings );
?>
</table>
