<?php
/**
 * The header for the output table.
 *
 * @global \GV\Template_Context $gravityview
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

?>
<?php gravityview_before( $gravityview ); ?>
<div class="<?php gv_container_class( 'gv-table-view gv-table-container gv-table-multiple-container', true, $gravityview ); ?>">
<table class="gv-table-view">
	<thead>
		<?php gravityview_header( $gravityview ); ?>
		<tr>
			<?php $gravityview->template->the_columns(); ?>
		</tr>
	</thead>
