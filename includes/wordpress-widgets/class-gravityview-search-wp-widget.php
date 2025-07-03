<?php


/**
 * Search widget class
 *
 * @since 1.6
 */
class GravityView_Search_WP_Widget extends WP_Widget {

	public function __construct() {

		$widget_ops = array(
			'classname'   => 'widget_gravityview_search',
			'description' => __( 'A search form for a specific GravityView.', 'gk-gravityview' ),
		);

		$widget_display = array(
			'width' => 650,
		);

		parent::__construct( 'gravityview_search', __( 'GravityView Search', 'gk-gravityview' ), $widget_ops, $widget_display );

		$this->load_required_files();

		$gravityview_widget = GravityView_Widget_Search::getInstance();

		// frontend - filter entries
		add_filter( 'gravityview_fe_search_criteria', array( $gravityview_widget, 'filter_entries' ), 10, 3 );

		// frontend - add template path
		add_filter( 'gravityview_template_paths', array( $gravityview_widget, 'add_template_path' ) );

		unset( $gravityview_widget );
	}

	private function load_required_files() {
		if ( ! class_exists( 'GravityView_Widget_Search' ) ) {
			gravityview_register_gravityview_widgets();
		}
	}

	private static function get_defaults() {
		return array(
			'title'         => '',
			'view_id'       => 0,
			'post_id'       => '',
			'search_fields' => '',
			'search_clear'  => 0,
			'search_mode'   => 'any',
		);
	}

	public function widget( $args, $instance ) {

		if ( GVCommon::is_rest_request() ) {
			return false;
		}

		// Don't show unless a View ID has been set.
		if ( empty( $instance['view_id'] ) ) {

			gravityview()->log->debug( 'No View ID has been defined. Not showing the widget.', array( 'data' => $instance ) );

			return;
		}

		if ( ! class_exists( 'GravityView_View' ) ) {
			gravityview()->log->debug( 'GravityView_View does not exist. Not showing the widget.' );
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
		$instance['form']    = GVCommon::get_form( $instance['form_id'] );

		// We don't want to overwrite existing context, etc.
		$previous_view = GravityView_View::getInstance();

		/** @hack */
		new GravityView_View( $instance );

		GravityView_Widget_Search::getInstance()->render_frontend( $instance );

		/**
		 * Restore previous View context
		 *
		 * @hack
		 */
		new GravityView_View( $previous_view );

		echo $args['after_widget'];
	}

	/**
	 * @inheritDoc
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		if ( $this->is_preview() ) {
			// Oh! Sorry but still not fully compatible with customizer
			return $instance;
		}

		$new_instance = wp_parse_args( (array) $new_instance, self::get_defaults() );

		$instance['title']         = wp_strip_all_tags( $new_instance['title'] );
		$instance['view_id']       = absint( $new_instance['view_id'] );
		$instance['search_fields'] = $new_instance['search_fields'];
		$instance['post_id']       = $new_instance['post_id'];
		$instance['search_clear']  = $new_instance['search_clear'];
		$instance['search_mode']   = $new_instance['search_mode'];

		$is_valid_embed_id = GravityView_View_Data::is_valid_embed_id( $instance['post_id'], $instance['view_id'], true );

		// check if post_id is a valid post with embedded View
		$instance['error_post_id'] = is_wp_error( $is_valid_embed_id ) ? $is_valid_embed_id->get_error_message() : null;

		// Share that the widget isn't brand new
		$instance['updated'] = 1;

		return $instance;
	}

	/**
	 * @inheritDoc
	 */
	public function form( $instance ) {

		// @todo Make compatible with Customizer
		if ( $this->is_preview() ) {

			$warning = sprintf( esc_html__( 'This widget is not configurable from this screen. Please configure it on the %1$sWidgets page%2$s.', 'gk-gravityview' ), '<a href="' . admin_url( 'widgets.php' ) . '">', '</a>' );

			echo wpautop( GravityView_Admin::get_floaty() . $warning );

			return;
		}

		$instance = wp_parse_args( (array) $instance, self::get_defaults() );

		$title         = $instance['title'];
		$view_id       = $instance['view_id'];
		$post_id       = $instance['post_id'];
		$search_fields = $instance['search_fields'];
		$search_clear  = $instance['search_clear'];
		$search_mode   = $instance['search_mode'];

		$views = GVCommon::get_all_views();

		// If there are no views set up yet, we get outta here.
		if ( empty( $views ) ) { ?>
			<div id="select_gravityview_view">
				<div class="wrap"><?php echo GravityView_Admin::no_views_text(); ?></div>
			</div>
			<?php
			return;
		}
		?>

		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'gk-gravityview' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></label></p>

		<?php
		/**
		 * Display errors generated for invalid embed IDs
		 *
		 * @see GravityView_View_Data::is_valid_embed_id
		 */
		if ( isset( $instance['updated'] ) && empty( $instance['view_id'] ) ) {
			?>
			<div class="error inline hide-on-view-change">
				<p><?php esc_html_e( 'Please select a View to search.', 'gk-gravityview' ); ?></p>
			</div>
			<?php
			unset( $error );
		}
		?>

		<p>
			<label for="gravityview_view_id"><?php _e( 'View:', 'gk-gravityview' ); ?></label>
			<select id="gravityview_view_id" name="<?php echo $this->get_field_name( 'view_id' ); ?>" class="widefat">
				<option value=""><?php esc_html_e( '&mdash; Select a View &mdash;', 'gk-gravityview' ); ?></option>
				<?php
				foreach ( $views as $view_option ) {
					$title = empty( $view_option->post_title ) ? __( '(no title)', 'gk-gravityview' ) : $view_option->post_title;
					echo '<option value="' . $view_option->ID . '" ' . selected( esc_attr( $view_id ), $view_option->ID, false ) . '>' . esc_html( sprintf( '%s #%d', $title, $view_option->ID ) ) . '</option>';
				}
				?>
			</select>

		</p>

		<?php
		/**
		 * Display errors generated for invalid embed IDs
		 *
		 * @see GravityView_View_Data::is_valid_embed_id
		 */
		if ( ! empty( $instance['error_post_id'] ) ) {
			?>
			<div class="error inline">
				<p><?php echo $instance['error_post_id']; ?></p>
			</div>
			<?php
			unset( $error );
		}
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'post_id' ); ?>"><?php esc_html_e( 'If Embedded, Page ID:', 'gk-gravityview' ); ?></label>
			<input class="code" size="3" id="<?php echo $this->get_field_id( 'post_id' ); ?>" name="<?php echo $this->get_field_name( 'post_id' ); ?>" type="text" value="<?php echo esc_attr( $post_id ); ?>" />
			<span class="howto gv-howto">
			<?php
				esc_html_e( 'To have a search performed on an embedded View, enter the ID of the post or page where the View is embedded.', 'gk-gravityview' );
				echo ' ' . gravityview_get_link( 'https://docs.gravitykit.com/article/222-the-search-widget', __( 'Learn more&hellip;', 'gk-gravityview' ), 'target=_blank' );
			?>
				</span>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'search_clear' ); ?>"><?php esc_html_e( 'Show Clear button', 'gk-gravityview' ); ?>:</label>
			<input name="<?php echo $this->get_field_name( 'search_clear' ); ?>" type="hidden" value="0">
			<input id="<?php echo $this->get_field_id( 'search_clear' ); ?>" name="<?php echo $this->get_field_name( 'search_clear' ); ?>" type="checkbox" class="checkbox" value="1" <?php checked( $search_clear, 1, true ); ?>>
		</p>

		<p>
			<label><?php esc_html_e( 'Search Mode', 'gk-gravityview' ); ?>:</label>
			<label for="<?php echo $this->get_field_id( 'search_mode' ); ?>_any">
				<input id="<?php echo $this->get_field_id( 'search_mode' ); ?>_any" name="<?php echo $this->get_field_name( 'search_mode' ); ?>" type="radio" class="radio" value="any" <?php checked( $search_mode, 'any', true ); ?>>
				<?php esc_html_e( 'Match Any Fields', 'gk-gravityview' ); ?>
			</label>
			<label for="<?php echo $this->get_field_id( 'search_mode' ); ?>_all">
				<input id="<?php echo $this->get_field_id( 'search_mode' ); ?>_all" name="<?php echo $this->get_field_name( 'search_mode' ); ?>" type="radio" class="radio" value="all" <?php checked( $search_mode, 'all', true ); ?>>
				<?php esc_html_e( 'Match All Fields', 'gk-gravityview' ); ?>
			</label>
			<span class="howto gv-howto"><?php esc_html_e( 'Should search results match all search fields, or any?', 'gk-gravityview' ); ?></span
		</p>

		<hr />

		<?php // @todo: move style to CSS ?>
		<div style="margin-bottom: 1em;">
			<label class="screen-reader-text" for="<?php echo $this->get_field_id( 'search_fields' ); ?>"><?php _e( 'Searchable fields:', 'gk-gravityview' ); ?></label>
			<div class="gv-widget-search-fields" title="<?php esc_html_e( 'Search Fields', 'gk-gravityview' ); ?>">
				<input id="<?php echo $this->get_field_id( 'search_fields' ); ?>" name="<?php echo $this->get_field_name( 'search_fields' ); ?>" type="hidden" value="<?php echo esc_attr( $search_fields ); ?>" class="gv-search-fields-value">
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
