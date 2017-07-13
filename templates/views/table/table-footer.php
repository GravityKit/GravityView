<?php
/**
 * The footer for the output table.
 *
 * @global stdClass $gravityview (\GV\View $gravityview::$view, \GV\View_Template $gravityview::$template)
 */
?>
	<tfoot>
		<tr>
			<?php $gravityview->template->the_columns(); ?>
		</tr>
		<?php gravityview_footer(); ?>
	</tfoot>
</table>
</div><!-- end .gv-table-container -->
<?php gravityview_after( $gravityview ); ?>
