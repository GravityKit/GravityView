<?php
/**
 * The footer for the output table.
 *
 * @global \GV\Template_Context $gravityview
 */
?>
	<tfoot>
		<tr>
			<?php $gravityview->template->the_columns(); ?>
		</tr>
		<?php gravityview_footer( $gravityview ); ?>
	</tfoot>
</table>
</div><!-- end .gv-table-container -->
<?php gravityview_after( $gravityview ); ?>
