<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * oEmbed functionality for GravityView
 */
class oEmbed {
	public static $provider_url = '';

	/**
	 * Initialize.
	 *
	 * Register the oEmbed handler and the provider.
	 * Fire off the provider handler if detected.
	 *
	 * @return void
	 */
	public static function init() {
		self::$provider_url = add_query_arg( 'gv_oembed_provider', '1', site_url() );

		wp_embed_register_handler( 'gravityview_entry', self::get_entry_regex(), array( __CLASS__, 'render' ), 20000 );
		wp_oembed_add_provider( self::get_entry_regex(), self::$provider_url, true );

		if ( ! empty( $_GET['gv_oembed_provider'] ) && ! empty( $_GET['url'] ) ) {
			add_action( 'template_redirect', array( __CLASS__, 'render_provider_request' ) );
		}

		add_action( 'pre_oembed_result', array( __CLASS__, 'pre_oembed_result' ), 11, 3 );
	}

	/**
	 * Output a response as a provider for an entry oEmbed URL.
	 *
	 * For now we only output the JSON format and don't care about the size (width, height).
	 * Our only current use-case is for it to provide output to the Add Media / From URL box
	 *  in WordPress 4.8.
	 *
	 * @return void
	 */
	public static function render_provider_request() {
		if ( ! empty( $_GET['url'] ) ) {
			$url = $_GET['url'];
		} else {
			header( 'HTTP/1.0 404 Not Found' );
			exit;
		}

		/** Parse the URL to an entry and a view */
		preg_match( self::get_entry_regex(), $url, $matches );
		$result = self::parse_matches( $matches, $url );
		if ( ! $result || 2 != count( $result ) ) {
			header( 'HTTP/1.0 404 Not Found' );
			exit;
		}

		list( $view, $entry ) = $result;

		echo json_encode(
			array(
				'version'       => '1.0',
				'provider_name' => 'gravityview',
				'provider_url'  => self::$provider_url,
				'html'          => self::render_preview_notice() . self::render_frontend( $view, $entry ),
			)
		);
		exit;
	}

	/**
	 * Output the embed HTML.
	 *
	 * @param array  $matches The regex matches from the provided regex when calling wp_embed_register_handler()
	 * @param array  $attr Embed attributes.
	 * @param string $url The original URL that was matched by the regex.
	 * @param array  $rawattr The original unmodified attributes.
	 *
	 * @return string The embed HTML.
	 */
	public static function render( $matches, $attr, $url, $rawattr ) {

		$result = self::parse_matches( $matches, $url );

		if ( ! $result || 2 != count( $result ) ) {
			gravityview()->log->notice(
				'View or entry could not be parsed in oEmbed url {url}',
				array(
					'url'     => $url,
					'matches' => $matches,
				)
			);
			return __( 'You are not allowed to view this content.', 'gk-gravityview' );
		}

		list( $view, $entry ) = $result;

		if ( Request::is_ajax() && ! Request::is_add_oembed_preview() ) {
			/** Render a nice placeholder in the Visual mode. */
			return self::render_admin( $view, $entry );
		} elseif ( Request::is_add_oembed_preview() ) {
			/** Prepend a preview notice in Add Media / From URL screen */
			return self::render_preview_notice() . self::render_frontend( $view, $entry );
		}

		return self::render_frontend( $view, $entry );
	}

	/**
	 * Parse oEmbed regex matches and return View and Entry.
	 *
	 * @param array  $matches The regex matches.
	 * @param string $url The URL of the embed.
	 *
	 * @return array (\GV\View, \GV\Entry)
	 */
	private static function parse_matches( $matches, $url ) {
		// If not using permalinks, re-assign values for matching groups
		if ( ! empty( $matches['entry_slug2'] ) ) {
			$matches['is_cpt']     = $matches['is_cpt2'];
			$matches['slug']       = $matches['slug2'];
			$matches['entry_slug'] = $matches['entry_slug2'];
			unset( $matches['is_cpt2'], $matches['slug2'], $matches['entry_slug2'] );
		}

		if ( empty( $matches['entry_slug'] ) ) {
			gravityview()->log->error( 'Entry slug not parsed by regex.', array( 'data' => $matches ) );
			return null;
		} else {
			$entry_id = $matches['entry_slug'];
		}

		if ( ! $entry = \GV\GF_Entry::by_id( $entry_id ) ) {
			gravityview()->log->error( 'Invalid entry ID {entry_id}', array( 'entry_id' => $entry_id ) );
			return null;
		}

		$view = null;

		if ( $view_id = url_to_postid( $url ) ) {
			$view = \GV\View::by_id( $view_id );
		}

		// Maybe it failed to find a GravityView CPT
		if ( ! $view ) {

			// If the slug doesn't work, maybe using Plain permalinks and not the slug, only ID
			if ( is_numeric( $matches['slug'] ) ) {
				$view = \GV\View::by_id( $matches['slug'] );
			}

			if ( ! $view ) {
				$view = \GV\View::from_post( get_page_by_path( $matches['slug'], OBJECT, 'gravityview' ) );
			}
		}

		if ( ! $view ) {
			gravityview()->log->error(
				'Could not detect View from URL {url}',
				array(
					'url'  => $url,
					'data' => $matches,
				)
			);
			return null;
		}

		return array( $view, $entry );
	}

	/**
	 * Display a nice placeholder in the admin for the entry.
	 *
	 * @param \GV\View  $view The View.
	 * @param \GV\Entry $entry The Entry.
	 *
	 * @return string A placeholder, with Mr. Floaty :)
	 */
	private static function render_admin( $view, $entry ) {

		// Floaty the astronaut
		$image = \GravityView_Admin::get_floaty();

		$embed_heading = sprintf( esc_html__( 'Embed Entry %d', 'gk-gravityview' ), $entry->ID );

		$embed_text = sprintf( esc_html__( 'This entry will be displayed as it is configured in View %d', 'gk-gravityview' ), $view->ID );

		return '
		<div class="loading-placeholder" style="background-color:#e6f0f5;">
			<h3 style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen-Sans, Ubuntu, Cantarell, \'Helvetica Neue\', sans-serif;">' . $image . $embed_heading . '</h3>
			<p style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen-Sans, Ubuntu, Cantarell, \'Helvetica Neue\', sans-serif;">
				' . $embed_text . '
			</p>
			<br style="clear: both;">
		</div>';
	}

	/**
	 * Generate a warning to users when previewing oEmbed in the Add Media modal.
	 *
	 * @return string HTML notice
	 */
	private static function render_preview_notice() {
		$floaty  = \GravityView_Admin::get_floaty();
		$title   = esc_html__( 'This will look better when it is embedded.', 'gk-gravityview' );
		$message = esc_html__( 'Styles don\'t get loaded when being previewed, so the content below will look strange. Don\'t be concerned!', 'gk-gravityview' );
		return '<div class="updated notice">' . $floaty . '<h3>' . $title . '</h3><p>' . $message . '</p><br style="clear:both;" /></div>';
	}

	/**
	 * Render the entry as an oEmbed.
	 *
	 * @param \GV\View  $view The View.
	 * @param \GV\Entry $entry The Entry.
	 *
	 * @return string The rendered oEmbed.
	 */
	private static function render_frontend( $view, $entry ) {

		// We do not give a hint that this content exists, for security purposes.
		if ( 'trash' === get_post_status( $view->ID ) ) {
			return '';
		}

		/** Private, pending, draft, etc. */
		$public_states = get_post_stati( array( 'public' => true ) );
		if ( ! in_array( $view->post_status, $public_states ) && ! \GVCommon::has_cap( 'read_gravityview', $view->ID ) ) {
			gravityview()->log->notice( 'The current user cannot access this View #{view_id}', array( 'view_id' => $view->ID ) );
			return __( 'You are not allowed to view this content.', 'gk-gravityview' );
		}

		if ( $entry && 'active' !== $entry['status'] ) {
			gravityview()->log->notice( 'Entry ID #{entry_id} is not active', array( 'entry_id' => $entry->ID ) );
			return __( 'You are not allowed to view this content.', 'gk-gravityview' );
		}

		if ( $view->settings->get( 'show_only_approved' ) ) {
			if ( ! \GravityView_Entry_Approval_Status::is_approved( gform_get_meta( $entry->ID, \GravityView_Entry_Approval::meta_key ) ) ) {
				gravityview()->log->error( 'Entry ID #{entry_id} is not approved for viewing', array( 'entry_id' => $entry->ID ) );
				return __( 'You are not allowed to view this content.', 'gk-gravityview' );
			}
		}

		/**
		 * When this is embedded inside a view we should not display the widgets.
		 */
		$request       = gravityview()->request;
		$is_reembedded = false; // Assume not embedded unless detected otherwise.
		if ( in_array( get_class( $request ), array( 'GV\Frontend_Request', 'GV\Mock_Request' ) ) ) {
			if ( ( $_view = $request->is_view() ) && $_view->ID !== $view->ID ) {
				$is_reembedded = true;
			}
		}

		/**
		 * Remove Widgets on a nested embedded View.
		 * Also, don't show widgets if we're embedding an entry
		 */
		if ( $is_reembedded || $entry ) {
			$view->widgets = new \GV\Widget_Collection();
		}

		if ( $request->is_edit_entry() ) {
			/**
			 * Based on code in our unit-tests.
			 * Mocks old context, etc.
			 */
			$loader = \GravityView_Edit_Entry::getInstance();
			$render = $loader->instances['render'];

			$form = \GVCommon::get_form( $entry['form_id'] );

			// @todo We really need to rewrite Edit Entry soon
			\GravityView_View::$instance      = null;
			\GravityView_View_Data::$instance = null;

			$data     = \GravityView_View_Data::getInstance( get_post( $view->ID ) );
			$template = \GravityView_View::getInstance(
				array(
					'form'    => $form,
					'form_id' => $form['id'],
					'view_id' => $view->ID,
					'entries' => array( $entry->as_entry() ),
					'atts'    => \GVCommon::get_template_settings( $view->ID ),
				)
			);

			ob_start() && $render->init( $data, \GV\Entry::by_id( $entry['id'] ), $view );
			$output = ob_get_clean(); // Render :)
		} else {
			/** Remove the back link. */
			add_filter( 'gravityview/template/links/back/url', '__return_false' );

			$renderer = new \GV\Entry_Renderer();
			$output   = $renderer->render( $entry, $view, gravityview()->request );
			$output   = sprintf( '<div class="gravityview-oembed gravityview-oembed-entry gravityview-oembed-entry-%d">%s</div>', $entry->ID, $output );

			remove_filter( 'gravityview/template/links/back/url', '__return_false' );
		}

		return $output;
	}

	/**
	 * Generate the Regular expression that matches embedded entries.
	 *
	 * Generates different regex if using permalinks and if not using permalinks
	 *
	 * @return string Regex code
	 */
	private static function get_entry_regex() {
		$entry_var_name = \GV\Entry::get_endpoint_name();

		/**
		 * Modify the url part for a View. [Read the doc](https://docs.gravitykit.com/article/62-changing-the-view-slug).
		 *
		 * @since 2.0
		 *
		 * @param string $rewrite_slug The slug shown in the URL.
		 */
		$rewrite_slug = apply_filters( 'gravityview_slug', 'view' );

		// Only support embeds for current site
		$prefix = trailingslashit( home_url() );

		// Using permalinks
		$using_permalinks = $prefix . "(?P<is_cpt>{$rewrite_slug})?/?(?P<slug>.+?)/{$entry_var_name}/(?P<entry_slug>.+?)/?\$";

		// Not using permalinks
		$not_using_permalinks = $prefix . '(?:index.php)?\?(?P<is_cpt2>[^=]+)=(?P<slug2>[^&]+)&entry=(?P<entry_slug2>[^&]+)$';

		// Catch either
		$match_regex = "(?:{$using_permalinks}|{$not_using_permalinks})";

		return '#' . $match_regex . '#i';
	}

	/**
	 * Internal oEmbed output, shortcircuit without proxying to the provider.
	 */
	public static function pre_oembed_result( $result, $url, $args ) {
		if ( ! preg_match( self::get_entry_regex(), $url, $matches ) ) {
			return $result;
		}

		$view_entry = self::parse_matches( $matches, $url );
		if ( ! $view_entry || 2 != count( $view_entry ) ) {
			return $result;
		}

		list( $view, $entry ) = $view_entry;

		return self::render_frontend( $view, $entry );
	}
}
