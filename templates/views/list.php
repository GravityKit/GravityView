<?php
/**
 * The list layout template.
 *
 * @global \GV\Template_Context $gravityview
 */
if (!isset($gravityview) || empty($gravityview->template)) {
    gravityview()->log->error('{file} template loaded without context', ['file' => __FILE__]);

    return;
}

$gravityview->template->get_template_part('list/list', 'header');
$gravityview->template->get_template_part('list/list', 'body');
$gravityview->template->get_template_part('list/list', 'footer');
