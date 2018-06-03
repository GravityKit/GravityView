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

<div class="<?php gv_container_class( 'gv-table-view gv-table-container gv-table-single-container' ); ?>">
	<table class="gv-table-view-content">
		<?php if( $this->getFields('single_table-columns') ) { ?>
			<thead>
				<?php gravityview_header(); ?>
			</thead>
			<tbody>
				<?php

					$markup = '
						<tr id="{{ field_id }}" class="{{class}}">
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
		<?php } ?>
	</table>
</div>
<?php gravityview_after(); ?>
