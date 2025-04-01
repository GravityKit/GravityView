<?php
/**
 * Display the Search widget
 *
 * @file class-search-widget.php See for usage
 *
 * @global \GravityView_View $this
 * @global array             $data
 */

$gravityview_view = GravityView_View::getInstance();

$view_id            = \GV\Utils::get( $data, 'view_id', null );
$search_method      = \GV\Utils::get( $data, 'search_method', 'get' );
$search_class       = \GV\Utils::get( $data, 'search_class', '' );
$permalink_fields   = \GV\Utils::get( $data, 'permalink_fields', [] );
$search_form_action = \GV\Utils::get( $data, 'search_form_action', '' );
$general_rows       = \GV\Utils::get( $data, 'search_rows_general', [] );
$advanced_rows      = \GV\Utils::get( $data, 'search_rows_advanced', [] );
$search_fields      = \GV\Utils::get( $data, 'search_fields', [] );
?>

<form class="gv-widget-search <?php echo gravityview_sanitize_html_class( $search_class ); ?>"
      method="<?php echo $search_method; ?>" action="<?php echo esc_url( $search_form_action ); ?>"
      data-viewid="<?php echo (int) $view_id; ?>">
	<?php
	/**
	 * @action `gravityview_search_widget_fields_before` Inside the `<form>` tag of the GravityView search form, before
	 *         inputs are rendered
	 *
	 * @param GravityView_Widget_Search $this GravityView Widget instance
	 */
	do_action( 'gravityview_search_widget_fields_before', $this );
	?>
    <div class="gv-widget-search-general-search gv-grid">
		<?php foreach ( $general_rows as $row ) { ?>
            <div class="gv-grid-row">
				<?php
				foreach ( $row as $col => $areas ) {
					$is_right = ( '2-2' === $col || strpos( $col, ' right' ) !== false );
					$column   = $col . ' gv-' . ( $is_right ? 'right' : 'left' );
					?>
                    <div class="gv-grid-col-<?php echo esc_attr( $column ); ?>">
						<?php
						if ( ! empty( $areas ) ) {
							foreach ( $areas as $area ) {
								foreach ( $search_fields->by_position( 'search-general' . '_' . $area['areaid'] )->all() as $field ) {
									if ( ! $field->is_visible() ) {
										continue;
									}

									$search_field = $field->to_template_data();
									/**
									 * @action `gravityview_search_widget_field_before` Before each search input is rendered (other than the submit button)
									 *
									 * @param GravityView_Widget_Search                                             $this GravityView Widget instance
									 * @param array{key:string,label:string,value:string,type:string,choices:array} $search_field
									 */
									do_action( 'gravityview_search_widget_field_before', $this, $search_field );
									$gravityview_view->search_field = $search_field;

									$data['search_field'] = $search_field;

									$this->render( 'search-field', $search_field['input'], false, $data );
								}
							}
						}
						?>
                    </div>
				<?php } // $row ?>
            </div>
		<?php } // $rows ?>
    </div>
	<?php

	if ( ! empty( $advanced_rows ) ) {
	// TODO: Show advanced search if any of the fields in the advanced search are being searched.
	// Todo: move styling and javascript to files.
	?>
    <a style="display: block; width: 100%; margin: 10px 0;" href='javascript:void(0);'
       onclick='jQuery(".gv-widget-search-advanced-search").toggleClass("gv-hide")'>
		<?php esc_html_e( 'Advanced Search', 'gk-gravityview' ); ?>
    </a>

    <style>.gv-hide {
            display: none !important;
        }</style>
    <div class="gv-widget-search-advanced-search gv-grid gv-hide">
		<?php

		/**
		 * @action `gravityview_search_widget_fields_before` Inside the `<form>` tag of the GravityView search form, before inputs are rendered
		 *
		 * @param GravityView_Widget_Search $this GravityView Widget instance
		 */
		do_action( 'gravityview_search_widget_advanced_fields_before', $this );

		foreach ( $advanced_rows as $row ) { ?>
            <div class="gv-grid-row">
				<?php
				foreach ( $row as $col => $areas ) {
					$is_right = ( '2-2' === $col || strpos( $col, ' right' ) !== false );
					$column   = $col . ' gv-' . ( $is_right ? 'right' : 'left' );
					?>
                    <div class="gv-grid-col-<?php echo esc_attr( $column ); ?>">
						<?php
						if ( ! empty( $areas ) ) {
							foreach ( $areas as $area ) {
								foreach ( $search_fields->by_position( 'search-advanced' . '_' . $area['areaid'] )->all() as $field ) {
									$search_field = $field->to_template_data();
									/**
									 * @action `gravityview_search_widget_field_before` Before each search input is rendered (other than the submit button)
									 *
									 * @param GravityView_Widget_Search                                             $this GravityView Widget instance
									 * @param array{key:string,label:string,value:string,type:string,choices:array} $search_field
									 */
									do_action( 'gravityview_search_widget_field_before', $this, $search_field );
									$gravityview_view->search_field = $search_field;

									$data['search_field'] = $search_field;

									$this->render( 'search-field', $search_field['input'], false, $data );

									/**
									 * @action `gravityview_search_widget_field_after` After each search input is rendered (other than the submit button)
									 *
									 * @param GravityView_Widget_Search $this GravityView Widget instance
									 * @param array                     $search_field
									 */
									do_action( 'gravityview_search_widget_field_after', $this, $search_field );
								}
							}
						}
						?>
                    </div>
				<?php } // $row ?>
            </div>
		<?php } // $rows

		/**
		 * @action `gravityview_search_widget_fields_after` Inside the `<form>` tag of the GravityView search form, after inputs are rendered
		 *
		 * @param GravityView_Widget_Search $this GravityView Widget instance
		 */
		do_action( 'gravityview_search_widget_advanced_fields_after', $this );

		echo '</div>';
		}

		foreach ( $permalink_fields as $search_field ) {
			/**
			 * @action `gravityview_search_widget_field_before` Before each search input is rendered (other than the submit button)
			 *
			 * @param GravityView_Widget_Search                                             $this GravityView Widget instance
			 * @param array{key:string,label:string,value:string,type:string,choices:array} $search_field
			 */
			do_action( 'gravityview_search_widget_field_before', $this, $search_field );

			$data['search_field'] = $search_field;

			$this->render( 'search-field', $search_field['input'], false, $data );

			/**
			 * @action `gravityview_search_widget_field_after` After each search input is rendered (other than the submit button)
			 *
			 * @param GravityView_Widget_Search $this GravityView Widget instance
			 * @param array                     $search_field
			 */
			do_action( 'gravityview_search_widget_field_after', $this, $search_field );
		}

		/**
		 * @action `gravityview_search_widget_fields_after` Inside the `<form>` tag of the GravityView search form,
		 *         after inputs are rendered
		 *
		 * @param GravityView_Widget_Search $this GravityView Widget instance
		 */
		do_action( 'gravityview_search_widget_fields_after', $this );
		?>
    </div>
</form>
