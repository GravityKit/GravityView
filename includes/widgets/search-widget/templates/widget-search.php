<?php
/**
 * Display the Search widget
 *
 * @see class-search-widget.php
 */

global $wp_rewrite;

$gravityview_view = GravityView_View::getInstance();

$view_id = $gravityview_view->getViewId();

$has_inputs = false;

?>

<form class="gv-widget-search <?php echo GravityView_Widget_Search::get_search_class(); ?>" method="get" action="<?php echo esc_url( GravityView_Widget_Search::get_search_form_action() ); ?>">

	<?php
	do_action( 'gravityview_search_widget_fields_before', $this );

	foreach( $this->search_fields as $search_field ) {
		$gravityview_view->search_field = $search_field;
		$this->render( 'search-field', $search_field['input'], false );

		// show/hide the search button if there are input type fields
		if( !$has_inputs &&  $search_field['input'] != 'link' ) {
			$has_inputs = true;
		}
	}

	do_action( 'gravityview_search_widget_fields_after', $this );

	if( $has_inputs ) : ?>
		<div class="gv-search-box gv-search-box-submit">
			<?php

			// Output the Clear button, if enabled
			GravityView_Widget_Search::the_clear_search_button();

			// Support default permalink structure
            if( false === $wp_rewrite->using_permalinks() ) {
                if( is_page() && !empty( $_GET['page_id'] ) ) {
                    echo '<input type="hidden" name="page_id" value="'. esc_attr( $_GET['page_id'] ) .'" />';
                } elseif( is_single() ) {
                    $q = get_queried_object();
                    echo '<input type="hidden" name="p" value="'. esc_attr( $q->ID ) .'" />';
                    echo '<input type="hidden" name="post_type" value="'. esc_attr( $q->post_type ) .'" />';
                }
            }
			?>
			<input type="submit" class="button gv-search-button" id="gv_search_button_<?php echo $view_id; ?>" value="<?php esc_attr_e( 'Search', 'gravityview' ); ?>" />
		</div>
	<?php endif; ?>
</form>
