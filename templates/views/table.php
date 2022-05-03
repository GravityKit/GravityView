<?php
/**
 * The table layout template.
 *
 * @global \GV\Template_Context $gravityview
 */
if (!isset($gravityview) || empty($gravityview->template)) {
    gravityview()->log->error('{file} template loaded without context', ['file' => __FILE__]);

    return;
}

$gravityview->template->get_template_part('table/table', 'header');
$gravityview->template->get_template_part('table/table', 'body');
$gravityview->template->get_template_part('table/table', 'footer');
