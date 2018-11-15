<?php
/**
 * Display the search LINK input field
 *
 * @see class-search-widget.php
 * @global \GV\Template_Context $gravityview
 * @global \GV\Widget           $widget
 * @global \GV\Template         $template
 * @global object               $search_field
 */

// base url to calculate the final full link
$base_url = $widget->search_form_action( $gravityview );

// Make sure that there are choices to display
if ( empty( $search_field->choices ) ) {
	gravityview()->log->debug( 'search-field-link.php - No choices for field' );
	return;
}

$links_label = empty( $search_field->label ) ? __( 'Show only:', 'gravityview' ) : $search_field->label;

/**
 * @filter `gravityview/extension/search/links_label` Change the label for the "Link" search bar input type
 * @since 1.17 Use search field label as default value, if set. Before that, it was hard-coded to "Show only:"
 * @param string $links_label Default: `Show only:` if search field label is not set. Otherwise, search field label.
 * @param \GV\Widget           $widget       The widget
 * @param \GV\Template_Context $gravityview  The template context.
 * @param object               $search_field The search field.
 */
$links_label = apply_filters( 'gravityview/extension/search/links_label', $links_label, $widget, $gravityview, $search_field );

/**
 * @filter `gravityview/extension/search/links_sep` Change what separates search bar "Link" input type links
 * @param string $links_sep Default: `&nbsp;|&nbsp;` Used to connect multiple links
 * @param \GV\Widget           $widget       The widget
 * @param \GV\Template_Context $gravityview  The template context.
 * @param object               $search_field The search field.
 */
$links_sep = apply_filters( 'gravityview/extension/search/links_sep', '&nbsp;|&nbsp;', $widget, $gravityview, $search_field );

?>

<div class="gv-search-box gv-search-field-link gv-search-box-links">
	<p>
		<?php echo esc_html( $links_label ); ?>

        <?php

        $search_value = \GV\Utils::_GET( $search_field->name );

        foreach ( $search_field->choices as $k => $choice ) {

            if ( 0 != $k ) {
                echo esc_html( $links_sep );
            }

            $active = ( '' !== $search_value && in_array( $search_value, array( $choice['text'], $choice['value'] ) ) ) ? ' class="active"' : false;

            if ( $active ) {
                $link = remove_query_arg( array( 'pagenum', $search_field->name ), $base_url );
            } else {
                $link = add_query_arg( array( $search_field->name => urlencode( $choice['value'] ) ), remove_query_arg( array('pagenum'), $base_url ) );
            }
            ?>

			<a href="<?php echo esc_url_raw( $link ); ?>" <?php echo $active; ?>><?php echo esc_html( $choice['text'] ); ?></a>

		<?php } ?>
	</p>
</div>
