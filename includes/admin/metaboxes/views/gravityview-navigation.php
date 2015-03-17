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
<ul>
	<?php

	foreach( $metaboxes as $metabox ) {

		$class = !isset( $class ) ? 'nav-tab-active' : '';
	?>
	<li>
		<a class="nav-tab <?php echo $class; ?>" href="#<?php echo esc_attr( $metabox->id ); ?>">
			<span class="<?php echo $metabox->icon_class_name; ?>"></span>&nbsp;
			<?php echo esc_html( $metabox->title ); ?>
		</a>
	</li>
	<?php
	}
	?>
</ul>