<?php
/**
 * @file class-gravityview-recent-entries-widget.php
 */

/**
 * Class GravityView_Recent_Entries_Widget
 * @since 1.6
 */
class GravityView_Recent_Entries_Widget extends WP_Widget {


	function __construct( ) {

		$name = __('GravityView Recent Entries', 'gravityview');

		$widget_options = array(
			'description' => __( 'Display the most recent entries for a View', 'gravityview' ),
		);

		parent::__construct( 'gv_recent_entries', $name, $widget_options );

		$this->initialize();
	}

	private function initialize() {

		add_action('admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts') );

		add_action( 'wp_ajax_gv_get_view_merge_tag_data', array( $this, 'ajax_get_view_merge_tag_data' ) );

	}

	/**
	 * When the widget View is changed, update the Merge Tag data
	 *
	 * @since 1.6
	 */
	function ajax_get_view_merge_tag_data() {

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajax_widget' ) ) {
			exit( false );
		}

		$form_id  = gravityview_get_form_id( $_POST['view_id'] );

		$form = RGFormsModel::get_form_meta( $form_id );

		$output = array(
			'form' => array(
				'id' => $form['id'],
				'title' => $form['title'],
				'fields' => $form['fields'],
			),
			'mergeTags' => GFCommon::get_merge_tags( $form['fields'], '', false ),
		);

		echo json_encode( $output );

		exit;
	}

	/**
	 * Enable the merge tags functionality
	 *
	 * @since 1.6
	 */
	function admin_enqueue_scripts() {
		global $pagenow;

		if( $pagenow === 'widgets.php' ) {

			$script_debug = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

			wp_enqueue_script( 'gravityview_widgets', plugins_url('assets/js/admin-widgets'.$script_debug.'.js', GRAVITYVIEW_FILE), array( 'jquery', 'gform_gravityforms' ), GravityView_Plugin::version );

			wp_localize_script( 'gravityview_widgets', 'GVWidgets', array(
				'nonce' => wp_create_nonce( 'gravityview_ajax_widget' )
			));

			wp_enqueue_style( 'gravityview_views_styles', plugins_url('assets/css/admin-views.css', GRAVITYVIEW_FILE), array('dashicons' ), GravityView_Plugin::version );
		}

	}

	/**
	 * @since 1.6
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	function widget( $args, $instance ) {

		// Don't have the Customizer render too soon.
		if( empty( $instance['view_id'] ) ) {
			return;
		}

		$args['id']        = ( isset( $args['id'] ) ) ? $args['id'] : 'gv_recent_entries';
		$instance['title'] = ( isset( $instance['title'] ) ) ? $instance['title'] : '';

		$title = apply_filters( 'widget_title', $instance[ 'title' ], $instance, $args['id'] );

		echo $args['before_widget'];

		if ( !empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		/**
		 * @action `gravityview/widget/recent-entries/before_widget` Before recent entries are displayed in the WordPress widget
		 * @param array $args     Display arguments including before_title, after_title, before_widget, and after_widget.
		 * @param array $instance The settings for the particular instance of the widget.
		 */
		do_action( 'gravityview/widget/recent-entries/before_widget', $args, $instance );

		// Print the entry list
		echo $this->get_output( $instance );

		/**
		 * @action `gravityview/widget/recent-entries/after_widget` After recent entries are displayed in the WordPress widget
		 * @param array $args     Display arguments including before_title, after_title, before_widget, and after_widget.
		 * @param array $instance The settings for the particular instance of the widget.
		 */
		do_action( 'gravityview/widget/recent-entries/after_widget', $args, $instance );

		echo $args['after_widget'];
	}

	/**
	 * Get the HTML output for the entry list.
	 *
	 * @since 1.7.2
	 *
	 * @param array $instance The settings for the particular instance of the widget.
	 *
	 * @return string
	 */
	private function get_output( $instance ) {

		$form_id = gravityview_get_form_id( $instance['view_id'] );

		$form = gravityview_get_form( $form_id );

		$entries = $this->get_entries( $instance, $form_id );

		/**
		 * @since 1.6.1
		 * @var int $entry_link_post_id The ID to use as the parent post for the entry
		 */
		$entry_link_post_id = ( empty( $instance['error_post_id'] ) && !empty( $instance['post_id'] ) ) ? $instance['post_id'] : $instance['view_id'];

		/**
		 * Generate list output
		 * @since 1.7.2
		 */
		$List = new GravityView_Entry_List( $entries, $entry_link_post_id, $form, $instance['link_format'], $instance['after_link'], 'recent-entries-widget' );

		$output = $List->get_output();

		/**
		 * Modify the HTML before it's echo'd
		 * @param string $output HTML to be displayed
		 * @param array $instance Widget settings
		 */
		$output = apply_filters( 'gravityview/widget/recent-entries/output', $output, $instance );

		return $output;
	}


	/**
	 * Get the entries that will be shown in the current widget
	 *
	 * @param  array $instance Settings for the current widget
	 *
	 * @return array $entries Multidimensional array of Gravity Forms entries
	 */
	private function get_entries( $instance, $form_id ) {

		// Get the settings for the View ID
		$view_settings = gravityview_get_template_settings( $instance['view_id'] );

        // Set the context view ID to avoid conflicts with the Advanced Filter extension.
        $criteria['context_view_id'] = $instance['view_id'];

		$instance['limit'] = isset( $instance['limit'] ) ? $instance['limit'] : 10;
		$view_settings['id'] = $instance['view_id'];
		$view_settings['page_size'] = $instance['limit'];

		// Prepare paging criteria
		$criteria['paging'] = array(
			'offset' => 0,
			'page_size' => $instance['limit']
		);

		// Prepare Search Criteria
		$criteria['search_criteria'] = array( 'field_filters' => array() );
		$criteria['search_criteria'] = GravityView_frontend::process_search_only_approved( $view_settings, $criteria['search_criteria']);
		$criteria['search_criteria']['status'] = apply_filters( 'gravityview_status', 'active', $view_settings );

		/**
		 * Modify the search parameters before the entries are fetched
		 */
		$criteria = apply_filters('gravityview/widget/recent-entries/criteria', $criteria, $instance, $form_id );

		$results = GVCommon::get_entries( $form_id, $criteria );

		return $results;
	}

	/**
	 * @since 1.6
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Widget form settings after update
	 * @param array $old_instance Widget form settings before update
	 *
	 * @return array Calculated widget settings after processing
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = $new_instance;

		// Force positive number
		$instance['limit'] = empty( $instance['limit'] ) ? 10 : absint( $instance['limit'] );

		$instance['view_id'] = intval( $instance['view_id'] );

		$instance['link_format'] = trim( rtrim( $instance['link_format'] ) );

		$instance['link_format'] = empty( $instance['link_format'] ) ? $old_instance['link_format'] : $instance['link_format'];

		$instance['post_id'] = empty( $instance['post_id'] ) ? '' : intval( $instance['post_id'] );

		$is_valid_embed_id = GravityView_View_Data::is_valid_embed_id( $instance['post_id'], $instance['view_id'] );

		//check if post_id is a valid post with embedded View
		$instance['error_post_id'] = is_wp_error( $is_valid_embed_id ) ? $is_valid_embed_id->get_error_message() : NULL;

		// Share that the widget isn't brand new
		$instance['updated']  = 1;

		/**
		 * Modify the updated instance. This will allow for validating any added instance settings externally.
		 *
		 * @param array $instance Calculated widget settings after processing
		 * @param array $new_instance Widget form settings after update
		 * @param array $old_instance Widget form settings before update
		 */
		$instance = apply_filters( 'gravityview/widget/update', $instance, $new_instance, $old_instance );

		return $instance;
	}

	/**
	 * @since 1.6
	 * @see WP_Widget::form()
	 */
	public function form( $instance ) {

		// Set up some default widget settings.
		$defaults = array(
			'title' 			=> __('Recent Entries', 'gravityview'),
			'view_id'           => NULL,
			'post_id'           => NULL,
			'limit'            => 10,
			'link_format'       => __('Entry #{entry_id}', 'gravityview'),
			'after_link'        => ''
		);

		$instance = wp_parse_args( (array) $instance, $defaults );

		?>

		<!-- Title -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'gravityview' ) ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>

		<!-- Download -->
		<?php
		$args = array(
			'post_type'      => 'gravityview',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);
		$views = get_posts( $args );

		// If there are no views set up yet, we get outta here.
		if( empty( $views ) ) {
			echo '<div id="select_gravityview_view"><div class="wrap">' . GravityView_Admin::no_views_text() . '</div></div>';
			return;
		}

		?>

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
			<label for="<?php echo esc_attr( $this->get_field_id( 'view_id' ) ); ?>"><?php esc_html_e('Select a View', 'gravityview'); ?></label>
			<select class="widefat gv-recent-entries-select-view" name="<?php echo esc_attr( $this->get_field_name( 'view_id' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'view_id' ) ); ?>">
				<option value=""><?php esc_html_e( '&mdash; Select a View as Entries Source &mdash;', 'gravityview' ); ?></option>
				<?php

				foreach( $views as $view ) {
					$title = empty( $view->post_title ) ? __('(no title)', 'gravityview') : $view->post_title;
					echo '<option value="'. $view->ID .'"'.selected( absint( $instance['view_id'] ), $view->ID ).'>'. esc_html( sprintf('%s #%d', $title, $view->ID ) ) .'</option>';
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
			<input class="code" size="3" id="<?php echo $this->get_field_id('post_id'); ?>" name="<?php echo $this->get_field_name('post_id'); ?>" type="text" value="<?php echo esc_attr( $instance['post_id'] ); ?>" />
			<span class="howto"><?php
				esc_html_e('To have a search performed on an embedded View, enter the ID of the post or page where the View is embedded.', 'gravityview' );
				echo ' '.gravityview_get_link('http://docs.gravityview.co/article/222-the-search-widget', __('Learn more&hellip;', 'gravityview' ), 'target=_blank' );
				?></span>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>">
				<span><?php _e( 'Number of entries to show:', 'gravityview' ); ?></span>
			</label>
			<input class="code" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="number" value="<?php echo intval( $instance['limit'] ); ?>" size="3" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'link_format' ); ?>">
				<span><?php _e( 'Entry link text (required)', 'gravityview' ); ?></span>
			</label>
			<input id="<?php echo $this->get_field_id( 'link_format' ); ?>" name="<?php echo $this->get_field_name( 'link_format' ); ?>" type="text" value="<?php echo esc_attr( $instance['link_format'] ); ?>" class="widefat merge-tag-support mt-position-right mt-hide_all_fields" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'after_link' ); ?>">
				<span><?php _e( 'Text or HTML to display after the link (optional)', 'gravityview' ); ?></span>
			</label>
			<textarea id="<?php echo $this->get_field_id( 'after_link' ); ?>" name="<?php echo $this->get_field_name( 'after_link' ); ?>" rows="5" class="widefat code merge-tag-support mt-position-right mt-hide_all_fields"><?php echo esc_textarea( $instance['after_link'] ); ?></textarea>
		</p>

		<?php

		/**
		 * @action `gravityview_recent_entries_widget_form` Displayed at the bottom of the Recent Entries widget admin form
		 * @param GravityView_Recent_Entries_Widget $this WP_Widget object
		 * @param array $instance Current widget instance
		 */
		do_action( 'gravityview_recent_entries_widget_form' , $this, $instance );

		?>

		<script>
			// When the widget is saved or added, refresh the Merge Tags (here for backward compatibility)
			// WordPress 3.9 added widget-added and widget-updated actions
			jQuery('#<?php echo $this->get_field_id( 'view_id' ); ?>').trigger( 'change' );
		</script>
	<?php }

}