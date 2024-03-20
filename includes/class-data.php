<?php

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class GravityView_View_Data {

	static $instance = null;

	public $views = array();

	/**
	 *
	 * @param null $passed_post
	 */
	private function __construct( $passed_post = null ) {
		$this->views = new \GV\View_Collection();

		if ( ! empty( $passed_post ) ) {
			$id_or_id_array = $this->maybe_get_view_id( $passed_post );
			foreach ( is_array( $id_or_id_array ) ? $id_or_id_array : array( $id_or_id_array ) as $view_id ) {
				if ( \GV\View::exists( $view_id ) && ! $this->views->contains( $view_id ) ) {
					$this->views->add( \GV\View::by_id( $view_id ) );
				}
			}
		}
	}

	/**
	 * @deprecated
	 * @see \GV\View_Collection::count
	 * @return boolean
	 */
	public function has_multiple_views() {
		return $this->views->count() > 1;
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
	 * @param WP_Post|WP_Post[]|array|string|int|null $passed_post See method description
	 *
	 * @deprecated
	 * @see \GV\View_Collection::from_post and \GV\Shortcode::parse
	 *
	 * @return int|null|array ID of the View. If there are multiple views in the content, array of IDs parsed.
	 */
	public function maybe_get_view_id( $passed_post = null ) {

		if ( empty( $passed_post ) ) {
			return null;
		}

		$ids = array();

		if ( is_numeric( $passed_post ) ) {
			$passed_post = get_post( $passed_post );
		}

		// Convert WP_Posts into WP_Posts[] array
		if ( $passed_post instanceof WP_Post ) {
			$passed_post = array( $passed_post );
		}

		if ( is_array( $passed_post ) ) {

			foreach ( $passed_post as &$post ) {
				$views = \GV\View_Collection::from_post( $post );
				foreach ( $views->all() as $view ) {
					$ids [] = $view->ID;

					/** And as a side-effect... add each view to the global scope. */
					if ( ! $this->views->contains( $view->ID ) ) {
						$this->views->add( $view );
					}
				}
			}
		} elseif ( is_string( $passed_post ) ) {

				$shortcodes = \GV\Shortcode::parse( $passed_post );
			foreach ( $shortcodes as $shortcode ) {
				if ( 'gravityview' == $shortcode->name && ! empty( $shortcode->atts['id'] ) ) {
					$ids [] = $shortcode->atts['id'];

					/** And as a side-effect... add each view to the global scope. */
					if ( ! $this->views->contains( $shortcode->atts['id'] ) && \GV\View::exists( $shortcode->atts['id'] ) ) {
						$this->views->add( $shortcode->atts['id'] );
					}
				}
			}
		} else {
			$id    = $this->get_id_from_atts( $passed_post );
			$ids[] = intval( $id );
		}

		if ( empty( $ids ) ) {
			return null;
		}

		// If it's just one ID, return that.
		// Otherwise, return array of IDs
		return ( 1 === count( $ids ) ) ? $ids[0] : $ids;
	}

	/**
	 * @return GravityView_View_Data
	 */
	public static function getInstance( $passed_post = null ) {

		if ( empty( self::$instance ) ) {
			self::$instance = new GravityView_View_Data( $passed_post );
		}

		return self::$instance;
	}

	/**
	 * @deprecated
	 * @see \GV\View_Collection::all()
	 */
	function get_views() {
		if ( ! $this->views->count() ) {
			return array();
		}
		return array_combine(
			array_map(
				function ( $view ) {
					return $view->ID; },
				$this->views->all()
			),
			array_map(
				function ( $view ) {
					return $view->as_data(); },
				$this->views->all()
			)
		);
	}

	/**
	 * @deprecated
	 * @see \GV\View_Collection::get()
	 */
	function get_view( $view_id, $atts = null ) {
		if ( ! $view = $this->views->get( $view_id ) ) {
			if ( ! \GV\View::exists( $view_id ) ) {
				return false;
			}

			/** Emulate this weird side-effect below... */
			$view = \GV\View::by_id( $view_id );
			if ( $atts ) {
				$view->settings->update( $atts );
			}
			$this->views->add( $view );
		} elseif ( $atts ) {
			$view->settings->update( $atts );
		}

		if ( ! $view ) {
			return false;
		}

		return $view->as_data();
	}

	/**
	 * Determines if a post, identified by the specified ID, exist
	 * within the WordPress database.
	 *
	 * @see http://tommcfarlin.com/wordpress-post-exists-by-id/ Fastest check available
	 * @param    int $view_id    The ID of the post to check
	 *
	 * @deprecated
	 * @see \GV\View::exists()
	 *
	 * @return   bool   True if the post exists; otherwise, false.
	 * @since    1.0.0
	 */
	function view_exists( $view_id ) {
		return \GV\View::exists( $view_id );
	}

	/**
	 *
	 * Add a view to the views array
	 *
	 * @param int|array    $view_id View ID or array of View IDs
	 * @param array|string $atts Combine other attributes (eg. from shortcode) with the view settings (optional)
	 *
	 * @deprecated
	 * @see \GV\View_Collection::append
	 *
	 * @return array|false All views if $view_id is array, a view data array if $view_id is an int, false on errors.
	 */
	function add_view( $view_id, $atts = null ) {
		return \GV\Mocks\GravityView_View_Data_add_view( $view_id, $atts, $this );
	}

	/**
	 * Get the visible fields for a View
	 *
	 * @uses  gravityview_get_directory_fields() Fetch the configured fields for a View
	 * @uses  GravityView_View_Data::filter_fields() Only show visible fields
	 * @param  int $view_id View ID
	 *
	 * @deprecated
	 * @see \GV\View::$fields
	 *
	 * @return array|null Array of fields as passed by `gravityview_get_directory_fields()`
	 */
	function get_fields( $view_id ) {
		if ( \GV\View::exists( $view_id ) ) {
			$view = \GV\View::by_id( $view_id );
			return $view->fields->by_visible( $view )->as_configuration();
		}
	}

	/**
	 * Retrieves view ID from an array.
	 *
	 * @param array $atts
	 * @deprecated Dead code, was probably superceded by GravityView_View_Data::parse_post_content
	 *
	 * @return int|null A view ID cast to int, or null.
	 */
	function get_id_from_atts( $atts ) {
		$settings = \GV\View_Settings::with_defaults();
		$settings->update( $atts );
		$view_id = $settings->get( 'view_id' );
		$view_id = empty( $view_id ) ? $settings->get( 'id' ) : $view_id;
		return empty( $view_id ) ? null : $view_id;
	}

	/**
	 * Parse content to determine if there is a GV shortcode to allow for enqueing necessary files in the head.
	 *
	 * @uses gravityview_has_shortcode_r() Check whether shortcode exists (recursively)
	 * @uses shortcode_parse_atts() Parse each GV shortcode
	 * @uses  gravityview_get_template_settings() Get the settings for the View ID
	 * @param  string $content $post->post_content content
	 *
	 * @deprecated
	 * @see \GV\View_Collection::from_content
	 *
	 * @return int|null|array If a single View is found, the ID of the View. If there are multiple views in the content, array of IDs parsed. If not found, NULL
	 */
	public function parse_post_content( $content ) {
		$ids = array();
		foreach ( \GV\Shortcode::parse( $content ) as $shortcode ) {
			if ( 'gravityview' == $shortcode->name && is_numeric( $shortcode->atts['id'] ) ) {
				if ( \GV\View::exists( $shortcode->atts['id'] ) && ! $this->views->contains( $shortcode->atts['id'] ) ) {
					$this->views->add( \GV\View::by_id( $shortcode->atts['id'] ) );
				}
				/**
				 * The original function outputs the ID even though it wasn't added by ::add_view()
				 * Wether this is a bug or not remains a mystery. But we need to emulate this behavior
				 * until better times.
				 */
				$ids [] = $shortcode->atts['id'];
			}
		}
		if ( empty( $ids ) ) {
			return null;
		}
		return ( 1 === sizeof( $ids ) ) ? $ids[0] : $ids;
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
	 * @param string $empty_is_valid If either $post_id or $view_id is empty consider valid. Default: false.
	 *
	 * @return bool|WP_Error If valid, returns true. If invalid, returns WP_Error containing error message.
	 */
	public static function is_valid_embed_id( $post_id = '', $view_id = '', $empty_is_valid = false ) {

		$message = null;

		// Not invalid if not set!
		if ( empty( $post_id ) || empty( $view_id ) ) {

			if ( $empty_is_valid ) {
				return true;
			}

			$message = esc_html__( 'The ID is required.', 'gk-gravityview' );
		}

		if ( ! $message ) {
			$status = get_post_status( $post_id );

			// Nothing exists with that post ID.
			if ( ! is_numeric( $post_id ) ) {
				$message = esc_html__( 'You did not enter a number. The value entered should be a number, representing the ID of the post or page the View is embedded on.', 'gk-gravityview' );

				// @todo Convert to generic article about Embed IDs
				$message .= ' ' . gravityview_get_link( 'https://docs.gravitykit.com/article/222-the-search-widget', __( 'Learn more&hellip;', 'gk-gravityview' ), 'target=_blank' );
			}
		}

		if ( ! $message ) {

			// Nothing exists with that post ID.
			if ( empty( $status ) || in_array( $status, array( 'revision', 'attachment' ) ) ) {
				$message = esc_html__( 'There is no post or page with that ID.', 'gk-gravityview' );
			}
		}

		if ( ! $message && $post = get_post( $post_id ) ) {
			$views            = GV\View_Collection::from_post( $post );
			$view_ids_in_post = array_map(
				function ( $view ) {
					return $view->ID;
				},
				$views->all()
			);

			// The post or page specified does not contain the shortcode.
			if ( false === in_array( $view_id, (array) $view_ids_in_post ) ) {
				$message = sprintf( esc_html__( 'The Post ID entered is not valid. You may have entered a post or page that does not contain the selected View. Make sure the post contains the following shortcode: %s', 'gk-gravityview' ), '<br /><code>[gravityview id="' . intval( $view_id ) . '"]</code>' );
			}
		}

		if ( ! $message ) {
			// It's a View
			if ( \GV\View::exists( $post_id ) ) {
				$message = esc_html__( 'The ID is already a View.', 'gk-gravityview' );

			}
		}

		if ( $message ) {
			return new WP_Error( 'invalid_embed_id', $message );
		}

		return true;
	}

	/**
	 * Get a specific default setting
	 *
	 * @param  string  $key          The key of the setting array item
	 * @param  boolean $with_details Include details
	 * @return mixed|array                If using $with_details, return array. Otherwise, mixed.
	 */
	public static function get_default_arg( $key, $with_details = false ) {

		$args = \GV\View_Settings::defaults( $with_details );

		if ( ! isset( $args[ $key ] ) ) {
			return null;
		}

		return $args[ $key ];
	}

	/**
	 * Retrieve the default args for shortcode and theme function
	 *
	 * @param boolean $with_details True: Return array with full default settings information, including description, name, etc. False: Return an array with only key => value pairs.
	 * @param string  $group Only fetch
	 *
	 * @return array $args Associative array of default settings for a View
	 *      @param string  $label Setting label shown in admin
	 *      @param string  $type Gravity Forms field type
	 *      @param string  $group The field group the setting is associated with. Default: "default"
	 *      @param mixed   $value The default value for the setting
	 *      @param string  $tooltip Tooltip displayed for the setting
	 *      @param boolean $show_in_shortcode Whether to show the setting in the shortcode configuration modal
	 *      @param array   $options Array of values to use when generating select, multiselect, radio, or checkboxes fields
	 *      @param boolean $full_width True: Display the input and label together when rendering. False: Display label and input in separate columns when rendering.
	 *
	 * @deprecated
	 * @see \GV\View_Settings::defaults()
	 */
	public static function get_default_args( $with_details = false, $group = null ) {
		return \GV\View_Settings::defaults( $with_details, $group );
	}
}
