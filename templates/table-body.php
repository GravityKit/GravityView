<?php
/**
 * Display the entry loop when using a table template
 *
 * @package GravityView
 * @subpackage GravityView/templates
 *
 * @global GravityView_View $this
 */
?>
	<tbody>
		<?php

		do_action('gravityview_table_body_before', $this );

		if( 0 === $this->getTotalEntries() ) {
			?>
			<tr>
				<?php do_action('gravityview_table_tr_before', $this ); ?>
				<td colspan="<?php echo isset( $this->fields['directory_table-columns'] ) ? sizeof($this->fields['directory_table-columns']) : ''; ?>" class="gv-no-results">
					<?php echo gv_no_results(); ?>
				</td>
				<?php do_action('gravityview_table_tr_after', $this ); ?>
			</tr>
		<?php
		} else {

			foreach( $this->getEntries() as $entry ) :

				$this->setCurrentEntry( $entry );

				// Add `alt` class to alternate rows
				$alt = empty( $alt ) ? 'alt' : false;

				$class = apply_filters( 'gravityview_entry_class', $alt, $entry, $this );
		?>
				<tr<?php echo ' class="'.esc_attr($class).'"'; ?>>
		<?php
					do_action('gravityview_table_cells_before', $this );

					$this->renderZone( 'columns', array(
						'markup' => '<td class="{{class}}">{{value}}</td>',
						'hide_empty' => false, // Always show <td>
					));

					do_action('gravityview_table_cells_after', $this );
		?>
				</tr>
			<?php
			endforeach;

		}

		do_action('gravityview_table_body_after', $this );
	?>
	</tbody>
