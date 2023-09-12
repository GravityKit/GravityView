<?php

namespace GV;

use GravityKit\Foundation\Onboarding\Framework as OnboardingFramework;
use GravityKit\Foundation\Onboarding\Step;

class Onboarding {

	private $plugin = GRAVITYVIEW_FILE;

	public function __construct() {

		// Define Steps.

		// Step 1.
		$element     = '#titlewrap';
		$title       = __( 'Start by giving your View a name.', 'gk-gravityview' );
		$description = __( 'Depending on your website, the name may display publicly on the front end.', 'gk-gravityview' );

		$step_1 = new Step( [ 'element' => $element, 'title' => $title, 'description' => $description ] );

		// Step 2.
		$element     = '#gravityview_form_id';
		$title       = __( 'Start by giving your View a name.', 'gk-gravityview' );
		$description = __( 'Depending on your website, the name may display publicly on the front end.', 'gk-gravityview' );

		$step_2 = new Step( [ 'element' => $element, 'title' => $title, 'description' => $description ] );

		// Step 3.
		$element     = '#gravityview_select_template';
		$title       = __( 'Now choose a View type.', 'gk-gravityview' );
		$description = __( 'GravityView includes different View types, allowing you to display your data using different layouts. Go ahead and select the type you want for your data. You can create multiple Views of same data using different View types!', 'gk-gravityview' );

		$step_3 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top'
		] );

		// Step 4.
		$element     = 'ul.ui-tabs-nav li.ui-tabs-tab a[href="#directory-view"]';
		$title       = __( 'Welcome to GravityView\'s drag-and-drop View editor!', 'gk-gravityview' );
		$description = __( 'Here you can start building your new application by adding fields and widgets. Let\'s get familiar with the basic functionality.', 'gk-gravityview' );

		$step_4 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top'
		] );

		// Step 5.
		$element     = 'ul.ui-tabs-nav li.ui-tabs-tab a[href="#directory-view"]';
		$title       = __( '', 'gk-gravityview' );
		$description = __( 'We are currently configuring the Multiple Entries layout. This page shows all the entries in a View. In future steps, you will learn how to control what entries are shown.', 'gk-gravityview' );

		$step_5 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top'
		] );

		// Step 6.
		$element     = '#directory-header-widgets';
		$title       = __( '', 'gk-gravityview' );
		$description = __( 'This area is for widgets. There are widget areas above and below where entries are shown. Widgets are tools for navigating a View (e.g. a search bar).', 'gk-gravityview' );

		$step_6 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top'
		] );

		// Step 7.
		$element     = '#directory-active-fields';
		$title       = __( '', 'gk-gravityview' );
		$description = __( 'This is where the form entries are shown. You can choose which fields to display by adding them here. Click this gear icon to configure the field settings.', 'gk-gravityview' );

		$step_7 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top'
		] );

		// Step 8.
		$element     = '.ui-dialog';
		$title       = __( '', 'gk-gravityview' );
		$description = __( 'Here you can set custom labels, modify visibility settings, add css classes, and more. As you can see, GravityView has made this field a link to the single entry.', 'gk-gravityview' );

		$step_8 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top'
		] );

		// Step 9.
		$element     = 'ul.ui-tabs-nav li.ui-tabs-tab a[href="#single-view"]';
		$title       = __( '', 'gk-gravityview' );
		$description = __( "<img src='https://i.imgur.com/EAQhHu5.gif' style='height: 202.5px; width: 270px;' />Here you can configure the Single Entry Layout, which is the screen that users will see when they click to view more details about a specific entry.", 'gk-gravityview' );

		$step_9 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top'
		] );

		// Step 10.
		$element     = 'ul.ui-tabs-nav li.ui-tabs-tab a[href="#edit-view"]';
		$title       = __( '', 'gk-gravityview' );
		$description = __( '(Optional) Configure the Edit Entry Layout to choose which fields are editable by users from the front end.', 'gk-gravityview' );

		$step_10 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top'
		] );

		// Step 11.
		$element     = '#gravityview_settings';
		$title       = __( '', 'gk-gravityview' );
		$description = __( 'In settings you will find a range of options for customizing your View. Click on tabs to see available options.', 'gk-gravityview' );

		$step_11 = new Step( [
			'element'     => $element,
			'title'       => $title,
			'description' => $description,
			'side'        => 'top'
		] );

		// Initialise onboarding.
		$onboarding = new OnboardingFramework( $this->plugin );

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

		$onboarding->init_onboarding();
	}
}

new Onboarding();
