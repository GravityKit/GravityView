<?php
/**
 * Display the search LINK input field
 *
 * @see class-search-widget.php
 */

global $gravityview_view;
$view_id = $gravityview_view->view_id;
$search_field = $gravityview_view->search_field;

?>

<div class="gv-search-box">

	<p class=""><?php esc_html_e( 'Show only:', 'gravity-view' ); ?>

		<?php foreach( $search_field['choices'] as $k => $choice ) :

			if( $k != 0 ) { echo '&nbsp;|&nbsp;'; }?>

			<a href="<?php echo esc_url( add_query_arg( array( $search_field['name'] => $choice['value'] ) ) ); ?>">
				<?php echo esc_html( $choice['text'] ); ?>
			</a>

		<?php endforeach; ?>
	</p>
</div>