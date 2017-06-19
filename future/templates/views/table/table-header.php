<?php
/**
 * The header for the output table.
 *
 * @global stdClass $gravityview (\GV\View $gravityview::$view, \GV\View_Template $gravityview::$template)
 */
?>
<?php gravityview_before(); ?>
<div class="<?php gv_container_class( 'gv-table-container' ); ?>">
<table class="gv-table-view">
	<thead>
		<?php gravityview_header(); ?>
		<tr>
			<?php $gravityview->template->the_columns(); ?>
		</tr>
	</thead>
