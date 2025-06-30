<?php
/**
 * Add GravityBoard integration to GravityView
 *
 * @file      class-gravityview-plugin-hooks-gravityboard.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      https://www.gravitykit.com
 * @copyright Copyright 2025, GravityKit
 *
 * @since TODO
 */

/**
 * @inheritDoc
 * @since TODO
 */
class GravityView_Plugin_Hooks_GravityBoard extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @var string Check for GravityBoard constant
	 */
	protected $constant_name = 'GRAVITYBOARD_FILE';

	/**
	 * @inheritDoc
	 * @since TODO
	 */
	protected $style_handles = array(
		'gravityboard-app-styles',
	);

	/**
	 * @inheritDoc
	 * @since TODO
	 */
	protected $script_handles = array(
		'gravityboard-app',
	);

	/**
	 * Add hooks when GravityBoard is active
	 *
	 * @since TODO
	 */
	protected function add_hooks() {
		new GravityView_Widget_GravityBoard();

		parent::add_hooks();
	}
}

/**
 * Widget to display a GravityBoard
 *
 * @since TODO
 */
class GravityView_Widget_GravityBoard extends \GV\Widget {

	/**
	 * @var string Widget icon.
	 */
	public $icon; // Defined below.

	/**
	 * Does this get displayed on a single entry?
	 *
	 * @var boolean
	 */
	protected $show_on_single = true;

	/**
	 * Constructor
	 *
	 * @since TODO
	 */
	function __construct() {

		if ( ! class_exists( '\GravityKit\GravityBoard\Feed' ) ) {
			return;
		}

		$this->icon = 'data:image/svg+xml,' . rawurlencode( \GravityKit\GravityBoard\Feed::get_instance()->get_menu_icon() );

		// Initialize widget in the frontend or when editing a View/performing widget AJAX action
		$doing_ajax   = defined( 'DOING_AJAX' ) && DOING_AJAX && 'gv_field_options' === \GV\Utils::_POST( 'action' );
		$editing_view = 'edit' === \GV\Utils::_GET( 'action' ) && 'gravityview' === get_post_type( \GV\Utils::_GET( 'post' ) );
		$is_frontend  = gravityview()->request->is_frontend();

		if ( ! $doing_ajax && ! $editing_view && ! $is_frontend ) {
			return;
		}

		$this->widget_description = __( 'Display a GravityBoard board.', 'gk-gravityview' );

		$default_values = array(
			'header' => 1,
			'footer' => 1,
		);

		$settings = array(
			'board_id' => array(
				'type'    => 'select',
				'label'   => __( 'Board to display', 'gk-gravityview' ),
				'value'   => '',
				'options' => $this->get_boards_as_options(),
				'desc'    => __( 'Select the GravityBoard to display in this widget.', 'gk-gravityview' ),
			),
		);

		add_filter( 'gravityview/widget/hide_until_searched/allowlist', array( $this, 'add_to_allowlist' ) );

		parent::__construct( __( 'GravityBoard', 'gk-gravityview' ), 'gravityboard', $default_values, $settings );
	}

	/**
	 * Get available boards as options for the widget settings
	 *
	 * @since TODO
	 *
	 * @return array Array of board options
	 */
	private function get_boards_as_options() {
		$options = array(
			'' => __( '&mdash; Select a Board &mdash;', 'gk-gravityview' ),
		);

		if ( ! class_exists( '\GravityKit\GravityBoard\Feed' ) ) {
			return $options;
		}

		try {
			$feed_addon = \GravityKit\GravityBoard\Feed::get_instance();
			$feeds = $feed_addon->get_feeds();

			if ( empty( $feeds ) ) {
				return $options;
			}

			foreach ( $feeds as $feed ) {
				if ( empty( $feed['is_active'] ) ) {
					continue;
				}

				// Check if user has permission to view this board
				if ( ! $feed_addon->user_has_permission( 'view_board', $feed ) ) {
					continue;
				}

				$board_name = $feed_addon->get_board_name( $feed );
				$options[ $feed['id'] ] = esc_html( $board_name );
			}
		} catch ( Exception $e ) {
			// If there's an error getting boards, just return the default options
			return $options;
		}

		return $options;
	}

	/**
	 * Add widget to a list of allowed "Hide Until Searched" items
	 *
	 * @since TODO
	 *
	 * @param array $allowlist Array of widgets to show before a search is performed, if the setting is enabled.
	 *
	 * @return array
	 */
	function add_to_allowlist( $allowlist ) {
		$allowlist[] = 'gravityboard';
		return $allowlist;
	}

	/**
	 * Render the widget on the frontend
	 *
	 * @since TODO
	 *
	 * @param array                       $widget_args Widget arguments
	 * @param string|\GV\Template_Context $content     Content
	 * @param string                      $context     Context
	 */
	public function render_frontend( $widget_args, $content = '', $context = '' ) {

		if ( ! $this->pre_render_frontend( $context ) ) {
			return;
		}

		$board_id = \GV\Utils::get( $widget_args, 'board_id' );

		if ( empty( $board_id ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				echo '<div class="gravityboard-widget-error">' . esc_html__( 'Admin-only notice: Please select a board to display in the widget settings.', 'gk-gravityview' ) . '</div>';
			}
			return;
		}

		if ( ! class_exists( '\GravityKit\GravityBoard\Feed' ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				echo '<div class="gravityboard-widget-error">' . esc_html__( 'Admin-only notice: GravityBoard plugin is not available.', 'gk-gravityview' ) . '</div>';
			}
			return;
		}

		echo do_shortcode( '[gravityboard id="' . esc_attr( $board_id ) . '"]' );
	}
}

new GravityView_Plugin_Hooks_GravityBoard();