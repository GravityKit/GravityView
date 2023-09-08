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

		$onboarding->init_onboarding();
	}
}

new Onboarding();
