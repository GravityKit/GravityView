<?php
/**
 * The default field output template.
 *
 * @global \GV\Template_Context $gravityview
 *
 * @since 2.0
 */
if (!isset($gravityview) || empty($gravityview->template)) {
    gravityview()->log->error('{file} template loaded without context', ['file' => __FILE__]);

    return;
}

echo $gravityview->display_value;
