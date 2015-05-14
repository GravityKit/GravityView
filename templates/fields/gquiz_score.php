<?php
/**
 * Display the post_id field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

echo GFCommon::replace_variables( '{quiz_passfail}', $gravityview_view->getForm(), $gravityview_view->getCurrentEntry() );