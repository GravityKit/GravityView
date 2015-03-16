<?php

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class GravityView_View_Data {

	static $instance = NULL;

	protected $views = array();

	/**
	 *
	 * @param null $passed_post
	 */
	private function __construct( $passed_post = NULL ) {

		if( !empty( $passed_post ) ) {

			$id_or_id_array = $this->maybe_get_view_id( $passed_post );

			if( !empty( $id_or_id_array ) ) {
				$this->add_view( $id_or_id_array );
			}
		}

	}

	/**
	 * @return boolean
	 */
	public function has_multiple_views() {

		//multiple views
		return count( $this->get_views() ) > 1 ? true : false;
	}


	/**
	 * Figure out what the View ID is for a variable, if any.
	 *
	 * Can be:
	 *      - WP_Post (Either a `gravityview` post type or not)
	 *      - Multi-dimensional array of WP_Post objects
	 *      - Array with `view_id` or `id` key(s) set
	 *      - String of content that may include GravityView shortcode
	 *      - Number representing the Post ID or View ID
	 *
	 * @param mixed $passed_post See method description
	 *
	 * @return int|null|array ID of the View. If there are multiple views in the content, array of IDs parsed.
	 */
	public function maybe_get_view_id( $passed_post ) {

		$ids = array();

		if( ! empty( $passed_post ) ) {

			if( is_numeric( $passed_post ) ) {
				$passed_post = get_post( $passed_post );
			}

			// Convert WP_Posts into array
			if( $passed_post instanceof WP_Post ) {
				$passed_post = array( $passed_post);
			}

			if( is_array( $passed_post ) ) {

				foreach ( $passed_post as &$post) {
					if( ( get_post_type( $post ) === 'gravityview' ) ) {

						$ids[] = $post->ID;

					} else{
						$id = $this->parse_post_content( $post->post_content );

						$ids = array_merge( $ids, (array)$id );
					}

				}

			} else {

				if ( is_string( $passed_post ) ) {

					$id = $this->parse_post_content( $passed_post );
					$ids = array_merge( $ids, (array)$id );

				} else {
					$id = $this->get_id_from_atts( $passed_post );
					$ids[] = intval( $id );
				}


			}
		}

		if( empty($ids) ) {
			return NULL;
		}

		// If it's just one ID, return that.
		// Otherwise, return array of IDs
		return ( sizeof( $ids ) === 1 ) ? $ids[0] : $ids;
	}

	public static function getInstance( $passed_post = NULL ) {

		if( empty( self::$instance ) ) {
			self::$instance = new GravityView_View_Data( $passed_post );
		}

		return self::$instance;
	}

	function get_views() {
		return $this->views;
	}

	function get_view( $view_id, $atts = NULL ) {

		if( ! is_numeric( $view_id) ) {
			do_action('gravityview_log_error', sprintf('GravityView_View_Data[get_view] $view_id passed is not numeric.', $view_id) );
			return false;
		}

		// Backup: the view hasn't been fetched yet. Doing it now.
		if ( ! isset( $this->views[ $view_id ] ) ) {
			do_action('gravityview_log_debug', sprintf('GravityView_View_Data[get_view] View #%s not set yet.', $view_id) );
			return $this->add_view( $view_id, $atts );
		}

		if ( empty( $this->views[ $view_id ] ) ) {
			do_action('gravityview_log_debug', sprintf('GravityView_View_Data[get_view] Returning; View #%s was empty.', $view_id) );
			return false;
		}

		return $this->views[ $view_id ];
	}

	/**
	 * Determines if a post, identified by the specified ID, exist
	 * within the WordPress database.
	 *
	 * @link http://tommcfarlin.com/wordpress-post-exists-by-id/
	 * @param    int    $id    The ID of the post to check
	 * @return   bool          True if the post exists; otherwise, false.
	 * @since    1.0.0
	 */
	function view_exists( $view_id ) {
		return is_string( get_post_status( $view_id ) );
	}

	/**
	 *
	 * Add a view to the views array
	 *
	 * @param int|array $view_id View ID or array of View IDs
	 * @param array|string $atts Combine other attributes (eg. from shortcode) with the view settings (optional)
	 * @return type
	 */
	function add_view( $view_id, $atts = NULL ) {

		// Handle array of IDs
		if( is_array( $view_id ) ) {
			foreach( $view_id as $id ) {

				$this->add_view( $id, $atts );
			}

			return $this->views;
		}

		// The view has been set already; returning stored view.
		if ( !empty( $this->views[ $view_id ] ) ) {
			do_action('gravityview_log_debug', sprintf('GravityView_View_Data[add_view] Returning; View #%s already exists.', $view_id) );
			return $this->views[ $view_id ];
		}

		if( ! $this->view_exists( $view_id ) ) {
			do_action('gravityview_log_debug', sprintf('GravityView_View_Data[add_view] Returning; View #%s does not exist.', $view_id) );
			return false;
		}

		$form_id = gravityview_get_form_id( $view_id );

		if( empty( $form_id ) ) {

			do_action('gravityview_log_debug', sprintf('GravityView_View_Data[add_view] Returning; Post ID #%s does not have a connected form.', $view_id) );

			return false;
		}

		// Get the settings for the View ID
		$view_settings = gravityview_get_template_settings( $view_id );

		do_action('gravityview_log_debug', sprintf('GravityView_View_Data[add_view] Settings pulled in from View #%s', $view_id), $view_settings );

		// Merge the view settings with the defaults
		$view_defaults = wp_parse_args( $view_settings, self::get_default_args() );

		do_action('gravityview_log_debug', 'GravityView_View_Data[add_view] View Defaults after merging View Settings with the default args.', $view_defaults );

		if( ! empty( $atts ) && is_array( $atts ) ) {

			do_action('gravityview_log_debug', 'GravityView_View_Data[add_view] $atts before merging  with the $view_defaults', $atts );

			// Get the settings from the shortcode and merge them with defaults.
			$atts = shortcode_atts( $view_defaults, $atts );

			do_action('gravityview_log_debug', 'GravityView_View_Data[add_view] $atts after merging  with the $view_defaults', $atts );

		} else {

			// If there are no passed $atts, the defaults will be used.
			$atts = $view_defaults;

		}

		unset( $atts['id'], $view_defaults, $view_settings );

		$data = array(
			'id' => $view_id,
			'view_id' => $view_id,
			'form_id' => $form_id,
			'template_id' => gravityview_get_template_id( $view_id ),
			'atts' => $atts,
			'fields' => $this->get_fields( $view_id ),
			'widgets' => get_post_meta( $view_id, '_gravityview_directory_widgets', true ),
			'form' => gravityview_get_form( $form_id ),
		);

		do_action('gravityview_log_debug', sprintf('GravityView_View_Data[add_view] View #%s being added.', $view_id), $data );

		$this->views[ $view_id ] = $data;

		return $this->views[ $view_id ];
	}

	/**
	 * Get the visible fields for a View
	 * @uses  gravityview_get_directory_fields() Fetch the configured fields for a View
	 * @uses  GravityView_View_Data::filter_fields() Only show visible fields
	 * @param  int $view_id View ID
	 * @return array          Array of fields as passed by `gravityview_get_directory_fields()`
	 */
	function get_fields( $view_id ) {

		$dir_fields = gravityview_get_directory_fields( $view_id );
		do_action( 'gravityview_log_debug', '[render_view] Fields: ', $dir_fields );

		// remove fields according to visitor visibility permissions (if logged-in)
		$dir_fields = $this->filter_fields( $dir_fields );
		do_action( 'gravityview_log_debug', '[render_view] Fields after visibility filter: ', $dir_fields );

		return $dir_fields;
	}

	/**
	 * Filter area fields based on specified conditions
	 *
	 * @access public
	 * @param array $dir_fields
	 * @return array
	 */
	private function filter_fields( $dir_fields ) {

		if( empty( $dir_fields ) || !is_array( $dir_fields ) ) {
			return $dir_fields;
		}

		foreach( $dir_fields as $area => $fields ) {

			foreach( (array)$fields as $uniqid => $properties ) {

				if( $this->hide_field_check_conditions( $properties ) ) {
					unset( $dir_fields[ $area ][ $uniqid ] );
				}

			}
		}

		return $dir_fields;

	}


	/**
	 * Check wether a certain field should not be presented based on its own properties.
	 *
	 * @access public
	 * @param array $properties
	 * @return void|boolean (field should be hidden) or false (field should be presented)
	 */
	private function hide_field_check_conditions( $properties ) {

		// logged-in visibility
		if( ! empty( $properties['only_loggedin'] ) && ! current_user_can( $properties['only_loggedin_cap'] ) ) {
			return true;
		}

		return false;
	}

	function get_id_from_atts( $atts ) {

		$atts = is_array( $atts ) ? $atts : shortcode_parse_atts( $atts );

		// Get the settings from the shortcode and merge them with defaults.
		$atts = wp_parse_args( $atts, self::get_default_args() );

		$view_id = ! empty( $atts['view_id'] ) ? (int)$atts['view_id'] : NULL;

		if( empty( $view_id ) && !empty( $atts['id'] ) ) {
			$view_id = (int)$atts['id'];
		}

		if( empty( $view_id ) ) {
			do_action('gravityview_log_error', 'GravityView_View_Data[get_id_from_atts] Returning; no ID defined (Atts)', $atts );
			return;
		}

		return $view_id;
	}

	/**
	 * Parse content to determine if there is a GV shortcode to allow for enqueing necessary files in the head.
	 *
	 * @uses gravityview_has_shortcode_r() Check whether shortcode exists (recursively)
	 * @uses shortcode_parse_atts() Parse each GV shortcode
	 * @uses  gravityview_get_template_settings() Get the settings for the View ID
	 * @param  string $content $post->post_content content
	 * @return int|null|array ID of the View. If there are multiple views in the content, array of IDs parsed.
	 */
	function parse_post_content( $content ) {

		/**
		 * @hack This is so that the shortcode is registered for the oEmbed preview in the Admin
		 * @since 1.6
		 */
		if( ! shortcode_exists('gravityview') ) {
			add_shortcode( 'gravityview', array( GravityView_frontend::getInstance(), 'shortcode' ) );
		}

		$shortcodes = gravityview_has_shortcode_r( $content, 'gravityview' );

		if( empty( $shortcodes ) ) {
			return NULL;
		}

		do_action('gravityview_log_debug', 'GravityView_View_Data[parse_post_content] Parsing content, found shortcodes:', $shortcodes );

		$ids = array();

		foreach ($shortcodes as $key => $shortcode) {

			$args = shortcode_parse_atts( $shortcode[3] );

			if( empty( $args['id'] ) ) {
				do_action('gravityview_log_error', sprintf( 'GravityView_View_Data[parse_post_content] Returning; no ID defined in shortcode atts for Post #%s (Atts)', $post->ID ), $shortcode );
				continue;
			}

			do_action('gravityview_log_debug', sprintf('GravityView_View_Data[parse_post_content] Adding view #%s with shortcode args', $args['id']), $args );

			// Store the View to the object for later fetching.
			$this->add_view( $args['id'], $args );

			$ids[] = $args['id'];
		}

		if( empty($ids) ) {
			return NULL;
		}

		// If it's just one ID, return that.
		// Otherwise, return array of IDs
		return ( sizeof( $ids ) === 1 ) ? $ids[0] : $ids;

	}

	/**
	 * Checks if the passed post id has the passed View id embedded.
	 *
	 * Returns
	 *
	 * @since 1.6.1
	 *
	 * @param string $post_id Post ID where the View is embedded
	 * @param string $view_id View ID
	 *
	 * @return bool|WP_Error If valid, returns true. If invalid, returns WP_Error containing error message.
	 */
	public static function is_valid_embed_id( $post_id = '', $view_id = '', $empty_is_valid = true ) {

		$message = NULL;

		// Not invalid if not set!
		if( empty( $post_id ) || empty( $view_id ) ) {

			if( $empty_is_valid ) {
				return true;
			}

			$message = esc_html__( 'The ID is required.', 'gravityview' );
		}

		if( ! $message ) {
			$status = get_post_status( $post_id );

			// Nothing exists with that post ID.
			if ( ! is_numeric( $post_id ) ) {
				$message = esc_html__( 'You did not enter a number. The value entered should be a number, representing the ID of the post or page the View is embedded on.', 'gravityview' );

				// @todo Convert to generic article about Embed IDs
				$message .= ' ' . gravityview_get_link( 'http://docs.gravityview.co/article/222-the-search-widget', __( 'Learn more&hellip;', 'gravityview' ), 'target=_blank' );
			}
		}

		if( ! $message ) {

			// Nothing exists with that post ID.
			if ( empty( $status ) || in_array( $status, array( 'revision', 'attachment' ) ) ) {
				$message = esc_html__( 'There is no post or page with that ID.', 'gravityview' );
			}

		}

		if( ! $message ) {
			$view_ids_in_post = GravityView_View_Data::getInstance()->maybe_get_view_id( $post_id );

			// The post or page specified does not contain the shortcode.
			if ( false === in_array( $view_id, (array) $view_ids_in_post ) ) {
				$message = sprintf( esc_html__( 'The Post ID entered is not valid. You may have entered a post or page that does not contain the selected View. Make sure the post contains the following shortcode: %s', 'gravityview' ), '<br /><code>[gravityview id="' . intval( $view_id ) . '"]</code>' );
			}
		}

		if( ! $message ) {

			// It's a View
			if( 'gravityview' === get_post_type( $post_id ) ) {
				$message = esc_html__( 'The ID is already a View.', 'gravityview' );;
			}

		}

		if( $message ) {
			return new WP_Error( 'invalid_embed_id', $message );
		}

		return true;
	}

	/**
	 * Get a specific default setting
	 * @param  string  $key          The key of the setting array item
	 * @param  boolean $with_details Include details
	 * @return mixed|array                If using $with_details, return array. Otherwise, mixed.
	 */
	public static function get_default_arg( $key, $with_details = false ) {

		$args = self::get_default_args( $with_details );

		if( !isset( $args[ $key ] ) ) { return NULL; }

		return $args[ $key ];
	}

	/**
	 * Retrieve the default args for shortcode and theme function
	 *
	 * @param boolean $with_details True: Return array with full default settings information, including description, name, etc. False: Return an array with only key => value pairs.
	 * @param string $group Only fetch
	 * @access public
	 * @static
	 * @return array {
	 *      Associative array of default settings for a View
	 *
	 *      @type string $label Setting label shown in admin
	 *      @type string $type Gravity Forms field type
	 *      @type string $group The field group the setting is associated with. Default: "default"
	 *      @type mixed  $value The default value for the setting
	 *      @type string $tooltip Tooltip displayed for the setting
	 *      @type boolean $show_in_shortcode Whether to show the setting in the shortcode configuration modal
	 *      @type array  $options Array of values to use when generating select, multiselect, radio, or checkboxes fields
	 *      @type boolean $full_width True: Display the input and label together when rendering. False: Display label and input in separate columns when rendering.
	 * }
	 * @filter gravityview_default_args Modify the default settings for new Views
	 */
	public static function get_default_args( $with_details = false, $group = NULL ) {

		$default_settings = apply_filters( 'gravityview_default_args', array(
			'id' => array(
				'label' => __('View ID', 'gravityview'),
				'type' => 'number',
				'group'	=> 'default',
				'value' => NULL,
				'tooltip' => NULL,
				'show_in_shortcode' => false,
			),
			'page_size' => array(
				'label' 	=> __('Number of entries per page', 'gravityview'),
				'type' => 'number',
				'class'	=> 'small-text',
				'group'	=> 'default',
				'value' => 25,
				'show_in_shortcode' => true,
			),
			'lightbox' => array(
				'label' => __( 'Enable lightbox for images', 'gravityview' ),
				'type' => 'checkbox',
				'group'	=> 'default',
				'value' => 1,
				'tooltip' => NULL,
				'show_in_shortcode' => true,
			),
			'show_only_approved' => array(
				'label' => __( 'Show only approved entries', 'gravityview' ),
				'type' => 'checkbox',
				'group'	=> 'default',
				'value' => 0,
				'show_in_shortcode' => false,
			),
			'hide_until_searched' => array(
				'label' => __( 'Hide View data until search is performed', 'gravityview' ),
				'type' => 'checkbox',
				'group'	=> 'default',
				'tooltip' => __( 'When enabled it will only show any View entries after a search is performed.', 'gravityview' ),
				'value' => 0,
				'show_in_shortcode' => false,
			),
			'hide_empty' => array(
				'label' 	=> __( 'Hide empty fields', 'gravityview' ),
				'group'	=> 'default',
				'type'	=> 'checkbox',
				'value' => 1,
				'show_in_shortcode' => false,
			),
			'user_edit' => array(
				'label'	=> __( 'Allow User Edit', 'gravityview' ),
				'group'	=> 'default',
				'desc'	=> __('Allow logged-in users to edit entries they created.', 'gravityview'),
				'value'	=> 0,
				'tooltip' => __('Display "Edit Entry" fields to non-administrator users if they created the entry. Edit Entry fields will always be displayed to site administrators.', 'gravityview'),
				'type'	=> 'checkbox',
				'show_in_shortcode' => true,
			),
			'user_delete' => array(
				'label'	=> __( 'Allow User Delete', 'gravityview' ),
				'group'	=> 'default',
				'desc'	=> __('Allow logged-in users to delete entries they created.', 'gravityview'),
				'value'	=> 0,
				'tooltip' => __('Display "Delete Entry" fields to non-administrator users if they created the entry. Delete Entry fields will always be displayed to site administrators.', 'gravityview'),
				'type'	=> 'checkbox',
				'show_in_shortcode' => true,
			),
			'sort_field' => array(
				'label'	=> __('Sort by field', 'gravityview'),
				'type' => 'select',
				'value' => '',
				'group'	=> 'sort',
				'options' => array(
					'' => __( 'Default', 'gravityview'),
					'date_created' => __( 'Date Created', 'gravityview'),
				),
				'show_in_shortcode' => true,
			),
			'sort_direction' => array(
				'label' 	=> __('Sort direction', 'gravityview'),
				'type' => 'select',
				'value' => 'ASC',
				'group'	=> 'sort',
				'options' => array(
					'ASC' => __('ASC', 'gravityview'),
					'DESC' => __('DESC', 'gravityview'),
				),
				'show_in_shortcode' => true,
			),
			'sort_columns' => array(
				'label' 	=> __( 'Enable sorting by column', 'gravityview' ),
				'left_label' => __( 'Column Sorting', 'gravityview' ),
				'type' => 'checkbox',
				'value' => false,
				'group'	=> 'sort',
				'tooltip' => NULL,
				'show_in_shortcode' => true,
				'show_in_template' => array( 'default_table' ),
			),
			'start_date' => array(
				'label' 	=> __('Filter by Start Date', 'gravityview'),
				'class'	=> 'gv-datepicker',
				'desc'	=> __('Show entries submitted after this date. Supports relative dates, such as "-1 week" or "-1 month".', 'gravityview' ),
				'type' => 'text',
				'value' => '',
				'group'	=> 'filter',
				'show_in_shortcode' => true,
			),
			'end_date' => array(
				'label' 	=> __('Filter by End Date', 'gravityview'),
				'class'	=> 'gv-datepicker',
				'desc'	=> __('Show entries submitted before this date. Supports relative dates, such as "now" or "-3 days".', 'gravityview' ),
				'type' => 'text',
				'value' => '',
				'group'	=> 'filter',
				'show_in_shortcode' => true,
			),
			'class' => array(
				'label' 	=> __('CSS Class', 'gravityview'),
				'desc'	=> __('CSS class to add to the wrapping HTML container.', 'gravityview'),
				'group'	=> 'default',
				'type' => 'text',
				'value' => '',
				'show_in_shortcode' => false,
			),
			'search_value' => array(
				'label' 	=> __('Search Value', 'gravityview'),
				'desc'	=> __('Define a default search value for the View', 'gravityview'),
				'type' => 'text',
				'value' => '',
				'group'	=> 'filter',
				'show_in_shortcode' => false,
			),
			'search_field' => array(
				'label' 	=> __('Search Field', 'gravityview'),
				'desc'	=> __('If Search Value is set, you can define a specific field to search in. Otherwise, all fields will be searched.', 'gravityview'),
				'type' => 'number',
				'value' => '',
				'group'	=> 'filter',
				'show_in_shortcode' => false,
			),
			'single_title' => array(
				'label'	=> __('Single Entry Title', 'gravityview'),
				'type'	=> 'text',
				'desc'	=> __('When viewing a single entry, change the title of the page to this setting. Otherwise, the title will not change between the Multiple Entries and Single Entry views.', 'gravityview'),
				'group'	=> 'default',
				'value'	=> '',
				'show_in_shortcode' => false,
				'full_width' => true,
			),
			'back_link_label' => array(
				'label'	=> __('Back Link Label', 'gravityview'),
				'group'	=> 'default',
				'desc'	=> __('The text of the link that returns to the multiple entries view.', 'gravityview'),
				'type'	=> 'text',
				'value'	=> '',
				'show_in_shortcode' => false,
				'full_width' => true,
			),
		));

		// By default, we only want the key => value pairing, not the whole array.
		if( empty( $with_details ) ) {

			$defaults = array();

			foreach( $default_settings as $key => $value ) {
				$defaults[ $key ] = $value['value'];
			}

			return $defaults;

		}
		// But sometimes, we want all the details.
		else {

			foreach ($default_settings as $key => $value) {

				// If the $group argument is set for the method,
				// ignore any settings that aren't in that group.
				if( !empty( $group ) && is_string( $group ) ) {
					if( empty( $value['group'] ) || $value['group'] !== $group ) {
						unset( $default_settings[ $key ] );
					}
				}

			}

			return $default_settings;

		}
	}


}
