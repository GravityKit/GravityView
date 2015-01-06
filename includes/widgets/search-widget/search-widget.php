<?php
/**
 * GravityView Search Widget
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since
 */

/**
 * Search widget class
 */
class WP_Widget_GravityView_Search extends WP_Widget {

	public function __construct() {
		$widget_ops = array('classname' => 'widget_gravityview_search', 'description' => __( "A search form for a specific GravityForm view.") );
		parent::__construct( 'gravityview_search', _x( 'GravityView Search', 'GravityView Search widget' ), $widget_ops );

		// frontend - filter entries
		add_filter( 'gravityview_fe_search_criteria', array( $this, 'filter_entries' ), 10, 1 );

		// frontend - add template path
		add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );


		// admin - add scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts_and_styles' ), 999 );
		add_filter( 'gravityview_noconflict_scripts', array( $this, 'register_no_conflict') );

		// ajax - get the searchable fields
		add_action( 'wp_ajax_gv_searchable_fields', array( 'GravityView_Widget_Search', 'get_searchable_fields' ) );
	}

	public function widget( $args, $instance ) {

		/** This filter is documented in wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		// Use current theme search form if it exists
		get_search_form();

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'view' => 0, 'search_settings' => '' ) );
		$title           = $instance['title'];
		$view            = $instance['view'];
		$search_settings = $instance['search_settings'];

		$views = get_posts( array('post_type' => 'gravityview', 'posts_per_page' => -1 ) );

		// If there are no views set up yet, we get outta here.
		if( empty( $views ) ) {
			echo '<div id="select_gravityview_view"><div class="wrap">'. GravityView_Post_Types::no_views_text() .'</div></div>';
			return;
		}
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id('view'); ?>"><?php _e('View:'); ?>
			<select id="<?php echo $this->get_field_id('view'); ?>" name="<?php echo $this->get_field_name('view'); ?>">
				<option value=""><?php esc_html_e( '&mdash; Select a View &mdash;', 'gravityview' ); ?></option>
				<?php
				foreach( $views as $view_option ) {
					$title = empty( $view_option->post_title ) ? __('(no title)', 'gravityview') : $view_option->post_title;
					echo '<option value="'. $view_option->ID .'" ' . selected( esc_attr($view), $view_option->ID, false ) . '>'. esc_html( sprintf('%s #%d', $title, $view_option->ID ) ) .'</option>';
				}
				?>
			</select>
		</label></p>
		<div class="gv-search-fields">
			<p><a href="#gv-search-settings"><span class="dashicons-admin-generic dashicons" style="text-decoration: none;padding-right: 5px;"></span>Configure Search Settings</a></p>
			<input id="<?php echo $this->get_field_id('search_settings'); ?>" name="<?php echo $this->get_field_name('search_settings'); ?>" type="hidden" value="" class="gv-search-fields-value">
		</div>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$new_instance = wp_parse_args((array) $new_instance, array( 'title' => '', 'view' => 0 ));
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['view'] = absint($new_instance['view']);
		return $instance;
	}

	function add_scripts_and_styles( $hook ) {

		wp_enqueue_script( 'gravityview_searchwidget_admin', plugins_url( 'assets/js/search-widget.js', __FILE__ ), array( 'jquery' ), GravityView_Plugin::version );


		/**
		 * Input Type labels l10n
		 * @see admin-search-widget.js (getSelectInput)
		 * @var array
		 */
		$input_labels = array(
			'input_text' => esc_html__( 'Text', 'gravityview'),
			'date' => esc_html__('Date', 'gravityview'),
			'select' => esc_html__( 'Select', 'gravityview' ),
			'multiselect' => esc_html__( 'Select (multiple values)', 'gravityview' ),
			'radio' => esc_html__('Radio', 'gravityview'),
			'checkbox' => esc_html__( 'Checkbox', 'gravityview' ),
			'single_checkbox' => esc_html__( 'Checkbox', 'gravityview' ),
			'link' => esc_html__('Links', 'gravityview')
		);

		/**
		 * Input Type groups
		 * @see admin-search-widget.js (getSelectInput)
		 * @var array
		 */
		$input_types = array(
			'text' => array( 'input_text' ),
			'address' => array( 'input_text' ),
			'date' => array( 'date' ),
			'boolean' => array( 'single_checkbox' ),
			'select' => array( 'select', 'radio', 'link' ),
			'multi' => array( 'select', 'multiselect', 'radio', 'checkbox', 'link' ),
		);

		wp_localize_script( 'gravityview_searchwidget_admin', 'gvSearchVar', array(
			'nonce' => wp_create_nonce( 'gravityview_ajaxsearchwidget'),
			'label_nofields' =>  esc_html__( 'No search fields configured yet.', 'gravityview' ),
			'label_addfield' =>  esc_html__( 'Add Search Field', 'gravityview' ),
			'label_searchfield' => esc_html__( 'Search Field', 'gravityview' ),
			'label_inputtype' => esc_html__( 'Input Type', 'gravityview' ),
			'input_labels' => json_encode( $input_labels ),
			'input_types' => json_encode( $input_types ),
		) );

	}

}


function gravityview_register_search_widget() {

	register_widget( 'WP_Widget_GravityView_Search' );

}
add_action( 'widgets_init', 'gravityview_register_search_widget' );
