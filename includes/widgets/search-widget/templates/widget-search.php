<?php
/**
 * Display the Search widget
 *
 * @file class-search-widget.php See for usage
 *
 * @global \GravityView_View $this
 * @global array $data
 */


$view_id = \GV\Utils::get( $data, 'view_id', null );
$search_method = \GV\Utils::get( $data, 'search_method', 'get' );
$search_fields = \GV\Utils::get( $data, 'search_fields', [] );
$search_class = \GV\Utils::get( $data, 'search_class', '' );
$permalink_fields = \GV\Utils::get( $data, 'permalink_fields', [] );
$search_form_action = \GV\Utils::get( $data, 'search_form_action', '' );
?>

<form class="gv-widget-search <?php echo gravityview_sanitize_html_class( $search_class ); ?>" method="<?php echo $search_method; ?>" action="<?php echo esc_url( $search_form_action ); ?>" data-viewid="<?php echo (int) $view_id; ?>">

	<?php

	/**
	 * @action `gravityview_search_widget_fields_before` Inside the `<form>` tag of the GravityView search form, before inputs are rendered
	 *
	 * @param GravityView_Widget_Search $this GravityView Widget instance
	 */
	do_action( 'gravityview_search_widget_fields_before', $this );

	$has_inputs = false;
	foreach( array_merge( $search_fields, $permalink_fields ) as $search_field ) {

		/**
		 * @action `gravityview_search_widget_field_before` Before each search input is rendered (other than the submit button)
		 *
		 * @param GravityView_Widget_Search $this GravityView Widget instance
		 * @param array{key:string,label:string,value:string,type:string,choices:array} $search_field
		 */
		do_action( 'gravityview_search_widget_field_before', $this, $search_field );

		$data['search_field'] = $search_field;

		$this->render( 'search-field', $search_field['input'], false, $data );

		// show/hide the search button if there are input type fields
		if ( ! $has_inputs && 'link' != $search_field['input'] ) {
			$has_inputs = true;
		}

		/**
		 * @action `gravityview_search_widget_field_after` After each search input is rendered (other than the submit button)
		 *
		 * @param GravityView_Widget_Search $this GravityView Widget instance
		 * @param array $search_field
		 */
		do_action( 'gravityview_search_widget_field_after', $this, $search_field );
	}

	/**
	 * @action `gravityview_search_widget_fields_after` Inside the `<form>` tag of the GravityView search form, after inputs are rendered
	 * @param GravityView_Widget_Search $this GravityView Widget instance
	 */
	do_action( 'gravityview_search_widget_fields_after', $this );

	if( ! empty($search_fields['search_advanced-search-fields']) ) {

		// TODO: Show advanced search if any of the fields in the advanced search are being searched.
		?>
		<a style="display: block; width: 100%; margin: 10px 0;" href='#' onclick='jQuery(".gv-widget-search-advanced-search").toggleClass("gv-hide")'><?php esc_html_e( 'Advanced Search', 'gk-gravityview' ); ?></a>

		<style>.gv-hide {display: none!important;}</style>
		<div class='gv-widget-search-advanced-search gv-hide' style='display: flex; flex: 100% 1 1; flex-wrap: wrap;'>
		<?php

		/**
		 * @action `gravityview_search_widget_fields_before` Inside the `<form>` tag of the GravityView search form, before inputs are rendered
		 *
		 * @param GravityView_Widget_Search $this GravityView Widget instance
		 */
		do_action( 'gravityview_search_widget_advanced_fields_before', $this );

		foreach ( $search_fields['search_advanced-search-fields'] as $search_field ) {

			/**
			 * @action `gravityview_search_widget_field_before` Before each search input is rendered (other than the submit button)
			 *
			 * @param GravityView_Widget_Search $this GravityView Widget instance
			 * @param array{key:string,label:string,value:string,type:string,choices:array} $search_field
			 */
			do_action( 'gravityview_search_widget_field_before', $this, $search_field );

			$data['search_field'] = $search_field;

			$this->render( 'search-field', $search_field['input'], false, $data );

			/**
			 * @action `gravityview_search_widget_field_after` After each search input is rendered (other than the submit button)
			 *
			 * @param GravityView_Widget_Search $this GravityView Widget instance
			 * @param array $search_field
			 */
			do_action( 'gravityview_search_widget_field_after', $this, $search_field );
		}

		/**
		 * @action `gravityview_search_widget_fields_after` Inside the `<form>` tag of the GravityView search form, after inputs are rendered
		 *
		 * @param GravityView_Widget_Search $this GravityView Widget instance
		 */
		do_action( 'gravityview_search_widget_advanced_fields_after', $this );

		echo '</div>';
	}

	if ( $has_inputs ) {
	    $this->render( 'search-field', 'submit', false, $data );
	}
	?>
</form>
