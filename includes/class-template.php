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


class GravityView_View extends Gamajo_Template_Loader {

	protected $vars = array();

	// Prefix for filter names.
	protected $filter_prefix = 'gravityview';

	// Directory name where custom templates for this plugin should be found in the theme.
	protected $theme_template_directory = '';
	//apply_filters( 'gravityview_theme_template_directory', '' );

	// Reference to the root directory path of this plugin.
	protected $plugin_directory = GRAVITYVIEW_DIR;

	function __construct() {
		// widget logic
		add_action( 'gravityview_before', array( $this, 'render_widget_hooks' ) );
		add_action( 'gravityview_after', array( $this, 'render_widget_hooks' ) );
	}

	// Magic methods
	public function __set( $name, $value ) {
		$this->vars[ $name ] = $value;
	}

	public function __get( $name ) {
		return $this->vars[ $name ];
	}

	// Load the template
	public function render( $slug, $name, $require_once = true ) {

		$template_file = $this->get_template_part( $slug, $name, false );

		if( !empty( $template_file) ) {
			if ( $require_once )
				require_once( $template_file );
			else
				require( $template_file );
		}
	}

	public function render_widget_hooks( $view_id ) {

		if( empty( $view_id ) || 'single' == gravityview_get_context() ) {
			return;
		}

		// get View widget configuration
		$widgets = get_post_meta( $view_id, '_gravityview_directory_widgets', true );

		$rows = GravityView_Plugin::get_default_widget_areas();

		switch( current_filter() ) {
			case 'gravityview_before':
				$zone = 'header';
				break;
			case 'gravityview_after':
				$zone = 'footer';
				break;
		}

		foreach( $rows as $row ) :
			foreach( $row as $col => $areas ) :
				$column = ($col == '2-2') ? '1-2' : $col; ?>
				<div class="gv-view-col-<?php echo esc_attr( $column ); ?>">
					<?php
					foreach( $areas as $area ) {
						if( !empty( $widgets[ $zone .'_'. $area['areaid'] ] ) ) {
							foreach( $widgets[ $zone .'_'. $area['areaid'] ] as $widget ) {
								do_action( "gravityview_render_widget_{$widget['id']}", $widget );
							}
						}
					} ?>
				</div>
			<?php endforeach; ?>
		<?php endforeach; ?>

		<?php
	}



}






?>