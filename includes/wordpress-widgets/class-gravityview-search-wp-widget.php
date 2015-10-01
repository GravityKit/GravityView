<?php


/**
 * Search widget class
 * @since 1.6
 */
class GravityView_Search_WP_Widget extends WP_Widget {

	public function __construct() {

		$widget_ops = array(
			'classname' => 'widget_gravityview_search',
			'description' => __( 'A search form for a specific GravityView.', 'gravityview')
		);

		$widget_display = array(
			'width' => 400
		);

		parent::__construct( 'gravityview_search', __( 'GravityView Search', 'gravityview' ), $widget_ops, $widget_display );

		$this->load_required_files();

		$gravityview_widget = GravityView_Widget_Search::getInstance();

		// frontend - filter entries
		add_filter( 'gravityview_fe_search_criteria', array( $gravityview_widget, 'filter_entries' ), 10, 1 );

		// frontend - add template path
		add_filter( 'gravityview_template_paths', array( $gravityview_widget, 'add_template_path' ) );

		unset( $gravityview_widget );
	}

	private function load_required_files() {
		if( !class_exists( 'GravityView_Widget_Search' ) ) {
			gravityview_register_gravityview_widgets();
		}
	}

	public function widget( $args, $instance ) {

		// Don't show unless a View ID has been set.
		if( empty( $instance['view_id'] ) ) {

			do_action('gravityview_log_debug', sprintf( '%s[widget]: No View ID has been defined. Not showing the widget.', get_class($this)), $instance );

			return;
		}

		/** This filter is documented in wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		// @todo Add to the widget configuration form
		$instance['search_layout'] = apply_filters( 'gravityview/widget/search/layout', 'vertical', $instance );

		$instance['context'] = 'wp_widget';

		// form
		$instance['form_id'] = GVCommon::get_meta_form_id( $instance['view_id'] );
		$instance['form'] = GVCommon::get_form( $instance['form_id'] );

		$gravityview_view = new GravityView_View( $instance );

		GravityView_Widget_Search::getInstance()->render_frontend( $instance );

		echo $args['after_widget'];
	}

	/**
	 * @inheritDoc
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		if( $this->is_preview() ) {
			//Oh! Sorry but still not fully compatible with customizer
			return $instance;
		}

		$defaults = array(
			'title' => '',
			'view_id' => 0,
			'post_id' => '',
			'search_fields' => '',
		);

		$new_instance = wp_parse_args( (array) $new_instance, $defaults );

		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['view_id'] = absint( $new_instance['view_id'] );
		$instance['search_fields'] = $new_instance['search_fields'];
		$instance['post_id'] = $new_instance['post_id'];

		$is_valid_embed_id = GravityView_View_Data::is_valid_embed_id( $new_instance['post_id'], $instance['view_id'] );

		//check if post_id is a valid post with embedded View
		$instance['error_post_id'] = is_wp_error( $is_valid_embed_id ) ? $is_valid_embed_id->get_error_message() : NULL;

		// Share that the widget isn't brand new
		$instance['updated']  = 1;

		return $instance;
	}

	/**
	 * @inheritDoc
	 */
	public function form( $instance ) {

		// @todo Make compatible with Customizer
		if( $this->is_preview() ) {

			$warning = sprintf( esc_html__( 'This widget is not configurable from this screen. Please configure it on the %sWidgets page%s.', 'gravityview' ), '<a href="'.admin_url('widgets.php').'">', '</a>' );

			echo wpautop( GravityView_Admin::get_floaty() . $warning );

			return;
		}

		$defaults = array(
			'title' => '',
			'view_id' => 0,
			'post_id' => '',
			'search_fields' => ''
		);

		$instance = wp_parse_args( (array) $instance, $defaults );

		$title    = $instance['title'];
		$view_id  = $instance['view_id'];
		$post_id  = $instance['post_id'];
		$search_fields = $instance['search_fields'];

		$views = GVCommon::get_all_views();

		// If there are no views set up yet, we get outta here.
		if( empty( $views ) ) : ?>
			<div id="select_gravityview_view">
				<div class="wrap"><?php echo GravityView_Post_Types::no_views_text(); ?></div>
			</div>
			<?php return;
		endif;
		?>

		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'gravityview'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></label></p>

		<?php
		/**
		 * Display errors generated for invalid embed IDs
		 * @see GravityView_View_Data::is_valid_embed_id
		 */
		if( isset( $instance['updated'] ) && empty( $instance['view_id'] ) ) {
			?>
			<div class="error inline hide-on-view-change">
				<p><?php esc_html_e('Please select a View to search.', 'gravityview'); ?></p>
			</div>
			<?php
			unset ( $error );
		}
		?>

		<p>
			<label for="gravityview_view_id"><?php _e( 'View:', 'gravityview' ); ?></label>
			<select id="gravityview_view_id" name="<?php echo $this->get_field_name('view_id'); ?>" class="widefat">
				<option value=""><?php esc_html_e( '&mdash; Select a View &mdash;', 'gravityview' ); ?></option>
				<?php
				foreach( $views as $view_option ) {
					$title = empty( $view_option->post_title ) ? __('(no title)', 'gravityview') : $view_option->post_title;
					echo '<option value="'. $view_option->ID .'" ' . selected( esc_attr( $view_id ), $view_option->ID, false ) . '>'. esc_html( sprintf('%s #%d', $title, $view_option->ID ) ) .'</option>';
				}
				?>
			</select>

		</p>

		<?php
		/**
		 * Display errors generated for invalid embed IDs
		 * @see GravityView_View_Data::is_valid_embed_id
		 */
		if( !empty( $instance['error_post_id'] ) ) {
			?>
			<div class="error inline">
				<p><?php echo $instance['error_post_id']; ?></p>
			</div>
			<?php
			unset ( $error );
		}
		?>

		<p>
			<label for="<?php echo $this->get_field_id('post_id'); ?>"><?php esc_html_e( 'If Embedded, Page ID:', 'gravityview' ); ?></label>
			<input class="code" size="3" id="<?php echo $this->get_field_id('post_id'); ?>" name="<?php echo $this->get_field_name('post_id'); ?>" type="text" value="<?php echo esc_attr( $post_id ); ?>" />
			<span class="howto"><?php
				esc_html_e('To have a search performed on an embedded View, enter the ID of the post or page where the View is embedded.', 'gravityview' );
				echo ' '.gravityview_get_link('http://docs.gravityview.co/article/222-the-search-widget', __('Learn more&hellip;', 'gravityview' ), 'target=_blank' );
				?></span>
		</p>

		<hr />

		<?php // @todo: move style to CSS ?>
		<div style="margin-bottom: 1em;">
			<label class="screen-reader-text" for="<?php echo $this->get_field_id('search_fields'); ?>"><?php _e( 'Searchable fields:', 'gravityview' ); ?></label>
			<div class="gv-widget-search-fields" title="<?php esc_html_e('Search Fields', 'gravityview'); ?>">
				<input id="<?php echo $this->get_field_id('search_fields'); ?>" name="<?php echo $this->get_field_name('search_fields'); ?>" type="hidden" value="<?php echo esc_attr( $search_fields ); ?>" class="gv-search-fields-value">
			</div>

		</div>

		<script>
			// When the widget is saved or added, refresh the Merge Tags (here for backward compatibility)
			// WordPress 3.9 added widget-added and widget-updated actions
			jQuery('#<?php echo $this->get_field_id( 'view_id' ); ?>').trigger( 'change' );
		</script>
	<?php
	}

}