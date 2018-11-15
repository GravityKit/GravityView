<?php
/**
 * Display hidden field input
 *
 * @see class-search-widget.php
 * @global \GV\Template_Context $gravityview
 * @global \GV\Widget           $widget
 * @global \GV\Template         $template
 * @global object               $search_field
 */

?><div><input type="hidden" name="<?php echo esc_attr( $search_field->name ); ?>" value="<?php echo esc_attr( $search_field->value ); ?>"></div>
