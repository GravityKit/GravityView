<?php
/**
 * Display the phone field type.
 *
 * @since 1.17
 */
$gravityview_view = GravityView_View::getInstance();

/**
 * @var float|int|string $value
 * @var float|int|string $display_value
 */
extract($gravityview_view->getCurrentField());

$value = esc_attr($value);

if (!empty($field_settings['link_phone']) && !empty($value)) {
    echo gravityview_get_link('tel:'.$value, $value);
} else {
    echo $value;
}
