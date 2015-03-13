<?php

class GravityView_Metabox {

	/**
	 * String prepended to the $id when registering the metabox
	 * @var string
	 */
	protected $prefix = 'gravityview_';

	/**
	 * String for use in the 'id' attribute of tags.
	 * @param string
	 */
	public $id = '';

	/**
	 * Title of the meta box.
	 *
	 * @param string
	 */
	public $title = '';

	/**
	 * Function that fills the box with the desired content. The function should echo its output.
	 * @param callback
	 */
	protected $callback = '';

	/**
	 * Optional. The screen on which to show the box (like a post type, 'link', or 'comment'). Default is the current screen.
	 *
	 * @param string|WP_Screen
	 */
	protected $screen = '';

	/**
	 * Optional. The context within the screen where the boxes should display.
	 * Available contexts vary from screen to screen. Post edit screen contexts include 'normal', 'side', and 'advanced'.
	 * Comments screen contexts include 'normal' and 'side'.
	 * Menus meta boxes (accordion sections) all use the 'side' context. Global default is 'advanced'.
	 *
	 * @param string
	 */
	protected $context = 'advanced';

	/**
	 * Optional. The priority within the context where the boxes should show ('high', 'low'). Default 'default'.
	 * @param string
	 */
	protected $priority = '';

	/**
	 * Optional. Data that should be set as the $args property of the box array (which is the second parameter passed to your callback). Default null.
	 * @param array
	 */
	protected $callback_args = array();

	/**
	 * Define a file stored in the partials directory to render the output
	 * @var string
	 */
	protected $render_template_file = '';

	public $icon_class_name = '';

	function __construct( $id, $title = '', $file = '', $icon_class_name = '', $callback_args = null  ) {

		$this->id = $this->prefix.$id;
		$this->title = $title;
		$this->render_template_file = $file;
		$this->callback_args = $callback_args;
		$this->icon_class_name = $this->set_icon_class_name( $icon_class_name );
	}

	function set_icon_class_name( $icon_class_name ) {

		if( preg_match( '/dashicon/i', $icon_class_name ) ) {
			$icon_class_name = 'dashicons ' . $icon_class_name;
		}

		return esc_attr( $icon_class_name );
	}

	function render() {

		if( $file = $this->render_template_file ) {

			// If the full path exists, use it
			if( file_exists( $file ) ) {
				$path = $file;
			} else {
				$path = GRAVITYVIEW_DIR .'includes/admin/metaboxes/views/'.$file;
			}

			if( file_exists( $path ) ) {
				include_once( $path );
			}
		}
	}

}