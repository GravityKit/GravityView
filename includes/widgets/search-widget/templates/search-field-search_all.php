<?php
/**
 * Display the search all input box
 *
 * @file class-search-widget.php See for usage
 *
 * @global array $data
 */

$view_id      = \GV\Utils::get( $data, 'view_id', null );
$search_field = \GV\Utils::get( $data, 'search_field', [] );
$custom_class = \GV\Utils::get( $search_field, 'custom_class', [] );
$value        = \GV\Utils::get( $search_field, 'value' );
$label        = \GV\Utils::get( $search_field, 'label' );
$placeholder  = \GV\Utils::get( $search_field, 'placeholder', '' );

$input_id = sprintf( 'gv_search_%d', $view_id );
?>

<div class="gv-search-box gv-search-field-text gv-search-field-search_all <?php echo $custom_class; ?>">
    <div class="gv-search">
		<?php if ( ! gv_empty( $label, false, false ) ) { ?>
            <label for="<?php echo $input_id; ?>"><?php echo esc_html( $label ); ?></label>
		<?php } ?>
        <p>
			<?php printf(
				'<input type="search" name="gv_search" id="%s" value="%s" placeholder="%s"/>',
				$input_id,
				esc_attr( $value ),
				esc_attr( $placeholder ),
			); ?>
        </p>
    </div>
</div>
