<?php
/**
 * GravityView oEmbed handling
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 * @since 1.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Register oEmbed handlers for embedding GravityView data and render that data
 *
 * @since 1.6
 */
class GravityView_oEmbed {

	protected $output = array();
	protected $entry_id = NULL;
	protected $view_id = NULL;
	protected $is_full_oembed_preview = false;

	static $instance = NULL;

	private function __construct() {}

	private function initialize() {

		add_action( 'init', array( $this, 'register_handler' ) );
		add_action( 'init', array( $this, 'add_provider' ) );

		if ( ! empty( $_GET['gv_oembed_provider'] ) && ! empty( $_GET['url'] ) ) {
			add_action( 'template_redirect', array( $this, 'render_provider_request' ) );
		}
	}

	/**
	 * @return GravityView_oEmbed
	 * @since 1.6
	 */
	static function getInstance() {

		if( empty( self::$instance ) ) {
			self::$instance = new self;

			self::$instance->initialize();
		}

		return self::$instance;
	}

	/**
	 * Register the oEmbed handler
	 *
	 * @since 1.6
	 * @uses get_handler_regex
	 */
	function register_handler() {

		wp_embed_register_handler( 'gravityview_entry', $this->get_handler_regex(), array( $this, 'render_handler' ), 20000 );

	}

	/**
	 * Become an oEmbed provider for GravityView.
	 *
	 * @since 1.21.5.3
	 *
	 * @return void
	 */
	function add_provider() {
		wp_oembed_add_provider( $this->get_handler_regex(), add_query_arg( 'gv_oembed_provider', '1', site_url() ), true );
	}

	/**
	 * Output a response as a provider for an entry oEmbed URL.
	 *
	 * For now we only output the JSON format and don't care about the size (width, height).
	 * Our only current use-case is for it to provide output to the Add Media / From URL box
	 *  in WordPress 4.8.
	 *
	 * @since 1.21.5.3
	 *
	 * @return void
	 */
	function render_provider_request() {
		if ( ! empty( $_GET['url'] ) ) {
			$url = $_GET['url'];
		} else {
			header( 'HTTP/1.0 404 Not Found' );
			exit;
		}

		preg_match( $this->get_handler_regex(), $url, $matches );

		// If not using permalinks, re-assign values for matching groups
		if ( ! empty( $matches['entry_slug2'] ) ) {
			$matches['is_cpt'] = $matches['is_cpt2'];
			$matches['slug'] = $matches['slug2'];
			$matches['entry_slug'] = $matches['entry_slug2'];
			unset( $matches['is_cpt2'], $matches['slug2'], $matches['entry_slug2'] );
		}

		// No Entry was found
		if ( empty( $matches['entry_slug'] ) ) {
			do_action('gravityview_log_error', 'GravityView_oEmbed[render_handler] $entry_slug not parsed by regex.', $matches );
			header( 'HTTP/1.0 404 Not Found' );
			exit;
		}

		// Setup the data used
		$this->set_vars( $matches, null, $url, null );

		echo json_encode( array(
			'version' => '1.0',
			'provider_name' => 'gravityview',
			'provider_url' => add_query_arg( 'gv_oembed_provider', '1', site_url() ),
			'html' => $this->generate_preview_notice() . $this->render_frontend( null, null, null, null ),
		) );
		exit;
	}

	/**
	 * Generate the Regular expression that matches embedded entries.
	 *
	 * Generates different regex if using permalinks and if not using permalinks
	 *
	 * @since 1.6
	 *
	 * @return string Regex code
	 */
	private function get_handler_regex() {

		if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
			$entry_var_name = \GV\Entry::get_endpoint_name();
		} else {
			/** Deprecated. Use \GV\Entry::get_endpoint_name instead. */
			$entry_var_name = GravityView_Post_Types::get_entry_var_name();
		}

		/**
		 * @filter `gravityview_slug` Modify the url part for a View. [Read the doc](http://docs.gravityview.co/article/62-changing-the-view-slug)
		 * @param string $rewrite_slug The slug shown in the URL
		 */
		$rewrite_slug = apply_filters( 'gravityview_slug', 'view' );

		// Only support embeds for current site
		$prefix = trailingslashit( home_url() );

		// Using permalinks
		$using_permalinks = $prefix . "(?P<is_cpt>{$rewrite_slug})?/?(?P<slug>.+?)/{$entry_var_name}/(?P<entry_slug>.+?)/?\$";

		// Not using permalinks
		$not_using_permalinks = $prefix . "(?:index.php)?\?(?P<is_cpt2>[^=]+)=(?P<slug2>[^&]+)&entry=(?P<entry_slug2>[^&]+)\$";

		// Catch either
		$match_regex = "(?:{$using_permalinks}|{$not_using_permalinks})";

		return '#'.$match_regex.'#i';
	}

	/**
	 * Get the post ID from an URL
	 *
	 * This is necessary because url_to_postid() doesn't work with permalinks off for custom post types
	 *
	 * @uses url_to_postid()
	 * @since 1.6
	 *
	 * @param string $url URL to get the post ID from
	 * @param string $slug The name of a post, used as backup way of checking for post ID
	 * @return int 0 if not found; int of URL post ID otherwise
	 */
	private function get_postid_from_url_and_slug( $url = '', $slug = '' ) {

		$post_id = url_to_postid( $url );

		if( empty( $post_id ) ) {

			$args = array(
				'post_status' => 'publish',
				'name' => $slug,
				'post_type' => array('any', 'gravityview'),
			);

			$posts = get_posts( $args );

			if( !empty( $posts ) ) {
				$post_id = $posts[0]->ID;
			}
		}

		return $post_id;
	}

	/**
	 * Get the entry id for the current oEmbedded entry
	 *
	 * @since 1.6
	 *
	 * @return int|null
	 */
	public function get_entry_id() {
		return $this->entry_id;
	}

	/**
	 *
	 *
	 * @since 1.6
	 * @see GravityView_oEmbed::add_providers() for the regex
	 *
	 * @param array $matches The regex matches from the provided regex when calling wp_embed_register_handler()
	 * @param array $attr Embed attributes.
	 * @param string $url The original URL that was matched by the regex.
	 * @param array $rawattr The original unmodified attributes.
	 * @return string The embed HTML.
	 */
	public function render_handler( $matches, $attr, $url, $rawattr ) {

		// If not using permalinks, re-assign values for matching groups
		if( !empty( $matches['entry_slug2'] ) ) {
			$matches['is_cpt'] = $matches['is_cpt2'];
			$matches['slug'] = $matches['slug2'];
			$matches['entry_slug'] = $matches['entry_slug2'];
			unset( $matches['is_cpt2'], $matches['slug2'], $matches['entry_slug2'] );
		}

		// No Entry was found
		if( empty( $matches['entry_slug'] ) ) {

			do_action('gravityview_log_error', 'GravityView_oEmbed[render_handler] $entry_slug not parsed by regex.', $matches );

			return '';
		}

		$return = '';

		// Setup the data used
		$this->set_vars( $matches, $attr, $url, $rawattr );

		if( is_admin() && !$this->is_full_oembed_preview ) {
			$return = $this->render_admin( $matches, $attr, $url, $rawattr );
		} else {

			if( $this->is_full_oembed_preview ) {
				$return .= $this->generate_preview_notice();
			}

			$return .= $this->render_frontend( $matches, $attr, $url, $rawattr );
		}

		return $return;
	}


	/**
	 * Generate a warning to users when previewing oEmbed in the Add Media modal
	 *
	 * @return string HTML notice
	 */
	private function generate_preview_notice() {
		$floaty = GravityView_Admin::get_floaty();
		$title = esc_html__( 'This will look better when it is embedded.', 'gravityview' );
		$message = esc_html__('Styles don\'t get loaded when being previewed, so the content below will look strange. Don\'t be concerned!', 'gravityview');
		return '<div class="updated notice">'. $floaty. '<h3>'.$title.'</h3><p>'.$message.'</p><br style="clear:both;" /></div>';
	}

	/**
	 * Set entry_id and view_id from the data sent to render_handler
	 *
	 * @var $entry_id
	 * @var $view_id
	 *
	 * @see render_handler
	 */
	private function set_vars( $matches, $attr, $url, $rawattr ) {

		$this->entry_id = $matches['entry_slug'];

		$post_id = $this->get_postid_from_url_and_slug( $url, $matches['slug'] );

		// The URL didn't have the View Custom Post Type structure.
		if( empty( $matches['is_cpt'] ) || $matches['is_cpt'] !== 'gravityview' ) {

			do_action('gravityview_log_debug', 'GravityView_oEmbed[render_handler] Embedding an entry inside a post or page', $matches );

			if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) && $post = get_post( $post_id ) ) {
				$views = \GV\View_Collection::from_post( $post );
				$views = $views->all();
				if ( ! empty( $views ) ) {
					/** maybe_get_view_id has a side-effect that adds retrieved views to the global scope */
					foreach ( $views as $view ) {
						if ( \GV\View::exists( $view->ID ) && ! gravityview()->views->contains( $view->ID ) ) {
							gravityview()->views->add( $view );
						}
					}

					$this->view_id = $views[0]->ID;
				}
			} else {
				/** Deprecated. */
				$this->view_id = GravityView_View_Data::getInstance()->maybe_get_view_id( $post_id );
			}

		} else {

			$this->view_id = $post_id;

		}

		// The inline content has $_POST['type'] set to "embed", while the "Add Media" modal doesn't set that.
		$this->is_full_oembed_preview = ( isset( $_POST['action'] ) && $_POST['action'] === 'parse-embed' && !isset( $_POST['type'] ) );
	}

	/**
	 * Display a nice placeholder in the admin for the entry
	 *
	 * @param array $matches The regex matches from the provided regex when calling wp_embed_register_handler()
	 * @param array $attr Embed attributes.
	 * @param string $url The original URL that was matched by the regex.
	 * @param array $rawattr The original unmodified attributes.
	 * @return string The embed HTML.
	 */
	private function render_admin( $matches, $attr, $url, $rawattr ) {
		global $wp_version;

		// Floaty the astronaut
		$image = GravityView_Admin::get_floaty();

		$embed_heading = sprintf( esc_html__('Embed Entry %d', 'gravityview'), $this->entry_id );

		$embed_text = sprintf( esc_html__('This entry will be displayed as it is configured in View %d', 'gravityview'), $this->view_id );

		return '
		<div class="loading-placeholder" style="background-color:#e6f0f5;">
			<h3 style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen-Sans, Ubuntu, Cantarell, \'Helvetica Neue\', sans-serif;">'.$image.$embed_heading.'</h3>
			<p style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen-Sans, Ubuntu, Cantarell, \'Helvetica Neue\', sans-serif;">
				'.$embed_text.'
			</p>
			<br style="clear: both;">
		</div>';

	}

	private function generate_entry_output() {

		// Tell get_gravityview() to display a single entry
		add_filter( 'gravityview/is_single_entry', array( $this, 'set_single_entry_id' ) );

		ob_start();

		// Print the entry as configured in View
		the_gravityview( $this->view_id );

		$view_html = ob_get_clean();

		// Clean up the filter
		remove_filter( 'gravityview/is_single_entry', array( $this, 'set_single_entry_id' ) );

		// Strip the new lines that are generated--when editing an entry in particular, scripts are printed that
		// then are passed through wpautop() and everything looks terrible.
		$view_html = str_replace( "\n", ' ', $view_html );

		return $view_html;
	}

	/**
	 * Tell get_gravityview() to display a single entry
	 *
	 * REQUIRED FOR THE VIEW TO OUTPUT A SINGLE ENTRY
	 *
	 * @param bool|int $is_single_entry Existing single entry. False, because GV thinks we're in a post or page.
	 *
	 * @return int The current entry ID
	 */
	public function set_single_entry_id( $is_single_entry = false ) {

		return $this->entry_id;
	}

	/**
	 * GravityView embed entry handler
	 *
	 * @param array $matches The regex matches from the provided regex when calling {@link wp_embed_register_handler()}.
	 * @param array $attr Embed attributes.
	 * @param string $url The original URL that was matched by the regex.
	 * @param array $rawattr The original unmodified attributes.
	 * @return string The embed HTML.
	 */
	private function render_frontend( $matches, $attr, $url, $rawattr ) {

		// If it's already been parsed, don't re-output it.
		if( !empty( $this->output[ $this->entry_id ] ) ) {
			return $this->output[ $this->entry_id ];
		}

		$entry_output = $this->generate_entry_output();

		// Wrap a container div around the output to allow for custom styling
		$output = sprintf('<div class="gravityview-oembed gravityview-oembed-entry gravityview-oembed-entry-'.$this->entry_id.'">%s</div>', $entry_output );

		/**
		 * @filter `gravityview/oembed/entry` Filter the output of the oEmbed entry embed
		 * @param string $output HTML of the embedded entry, with wrapper div
		 * @param GravityView_oEmbed $object The current GravityView_oEmbed instance
		 * @param array $atts Other passed parameters and info. \n
		 *  @var string $entry_output HTML of just the View output, without the wrapper \n
		 *  @var array  $matches Capture group matches from the regex \n
		 *  @var array $attr Embed attributes. \n
		 *  @var string $url The original URL that was matched by the regex. \n
		 *  @var array $rawattr The original unmodified attributes.
		 */
		$output = apply_filters('gravityview/oembed/entry', $output, $this, compact( $entry_output, $matches, $attr, $url, $rawattr ) );

		unset( $entry_output );

		$this->output[ $this->entry_id ] = $output;

		return $this->output[ $this->entry_id ];

	}

}

GravityView_oEmbed::getInstance();