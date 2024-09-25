<?php
/**
 * Template that represents a placeholder.
 *
 * @since 2.26
 *
 * @param string   $type         The placeholder type.
 * @param string   $icon         The plugin icon.
 * @param string   $title        The plugin title.
 * @param string   $description  The plugin title.
 * @param string   $caps         The required caps.
 * @param string   $button_href  The button href.
 * @param string   $button_text  The button href.
 * @param string   $buy_now_link The buy now link.
 * @param string[] $attributes   The button attributes.
 *
 * @subpackage Gravityview/admin/metaboxes/views
 *
 * @package    GravityView
 */

$user_can = current_user_can( $caps );
?>
<div
	class="gk-gravityview-placeholder-container gk-gravityview-placeholder-container--<?php echo esc_attr( $type ); ?>">
	<div class="gk-gravityview-placeholder-content">
		<div class="gk-gravityview-placeholder-icon"><?php echo $icon; ?></div>
		<div class="gk-gravityview-placeholder-body">
			<div class="gk-gravityview-placeholder-summary">
				<h3><?php echo esc_html( $title ); ?></h3>
				<div class="howto">
					<p><?php echo esc_html( $description ); ?></p>
					<?php if ( $user_can ) {
						printf(
							'<p><a href="%s" rel="external noopener noreferrer" target="_blank">%s<span class="screen-reader-text">%s</span></span></a></p>',
							esc_url( $buy_now_link ),
							// translators: %s is the plugin title.
							esc_html( sprintf( __( 'Learn more about %s…', 'gk-gravityview' ), $title ) ),
							esc_html__( 'This link opens in a new window.', 'gk-gravityview' )
						);
					} ?>
				</div>
			</div>
			<div class="gk-gravityview-placeholder-actions">
				<?php

				// Only show the button if the user has ability to take action with the plugin.
				if ( $user_can && 'read' !== $caps ) {
					$attributes = array_map( static function ( string $key, string $value ): string {
						return sprintf( '%s="%s"', esc_html( $key ), esc_attr( $value ) );
					}, array_keys( $attributes ), array_values( $attributes ) );

					printf(
						'<a href="%1$s" %3$s class="gk-gravityview-placeholder-button button button-primary button-hero">%2$s</a>',
						esc_url( $button_href ),
						esc_html( $button_text ),
						implode( ' ', $attributes )
					);
				} else {
					printf(
						'<a href="%s" class="gk-gravityview-placeholder-button button button-primary button-hero" rel="external noopener noreferrer" target="_blank">%s<span class="screen-reader-text">%s</span></span></a>',
						esc_url( $buy_now_link ),
						esc_html( $button_text ),
						esc_html__( 'This link opens in a new window.', 'gk-gravityview' )
					);
				}
				?>
			</div>
		</div>
	</div>
</div>
