<?php
/**
 * Add GravityView integration to LifterLMS
 *
 * @file      class-gravityview-plugin-hooks-gravity-perks.php
 * @since     1.17.5
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      https://gravityview.co
 * @copyright Copyright 2016, Katz Web Services, Inc.
 *
 * @package   GravityView
 */

// This needs to happen outside the class because the class is loaded too late.
add_filter( 'lifterlms_integrations', function ( $integrations = [] ) {
	/**
	 * @inheritDoc
	 *
	 * @since 2.20
	 */
	class GravityView_Plugin_Hooks_LifterLMS extends GravityView_Plugin_and_Theme_Hooks {
		/**
		 * @var string Check for the LifterLMS loader function
		 */
		protected $function_name = 'llms';

		protected $content_meta_keys = [];

		public function __construct() {
			parent::__construct();
		}

		static public function add_lifterlms_integration( $integrations = [] ) {
			$integrations[] = 'LLMS_Integration_GravityView';

			return $integrations;
		}
	}

	/**
	 * GravityView Integration
	 *
	 * @since 2.20
	 */
	class LLMS_Integration_GravityView extends LLMS_Abstract_Integration {
		/**
		 * Integration ID
		 *
		 * @var string
		 */
		public $id = 'gravityview';

		/**
		 * Display order on Integrations tab
		 *
		 * @var integer
		 */
		protected $priority = 20;

		/**
		 * Configure the integration
		 *
		 * @return void
		 */
		protected function configure() {
			ray("asdasdasd");
			$this->title       = __( 'GravityView', 'gk-gravityview' );
			$this->description = strtr( __( 'Display Gravity Forms entries for the current student using [link]GravityView[/link].', 'gk-gravityview' ), [
				'[link]'  => '<a href="https://lifterlms.com/docs/lifterlms-and-gravityview/" target="_blank" rel="noopener noreferrer">',
				'[/link]' => '<span class="screen-reader-text"> ' . esc_html__( '(This link opens in a new window.)', 'gk-gravityview' ) . '</span></a>',
			] );

			if ( ! $this->is_available() ) {
				return;
			}

			add_filter( 'llms_get_student_dashboard_tabs', [ $this, 'filter_student_dashboard_tabs' ], 1 );
			add_action( 'lifterlms_student_dashboard_index', [ $this, 'add_student_dashboard_my_forms' ] );
			add_action( 'lifterlms_settings_save_integrations', [ $this, 'save' ], 30 );

			// Early hook inside DataTables layout output to allow for the endpoint to be added to the URL.
			add_action( 'gk/gravityview/datatables/get-output-data/before', [ $this, 'datatables_setup_filters' ], 10, 3 );
		}

		/**
		 * Add hooks to the DataTables output to fix Lifter dashboard behavior.
		 *
		 * @since 2.21
		 *
		 * @param \GV\Entry_Collection $entries The collection of entries for the current search.
		 * @param \GV\View             $view    The View.
		 * @param \WP_Post             $post    The current View or post/page where View is embedded.
		 *
		 * @return void
		 */
		public function datatables_setup_filters( $entries, $view, $post ) {
			$dashboard_page_id = llms_get_page_id( 'myaccount' );

			if ( $dashboard_page_id !== $post->ID ) {
				return;
			}

			add_filter( 'option_permalink_structure', [ $this, 'return_false' ] );

			// Append the LifterLMS GravityView endpoint to the directory link.
			add_filter( 'gravityview_directory_link', [ $this, 'add_endpoint_to_directory_link' ] );
			add_filter( 'gravityview_go_back_url', [ $this, 'single_entry_go_back_url' ] );
		}

		/**
		 * Fix the permalinks to the entry for the DataTables layout.
		 *
		 * @since 2.21
		 *
		 * @param string $permalink
		 *
		 * @return string The filtered output of the DataTables extension.
		 */
		public function filter_datatables_permalink( $permalink ) {
			$parts = explode( '?', $permalink );

			return $this->add_endpoint_to_directory_link( $parts[0] ) . '?' . $parts[1];
		}

		/**
		 * When the GravityView integration is saved, flush the rewrite rules.
		 *
		 * Even before adding the slug setting, the LifterLMS settings had to be saved twice before permalinks were flushed.
		 *
		 * @return void
		 */
		public function save() {
			if ( ! 'gravityview' === \GV\Utils::_REQUEST( 'section' ) ) {
				return;
			}

			/**
			 * Always flush the rewrite rules when saving the GravityView settings.
			 */
			$settings_page = new LLMS_Settings_Page();
			$settings_page->flush_rewrite_rules();
		}

		/**
		 * Determine if GravityView is installed and activated
		 *
		 * @return boolean
		 */
		public function is_installed() {
			return function_exists( 'gravityview' );
		}

		public function filter_student_dashboard_tabs( $tabs = [] ) {
			$tab_title = $this->get_option( 'label', __( 'My Forms', 'gk-gravityview' ) );

			$new_tab = [
				'gravityview' => [
					'content'  => [ $this, 'dashboard_content' ],
					'endpoint' => $this->get_endpoint(),
					'nav_item' => true,
					'title'    => $tab_title,
				],
			];

			$my_grades_index = array_search( 'view-certificates', array_keys( $tabs ) );

			array_splice( $tabs, $my_grades_index + 1, 0, $new_tab );

			return $tabs;
		}

		/**
		 * Return the default slug for the GravityView integration endpoint.
		 *
		 * @return string The default slug for the GravityView integration endpoint, sanitized by `sanitize_title_with_dashes`.
		 */
		private function get_default_slug() {
			return sanitize_title_with_dashes( __( 'my-forms', 'gk-gravityview' ) );
		}

		/**
		 * Get the endpoint slug from GravityView LifterLMS settings.
		 *
		 * @return string
		 */
		private function get_endpoint() {
			$slug_name = $this->get_option( 'slug', $this->get_default_slug() );

			return sanitize_title_with_dashes( $slug_name );
		}

		/**
		 * Template for My Courses section on dashboard index
		 *
		 * @since 3.14.0
		 * @since 3.19.0 Unknown.
		 *
		 * @param bool $preview Optional. If true, outputs a short list of courses (based on dashboard_recent_courses filter). Default `false`.
		 *
		 * @return void
		 */
		public function add_student_dashboard_my_forms( $preview = false ) {
			$student = llms_get_student();
			if ( ! $student ) {
				return;
			}

			$more = false;

			$is_endpoint_enabled = LLMS_Student_Dashboard::is_endpoint_enabled( 'gravityview' );
			if ( $is_endpoint_enabled ) {
				$more = [
					'url'  => llms_get_endpoint_url( 'gravityview', '', llms_get_page_url( 'myaccount' ) ),
					'text' => __( 'View All My Forms', 'gk-gravityview' ),
				];
			}

			ob_start();

			lifterlms_template_certificates_loop( $student );

			$content = ob_get_clean();

			llms_get_template( 'myaccount/dashboard-section.php', [
				'action'  => 'gravityview',
				'slug'    => 'llms-gravityview',
				'title'   => $preview ? __( 'My Forms', 'gk-gravityview' ) : '',
				'content' => $content,
				'more'    => $more,
			] );
		}

		/**
		 * Renders the Views that were configured in LifterLMS settings.
		 *
		 * @since 2.21
		 *
		 * @return void
		 */
		public function dashboard_content() {
			$user = wp_get_current_user();

			$student = llms_get_student( $user );

			if ( ! $student ) {
				return;
			}

			$content = $this->get_raw_content();

			if ( '' === $content ) {
				return;
			}

			/**
			 * Disable permalinks so that the /entry/123/ endpoint instead is rendered as ?entry=123
			 *
			 * We use a custom filter to avoid conflicts with other plugins that may be using `__return_false`.
			 *
			 * @see {GV\Entry::get_permalink}
			 */
			add_filter( 'option_permalink_structure', [ $this, 'return_false' ] );

			// Append the LifterLMS GravityView endpoint to the directory link.
			add_filter( 'gravityview_directory_link', [ $this, 'add_endpoint_to_directory_link' ] );
			add_filter( 'gravityview_go_back_url', [ $this, 'single_entry_go_back_url' ] );

			echo do_shortcode( $content );

			remove_filter( 'gravityview_directory_link', [ $this, 'add_endpoint_to_directory_link' ] );
			remove_filter( 'option_permalink_structure', [ $this, 'return_false' ] );
		}

		/**
		 * Fixes the go back URL when viewing a single entry by removing the single entry endpoint.
		 *
		 * @param string $url The current go back URL.
		 *
		 * @return string The go back URL with the single entry endpoint removed.
		 */
		public function single_entry_go_back_url( $url ) {
			return remove_query_arg( \GV\Entry::get_endpoint_name() );
		}

		/**
		 * Returns false!
		 *
		 * @since 2.21
		 *
		 * @return false
		 */
		public function return_false() {
			return false;
		}

		/**
		 * Appends the LifterLMS GravityView endpoint to the directory link.
		 *
		 * @since 2.21
		 *
		 * @param string $permalink The existing directory link, which points to the LifterLMS Student Dashboard URL.
		 *
		 * @return string The directory link with the GravityView endpoint appended.
		 */
		public function add_endpoint_to_directory_link( $permalink ) {
			/** Check against empty string (WordPress) instead of false (as returned by {@see return_false}). */
			if ( '' === get_option( 'permalink_structure' ) ) {
				return add_query_arg( [ $this->get_endpoint() => 1 ], $permalink );
			}

			return trailingslashit( $permalink ) . trailingslashit( $this->get_endpoint() );
		}

		/**
		 * Generate the shortcode output for the configured Views.
		 *
		 * @return string
		 */
		private function get_raw_content() {
			$content = '';

			$view_ids = $this->get_option( 'views', [] );

			if ( empty( $view_ids ) ) {
				return $content;
			}

			global $post;
			foreach ( $view_ids as $view_id ) {

				$view = \GV\View::by_id( (int) $view_id );

				if ( ! $view ) {
					return null;
				}

				$content .= $view->get_shortcode( [ 'post_id' => $post->ID ] );
			}

			return $content;
		}

		/**
		 * Return the settings for the integration.
		 *
		 * @return array[]
		 */
		public function get_integration_settings() {

			$views = GVCommon::get_all_views();

			if ( empty( $views ) ) {
				return [
					[
						'type'  => 'custom-html',
						'value' => '<div class="error inline"><h4>' . esc_html__( 'No Views found.', 'gk-gravityview' ) . '</h4></div>',
					],
				];
			}

			$view_array = [];
			foreach ( $views as $view ) {
				$view_array[ $view->ID ] = esc_html( sprintf( '%s #%d', $view->post_title, $view->ID ) );
			}

			return [
				[
					'title'   => __( 'Menu label:', 'gk-gravityview' ),
					'desc'    => __( 'Navigation label', 'gk-gravityview' ),
					'default' => __( 'My Forms', 'gk-gravityview' ),
					'id'      => $this->get_option_name( 'label' ),
					'type'    => 'text',
				],
				[
					'title'   => __( 'Endpoint slug:', 'gk-gravityview' ),
					'desc'    => __( 'The end of the URL to display when accessing this tab from the Student Dashboard. This value will be converted to lowercase spaces and special characters replaced by dashes.', 'gk-gravityview' ),
					'default' => $this->get_default_slug(),
					'id'      => $this->get_option_name( 'slug' ),
					'type'    => 'text',
				],
				[
					'title'             => __( 'Show the following Views:', 'gk-gravityview' ),
					'desc_tooltip'      => __( 'The selected Views will be embedded in the Student Dashboard', 'gk-gravityview' ),
					'default'           => null,
					'id'                => $this->get_option_name( 'views' ),
					'type'              => 'multiselect',
					'options'           => $view_array,
					'class'             => 'llms-select2',
					'custom_attributes' => [
						'size' => 10,
					],
				],
			];
		}
	}

	return GravityView_Plugin_Hooks_LifterLMS::add_lifterlms_integration( $integrations );
}, 20 );
