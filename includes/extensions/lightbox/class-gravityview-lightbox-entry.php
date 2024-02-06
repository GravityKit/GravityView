<?php

/**
 * - [ ] Allow for Next/Previous and Single Entry navigation options.
 * - [ ] Remove single entry widget header hooks `add_action( 'gravityview/template/before', array( $this, 'render_widget_hooks' ) );`
 */

add_filter( 'gk/gravityview/rest/entry/html', function( $rendered ) {
	?>
<html lang="<?php echo get_bloginfo( 'language' ); ?>">
	<head>
		<title>Test</title>
		<meta name="robots" content="noindex, nofollow" /> <!-- Prevent search engines from indexing the page -->
		<link rel='stylesheet' href='https://cdn.simplecss.org/simple.min.css'>
		<style>
			body {
				grid-template-columns: none;
			}
			.gv-widgets-header {display: none;}
			.gv-back-link {display: none;}
		</style>
	</head>
	<body>
	<?php echo $rendered; ?>
	</body>
</html>
<?php
} );

/**
 * Edit the Edit Link URL.
 *
 * @param string $href The Edit Link URL.
 * @param array $entry The GF entry array.
 * @param \GV\View $view The View.
 */
add_filter( 'gravityview/edit/link', function( $href, $entry, $view ) {

	// Get URL args from $href
	$args = wp_parse_args( parse_url( $href, PHP_URL_QUERY ) );

	$href = rest_url( 'gravityview/v1/views/' . $view->ID . '/entries/' . $entry['id'] . '.html' );

	$args['lightbox'] = 1;
	$args['output'] = 'raw';

	return add_query_arg( $args, $href );
}, 10, 3 );

/**
 * @filter `gravityview/entry/permalink` The permalink of this entry.
 * @since 2.0
 * @param string $permalink The permalink.
 * @param \GV\Entry $entry The entry we're retrieving it for.
 * @param \GV\View|null $view The view context.
 * @param \GV\Request $request The request context.
 */
add_filter( 'gravityview/entry/permalink', function( $permalink, $gv_entry, $view, $request ) {

	$href = rest_url( 'gravityview/v1/views/' . $view->ID . '/entries/' . $gv_entry->ID . '.html' );

	$args = [
		'lightbox' => 1,
		'output' => 'raw'
	];

	return add_query_arg( $args, $href );
}, 10, 4 );

add_filter( 'gravityview/lightbox/provider/fancybox/settings', function ( $settings ) {
	return $settings;
} );

/**
 *
 *
 */
add_filter( 'gk/gravityview/field/edit_link/atts', function ( $link_atts, $context ) {
	$link_atts['data-fancybox'] = 'edit';
	$link_atts['data-type'] = 'iframe';

	return $link_atts;
}, 10, 2 );

/**
 *
 *
 */
add_filter( 'gravityview/entry_link/link_atts', function ( $link_atts, $context ) {
	$link_atts['data-fancybox'] = 'gallery';
	$link_atts['data-type'] = 'iframe';

	return $link_atts;
}, 10, 2 );

