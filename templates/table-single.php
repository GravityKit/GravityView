<?php
/**
 * Display a single entry when using a table template
 *
 * @package GravityView
 * @subpackage GravityView/templates
 *
 * @global GravityView_View $this
 */
?>
<?php gravityview_before(); ?>

<p class="gv-back-link"><?php echo gravityview_back_link(); ?></p>

<div class="gv-table-view gv-container gv-table-single-container">
	<table class="gv-table-view-content">
		<?php if( !empty( $this->fields['single_table-columns'] ) ): ?>
			<thead>
				<?php gravityview_header(); ?>
			</thead>
			<tbody>
				<?php

					$markup = '
						<tr class="{{class}}">
							<th scope="row">{{label}}</th>
							<td>{{value}}</td>
						</tr>';

					$this->renderZone( 'columns', array(
						'markup' => $markup,
					));
			?>
			</tbody>
			<tfoot>
				<?php gravityview_footer(); ?>
			</tfoot>
		<?php endif; ?>
	</table>
</div>
<?php gravityview_after(); ?>
