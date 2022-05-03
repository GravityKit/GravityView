<?php
/**
 * Display below the entries loop when using a table layout.
 *
 *
 * @global GravityView_View $this
 */
?>
	<tfoot>
		<tr>
			<?php

            $this->renderZone('columns', [
                'markup'     => '<th id="{{ field_id }}" class="{{class}}">{{label}}</th>',
                'hide_empty' => false, // Always show <th>
            ]);

            ?>
		</tr>
		<?php gravityview_footer(); ?>
	</tfoot>
</table>
</div><!-- end .gv-table-container -->
<?php gravityview_after(); ?>
