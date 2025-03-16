<?php
/**
 * @global Template_Context $gravityview
 */

use GV\Grid;
use GV\Template_Context;

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template was loaded without context', [ 'file' => __FILE__ ] );

	return;
}

gravityview_before( $gravityview );

ob_start();
gravityview_header( $gravityview );

$zone = 'single';
$rows = Grid::prefixed(
	GravityView_Layout_Builder::ID,
	static fn() => Grid::get_rows_from_collection( $gravityview->fields, $zone )
);

// There are entries. Loop through them.
$entry = $gravityview->entry;
$back_link = gravityview_back_link( $gravityview );
if ( $back_link ) {
	printf( '<p class="gv-back-link">%s</p>', $back_link );
}
?>
	<div class="gv-layout-builder-view gv-layout-builder-view--entry gv-grid">
		<?php foreach ( $rows as $row ) { ?>
			<div class="gv-grid-row">
				<?php
				foreach ( $row as $col => $areas ) {
					$column = $col;
					?>
					<div class="gv-grid-col-<?php echo esc_attr( $column ); ?>">
						<?php
						if ( ! empty( $areas ) ) {
							foreach ( $areas as $area ) {
								foreach ( $gravityview->fields->by_position( $zone . '_' . $area['areaid'] )->all() as $field ) {
									echo $gravityview->template->the_field( $field, $entry );
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

gravityview_footer( $gravityview );
$content = ob_get_clean();
/**
 * Modify the wrapper container.
 *
 * @since  2.15
 *
 * @param string   $wrapper_container Wrapper container HTML markup
 * @param string   $anchor_id         (optional) Unique anchor ID to identify the view.
 * @param \GV\View $view              The View.
 */
$class             = gv_container_class( 'gv-layout-builder-container gv-layout-builder-container--single', false, $gravityview );
$wrapper_container = apply_filters(
	'gravityview/view/wrapper_container',
	'<div id="' . esc_attr( $gravityview->view->get_anchor_id() ) . '" class="' . esc_attr( $class ) . '">{content}</div>',
	$gravityview->view->get_anchor_id(),
	$gravityview->view
);

echo $wrapper_container ? str_replace( '{content}', $content, $wrapper_container ) : $content;
