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
$exclude_classes  = [ 'left', 'right', 'middle' ];

foreach ( $rows as $row ) { ?>
	<div class="gv-grid-row">
		<?php
		foreach ( $row as $col => $areas ) {
			// Remove text-align classes.
			$classes = array_filter(
				explode( ' ', $col ),
				static fn( string $column_class ) => ! in_array( trim( $column_class ), $exclude_classes, true ),
			);

			$column_class = apply_filters(
				'gk/gravityview/search/widget/grid/column-class',
				'gv-grid-col-' . implode( ' ', $classes ),
				$col,
				$areas,
				$search_fields
			);

			?>
			<div class="<?php echo esc_attr( $column_class ); ?>">
				<?php
				if ( ! empty( $areas ) ) {
					foreach ( $areas as $area ) {
						$position      = $search_section . '_' . $area['areaid'];
						$area_settings = $search_fields->get_area_configuration( $position );
						$classes       = [ 'gv-search-widget-area' ];

						if ( 'row' === ( $area_settings['layout'] ?? 'column' ) ) {
							$classes[] = 'gv-search-horizontal';
						}

						printf( '<div class="%s">', esc_attr( implode( ' ', $classes ) ) );
						foreach ( $search_fields->by_position( $position )->to_template_data() as $search_field ) {
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
						echo '</div>';
					}
				}
				?>
			</div>
		<?php } // $row ?>
	</div>
<?php } // $rows ?>
