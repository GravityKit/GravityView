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

ob_start();
gravityview_before( $gravityview );

gravityview_header( $gravityview );

// There are no entries.
if ( ! $gravityview->entries->count() ) {
	?>
	<div class="gv-layout-builder-view gv-no-results">
		<div class="gv-layout-builder-view-title">
			<h3><?php echo gv_no_results( true, $gravityview ); ?></h3>
		</div>
	</div>
	<?php
} else {
	$zone = 'directory';
	$rows = Grid::prefixed(
		GravityView_Layout_Builder::ID,
		static fn() => Grid::get_rows_from_collection( $gravityview->fields, $zone )
	);
	// There are entries. Loop through them.
	foreach ( $gravityview->entries->all() as $entry ) {
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
	}
}

gravityview_footer( $gravityview );

gravityview_after( $gravityview );

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
$class             = gv_container_class( 'gv-layout-builder-container gv-layout-builder-container--multiple', false, $gravityview );
$wrapper_container = apply_filters(
	'gravityview/view/wrapper_container',
	'<div id="' . esc_attr( $gravityview->view->get_anchor_id() ) . '" class="' . esc_attr( $class ) . '">{content}</div>',
	$gravityview->view->get_anchor_id(),
	$gravityview->view
);

echo $wrapper_container ? str_replace( '{content}', $content, $wrapper_container ) : $content;
