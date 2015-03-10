<?php


$metaboxes = GravityView_Metaboxes::get_all();

?>

<div id="gravityview-metabox-content-container" style="width: 70%; float: right;">
<?php
foreach( $metaboxes as $metabox ) {
?>
	<div id="<?php echo esc_attr( $metabox->id ); ?>">
<?php
	$metabox->render();
?>
	</div>
<?php
}
?>
</div>