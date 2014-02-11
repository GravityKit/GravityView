<?php 
/**
 * GravityView templating engine class
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2013, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */
 

if( ! class_exists( 'Gamajo_Template_Loader' ) ) {
	require( GRAVITYVIEW_DIR . 'includes/lib/class-gamajo-template-loader.php' );
}


class GravityView_Template extends Gamajo_Template_Loader {
	
	protected $vars = array();
	
	// Prefix for filter names.
	protected $filter_prefix = 'gravityview';
	
	// Directory name where custom templates for this plugin should be found in the theme.
	protected $theme_template_directory = apply_filters( 'gravityview_theme_template_directory', '' );
	
	// Reference to the root directory path of this plugin.
	protected $plugin_directory = GRAVITYVIEW_DIR;
	
	// Magic methods
	public function __set( $name, $value ) {
		$this->vars[ $name ] = $value;
	}
	
	public function __get( $name ) {
		return $this->vars[ $name ];
	}
	
}






?>