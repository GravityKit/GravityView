<?php
/**
 * Display the Search widget
 *
 * @see class-search-widget.php
 * @global \GV\Template_Context $gravityview
 * @global \GV\Widget           $widget
 * @global \GV\Template         $template
 */

$has_inputs = false;

?>

<form class="gv-widget-search <?php echo $widget->search_class( $gravityview ); ?>" method="<?php echo $widget->get_search_method(); ?>" action="<?php echo esc_url( $widget->search_form_action( $gravityview ) ); ?>" data-viewid="<?php echo $gravityview->view->ID; ?>">

	<?php

	/**
	 * @action `gravityview_search_widget_fields_before` Inside the `<form>` tag of the GravityView search form, before inputs are rendered
	 * @param \GV\Widget $widget The widget.
	 * @param \GV\Template_Context $gravityview The context.
	 */
	do_action( 'gravityview_search_widget_fields_before', $widget, $gravityview );

	foreach ( $widget->search_fields as $search_field ) {

		/**
		 * @action `gravityview_search_widget_field_before` Before each search input is rendered (other than the submit button)
		 * @param \GV\Widget $widget The widget.
         * @param array $search_field
		 * @param \GV\Template_Context $gravityview The context.
		 */
		do_action( 'gravityview_search_widget_field_before', $widget, $search_field, $gravityview );

		$template->push_template_data( $search_field, 'search_field' );
		$template->get_template_part( 'field', $search_field['input'] );
		$template->pop_template_data( 'search_field' );

		// show/hide the search button if there are input type fields
		if ( ! $has_inputs && $search_field['input'] != 'link' ) {
			$has_inputs = true;
		}

		/**
		 * @action `gravityview_search_widget_field_after` After each search input is rendered (other than the submit button)
		 * @param \GV\Widget $widget The widget.
         * @param array $search_field
		 * @param \GV\Template_Context $gravityview The context.
		 */
		do_action( 'gravityview_search_widget_field_after', $widget, $search_field, $gravityview );
	}

	/**
	 * @action `gravityview_search_widget_fields_after` Inside the `<form>` tag of the GravityView search form, after inputs are rendered
	 * @param \GV\Widget $widget The widget.
	 * @param \GV\Template_Context $gravityview The context.
	 */
	do_action( 'gravityview_search_widget_fields_after', $widget, $gravityview );

	if ( $has_inputs ) {
		$template->get_template_part( 'submit' );
    }
?>
</form>
