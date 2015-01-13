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

		$widget_ops = array(
			'classname' => 'widget_gravityview_search',
			'description' => __( "A search form for a specific GravityView.", 'gravityview')
		);

		$widget_display = array(
			'width' => 400
		);

		parent::__construct( 'gravityview_search', __( 'GravityView Search', 'GravityView Search widget' ), $widget_ops, $widget_display );

		if( !class_exists( 'GravityView_Widget_Search' ) ) {
			GravityView_Plugin::getInstance()->register_widgets();
		}

		$gravityview_widget = GravityView_Widget_Search::getInstance();

		// frontend - filter entries
		add_filter( 'gravityview_fe_search_criteria', array( $gravityview_widget, 'filter_entries' ), 10, 1 );

		// frontend - add template path
		add_filter( 'gravityview_template_paths', array( $gravityview_widget, 'add_template_path' ) );

	}

	public function widget( $args, $instance ) {

		/** This filter is documented in wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		GravityView_Widget_Search::getInstance()->render_frontend( $args, $content, $context );

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'view' => 0, 'search_settings' => '' ) );
		$title           = $instance['title'];
		$view            = $instance['view'];
		$search_settings = $instance['search_settings'];

		$views = GVCommon::get_all_views();

		// If there are no views set up yet, we get outta here.
		if( empty( $views ) ) {
			echo '<div id="select_gravityview_view"><div class="wrap">'. GravityView_Post_Types::no_views_text() .'</div></div>';
			return;
		}
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>

		<div class="gv-fields" data-fieldid="search_bar">

			<p><label for="gravityview_view_id"><?php _e('View:', 'gravityview'); ?>
				<select id="gravityview_view_id" name="<?php echo $this->get_field_name('view'); ?>">
					<option value=""><?php esc_html_e( '&mdash; Select a View &mdash;', 'gravityview' ); ?></option>
					<?php
					foreach( $views as $view_option ) {
						$title = empty( $view_option->post_title ) ? __('(no title)', 'gravityview') : $view_option->post_title;
						echo '<option value="'. $view_option->ID .'" ' . selected( esc_attr($view), $view_option->ID, false ) . '>'. esc_html( sprintf('%s #%d', $title, $view_option->ID ) ) .'</option>';
					}
					?>
				</select>
			</label></p>

			<p id="gv-widget-search-settings-link"><a href="#gv-search-settings"><span class="dashicons-admin-generic dashicons"></span>Configure Search Settings</a></p>

			<div class="gv-dialog-options" title="<?php esc_html_e('Search Fields', 'gravityview'); ?>">
				<div class="">
					<div class="gv-setting-container screen-reader-text">
						<input id="<?php echo $this->get_field_id('search_settings'); ?>" name="<?php echo $this->get_field_name('search_settings'); ?>" type="hidden" value="" class="gv-search-fields-value">
					</div>
				</div>
			</div>

			<!-- Placeholder. Required for JS. -->
			<input type="hidden" id="gravityview_directory_template" />
			<input type="hidden" id="gravityview_form_id" />
			<!-- END Placeholder. Required for JS. -->

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

}

/**
 * Register the GravityView widget
 * @return void
 */
function gravityview_register_search_widget() {

	register_widget( 'WP_Widget_GravityView_Search' );

}

add_action( 'widgets_init', 'gravityview_register_search_widget', 20 );
