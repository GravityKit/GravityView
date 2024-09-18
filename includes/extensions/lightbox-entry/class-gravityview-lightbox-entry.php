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
	const REST_NAMESPACE = 'gravityview';

	/**
	 * The REST version used for the single entry lightbox view.
	 *
	 * @since TBD
	 */
	const REST_VERSION = 1;

	/**
	 * Regex used to match the REST endpoint.
	 *
	 * @since TBD
	 */
	const REST_ENDPOINT_REGEX = 'view/(?P<view_id>[0-9]+)/entry/(?P<entry_id>[0-9]+)';

	/**
	 * Class constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		require_once 'class-gravityview-lightbox-entry-request.php';

		add_filter( 'gk/foundation/rest/routes', [ $this, 'register_rest_routes' ] );
		add_filter( 'gravityview_field_entry_link', [ $this, 'rewrite_entry_link' ], 10, 4 );
		add_filter( 'gk/foundation/inline-scripts', [ $this, 'enqueue_view_editor_script' ] );
		add_filter( 'gravityview/view/links/directory', [ $this, 'rewrite_directory_link' ] );
		add_filter( 'gform_get_form_confirmation_filter', [ $this, 'process_gravity_forms_form_submission' ] );
		add_filter( 'gform_get_form_filter', [ $this, 'process_gravity_forms_form_submission' ] );
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
			'namespace'           => self::REST_NAMESPACE,
			'version'             => self::REST_VERSION,
			'endpoint'            => self::REST_ENDPOINT_REGEX,
			'methods'             => [ 'GET', 'POST' ],
			'callback'            => [ $this, 'process_rest_request' ],
			'permission_callback' => '__return_true', // WP will handle the nonce and Entry_Renderer::render() will take care of permissions.
		];

		return $routes;
	}

	/**
	 * Processes the REST request by rendering the single or edit entry lightbox view, and handling delete and other actions.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function process_rest_request( $request ) {
		$view            = View::by_id( $request->get_param( 'view_id' ) ?? 0 );
		$entry           = GF_Entry::by_id( $request->get_param( 'entry_id' ) ?? 0 );
		$form            = GVCommon::get_form( $entry['form_id'] ?? 0 );
		$edit_nonce      = $request->get_param( 'edit' ) ?? null;
		$delete_nonce    = $request->get_param( 'delete' ) ?? null;
		$duplicate_nonce = $request->get_param( 'duplicate' ) ?? null;

		if ( ! $view || ! $entry || ! $form ) {
			gravityview()->log->error( "Unable to find View ID {$view->ID} and/or entry ID {$entry->ID}." );

			ob_start();

			printf( '<html>%s</html>', esc_html__( 'The requested entry could not be found.', 'gravityview' ) );

			return new WP_REST_Response( null, 404, [ 'Content-Type' => 'text/html' ] );
		}

		gravityview()->request = new GravityView_Lightbox_Entry_Request( $view, $entry );

		if ( $delete_nonce ) {
			return $this->process_delete_entry( $view );
		}

		if ( $duplicate_nonce ) {
			return $this->process_duplicate_entry();
		}

		if ( $edit_nonce ) {
			$this->process_edit_entry( $edit_nonce, $view, $entry, $form );
		}

		return $this->render_entry(
			$edit_nonce ? 'edit' : 'single',
			$view,
			$entry,
			$form
		);
	}

	/**
	 * Rewrites the directory link when inside the REST context.
	 *
	 * @since TBD
	 *
	 * @param string $link The directory link.
	 *
	 * @return string
	 */
	public function rewrite_directory_link( $link ) {
		if ( ! gravityview()->request instanceof GravityView_Lightbox_Entry_Request ) {
			return $link;
		}

		$view  = gravityview()->request->is_view();
		$entry = gravityview()->request->is_entry();

		if ( ! $view || ! $entry ) {
			return $link;
		}

		return $this->get_rest_directory_link( $view->ID, $entry->ID );
	}

	/**
	 * Returns REST directory link for specific View and entry.
	 *
	 * @return string
	 */
	public function get_rest_directory_link( $view_id, $entry_id ) {
		return add_query_arg(
			[ '_wpnonce' => wp_create_nonce( 'wp_rest' ) ],
			rest_url( $this->get_rest_endpoint( $view_id, $entry_id ) ),
		);
	}

	/**
	 * Returns REST endpoint for specific View and entry.
	 *
	 * @since TBD
	 *
	 * @param int $view_id  The View ID.
	 * @param int $entry_id The entry ID.
	 *
	 * @return string
	 */
	public function get_rest_endpoint( $view_id, $entry_id ) {
		return sprintf(
			'%s/v%s/view/%s/entry/%s',
			self::REST_NAMESPACE,
			self::REST_VERSION,
			$view_id,
			$entry_id
		);
	}

	/**
	 * Returns REST endpoint for specific View and entry.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_rest_endpoint_from_request() {
		preg_match(
			sprintf(
				'#%s/v%s/(?P<endpoint>%s)#',
				self::REST_NAMESPACE,
				self::REST_VERSION,
				self::REST_ENDPOINT_REGEX
			),
			urldecode( $_SERVER['REQUEST_URI'] ?? '' ),
			$matches
		);

		return $matches['endpoint'] ?? null;
	}

	/**
	 * Returns the View and entry IDs from the REST endpoint.
	 *
	 * @since TBD
	 *
	 * @param string $endpoint The REST endpoint.
	 *
	 * @return array{view_id:string, entry_id:string}|null
	 */
	public function get_view_and_entry_from_rest_endpoint( $endpoint ) {
		preg_match( self::REST_ENDPOINT_REGEX, $endpoint, $matches );

		return ! empty( $matches ) ? [ 'view_id' => $matches['view_id'], 'entry_id' => $matches['entry_id'] ] : null;
	}

	/**
	 * Rewrites Single or Edit Entry links to open inside lightbox.
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
	public function rewrite_entry_link( $link, $href, $entry, $field_settings ) {
		$view    = GravityView_View::getInstance();
		$is_rest = ! empty( $this->get_rest_endpoint_from_request() );
		$is_edit = 'edit_link' === ( $field_settings['id'] ?? '' );

		if ( ! (int) ( $field_settings['lightbox'] ?? 0 ) && ! $is_rest ) {
			return $link;
		}

		$directory_link = $this->get_rest_directory_link( $view->view_id, $entry['id'] );

		if ( $is_edit ) {
			$directory_link = add_query_arg(
				[
					'edit' => wp_create_nonce(
						GravityView_Edit_Entry::get_nonce_key(
							$view->view_id,
							$view->form_id,
							$entry['id']
						)
					),
				],
				$directory_link,
			);
		}

		$atts = [
			'class'         => ( $link_atts['class'] ?? '' ) . ' gravityview-fancybox',
			'rel'           => 'nofollow',
			'data-type'     => 'iframe',
			'data-fancybox' => $view->getCurrentField()['UID'],
		];

		if ( in_array( $field_settings['id'], [ 'edit_link', 'entry_link' ] ) ) {
			$link_text = $is_edit ? $field_settings['edit_link'] : $field_settings['entry_link_text'];
		} else {
			// This sets the text for entry values that link to the Single Entry.
			$link_text = preg_match( '/<a[^>]*>(.*?)<\/a>/', $link, $matches ) ? $matches[1] : '';
		}

		return gravityview_get_link(
			$directory_link,
			$link_text,
			$is_rest ? [] : $atts // Do not add the attributes if the link is being rendered in the REST context.
		);
	}

	/**
	 * Configures the necessary logic to process the edit entry request.
	 *
	 * @since TBD
	 *
	 * @param string   $nonce The edit entry nonce.
	 * @param View     $view  The View object.
	 * @param GF_Entry $entry The entry object.
	 * @param array    $form  The form data.
	 *
	 * @return void
	 */
	private function process_edit_entry( $nonce, $view, $entry, $form ) {
		if ( ! wp_verify_nonce( $nonce, GravityView_Edit_Entry::get_nonce_key( $view->ID, $form['id'], $entry->ID ) ) ) {
			return;
		}

		add_filter( 'gravityview/edit_entry/verify_nonce', '__return_true' );
		add_filter( 'gravityview/edit_entry/cancel_onclick', '__return_empty_string' );
		add_filter( 'gravityview/view/links/directory', function () use ( $view, $entry ) {
			return rest_url( $this->get_rest_endpoint( $view->ID, $entry->ID ) );
		} );

		// Prevent redirection inside the lightbox by sending event to the parent window and hiding the success message.
		if ( ! in_array( $view->settings->get( 'edit_redirect' ), [ '1', '2' ] ) ) {
			return;
		}

		$reload_page     = 1 === (int) $view->settings->get( 'edit_redirect' ) ? 'true' : 'false';
		$redirect_to_url = 2 === (int) $view->settings->get( 'edit_redirect' ) ? esc_url( $view->settings->get( 'edit_redirect_url', '' ) ) : '';

		add_filter( 'gravityview/edit_entry/success', function ( $message ) use ( $view, $reload_page, $redirect_to_url ) {
			return <<<JS
				<style>.gv-notice { display: none; }</style>
				<script>
					window.parent.postMessage( {
					    removeHash: {$reload_page},
						reloadPage: {$reload_page},
						redirectToUrl: '{$redirect_to_url}',
					} );
				</script>
			JS;
		} );
	}

	/**
	 * Processes the delete entry action.
	 *
	 * @since TBD
	 *
	 * @param View $view The View object.
	 *
	 * @return WP_REST_Response
	 */
	private function process_delete_entry( $view ) {
		add_filter( 'wp_redirect', '__return_false' ); // Prevent redirection after the entry is deleted.

		do_action( 'wp' ); // Entry deletion hooks to the `wp` action.

		$reload_page     = GravityView_Delete_Entry::REDIRECT_TO_MULTIPLE_ENTRIES_VALUE === (int) $view->settings->get( 'delete_redirect' ) ? 'true' : 'false';
		$redirect_to_url = GravityView_Delete_Entry::REDIRECT_TO_URL_VALUE === (int) $view->settings->get( 'delete_redirect' ) ? esc_url( $view->settings->get( 'delete_redirect_url', '' ) ) : '';

		ob_start();

		echo <<<JS
			<style>.gv-notice { display: none; }</style>
			<script>
				window.parent.postMessage( { 
					closeFancybox: true,
					reloadPage: {$reload_page},
					redirectToUrl: '{$redirect_to_url}',
				} );
			</script>
		JS;

		return new WP_REST_Response(
			null,
			200,
			[ 'Content-Type' => 'text/html' ]
		);
	}

	/**
	 * Processes the duplicate entry action.
	 *
	 * @since TBD
	 *
	 * @return WP_REST_Response
	 */
	private function process_duplicate_entry() {
		add_filter( 'wp_redirect', '__return_false' ); // Prevent redirection after the entry is duplicated.

		( GravityView_Duplicate_Entry::getInstance() )->process_duplicate();

		ob_start();

		echo <<<JS
			<script>
				window.parent.postMessage( {
					closeFancybox: true,
					reloadPage: true,
				} );
			</script>
		JS;

		return new WP_REST_Response(
			null,
			200,
			[ 'Content-Type' => 'text/html' ]
		);
	}

	/**
	 * Sets headers for Gravity Forms form submissions.
	 *
	 * @used-by `gform_get_form_confirmation_filter` filter.
	 *
	 * @since   TBD
	 *
	 * @param string $response The form submission response.
	 *
	 * @return string
	 */
	public function process_gravity_forms_form_submission( $response ) {
		$rest_endpoint = $this->get_rest_endpoint_from_request();

		if ( 1 === (int) ( $_REQUEST['gform_submit'] ?? 0 ) && $rest_endpoint ) {
			header( 'Content-Type: text/html' );
		}

		return $response;
	}

	/**
	 * Renders the single or edit entry lightbox view.
	 *
	 * @used-by `gk/foundation/rest/routes` filter.
	 *
	 * @since   TBD
	 *
	 * @param string   $type  The type of the entry view (single or edit).
	 * @param View     $view  The View object.
	 * @param GF_Entry $entry The entry data.
	 * @param array    $form  The form data.
	 *
	 * @return WP_REST_Response
	 */
	private function render_entry( $type, $view, $entry, $form ) {
		add_filter( 'gravityview_go_back_url', '__return_false' );

		$view_data = GravityView_View_Data::getInstance();
		$view_data->add_view( $view->ID );

		GravityView_frontend::getInstance()->setGvOutputData( $view_data );
		GravityView_frontend::getInstance()->add_scripts_and_styles();

		$entry_renderer = 'edit' === $type ? new Edit_Entry_Renderer() : new Entry_Renderer();

		do_action( 'wp' );

		do_action( 'wp_enqueue_scripts' );

		ob_start();

		$title = do_shortcode(
			GravityView_API::replace_variables(
				$view->settings->get( 'single_title', '' ),
				$form,
				$entry
			)
		);

		$content = $entry_renderer->render(
			$entry,
			$view,
			gravityview()->request,
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

		return new WP_REST_Response(
			null,
			200,
			[ 'Content-Type' => 'text/html' ]
		);
	}

	/**
	 * Enqueues View editor script that handles the lightbox entry settings.
	 *
	 * @since TBD
	 *
	 * @param array $scripts The registered scripts.
	 *
	 * @return array
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
