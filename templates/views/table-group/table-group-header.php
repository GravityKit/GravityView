<?php
/**
 * The header for the output table.
 *
 * @global \GV\Template_Context $gravityview
 */
?>
	<thead>
		<?php gravityview_header( $gravityview ); ?>
		<?php $gravityview->template->the_group(); ?>
		<tr>
			<?php $gravityview->template->the_columns(); ?>
		</tr>
	</thead>
