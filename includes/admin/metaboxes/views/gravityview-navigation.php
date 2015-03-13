<?php

$metaboxes = GravityView_Metaboxes::get_all();

?>
<script>
	jQuery(document).ready(function($) {
		$( "#gravityview_settings" )
			.tabs().addClass( "ui-tabs-vertical ui-helper-clearfix" )
			.find('li').removeClass( "ui-corner-top" ).addClass( "ui-corner-left" );
	});
</script>
<style>

</style>
<ul>
	<?php
	foreach( $metaboxes as $metabox ) {

		$class = !isset( $class ) ? 'nav-tab-active' : '';
	?>
	<li><a class="nav-tab <?php echo $class; ?>" href="#<?php echo esc_attr( $metabox->id ); ?>" style="width: 100%; display:block;"><span class="<?php echo $metabox->icon_class_name; ?>"></span>&nbsp;<?php echo esc_html( $metabox->title ); ?></a></li>
	<?php
	}
	?>
</ul>