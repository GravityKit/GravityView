<?php
/**
 * Add GravityView integration to LifterLMS
 *
 * @file      class-gravityview-plugin-hooks-gravity-perks.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      https://gravityview.co
 * @copyright Copyright 2016, Katz Web Services, Inc.
 *
 * @since 1.17.5
 */

// This needs to happen outside the class because the class is loaded too late.
add_filter( 'lifterlms_integrations', [ 'GravityView_Plugin_Hooks_LifterLMS', 'add_lifterlms_integration' ] );

/**
 * @inheritDoc
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

new GravityView_Plugin_Hooks_LifterLMS;


if ( ! class_exists( 'LLMS_Abstract_Integration' ) ) {
	return;
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

		$this->title       = __( 'GravityView', 'gk-gravityview' );
		$this->description = strtr( __( 'Display Gravity Forms entries for the current student using [link]GravityView[/link].', 'gk-gravityview' ), [
			'[link]' => '<a href="https://lifterlms.com/docs/lifterlms-and-gravityview/" target="_blank" rel="noopener noreferrer">',
			'[/link]' => '<span class="screen-reader-text"> ' . esc_html__( '(This link opens in a new window.)', 'gk-gravityview' ) . '</span></a>',
		] );

		if ( ! $this->is_available() ) {
			return;
		}

		add_filter( 'llms_get_student_dashboard_tabs', [ $this, 'filter_student_dashboard_tabs' ], 1 );
		add_action( 'lifterlms_student_dashboard_index', [ $this, 'add_student_dashboard_my_forms' ] );
		add_action( 'lifterlms_settings_save_integrations', [ $this, 'save' ], 30 );
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
		$slug_name = $this->get_option( 'slug', 'my-forms' );

		$new_tab = [
			'gravityview' => [
				'content'  => [ $this, 'dashboard_content' ],
				'endpoint' => sanitize_title_with_dashes( $slug_name ),
				'nav_item' => true,
				'title'    => $tab_title,
			],
		];

		$my_grades_index = array_search( 'view-certificates', array_keys( $tabs ) );

		array_splice( $tabs, $my_grades_index + 1, 0, $new_tab );

		return $tabs;
	}

	/**
	 * Template for My Courses section on dashboard index
	 *
	 * @since 3.14.0
	 * @since 3.19.0 Unknown.
	 *
	 * @param bool $preview Optional. If true, outputs a short list of courses (based on dashboard_recent_courses filter). Default `false`.
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
	 * Show all the Views as configured in LifterLMS settings
	 */
	public function dashboard_content() {

		$user = wp_get_current_user();

		$student = llms_get_student( $user );

		if ( ! $student ) {
			return;
		}

		$content = $this->get_raw_content();

		echo do_shortcode( $content );
	}

	private function get_raw_content() {

		$view_ids = $this->get_option( 'views', [] );

		if ( empty( $view_ids ) ) {
			return '';
		}

		global $post;
		$content = '';
		foreach ( $view_ids as $view_id ) {
			$content .= '[gravityview view_id="' . (int) $view_id . '" post_id="'. $post->ID . '" /]';
		}

		return $content;
	}

	/**
	 *
	 *
	 * @return array[]
	 */
	public function get_integration_settings() {

		$views = GVCommon::get_all_views();

		if ( empty( $views ) ) {
			return [
				[
					'type' => 'custom-html',
					'value' => '<div class="error inline"><h4>' . esc_html__( 'No Views found.', 'gravityview' ) . '</h4></div>',
				],
			];
		}

		$view_array = [];
		foreach ( $views as $view ) {
			$view_array[ $view->ID ] = esc_html( sprintf('%s #%d', $view->post_title, $view->ID ) );
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
				'default' => __( 'my-forms', 'gk-gravityview' ),
				'id'      => $this->get_option_name( 'slug' ),
				'type'    => 'text',
			],
			[
				'title'   => __( 'Show the following Views:', 'gk-gravityview' ),
				'desc_tooltip'    => __( 'The selected Views will be embedded in the Student Dashboard', 'gk-gravityview' ),
				'default' => null,
				'id'      => $this->get_option_name( 'views' ),
				'type'    => 'multiselect',
				'options' => $view_array,
				'custom_attributes' => [
					'size' => 10,
				],
			],
			/*[
				'default' => 'embed',
				'id'      => $this->get_option_name( 'display' ),
				'type'    => 'radio',
				'options' => [
					'embed' => __( 'Embedded', 'gk-gravityview' ),
					'as_list' => __( 'Links', 'gk-gravityview' ),
				],
				'title'   => __( 'Display the Views as:', 'gk-gravityview' ),
			),*/
		];

	}

}
