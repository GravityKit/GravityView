<?php
/**
 * Display the HTML field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

// Tell the renderer not to wrap this field in an anchor tag.
$gravityview_view->setCurrentFieldSetting('show_as_link', false);

echo GravityView_API::replace_variables( $field['content'], $form, $entry );
