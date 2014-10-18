<?php
/**
 * GravityView templating engine class
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */


if( ! class_exists( 'Gamajo_Template_Loader' ) ) {
	require( GRAVITYVIEW_DIR . 'includes/lib/class-gamajo-template-loader.php' );
}


class GravityView_View extends Gamajo_Template_Loader {

	// Prefix for filter names.
	protected $filter_prefix = 'gravityview';

	// Directory name where custom templates for this plugin should be found in the theme.
	protected $theme_template_directory = 'gravityview';

	// Reference to the root directory path of this plugin.
	protected $plugin_directory = GRAVITYVIEW_DIR;

	/**
	 * Store templates locations that have already been located
	 * @var array
	 */
	protected $located_templates = array();

	/**
	 * Construct the view object
	 * @param  array       $atts Associative array to set the data of
	 */
	function __construct( $atts = array() ) {

		$atts = wp_parse_args( $atts, array(
			'form_id' => NULL,
			'view_id' => NULL,
			'fields'  => NULL,
			'context' => NULL,
			'post_id' => NULL,
			'form'    => NULL,
			'atts'	  => NULL,
		) );

		foreach ($atts as $key => $value) {
			$this->{$key} = $value;
		}


		// Add granular overrides
		add_filter( $this->filter_prefix . '_get_template_part', array( $this, 'add_id_specific_templates' ), 10, 3 );

		// widget logic
		add_action( 'gravityview_before', array( $this, 'render_widget_hooks' ) );
		add_action( 'gravityview_after', array( $this, 'render_widget_hooks' ) );
	}

	/**
	 * In order to improve lookup times, we store located templates in a local array.
	 *
	 * This improves performance by up to 1/2 second on a 250 entry View with 7 columns showing
	 *
	 * @inheritdoc
	 * @see Gamajo_Template_Loader::locate_template()
	 */
	function locate_template( $template_names, $load = false, $require_once = true ) {

		$located = false;

		if( is_string( $template_names ) && isset( $this->located_templates[ $template_names ] ) ) {
			$located = $this->located_templates[ $template_names ];

		} else {

			// Set $load to always falso so we handle it here.
			$located = parent::locate_template( $template_names, false, $require_once );

			if( is_string( $template_names ) ) {
				$this->located_templates[ $template_names ] = $located;
			}
		}

		if ( $load && $located ) {
			load_template( $located, $require_once );
		}

		return $located;
	}

	/**
	 * Magic Method: Instead of throwing an errow when a variable isn't set, return null.
	 * @param  string      $name Key for the data retrieval.
	 * @return mixed|null    The stored data.
	 */
	public function __get( $name ) {
		if( isset( $this->{$name} ) ) {
			return $this->{$name};
		} else {
			return NULL;
		}
	}

	/**
	 * Enable overrides of GravityView templates on a granular basis
	 *
	 * The loading order is:
	 *
	 * - view-[View ID]-table-footer.php
	 * - form-[Form ID]-table-footer.php
	 * - page-[ID of post or page where view is embedded]-table-footer.php
	 * - table-footer.php
	 *
	 * @see  Gamajo_Template_Loader::get_template_file_names() Where the filter is
	 * @param array $templates Existing list of templates.
	 * @param [type] $slug      [description]
	 * @param [type] $name      [description]
	 */
	function add_id_specific_templates( $templates, $slug, $name ) {

		$additional = array();

		// form-19-table-body.php
		$additional[] = sprintf( 'form-%d-%s-%s.php', $this->form_id, $slug, $name );

		// view-3-table-body.php
		$additional[] = sprintf( 'view-%d-%s-%s.php', $this->view_id, $slug, $name );

		if( !empty( $this->post_id ) ) {

			// page-19-table-body.php
			$additional[] = sprintf( 'page-%d-%s-%s.php', $this->post_id, $slug, $name );
		}

		// Combine with existing table-body.php and table.php
		$templates = array_merge( $additional, $templates );

		do_action( 'gravityview_log_debug', '[add_id_specific_templates] List of Template Files', $templates );

		return $templates;
	}

	// Load the template
	public function render( $slug, $name, $require_once = true ) {

		$template_file = $this->get_template_part( $slug, $name, false );

		do_action( 'gravityview_log_debug', '[render] Rendering Template File', $template_file );

		if( !empty( $template_file) ) {

			if ( $require_once ) {
				require_once( $template_file );
			} else {
				require( $template_file );
			}

		}
	}

	public function render_widget_hooks( $view_id ) {

		if( empty( $view_id ) || 'single' == gravityview_get_context() ) {
			return;
		}

		$view_data = gravityview_get_current_view_data( $view_id );

		wp_enqueue_style( 'gravityview_default_style');

		// get View widget configuration
		$widgets = $view_data['widgets'];

		$rows = GravityView_Plugin::get_default_widget_areas();

		switch( current_filter() ) {
			case 'gravityview_before':
				$zone = 'header';
				break;
			case 'gravityview_after':
				$zone = 'footer';
				break;
		}

		// Prevent being called twice
		if( did_action( $zone.'_'.$view_id.'_widgets' ) ) { return; }

		?>

		<div class="gv-grid">
			<?php
			foreach( $rows as $row ) :
				foreach( $row as $col => $areas ) :
					$column = ($col == '2-2') ? '1-2 gv-right' : $col.' gv-left';
				?>
					<div class="gv-grid-col-<?php echo esc_attr( $column ); ?>">
						<?php
						if( !empty( $areas ) ) {
							foreach( $areas as $area ) {
								if( !empty( $widgets[ $zone .'_'. $area['areaid'] ] ) ) {
									foreach( $widgets[ $zone .'_'. $area['areaid'] ] as $widget ) {
										do_action( "gravityview_render_widget_{$widget['id']}", $widget );
									}
								}
							}
						} ?>
					</div>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</div>

		<?php
		// Prevent being called twice
		do_action( $zone.'_'.$view_id.'_widgets' );
	}

}

