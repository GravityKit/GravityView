<?php
/**
 * Display a single entry when using a table template
 *
 * @global array $gravityview
 */
?>
<?php gravityview_before(); ?>

<p class="gv-back-link"><?php echo gravityview_back_link(); ?></p>

<div class="gv-table-view gv-container gv-table-single-container">
	<table class="gv-table-view-content">
		<thead>
			<?php gravityview_header(); ?>
		</thead>
		<tbody>
			<?php
				$gravityview->template->the_entry();
			?>
		</tbody>
		<tfoot>
			<?php gravityview_footer(); ?>
		</tfoot>
	</table>
</div>
<?php gravityview_after(); ?>
