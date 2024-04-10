<?php

namespace GV;

use GravityKit\GravityView\Foundation\Onboarding\Framework as OnboardingFramework;
use GravityKit\GravityView\Foundation\Onboarding\Step;

class Onboarding {
	/**
	 * @var string Plugin Identifier.
	 */
	private $plugin = GRAVITYVIEW_FILE;

	private $onboarding_id = 'create_view';

	/**
	 * Onboarding constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'init' ] );
		add_action( 'admin_init', [ $this, 'handle_restart_product_tour' ] );
	}

	/**
	 * Restart product tour on request.
	 *
	 * @return void
	 */
	public function handle_restart_product_tour() {
		if ( ! $this->should_restart_onboarding() ) {
			return;
		}

		$this->restart_onboarding( $this->plugin, $this->onboarding_id );

		$this->redirect_after_restart();
	}

	/**
	 * Allow to restart onboarding request?
	 *
	 * @return bool
	 */
	private function should_restart_onboarding() {
		return isset( $_GET['restart_product_tour'] )
		       && wp_verify_nonce( $_GET['restart_product_tour'], 'restart_product_tour' )
		       && OnboardingFramework::is_enabled();
	}

	/**
	 * Restart onboarding.
	 *
	 * @return void
	 */
	private function restart_onboarding( $plugin, $onboarding_id ) {
		$onboarding = OnboardingFramework::get_instance( $plugin );
		$onboarding->restart_onboarding( $onboarding_id );
	}

	/**
	 * Remove restart product tour query args from URL to avoid infinite loop.
	 *
	 * @return void
	 */
	private function redirect_after_restart() {
		$url = remove_query_arg( 'restart_product_tour' );
		wp_safe_redirect( $url );
	}

	/**
	 * Initialise onboarding.
	 *
	 * @return void
	 */
	public function init() {

		// Define Steps.
		// Step 1.
		$element     = '#titlewrap';
		$title       = __( 'Start by giving your View a name.', 'gk-gravityview' );
		$description = __( 'Depending on your website, the name may display publicly on the front end.', 'gk-gravityview' );

		$step_1 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'screen'      => 'gravityview',
		] );


		// Step 2.
		$forms = gravityview_get_forms( 'any', false );
		if ( empty( $forms ) ) {
			$element     = '#gravityview_select_form a[href="#gv_start_fresh"]';
			$title       = __( 'There are no existing forms.', 'gk-gravityview' );
			$description = __( 'You can click "Use a Form Preset" and we will create one for you. Or you can create a new form and return to this step.', 'gk-gravityview' );
		} else {
			$element     = '#gravityview_form_id';
			$title       = __( 'Next, select what form submissions to show.', 'gk-gravityview' );
			$description = __( 'Choose a Gravity Form with the entry data that you want to display on the front end of your website.', 'gk-gravityview' );
		}

		$step_2 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'screen'      => 'gravityview',
		] );

		$step_2->setOnHighlightStartedCallback( 'function(element, step, options) {
				jQuery("#gravityview_select_form .inside").show();
		}' );

		// Step 3.
		$element     = '#gravityview_select_template';
		$title       = __( 'Now choose a View type.', 'gk-gravityview' );
		$description = __( 'GravityView includes different View types, allowing you to display your data using different layouts. Go ahead and select the type you want for your data. You can create multiple Views of same data using different View types!', 'gk-gravityview' );

		$step_3 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top',
			'screen'      => 'gravityview',
		] );

		$step_3->setOnHighlightStartedCallback( 'function(element, step, options) {
				let formVal = jQuery("select#gravityview_form_id option:nth-child(2)").val();
				jQuery("select#gravityview_form_id").val(formVal);
				jQuery("select#gravityview_form_id").trigger("change");
				jQuery("#gravityview_select_template").show();
		}' );

		$step_3->setPopoverOnNextClickCallback( 'function(element, step, options) {
				jQuery("#gravityview_select_template > div.inside > div.gv-grid > div:nth-child(1) > div > div.gv-view-types-hover > div > p > a").click();

				setTimeout(function() {
                    driverObj.moveNext();
                }, 1000);
		}' );

		// Step 4.
		$element     = 'ul.ui-tabs-nav li.ui-tabs-tab a[href="#directory-view"]';
		$title       = __( 'Welcome to GravityView\'s drag-and-drop View editor!', 'gk-gravityview' );
		$description = __( 'Here you can start building your new application by adding fields and widgets. Let\'s get familiar with the basic functionality.', 'gk-gravityview' );

		$step_4 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top',
			'screen'      => 'gravityview',
		] );

		// Step 5.
		$element     = 'ul.ui-tabs-nav li.ui-tabs-tab a[href="#directory-view"]';
		$title       = __( '', 'gk-gravityview' );
		$description = __( 'We are currently configuring the Multiple Entries layout. This page shows all the entries in a View. In future steps, you will learn how to control what entries are shown.', 'gk-gravityview' );

		$step_5 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top',
			'screen'      => 'gravityview',
		] );

		// Step 6.
		$element     = '#directory-header-widgets';
		$title       = __( '', 'gk-gravityview' );
		$description = __( 'This area is for widgets. There are widget areas above and below where entries are shown. Widgets are tools for navigating a View (e.g. a search bar).', 'gk-gravityview' );

		$step_6 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top',
			'screen'      => 'gravityview',
		] );

		// Step 7.
		$element     = '#directory-active-fields';
		$title       = __( '', 'gk-gravityview' );
		$description = __( 'This is where the form entries are shown. You can choose which fields to display by adding them here. Click this gear icon to configure the field settings.', 'gk-gravityview' );

		$step_7 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top',
			'screen'      => 'gravityview',
		] );

		// Step 8.
		$element     = '#directory-active-fields > div > div > div > div.active-drop.active-drop-field > div.gv-fields.gv-child-field.has-single-entry-link > h5 > span.gv-field-controls > button.gv-field-settings:first-child';
		$title       = '';
		$description = __( 'Here you can set custom labels, modify visibility settings, add css classes, and more. As you can see, GravityView has made this field a link to the single entry.', 'gk-gravityview' );

		$step_8 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top',
			'screen'      => 'gravityview',
		] );

		// Step 9.
		$element = 'ul.ui-tabs-nav li.ui-tabs-tab a[href="#single-view"]';
		$title   = '';

		$img_url     = esc_url( plugins_url( 'assets/images/click-to-single-entry.gif', GRAVITYVIEW_FILE ) );
		$description = sprintf( __( "<img src='%s' style='height: 202.5px; width: 270px;' />Here you can configure the Single Entry Layout, which is the screen that users will see when they click to view more details about a specific entry.", 'gk-gravityview' ), $img_url );

		$step_9 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top',
			'screen'      => 'gravityview',
		] );

		$step_9->setOnHighlightStartedCallback( 'function(element, step, options) {
			jQuery(".ui-dialog").hide();
		}' );
		// Step 10.
		$element     = 'ul.ui-tabs-nav li.ui-tabs-tab a[href="#edit-view"]';
		$title       = '';
		$description = __( '(Optional) Configure the Edit Entry Layout to choose which fields are editable by users from the front end.', 'gk-gravityview' );

		$step_10 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top',
			'screen'      => 'gravityview',
		] );

		// Step 11.
		$element     = '#gravityview_settings';
		$title       = '';
		$description = __( 'In settings you will find a range of options for customizing your View. Click on tabs to see available options.', 'gk-gravityview' );

		$step_11 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top',
			'screen'      => 'gravityview',
		] );

		// Initialise onboarding.
		$onboarding = OnboardingFramework::get_instance( $this->plugin);

		$onboarding->steps->add( $step_1 )
		                  ->add( $step_2 )
		                  ->add( $step_3 )
		                  ->add( $step_4 )
		                  ->add( $step_5 )
		                  ->add( $step_6 )
		                  ->add( $step_7 )
		                  ->add( $step_8 )
		                  ->add( $step_9 )
		                  ->add( $step_10 )
		                  ->add( $step_11 );

		$onboarding->init_onboarding( $this->onboarding_id );
	}
}

new Onboarding();
