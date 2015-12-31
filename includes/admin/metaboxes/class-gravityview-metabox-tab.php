<?php

/**
 * The class for a metabox tab in the GravityView View Settings metabox
 *
 * @see https://gist.github.com/zackkatz/6cc381bcf54849f2ed41 For example
 *
 * @since 1.8
 */
class GravityView_Metabox_Tab {

	/**
	 * String prepended to the $id when registering the metabox
	 * @since 1.8
	 * @var string
	 */
	private $prefix = 'gravityview_';

	/**
	 * String for use in the 'id' attribute of tags.
	 * @since 1.8
	 * @param string
	 */
	public $id = '';

	/**
	 * Title of the meta box.
	 * @since 1.8
	 * @param string
	 */
	public $title = '';

	/**
	 * Function that fills the box with the desired content. The function should echo its output.
	 * @since 1.8
	 * @param callback
	 */
	protected $callback = '';

	/**
	 * Optional. The screen on which to show the box (like a post type, 'link', or 'comment'). Default is the current screen.
	 *
	 * @since 1.8
	 * @param string|WP_Screen
	 */
	protected $screen = '';

	/**
	 * Optional. The context within the screen where the boxes should display.
	 * Available contexts vary from screen to screen. Post edit screen contexts include 'normal', 'side', and 'advanced'.
	 * Comments screen contexts include 'normal' and 'side'.
	 * Menus meta boxes (accordion sections) all use the 'side' context. Global default is 'advanced'.
	 *
	 * @since 1.8
	 * @param string
	 */
	protected $context = 'advanced';

	/**
	 * Optional. The priority within the context where the boxes should show ('high', 'low'). Default 'default'.
	 * @since 1.8
	 * @param string
	 */
	protected $priority = '';

	/**
	 * Optional. Data that should be set as the $args property of the box array (which is the second parameter passed to your callback). Default null.
	 * @since 1.8
	 * @param array
	 */
	protected $callback_args = array();

	/**
	 * Define a file stored in the partials directory to render the output
	 * @since 1.8
	 * @var string
	 */
	protected $render_template_file = '';

	/**
	 * CSS class for the tab icon
	 * @since 1.8
	 * @var string
	 */
	public $icon_class_name = '';


	/**
	 * Create a new metabox tab
	 *
	 * @since 1.8
	 * @param $id Metabox HTML ID, without `gravityview_` prefix
	 * @param string $title Name of the metabox. Shown in the tab.
	 * @param string $file The file name of a file stored in the /gravityview/includes/admin/metaboxes/views/ directory to render the metabox output, or the full path to a file. If defined, `callback` is not used.
	 * @param string $icon_class_name Icon class used in vertical tabs. Supports non-dashicon. If dashicons, no need for `dashicons ` prefix
	 * @param string $callback Function to render the metabox, if $file is not defined.
	 * @param array $callback_args Arguments passed to the callback
	 * @return void
	 */
	function __construct( $id, $title = '', $file = '', $icon_class_name = '', $callback = '', $callback_args = array()  ) {

		$this->id = $this->prefix.$id;
		$this->title = $title;
		$this->render_template_file = $file;
		$this->callback = $callback;
		$this->callback_args = $callback_args;
		$this->icon_class_name = $this->parse_icon_class_name( $icon_class_name );
	}

	/**
	 * If the name of the CSS class has dashicon in it, add the `dashicons` prefix
	 *
	 * @since 1.8
	 *
	 * @param $icon_class_name Passed class name
	 *
	 * @return string sanitized CSS class
	 */
	function parse_icon_class_name( $icon_class_name = '' ) {

		if( preg_match( '/dashicon/i', $icon_class_name ) ) {
			$icon_class_name = 'dashicons ' . $icon_class_name;
		}

		return esc_attr( $icon_class_name );
	}

	/**
	 * Render the tab
	 *
	 * If the $file parameter was passed when registering a new GravityView_Metabox_Tab,
	 * then the file is included.
	 *
	 * The include checks for a full file path first, and if a file exists, use that file.
	 * If the file doesn't exist, then the code looks inside the [gravityview]/includes/admin/metaboxes/views/ dir.
	 *
	 * Finally, if there's no file specified, but there's a $callback parameter specified, use the callback.
	 *
	 * @since 1.8
	 *
	 * @param WP_Post $post Currently edited post object
	 */
	function render( $post ) {

		if( !empty( $this->render_template_file ) ) {

			$file = $this->render_template_file;

			// If the full path exists, use it
			if( file_exists( $file ) ) {
				$path = $file;
			} else {
				$path = GRAVITYVIEW_DIR .'includes/admin/metaboxes/views/'.$file;
			}

			if( file_exists( $path ) ) {
				include $path;
			} else {
				do_action( 'gravityview_log_error', 'Metabox template file not found', $this );
			}

		} else if( !empty( $this->callback ) ) {

			if( is_callable( $this->callback ) ) {

				/** @see do_accordion_sections() */
				call_user_func( $this->callback, $post, (array) $this );

			} else {
				do_action( 'gravityview_log_error', 'Metabox callback was not callable', $this );
			}

		} else {
			do_action( 'gravityview_log_error', 'Metabox file and callback were not found', $this );
		}
	}

}