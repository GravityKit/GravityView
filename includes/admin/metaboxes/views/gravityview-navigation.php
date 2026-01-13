<?php
/**
 * Display the tab navigation for the Settings metabox
 *
 * @package GravityView
 * @subpackage Gravityview/admin/metaboxes/views
 * @since 1.8
 *
 * @global GravityView_Metabox_Tab[] $metaboxes
 * @global WP_Post $post
 */

?>
<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
	<?php

	foreach ( $metaboxes as $metabox ) {
		$class = ! isset( $class ) ? 'nav-tab-active' : '';
		if ( $metabox->extra_nav_class ) {
			$class .= ' ' . gravityview_sanitize_html_class( (string) $metabox->extra_nav_class );
		}
		?>
	<li class="ui-state-default">
		<a class="nav-tab ui-tabs-anchor <?php echo $class; ?>" href="#<?php echo esc_attr( $metabox->id ); ?>">
			<span class="<?php echo $metabox->icon_class_name; ?>"></span>&nbsp;
			<?php
			/**
			 * Modifies the metabox title for the navigation tab.
			 *
			 * @since 2.26
			 *
			 * @param string                  $title   The metabox title.
			 * @param GravityView_Metabox_Tab $metabox The metabox object.
			 */
			$title = apply_filters( 'gk/gravityview/metaboxes/navigation/title', esc_html( $metabox->title ), $metabox );

			echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped - Intent is to allow HTML.
			?>
		</a>
	</li>
		<?php
	}
	?>
</ul>
