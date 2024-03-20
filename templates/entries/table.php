<?php
/**
 * Display a single entry when using a table template
 *
 * @global \GV\Template_Context $gravityview
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

\GV\Mocks\Legacy_Context::push( array( 'view' => $gravityview->view ) );

gravityview_before( $gravityview );

?>
<?php
if ( $link = gravityview_back_link( $gravityview ) ) {
	?>
	<p class="gv-back-link"><?php echo $link; ?></p><?php } ?>

<div class="<?php gv_container_class( 'gv-table-view gv-table-container gv-table-single-container', true, $gravityview ); ?>">
	<table class="gv-table-view-content">
		<?php if ( $gravityview->fields->by_position( 'single_table-columns' )->by_visible( $gravityview->view )->count() ) : ?>
			<thead>
				<?php gravityview_header( $gravityview ); ?>
			</thead>
			<tbody>
				<?php
					$gravityview->template->the_entry();
				?>
			</tbody>
			<tfoot>
				<?php gravityview_footer( $gravityview ); ?>
			</tfoot>
		<?php endif; ?>
	</table>
</div>
<?php

gravityview_after( $gravityview );

\GV\Mocks\Legacy_Context::pop();
