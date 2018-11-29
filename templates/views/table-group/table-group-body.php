<?php
/**
 * The entry loop for the table output.
 *
 * @global \GV\Template_Context $gravityview
 */

/** @var \GV\View_Table_Template $template */
$template = $gravityview->template;
?>
	<tbody>
		<?php
			while ( $template->next_group() ) {
				$template->get_template_part( 'table-group/table-group', 'header' );
				$template->get_template_part( 'table-group/table-group', 'body' );
				$template->get_template_part( 'table-group/table-group', 'footer' );
			}
		?>
		<?php

		/** @action `gravityview/template/table/body/before` */
		$template::body_before( $gravityview );

		while ( $entry = $template->next_entry() ) {

			// Add `alt` class to alternate rows
			$alt = empty( $alt ) ? 'alt' : '';

			/** @filter `gravityview/template/table/entry/class` */
			$class = $template::entry_class( $alt, $entry, $gravityview );

			$attributes = array(
				'class' => $class,
			);

			$template->the_entry( $entry, $attributes );
		}

		/** @action `gravityview/template/table/body/after` */
		$template::body_after( $gravityview );
		?>
	</tbody>
