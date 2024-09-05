<?php

use GV\Entry;
use GV\GF_Entry;
use GV\Request;
use GV\View;

/**
 *
 * - [ ] Allow for Next/Previous and Single Entry navigation options.
 * - [ ] Remove single entry widget header hooks `add_action( 'gravityview/template/before', array( $this, 'render_widget_hooks' ) );`
 * - [ ] When "open in lightbox" is enabled, disable "open in new window" option.
 * - [ ] Add setting to enable/disable indexing of single entry pages when opening in a lightbox.
 * - [ ] Make sure to embed Custom CSS and Custom JS output.
 * - [ ] Edit Entry isn't working.
 * - [ ] Enable galleries inside modal
 */

function gravityview_is_request_lightbox( WP_REST_Request $request ) {
	if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
		return false;
	}

	/**
	 * Don't format the HTML as JSON, just return it.
	 */
	if ( 'raw' !== $request->get_param( 'output' ) ) {
		return false;
	}

	if ( '1' !== $request->get_param( 'lightbox' ) ) {
		return false;
	}

	return true;
}

add_filter( 'rest_post_dispatch', function ( $response, $server, $request ) {
	if ( ! $response instanceof WP_REST_Response ) {
		return $response;
	}

	if ( false === strpos( $response->get_matched_route(), 'gravityview' ) ) {
		return $response;
	}

	if ( ! gravityview_is_request_lightbox( $request ) ) {
		return $response;
	}

	// Define the content type as being HTML instead of JSON.
	$response->header( 'Content-Type', 'text/html' );

	return $response;
}, 20, 3 );

/**
 * Filters whether the REST API request has already been served.
 *
 * Allow sending the request manually - by returning true, the API result
 * will not be sent to the client.
 *
 * @since 4.4.0
 *
 * @param bool             $served           Whether the request has already been served.
 *                                           Default false.
 * @param WP_HTTP_Response $result           Result to send to the client. Usually a `WP_REST_Response`.
 * @param WP_REST_Request  $request          Request used to generate the response.
 * @param WP_REST_Server   $server           Server instance.
 */
add_filter( 'rest_pre_serve_request', function ( $served, $result, $request, $server ) {
	if ( ! gravityview_is_request_lightbox( $request ) ) {
		return $served;
	}

	$entry_id = $request->get_params()['s_id'];
	$view_id  = $request->get_params()['id'];

	$rendered = apply_filters( 'gk/gravityview/rest/entry/html', $result->get_data(), $result, $request, $entry_id, $view_id );

	echo $rendered;

	return true;
}, 10, 4 );

/**
 * Wrap the rendered HTML snippet inside a full HTML page.
 *
 * @internal
 *
 * @return void
 */
add_filter( 'gk/gravityview/rest/entry/html', function ( $rendered, $result, $request, $entry_id, $view_id ) {
	$view  = View::by_id( $view_id );
	$entry = GF_Entry::by_id( $entry_id );

	$title = $view->settings->get( 'single_title', '' );

	$form = GVCommon::get_form( $entry['form_id'] );

	// We are allowing HTML in the fields, so no escaping the output
	$title = GravityView_API::replace_variables( $title, $form, $entry );

	$title = do_shortcode( $title );

	ob_start();
	?>

	<html lang="<?php echo get_bloginfo( 'language' ); ?>">
		<head>
			<title>{{title}}</title>
			<?php wp_head(); ?>
			<style>
				<?php echo $view->settings->get( 'custom_css', '' ); ?>
			</style>

			<script type="text/javascript">
				<?php echo $view->settings->get( 'custom_javascript', '' ); ?>
			</script>

		</head>
		<body>
			{{content}}
		</body>
	</html>

	<?php
	$template = ob_get_clean();

	$rendered = str_replace( '{{content}}', $rendered, $template );
	$rendered = str_replace( '{{title}}', $title, $rendered );

	echo $rendered;
}, 10, 5 );

/**
 * Edit the Edit Link URL.
 *
 * @param string $href  The Edit Link URL.
 * @param array  $entry The GF entry array.
 * @param View   $view  The View.
 */
add_filter( 'gravityview/edit/link', function ( $href, $entry, $view ) {

	// Get URL args from $href
	$args = wp_parse_args( parse_url( $href, PHP_URL_QUERY ) );

	$href = rest_url( 'gravityview/v1/views/' . $view->ID . '/entries/' . $entry['id'] . '.html' );

	$args['lightbox'] = 1;
	$args['output']   = 'raw';

	return add_query_arg( $args, $href );
}, 10, 3 );

/**
 * @filter `gravityview/entry/permalink` The permalink of this entry.
 * @since  2.0
 *
 * @param string    $permalink The permalink.
 * @param Entry     $entry     The entry we're retrieving it for.
 * @param View|null $view      The view context.
 * @param Request   $request   The request context.
 */
add_filter( 'gravityview/entry/permalink', function ( $permalink, $gv_entry, $view, $request ) {
	$href = rest_url( 'gravityview/v1/views/' . $view->ID . '/entries/' . $gv_entry->ID . '.html' );

	$args = [
		'lightbox' => 1,
		'output'   => 'raw',
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
	$link_atts['data-type']     = 'iframe';

	return $link_atts;
}, 10, 2 );

/**
 *
 *
 */
add_filter( 'gravityview/entry_link/link_atts', function ( $link_atts, $context ) {
	$link_atts['data-fancybox'] = 'gallery';
	$link_atts['data-type']     = 'iframe';

	return $link_atts;
}, 10, 2 );

/*
 * Prevents the back link from being displayed in the single entry lightbox view.
 *
 * @since TBD
 */
add_filter( 'gravityview/template/links/back/url', function ( $url, $context ) {
	$request = $context->request instanceof Request ? $context->request->get_request() : $context->request;

	if ( ! $request instanceof WP_REST_Request ) {
		return $url;
	}

	return gravityview_is_request_lightbox( $request ) ? null : $url;
}, 10, 3 );
