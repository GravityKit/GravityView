<?php

namespace GV;

use GravityKit\Foundation\Helpers\Onboarding as GKOnboarding;

class Onboarding {

	private $plugin = 'GV';

	public function __construct() {

		$onboarding = new GKOnboarding( $this->plugin );

		$steps = [
			[
				'element' => '#titlewrap',
				'popover' => [
					'title'       => __( 'Start by giving your View a name.', 'gk-gravityview' ),
					'description' => __( 'Depending on your website, the name may display publicly on the front end.', 'gk-gravityview' ),
				],
			],
			[
				'element' => '#gravityview_form_id',
				'popover' => [
					'title'       => __( 'Next, select what form submissions to show', 'gk-gravityview' ),
					'description' => __( 'Choose a Gravity Form with the entry data that you want to display on the frontend of your website.', 'gk-gravityview' ),
				],
			],
			[
				'element' => '#gravityview_select_template',
				'popover' => [
					'title'       => __( 'Now choose a View type.', 'gk-gravityview' ),
					'description' => __( 'GravityView includes different View types, allowing you to display your data using different layouts. Go ahead and select the type you want for your data. You can create multiple Views of same data using different View types!', 'gk-gravityview' ),
					'side'        => 'top',
				],
			],
		];

		$onboarding->add_steps( $steps );

		$onboarding->init_onboarding();
	}
}

new Onboarding();
