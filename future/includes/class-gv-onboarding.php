<?php

namespace GV;

use GravityKit\Foundation\Onboarding\Framework as OnboardingFramework;
use GravityKit\Foundation\Onboarding\Step;

class Onboarding {

	private $plugin = GRAVITYVIEW_FILE;

	public function __construct() {

		$onboarding = new OnboardingFramework( $this->plugin );

		$element     = '#titlewrap';
		$title       = __( 'Start by giving your View a name.', 'gk-gravityview' );
		$description = __( 'Depending on your website, the name may display publicly on the front end.', 'gk-gravityview' );

		$step = new Step( $element, $title, $description );

		$onboarding->steps->add( $step );

		$element     = '#gravityview_form_id';
		$title       = __( 'Start by giving your View a name.', 'gk-gravityview' );
		$description = __( 'Depending on your website, the name may display publicly on the front end.', 'gk-gravityview' );

		$step = new Step( $element, $title, $description );

		$onboarding->steps->add( $step );

		$element     = '#gravityview_select_template';
		$title       = __( 'Now choose a View type.', 'gk-gravityview' );
		$description = __( 'GravityView includes different View types, allowing you to display your data using different layouts. Go ahead and select the type you want for your data. You can create multiple Views of same data using different View types!', 'gk-gravityview' );

		$step = new Step( $element, $title, $description );
		$step->setSide( 'top' );
		$onboarding->steps->add( $step );

		$element     = 'ul.ui-tabs-nav li.ui-tabs-tab a[href="#directory-view"]';
		$title       = __( 'Welcome to GravityView\'s drag-and-drop View editor!' , 'gk-gravityview' );
		$description = __( 'Here you can start building your new application by adding fields and widgets. Let\'s get familiar with the basic functionality.', 'gk-gravityview' );

		$step = new Step( $element, $title, $description );
		$step->setSide( 'top' );
		$onboarding->steps->add( $step );

		$element     = 'ul.ui-tabs-nav li.ui-tabs-tab a[href="#directory-view"]';
		$title       = __( '' , 'gk-gravityview' );
		$description = __( 'We are currently configuring the Multiple Entries layout. This page shows all the entries in a View. In future steps, you will learn how to control what entries are shown.', 'gk-gravityview' );

		$step = new Step( $element, $title, $description );
		$step->setSide( 'top' );
		$onboarding->steps->add( $step );

		$element     = '#directory-header-widgets';
		$title       = __( '' , 'gk-gravityview' );
		$description = __( 'This area is for widgets. There are widget areas above and below where entries are shown. Widgets are tools for navigating a View (e.g. a search bar).', 'gk-gravityview' );

		$step = new Step( $element, $title, $description );
		$step->setSide( 'top' );
		$onboarding->steps->add( $step );

		$element     = '#directory-active-fields';
		$title       = __( '' , 'gk-gravityview' );
		$description = __( 'This is where the form entries are shown. You can choose which fields to display by adding them here. Click this gear icon to configure the field settings.', 'gk-gravityview' );

		$step = new Step( $element, $title, $description );
		$step->setSide( 'top' );
		$onboarding->steps->add( $step );

		$element     = '.ui-dialog';
		$title       = __( '' , 'gk-gravityview' );
		$description = __( 'Here you can set custom labels, modify visibility settings, add css classes, and more. As you can see, GravityView has made this field a link to the single entry.', 'gk-gravityview' );

		$step = new Step( $element, $title, $description );
		$step->setSide( 'top' );
		$onboarding->steps->add( $step );


		$element     = 'ul.ui-tabs-nav li.ui-tabs-tab a[href="#single-view"]';
		$title       = __( '' , 'gk-gravityview' );
		$description = __( "<img src='https://i.imgur.com/EAQhHu5.gif' style='height: 202.5px; width: 270px;' />Here you can configure the Single Entry Layout, which is the screen that users will see when they click to view more details about a specific entry.", 'gk-gravityview' );

		$step = new Step( $element, $title, $description );
		$step->setSide( 'top' );
		$onboarding->steps->add( $step );

		$element     = 'ul.ui-tabs-nav li.ui-tabs-tab a[href="#edit-view"]';
		$title       = __( '' , 'gk-gravityview' );
		$description = __( '(Optional) Configure the Edit Entry Layout to choose which fields are editable by users from the front end.', 'gk-gravityview' );

		$step = new Step( $element, $title, $description );
		$step->setSide( 'top' );
		$onboarding->steps->add( $step );

		$element     = '#gravityview_settings';
		$title       = __( '' , 'gk-gravityview' );
		$description = __( 'In settings you will find a range of options for customizing your View. Click on tabs to see available options.', 'gk-gravityview' );

		$step = new Step( $element, $title, $description );
		$step->setSide( 'top' );
		$onboarding->steps->add( $step );

		$onboarding->init_onboarding();
	}
}

new Onboarding();
