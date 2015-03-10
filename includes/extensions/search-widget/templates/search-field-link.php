<?php
/**
 * Display the search LINK input field
 *
 * @see class-search-widget.php
 */

$gravityview_view = GravityView_View::getInstance();
$view_id = $gravityview_view->getViewId();
$search_field = $gravityview_view->search_field;

// Make sure that there are choices to display
if( empty( $search_field['choices'] ) ) {
	do_action('gravityview_log_debug', 'search-field-link.php - No choices for field' );
	return;
}

$links_label = apply_filters( 'gravityview/extension/search/links_label', __( 'Show only:', 'gravityview' ) );
$links_sep = apply_filters( 'gravityview/extension/search/links_sep', '&nbsp;|&nbsp;' );
?>

<div class="gv-search-box">

	<p class="gv-search-box-links">
		<?php echo esc_html( $links_label ); ?>

		<?php foreach( $search_field['choices'] as $k => $choice ) :

			if( $k != 0 ) { echo esc_html( $links_sep ); }

			$active = ( !empty( $_GET[ $search_field['name'] ] ) && $_GET[ $search_field['name'] ] === $choice['text'] ) ? ' class="active"' : false;

			if( $active ) {
				$link = remove_query_arg( array( 'pagenum', $search_field['name'] ) );
			} else {
				$link = add_query_arg( array( $search_field['name'] => urlencode( $choice['value'] ) ), remove_query_arg( array('pagenum') ) );
			}

		?>

			<a href="<?php echo esc_url( $link ); ?>"<?php echo $active; ?>><?php echo esc_html( $choice['text'] ); ?></a>

		<?php endforeach; ?>
	</p>
</div>
