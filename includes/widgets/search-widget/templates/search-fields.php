<?php

use GV\Search\Search_Field_Collection;

/**
 * Template part that renders the search fields for a section.
 *
 * @since 2.42
 *
 * @global array $data
 * @var          $search_fields Search_Field_Collection The Fields.
 */
$search_section   = \GV\Utils::get( $data, 'section', '' );
$rows             = \GV\Utils::get( $data, 'search_rows_' . $search_section, [] );
$search_fields    = \GV\Utils::get( $data, 'search_fields', [] );
$gravityview_view = GravityView_View::getInstance();

foreach ( $rows as $row ) { ?>
	<div class="gv-grid-row">
		<?php
		foreach ( $row as $col => $areas ) {
			?>
			<div class="gv-grid-col-<?php echo esc_attr( $col ); ?>">
				<?php
				if ( ! empty( $areas ) ) {
					foreach ( $areas as $area ) {
						foreach ( $search_fields->by_position( $search_section . '_' . $area['areaid'] )->to_template_data() as $search_field ) {
							/**
							 * @action `gravityview_search_widget_field_before` Before each search input is rendered.
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
