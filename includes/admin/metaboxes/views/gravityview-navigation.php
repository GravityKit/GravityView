<?php

$metaboxes = GravityView_Metabox_Tabs::get_all();

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