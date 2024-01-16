<?php
/**
 * Display the Search widget
 *
 * @file class-search-widget.php See for usage
 * @global GravityView_Widget_Search $this
 */

$gravityview_view = GravityView_View::getInstance();

$view_id = $gravityview_view->getViewId();

$has_inputs = false;

$search_method = GravityView_Widget_Search::getInstance()->get_search_method();

?>

<form class="gv-widget-search <?php echo GravityView_Widget_Search::get_search_class(); ?>" method="<?php echo $search_method; ?>" action="<?php echo esc_url( GravityView_Widget_Search::get_search_form_action() ); ?>" data-viewid="<?php echo $view_id; ?>">

	<?php

	/**
	 * tag of the GravityView search form, before inputs are rendered.
	 *
	 * @param GravityView_Widget_Search $this GravityView Widget instance
	 */
	do_action( 'gravityview_search_widget_fields_before', $this );

	foreach ( array_merge( $this->search_fields, $this->permalink_fields ) as $search_field ) {

		/**
		 * Before each search input is rendered (other than the submit button).
		 *
		 * @param GravityView_Widget_Search $this GravityView Widget instance
		 * @param array{key:string,label:string,value:string,type:string,choices:array} $search_field
		 */
		do_action( 'gravityview_search_widget_field_before', $this, $search_field );

		$gravityview_view->search_field = $search_field;
		$this->render( 'search-field', $search_field['input'], false );

		// show/hide the search button if there are input type fields
		if ( ! $has_inputs && 'link' != $search_field['input'] ) {
			$has_inputs = true;
		}

		/**
		 * After each search input is rendered (other than the submit button).
		 *
		 * @param GravityView_Widget_Search $this GravityView Widget instance
		 * @param array $search_field
		 */
		do_action( 'gravityview_search_widget_field_after', $this, $search_field );
	}

	/**
	 * tag of the GravityView search form, after inputs are rendered.
	 *
	 * @param GravityView_Widget_Search $this GravityView Widget instance
	 */
	do_action( 'gravityview_search_widget_fields_after', $this );

	if ( $has_inputs ) {
		$this->render( 'search-field', 'submit', false );
	}
	?>
</form>
