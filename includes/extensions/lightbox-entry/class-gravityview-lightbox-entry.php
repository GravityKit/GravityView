<?php

use GV\Edit_Entry_Renderer;
use GV\Entry_Renderer;
use GV\GF_Entry;
use GV\Multi_Entry;
use GV\Template_Context;
use GV\View;

class GravityView_Lightbox_Entry {
	/**
	 * The REST namespace used for the single entry lightbox view.
	 *
	 * @since 2.29.0
	 */
	const REST_NAMESPACE = 'gravityview';

	/**
	 * The REST version used for the single entry lightbox view.
	 *
	 * @since 2.29.0
	 */
	const REST_VERSION = 1;

	/**
	 * Regex used to match the REST endpoint.
	 *
	 * @since 2.29.0
	 */
	const REST_ENDPOINT_REGEX = 'view/(?P<view_id>[0-9]+)/entry/(?P<entry_ids>[0-9,]+)';

	/**
	 * Class constructor.
	 *
	 * @since 2.29.0
	 */
	public function __construct() {
		require_once 'class-gravityview-lightbox-entry-request.php';

		add_filter( 'gravityview/template/before', [ $this, 'maybe_enable_lightbox' ] );
		add_filter( 'gk/foundation/rest/routes', [ $this, 'register_rest_routes' ] );
		add_filter( 'gravityview/template/field/entry_link', [ $this, 'rewrite_entry_link' ], 10, 3 );
		add_filter( 'gk/foundation/inline-scripts', [ $this, 'enqueue_view_editor_script' ] );
		add_filter( 'gravityview/view/links/directory', [ $this, 'rewrite_directory_link' ] );
		add_filter( 'gform_get_form_confirmation_filter', [ $this, 'process_gravity_forms_form_submission' ] );
		add_filter( 'gform_get_form_filter', [ $this, 'process_gravity_forms_form_submission' ] );
		add_filter( 'gk/gravityview/lightbox/entry/output/head-after', [ $this, 'run_during_head_output' ], 10, 2 );
	}

	/**
	 * Enables lightbox when it's not explicitly enabled in the View settings but a field is configured to use it.
	 *
	 * @used-by `gravityview/template/before` filter.
	 *
	 * @since   2.29.0
	 *
	 * @param Template_Context $context
	 *
	 * @return void
	 */
	public function maybe_enable_lightbox( $context ) {
		if ( $context->view->settings->get( 'lightbox' ) ) {
			return;
		}

		foreach ( $context->view->fields->all() as $field ) {
			if ( (int) ( $field->as_configuration()['lightbox'] ?? 0 ) ) {
				$context->view->settings->set( 'lightbox', 1 );

				break;
			}
		}
	}

	/**
	 * Registers the REST route for the single entry lightbox view.
	 *
	 * @used-by `gk/foundation/rest/routes` filter.
	 *
	 * @since   2.29.0
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
	 * @since 2.29.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function process_rest_request( $request ) {
		$entry_ids = $request->get_param( 'entry_ids' ) ?? '';
		$entries   = [];

		foreach ( explode( ',', $entry_ids ) as $entry_id ) {
			$_entry = GF_Entry::by_id( $entry_id );

			if ( ! $_entry ) {
				continue;
			}

			$entries[] = $_entry;
		}

		$entry            = ! empty( $entries ) ? reset( $entries ) : null;
		$multiple_entries = count( $entries ) > 1 ? Multi_Entry::from_entries( $entries ) : null;
		$view             = View::by_id( $request->get_param( 'view_id' ) ?? 0 );
		$form             = GVCommon::get_form( $view->form->ID ?? 0 );
		$edit_nonce       = $request->get_param( 'edit' ) ?? null;
		$delete_nonce     = $request->get_param( 'delete' ) ?? null;
		$duplicate_nonce  = $request->get_param( 'duplicate' ) ?? null;

		if ( ! $view || ! $entry || ! $form ) {
			gravityview()->log->error( 'Unable to find View, entry or form.' );

			ob_start();

			printf( '<html>%s</html>', esc_html__( 'The requested entry could not be found.', 'gk-gravityview' ) );

			return new WP_REST_Response( null, 404, [ 'Content-Type' => 'text/html' ] );
		}

		gravityview()->request = new GravityView_Lightbox_Entry_Request( $view, $entry );

		if ( $delete_nonce ) {
			return $this->process_delete_entry( $view, $entry, $form );
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
			$multiple_entries ?? $entry,
			$form
		);
	}

	/**
	 * Rewrites the directory link when inside the REST context.
	 *
	 * @used-by `gravityview/view/links/directory` filter.
	 *
	 * @since   2.29.0
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
	 * @since 2.9.0
	 *
	 * @param int    $view_id   The View ID.
	 * @param string $entry_ids The entry IDs (comma-separated).
	 *
	 * @return string
	 */
	public function get_rest_directory_link( $view_id, $entry_ids ) {
		return add_query_arg(
			[ '_wpnonce' => wp_create_nonce( 'wp_rest' ) ],
			rest_url( $this->get_rest_endpoint( $view_id, $entry_ids ) ),
		);
	}

	/**
	 * Returns REST endpoint for specific View and entry.
	 *
	 * @since 2.29.0
	 *
	 * @param int    $view_id   The View ID.
	 * @param string $entry_ids The entry IDs (comma-separated).
	 *
	 * @return string
	 */
	public function get_rest_endpoint( $view_id, $entry_ids ) {
		return sprintf(
			'%s/v%s/view/%s/entry/%s',
			self::REST_NAMESPACE,
			self::REST_VERSION,
			$view_id,
			$entry_ids
		);
	}

	/**
	 * Returns REST endpoint from the current request.
	 *
	 * @since 2.29.0
	 *
	 * @return string|null
	 */
	public function get_rest_endpoint_from_request() {
		global $wp;

		preg_match(
			sprintf(
				'#%s/v%s/(?P<endpoint>%s)#',
				self::REST_NAMESPACE,
				self::REST_VERSION,
				self::REST_ENDPOINT_REGEX
			),
			$wp->query_vars['rest_route'] ?? '',
			$matches
		);

		return $matches['endpoint'] ?? null;
	}

	/**
	 * Returns the View and entry IDs from the REST endpoint.
	 *
	 * @since 2.29.0
	 *
	 * @param string $endpoint The REST endpoint.
	 *
	 * @return array{view_id:string, entry_ids:string}|null
	 */
	public function get_view_and_entry_from_rest_endpoint( $endpoint ) {
		preg_match( self::REST_ENDPOINT_REGEX, $endpoint, $matches );

		return ! empty( $matches ) ? [ 'view_id' => $matches['view_id'], 'entry_ids' => $matches['entry_ids'] ] : null;
	}

	/**
	 * Rewrites Single or Edit Entry links to open inside lightbox.
	 *
	 * @used-by `gravityview/template/field/entry_link` filter.
	 *
	 * @since   2.29.0
	 * @since   2.36.0 Switched to using `gravityview/template/field/entry_link` filter, and updated method parameters.
	 *
	 * @param string           $link    The entry link (HTML markup).
	 * @param string           $href    The entry link URL.
	 * @param Template_Context $context The context
	 *
	 * @return string
	 */
	public function rewrite_entry_link( $link, $href, $context ) {
		$view           = GravityView_View::getInstance();
		$entry          = $context->entry->as_entry();
		$field_settings = $context->field->as_configuration();
		$is_rest        = ! empty( $this->get_rest_endpoint_from_request() );
		$is_edit        = 'edit_link' === ( $field_settings['id'] ?? '' );

		if ( ! (int) ( $field_settings['lightbox'] ?? 0 ) && ! $is_rest ) {
			return $link;
		}

		$entry_ids = $context->entry->is_multi() ? array_map( fn( $entry ) => $entry->ID, $context->entry->entries ) : [ $entry['id'] ];

		$directory_link = $this->get_rest_directory_link( $view->view_id, implode( ',', $entry_ids ) );

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
	 * @since 2.29.0
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

		add_filter( 'gk/gravityview/edit-entry/renderer/enqueue-entry-lock-assets', '__return_true' );

		add_filter( 'gravityview/edit_entry/verify_nonce', '__return_true' );

		add_filter( 'gravityview/edit_entry/cancel_onclick', function () use ( $view ) {
			if ( 'close_lightbox' === $view->settings->get( 'edit_cancel_lightbox_action' ) ) {
				return 'window.parent.postMessage( { closeFancybox: true, } );';
			} else {
				return '';
			}
		} );

		// Updates the GF entry lock UI markup to properly handle requests for accepting the release or taking over the edit lock.
		add_filter( 'gk/gravityview/edit-entry/renderer/entry-lock-dialog-markup', function ( $markup ) {
			// To accept the release, we do an Ajax GET request by passing "release-edit-lock=1" and then close the lightbox.
			$markup = str_replace(
				'id="gform-release-lock-button"',
				'id="gform-release-lock-button" onclick="event.preventDefault(); jQuery.ajax({ url: window.location.href, data: { \'release-edit-lock\': 1 }, method: \'GET\', dataType: \'html\' }).done(function() { window.parent.postMessage({ closeFancybox: true }); });"',
				$markup
			);

			// To take over once the release has been accepted, we do an Ajax GET request by passing "get-edit-lock=1" and then close the GF lock dialog window.
			$markup = str_replace(
				'id="gform-take-over-button"',
				'id="gform-take-over-button" onclick="event.preventDefault(); jQuery.ajax({ url: window.location.href, data: { \'get-edit-lock\': 1 }, method: \'GET\', dataType: \'html\' }).done(function() { jQuery( \'#gform-lock-dialog\' ).hide(); });"',
				$markup
			);

			return $markup;
		} );

		// Prevent redirection inside the lightbox by sending event to the parent window and hiding the success message.
		if ( ! in_array( $view->settings->get( 'edit_redirect' ), [ '1', '2' ] ) ) {
			return;
		}

		$reload_page     = 1 === (int) $view->settings->get( 'edit_redirect' ) ? 'true' : 'false';
		$redirect_to_url = 2 === (int) $view->settings->get( 'edit_redirect' ) ? $view->settings->get( 'edit_redirect_url', '' ) : '';

		if ( $redirect_to_url ) {
			$redirect_to_url = esc_url( GravityView_API::replace_variables( $redirect_to_url, $form, $entry->as_entry() ) );
		}

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
	 * @since 2.29.0
	 * @since 2.33 Added $entry and $form parameters.
	 *
	 * @param View     $view  The View object.
	 * @param GF_Entry $entry The entry object.
	 * @param array    $form  The form data.
	 *
	 * @return WP_REST_Response
	 */
	private function process_delete_entry( $view, $entry, $form ) {
		global $wp;

		add_filter( 'wp_redirect', '__return_false' ); // Prevent redirection after the entry is deleted.

		do_action_ref_array( 'wp', [ $wp ] ); // Entry deletion hooks to the `wp` action.

		$reload_page     = GravityView_Delete_Entry::REDIRECT_TO_MULTIPLE_ENTRIES_VALUE === (int) $view->settings->get( 'delete_redirect' ) ? 'true' : 'false';
		$redirect_to_url = GravityView_Delete_Entry::REDIRECT_TO_URL_VALUE === (int) $view->settings->get( 'delete_redirect' ) ? esc_url( $view->settings->get( 'delete_redirect_url', '' ) ) : '';

		if ( $redirect_to_url ) {
			$redirect_to_url = esc_url( GravityView_API::replace_variables( $redirect_to_url, $form, $entry->as_entry() ) );
		}

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
	 * @since 2.29.0
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
	 * Sets headers for Gravity Forms form submission.
	 *
	 * @used-by `gform_get_form_confirmation_filter` filter.
	 *
	 * @since   2.29.0
	 *
	 * @param string $response The form submission response.
	 *
	 * @return string
	 */
	public function process_gravity_forms_form_submission( $response ) {
		$rest_endpoint = $this->get_rest_endpoint_from_request();

		if ( array_key_exists( 'gform_submit', $_REQUEST ) && $rest_endpoint ) {
			header( 'Content-Type: text/html' );
		}

		return $response;
	}

	/**
	 * Renders the single or edit entry lightbox view.
	 *
	 * @since   2.29.0
	 *
	 * @param string               $type  The type of the entry view (single or edit).
	 * @param View                 $view  The View object.
	 * @param Multi_Entry|GF_Entry $entry The entry data.
	 * @param array                $form  The form data.
	 *
	 * @return WP_REST_Response
	 */
	private function render_entry( $type, $view, $entry, $form ) {
		global $wp;
		global $post;

		$post = $post ?? get_post( $view->ID );

		add_filter( 'gravityview_go_back_url', '__return_false' );

		$view_data = GravityView_View_Data::getInstance();
		$view_data->add_view( $view->ID );

		GravityView_frontend::getInstance()->setGvOutputData( $view_data );
		GravityView_frontend::getInstance()->add_scripts_and_styles();

		$entry_renderer = 'edit' === $type ? new Edit_Entry_Renderer() : new Entry_Renderer();

		do_action_ref_array( 'wp', [ $wp ] );

		/**
		 * Fires before rendering the lightbox entry view.
		 *
		 * @action `gk/gravityview/lightbox/entry/before-output`
		 *
		 * @since  2.31.0
		 *
		 * @param array       $args           {
		 *
		 * @type View         $view           The View object being rendered.
		 * @type GF_Entry     $entry          The Gravity Forms entry data.
		 * @type array        $form           The Gravity Forms form array.
		 * @type Entry_Render $entry_renderer The renderer object responsible for rendering the entry.
		 *                                    }
		 */
		do_action_ref_array( 'gk/gravityview/lightbox/entry/before-output', [ &$view, &$entry, &$form, &$entry_renderer ] );

		ob_start();

		$title = do_shortcode(
			GravityView_API::replace_variables(
				$view->settings->get( 'single_title', '' ),
				$form,
				$entry->as_entry()
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
				<?php
				/**
				 * Fires after <head> tag.
				 *
				 * @action `gk/gravityview/lightbox/entry/output/head-before`
				 *
				 * @since  2.31.0
				 *
				 * @param string   $type  The type of the entry view (single or edit).
				 * @param View     $view  The View object being rendered.
				 * @param GF_Entry $entry The Gravity Forms entry data.
				 * @param array    $form  The Gravity Forms form array.
				 */
				do_action( 'gk/gravityview/lightbox/entry/output/head-before', $type, $view, $entry, $form );
				?>

				<title><?php echo $title; ?></title>

				<?php wp_head(); ?>

				<style>
					<?php echo $view->settings->get( 'custom_css', '' ); ?>
				</style>

				<script type="text/javascript">
					<?php echo $view->settings->get( 'custom_javascript', '' ); ?>
				</script>

				<?php
				/**
				 * Fires before </head> tag.
				 *
				 * @action `gk/gravityview/lightbox/entry/output/head-after`
				 *
				 * @since  2.31.0
				 *
				 * @param string   $type  The type of the entry view (single or edit).
				 * @param View     $view  The View object being rendered.
				 * @param GF_Entry $entry The Gravity Forms entry data.
				 * @param array    $form  The Gravity Forms form array.
				 */
				do_action( 'gk/gravityview/lightbox/entry/output/head-after', $type, $view, $entry, $form );
				?>
			</head>

			<body>
				<?php
				/**
				 * Fires after <body> tag before the content is rendered.
				 *
				 * @action `gk/gravityview/lightbox/entry/output/content-before`
				 *
				 * @since  2.31.0
				 *
				 * @param string   $type  The type of the entry view (single or edit).
				 * @param View     $view  The View object being rendered.
				 * @param GF_Entry $entry The Gravity Forms entry data.
				 * @param array    $form  The Gravity Forms form array.
				 */
				do_action( 'gk/gravityview/lightbox/entry/output/content-before', $type, $view, $entry, $form );
				?>

				<?php echo $content; ?>

				<?php
				/**
				 * Fires inside the <body> tag after the content is rendered and before the footer.
				 *
				 * @action `gk/gravityview/lightbox/entry/output/content-after`
				 *
				 * @since  2.31.0
				 *
				 * @param string   $type  The type of the entry view (single or edit).
				 * @param View     $view  The View object being rendered.
				 * @param GF_Entry $entry The Gravity Forms entry data.
				 * @param array    $form  The Gravity Forms form array.
				 */
				do_action( 'gk/gravityview/lightbox/entry/output/content-after', $type, $view, $entry, $form );
				?>

				<?php wp_footer(); ?>

				<?php
				/**
				 * Fires after the footer and before the closing </body> tag.
				 *
				 * @action `gk/gravityview/lightbox/entry/output/footer-after`
				 *
				 * @since  2.31.0
				 *
				 * @param string   $type  The type of the entry view (single or edit).
				 * @param View     $view  The View object being rendered.
				 * @param GF_Entry $entry The Gravity Forms entry data.
				 * @param array    $form  The Gravity Forms form array.
				 */
				do_action( 'gk/gravityview/lightbox/entry/output/footer-after', $type, $view, $entry, $form );
				?>
			</body>
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
	 * @used-by `gk/foundation/inline-scripts` filter.
	 *
	 * @since   2.29.0
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
				jQuery( document ).ready( function ( jQuery ) {
					// Show/hide the View's "Cancel Link Action" setting under Edit Entry.
					function toggleCancelLinkActionViewSetting() {
						const isLightBoxEnabled = jQuery( 'input[type="checkbox"]' )
							.filter( function () {
								return /^fields\[directory_table-columns\]\[.*?\]\[lightbox\]$/.test( jQuery( this ).attr( 'name' ) || '' );
							} )
							.toArray()
							.some( checkbox => checkbox.checked );

						jQuery( 'tr:has(#gravityview_se_edit_cancel_lightbox_action)' ).toggle( isLightBoxEnabled );
					}

					// Disable "open in new tab/window" input when "open in lightbox" is checked, and vice versa.
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
						jQuery( thisDialog )
							.find( '.gv-setting-container-new_window input, .gv-setting-container-lightbox input' )
							.off( 'change.lightboxEntry' );

						toggleCancelLinkActionViewSetting();
					} );

					toggleCancelLinkActionViewSetting();
				} );
			JS,
			'deps'   => [ 'jquery' ],
		];

		return $scripts;
	}

	/**
	 * Performs actions during <head> output.
	 *
	 * @since 2.31.0
	 *
	 * @param string $type The type of the entry view (single or edit).
	 * @param View   $view The View object being rendered.
	 *
	 * @return void
	 */
	public function run_during_head_output( $type, $view ) {
		// Enqueue scripts for the Entry Notes field.
		if ( 'single' !== $type ) {
			return;
		}

		foreach ( $view->fields->all() as $field ) {
			if ( 'notes' === $field->type ) {
				do_action( 'gravityview/field/notes/scripts' );

				break;
			}
		}
	}
}

new GravityView_Lightbox_Entry();
