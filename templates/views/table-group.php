<?php
/**
 * The table layout template
 *
 * @global \GV\Template_Context $gravityview
 */
	$gravityview->template->get_template_part( 'table-group/table', 'header' );

	if ( ! $group = $gravityview->template->next_group() ) {
		// @todo No entries found message here
	} else {
		while ( $group ) {
			$gravityview->template->get_template_part( 'table-group/table-group', 'header' );
			$gravityview->template->get_template_part( 'table-group/table-group', 'body' );
			$gravityview->template->get_template_part( 'table-group/table-group', 'footer' );

			$group = $gravityview->template->next_group();
		}
	}

	$gravityview->template->get_template_part( 'table-group/table', 'footer' );
