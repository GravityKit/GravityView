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

add_filter( 'lifterlms_integrations', array( 'GravityView_Plugin_Hooks_LifterLMS', 'add_lifterlms_integration' ) );

/**
 * @inheritDoc
 * @since 2.10
 */
class GravityView_Plugin_Hooks_LifterLMS extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @var string Check for the LifterLMS loader function
	 */
	protected $function_name = 'llms';

	protected $content_meta_keys = array(

	);

	static public function add_lifterlms_integration( $integrations = array() ) {

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
 * @since 2.10
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

		$this->title       = __( 'GravityView', 'lifterlms' );
		$this->description = sprintf( __( 'Display Gravity Forms entries for the current student using %sGravityView%s', 'lifterlms' ), '<a href="https://lifterlms.com/docs/lifterlms-and-gravityview/" target="_blank">', '</a>' );

		if ( ! $this->is_available() ) {
			return;
		}

		add_filter( 'llms_get_student_dashboard_tabs', array( $this, 'filter_student_dashboard_tabs' ), 1 );
		add_action( 'lifterlms_student_dashboard_index', array( $this, 'add_student_dashboard_my_forms' ) );
	}

	/**
	 * Determine if GravityView is installed and activated
	 *
	 * @return boolean
	 */
	public function is_installed() {
		return function_exists( 'gravityview' );
	}

	public function filter_student_dashboard_tabs( $tabs = array() ) {

		$new_tab = array(
			'gravityview' => array(
				'content'  => array( $this, 'dashboard_content' ),
				'endpoint' => 'entry',
				'nav_item' => true,
				'title'    => $this->get_option( 'label', __( 'My Forms', 'lifterlms' ) ),
			),
		);

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
		if ( LLMS_Student_Dashboard::is_endpoint_enabled( 'gravityview' ) ) {
			$more = array(
				'url'  => llms_get_endpoint_url( 'gravityview', '', llms_get_page_url( 'myaccount' ) ),
				'text' => __( 'View All My Forms', 'lifterlms' ),
			);
		}

		ob_start();
		lifterlms_template_certificates_loop( $student );

		llms_get_template(
			'myaccount/dashboard-section.php',
			array(
				'action'  => 'gravityview',
				'slug'    => 'llms-gravityview',
				'title'   => $preview ? __( 'My Forms', 'lifterlms' ) : '',
				'content' => ob_get_clean(),
				'more'    => $more,
			)
		);
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

		$view_ids = $this->get_option( 'views', __( 'My Forms', 'lifterlms' ) );

		if ( empty( $view_ids ) ) {
			return '';
		}

		$content = '';

		foreach ( $view_ids as $view_id ) {

			$view = \GV\View::by_id( $view_id );
			$request = new GV\Frontend_Request();

			$request->is_view( $view_id );
			$renderer = new GV\View_Renderer( $view );

			echo $renderer->render( $view );
			//$content .= '[gravityview view_id="' . $view_id . '" post_id="181"]';
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
			return array(
				array(
					'type' => 'custom-html',
					'value' => '<div class="error inline"><h4>' . esc_html__( 'No Views found.', 'gravityview' ) . '</h4></div>',
				),
			);
		}

		$view_array = array();
		foreach ( $views as $view ) {
			$view_array[ $view->ID ] = esc_html( sprintf('%s #%d', $view->post_title, $view->ID ) );
		}

		return array(
			array(
				'title'   => __( 'Menu label:', 'lifterlms' ),
				'desc'    => __( 'Navigation label', 'lifterlms' ),
				'default' => __( 'My Forms', 'lifterlms' ),
				'id'      => $this->get_option_name( 'label' ),
				'type'    => 'text',
			),
			array(
				'title'   => __( 'Show the following Views:', 'lifterlms' ),
				'desc_tooltip'    => __( 'The selected Views will be embedded in the Student Dashboard', 'lifterlms' ),
				'default' => null,
				'id'      => $this->get_option_name( 'views' ),
				'type'    => 'multiselect',
				'options' => $view_array,
				'custom_attributes' => array(
					'size' => 10,
				),
			),
			/*array(
				'default' => 'embed',
				'id'      => $this->get_option_name( 'display' ),
				'type'    => 'radio',
				'options' => array(
					'embed' => __( 'Embedded', 'lifterlms' ),
					'as_list' => __( 'Links', 'lifterlms' ),
				),
				'title'   => __( 'Display the Views as:', 'lifterlms' ),
			),*/
		);

	}

}
