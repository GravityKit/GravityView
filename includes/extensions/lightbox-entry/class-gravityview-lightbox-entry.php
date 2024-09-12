<?php

use GV\Edit_Entry_Renderer;
use GV\Entry_Renderer;
use GV\GF_Entry;
use GV\View;

class GravityView_Lightbox_Entry {
	/**
	 * The REST namespace used for the single entry lightbox view.
	 *
	 * @since TBD
	 */
	const REST_NAMESPACE = 'gravityview/v1';

	/**
	 * Class constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		require_once 'class-gravityview-lightbox-entry-request.php';

		add_filter( 'gk/foundation/rest/routes', [ $this, 'register_rest_routes' ] );
		add_filter( 'gravityview_field_entry_link', [ $this, 'modify_entry_link' ], 10, 4 );
		add_filter( 'gk/foundation/inline-scripts', [ $this, 'enqueue_view_editor_script' ] );
	}

	/**
	 * Registers the REST route for the single entry lightbox view.
	 *
	 * @used-by `gk/foundation/rest/routes` filter.
	 *
	 * @since   TBD
	 *
	 * @param array[] $routes The registered REST routes.
	 *
	 * @return array
	 */
	public function register_rest_routes( $routes ) {
		$routes = $routes ?? [];

		$routes[] = [
			'namespace'           => explode( '/v', self::REST_NAMESPACE )[0],
			'version'             => explode( '/v', self::REST_NAMESPACE )[1],
			'endpoint'            => sprintf(
				'view/%s/entry/%s',
				'(?P<view_id>[0-9]+)',
				'(?P<entry_id>[0-9]+)' ),
			'methods'             => [ 'GET', 'POST' ],
			'callback'            => [ $this, 'render_entry' ],
			'permission_callback' => '__return_true', // WP will handle the nonce and Entry_Renderer::render() will take care of permissions.
		];

		return $routes;
	}

	/**
	 * Modify attributes for the single or entry link to open in a lightbox.
	 *
	 * @used-by `gravityview_field_entry_link` filter.
	 *
	 * @since   TBD
	 *
	 * @param string $link           The entry link (HTML markup).
	 * @param string $href           The entry link URL.
	 * @param array  $entry          The entry data.
	 * @param array  $field_settings The Link to Single Entry field settings.
	 *
	 * @return string
	 */
	public function modify_entry_link( $link, $href, $entry, $field_settings ) {
		$view          = GravityView_View::getInstance();
		$rest_endpoint = $this->get_rest_endpoint( $view->view_id, $entry['id'] );
		$is_rest       = strpos( $_SERVER['REQUEST_URI'] ?? '', $rest_endpoint ) !== false;
		$is_edit_entry = 'edit_link' === ( $field_settings['id'] ?? '' );

		if ( ! (int) ( $field_settings['lightbox'] ?? 0 ) && ! $is_rest ) {
			return $link;
		}

		$args = [
			'_wpnonce' => wp_create_nonce( 'wp_rest' ),
		];

		if ( $is_edit_entry ) {
			$args['edit'] = wp_create_nonce( GravityView_Edit_Entry::get_nonce_key( $view->view_id, $view->form_id, $entry['id'] ) );
		}

		$href = add_query_arg(
			$args,
			rest_url( $rest_endpoint ),
		);

		$atts = [
			'class'         => ( $link_atts['class'] ?? '' ) . ' gravityview-fancybox',
			'rel'           => 'nofollow',
			'data-type'     => 'iframe',
			'data-fancybox' => $view->getCurrentField()['UID'],
		];

		return gravityview_get_link(
			$href,
			$is_edit_entry ? $field_settings['edit_link'] : $field_settings['entry_link_text'],
			$is_rest ? [] : $atts // Do not add the attributes if the link is being rendered in the REST context.
		);
	}

	/**
	 * Renders the single or edit entry view.
	 *
	 * @used-by `gk/foundation/rest/routes` filter.
	 *
	 * @since   TBD
	 *
	 * @param GravityView_Lightbox_Entry_Request $request The request object.
	 *
	 * @return void
	 */
	public function render_entry( $request ) {
		$view_id  = $request->get_param( 'view_id' ) ?? 0;
		$entry_id = $request->get_param( 'entry_id' ) ?? 0;

		$view      = View::by_id( $view_id );
		$entry     = GF_Entry::by_id( $entry_id );
		$form      = GVCommon::get_form( $entry['form_id'] ?? 0 );
		$is_edit   = $request->get_param( 'edit' ) ?? null;
		$is_delete = $request->get_param( 'delete' ) ?? null;

		if ( ! $view || ! $entry || ! $form ) {
			gravityview()->log->error( "Unable to find View ID {$view_id} and/or entry ID {$entry_id}." );

			return;
		}

		$view_data = GravityView_View_Data::getInstance();
		$view_data->add_view( $view->ID );

		if ( $is_edit && wp_verify_nonce( $is_edit, GravityView_Edit_Entry::get_nonce_key( $view->ID, $form['id'], $entry->ID ) ) ) {
			add_filter( 'gravityview/edit_entry/cancel_onclick', '__return_null' );
			add_filter( 'gravityview/edit_entry/verify_nonce', '__return_true' );

			add_filter( 'gravityview/view/links/directory', function () use ( $view, $entry ) {
				return rest_url( $this->get_rest_endpoint( $view->ID, $entry->ID ) );
			} );
		}

		ob_start();

		add_filter( 'gravityview_go_back_url', '__return_false' );

		if ( $is_delete ) {
			add_filter( 'wp_redirect', '__return_false' );

			do_action( 'wp' ); // Entry deletion hooks to the `wp` action.

			$reload_page     = GravityView_Delete_Entry::REDIRECT_TO_MULTIPLE_ENTRIES_VALUE === (int) $view->settings->get( 'delete_redirect' );
			$redirect_to_url = GravityView_Delete_Entry::REDIRECT_TO_URL_VALUE === (int) $view->settings->get( 'delete_redirect' );
			$redirect_url    = esc_url( $view->settings->get( 'delete_redirect_url', '' ) );

			?>
			<script type="text/javascript">
				window.parent.postMessage( {
					closeFancybox: true,
					reloadPage: <?php echo $reload_page ? 'true' : 'false'; ?>,
					redirectToUrl: '<?php echo $redirect_to_url ? $redirect_url : ''; ?>',
				} );
			</script>
			<?php

			return new WP_REST_Response( null, 200, [ 'Content-Type' => 'text/html' ] );
		}

		GravityView_frontend::getInstance()->setGvOutputData( $view_data );
		GravityView_frontend::getInstance()->add_scripts_and_styles();

		$title = do_shortcode(
			GravityView_API::replace_variables(
				$view->settings->get( 'single_title', '' ),
				$form,
				$entry
			)
		);

		$entry_renderer = $is_edit ? new Edit_Entry_Renderer() : new Entry_Renderer();

		$content = $entry_renderer->render(
			$entry,
			$view,
			new GravityView_Lightbox_Entry_Request( $view, $entry )
		);

		?>
		<html lang="<?php echo get_bloginfo( 'language' ); ?>">
			<head>
				<title><?php echo $title; ?></title>

				<?php wp_head(); ?>

				<style>
					<?php echo $view->settings->get( 'custom_css', '' ); ?>
				</style>

				<script type="text/javascript">
					<?php echo $view->settings->get( 'custom_javascript', '' ); ?>
				</script>
			</head>

			<body>
				<?php echo $content; ?>
			</body>

			<?php wp_print_scripts(); ?>
			<?php wp_print_styles(); ?>
		</html>
		<?php

		// Set the response content type and let WP take care of returning the buffered content.
		return new WP_REST_Response( null, 200, [ 'Content-Type' => 'text/html' ] );
	}

	/**
	 * Returns REST endpoint for specific View and entry.
	 *
	 * @since TBD
	 *
	 * @param int $view_id  The View object.
	 * @param int $entry_id The entry object.
	 *
	 * @return string
	 */
	public function get_rest_endpoint( $view_id, $entry_id ) {
		return self::REST_NAMESPACE . "/view/{$view_id}/entry/{$entry_id}";
	}

	/**
	 * Enqueues View editor script that handles the lightbox entry settings.
	 *
	 * @since TBD
	 *
	 * @param array $scripts The registered scripts.
	 *
	 * @return void
	 */
	public function enqueue_view_editor_script( $scripts ) {
		global $post;

		if ( ! $post || ( ! $post instanceof WP_Post && 'gravityview' !== $post->post_type && 'edit' !== $post->filter ) ) {
			return $scripts;
		}

		$scripts[] = [
			'script' => <<<JS
				// Disable "open in new tab/window" input when "open in lightbox" is checked, and vice versa.
				jQuery( document ).ready( function ( jQuery ) {
					function handleInputState( dialog ) {
						const newWindow = dialog.find( '.gv-setting-container-new_window input' );
						const lightbox = dialog.find( '.gv-setting-container-lightbox input' );
				
						function updateState( active, inactive ) {
							if ( active.is( ':checked' ) ) {
								inactive.prop( 'checked', false ).prop( 'disabled', true );
							} else {
								inactive.prop( 'disabled', false );
							}
						}
				
						updateState( newWindow, lightbox );
						updateState( lightbox, newWindow );
					}
					
					function handleChange( changedInput, otherInput ) {
						otherInput.prop( 'checked', false );

						// This is a workaround for the element not being disabled probably due to interference from other JS.
						setTimeout( function () {
							otherInput.prop( 'disabled', changedInput.is( ':checked' ) );
						}, 10 );
					}
					
					jQuery( document ).on( 'gravityview/dialog-opened', function ( event, thisDialog ) {
						const dialog = jQuery( thisDialog );
				
						handleInputState( dialog );
				
						const newWindow = dialog.find( '.gv-setting-container-new_window input' );
						const lightbox = dialog.find( '.gv-setting-container-lightbox input' );
				
						newWindow.on( 'change.lightboxEntry', function () {
							handleChange( jQuery( this ), lightbox );
						} );
				
						lightbox.on( 'change.lightboxEntry', function () {
							handleChange( jQuery( this ), newWindow );
						} );
					} );
				
					jQuery( document ).on( 'gravityview/dialog-closed', function ( event, thisDialog ) {
						jQuery( thisDialog ).find( '.gv-setting-container-new_window input, .gv-setting-container-lightbox input' )
							.off( 'change.lightboxEntry' );
					} );
				} );
JS,
			'deps'   => [ 'jquery' ],
		];

		return $scripts;
	}
}

new GravityView_Lightbox_Entry();
