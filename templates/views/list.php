<?php
/**
 * The list layout template
 *
 * @global stdClass $gravityview (\GV\View $gravityview::$view, \GV\View_Template $gravityview::$template)
 */
	$gravityview->template->get_template_part( 'list/list', 'header' );
	$gravityview->template->get_template_part( 'list/list', 'body' );
	$gravityview->template->get_template_part( 'list/list', 'footer' );
