<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The base \GV\Shortcode class.
 *
 * Contains some unitility methods, base class for all GV Shortcodes.
 */
class Shortcode {
	/*
	 * @var array All GravityView-registered and loaded shortcodes can be found here.
	 */
	private static $shortcodes;

	/**
	 * @var array The parsed attributes of this shortcode.
	 */
	public $atts;

	/**
	 * @var string The parsed name of this shortcode.
	 */
	public $name;

	/**
	 * @var string The parsed content between tags of this shortcode.
	 */
	public $content;

	/**
	 * The WordPress Shortcode API callback for this shortcode.
	 *
	 * @param array $atts The callback shortcode attributes.
	 * @param string|null $content The wrapped content. Default: null.
	 *
	 * @return string The result of the shortcode logic.
	 */
	public function callback( $atts, $content = null ) {
		gravityview()->log->error( '[{shortcode}] shortcode {class}::callback method not implemented.', array( 'shortcode' => $this->name, 'class' => get_class( $this ) ) );
		return '';
	}

	/**
	 * Get entry based on entry ID for the shortcode
	 *
	 * @param string    $entry_id
	 * @param \GV\View  $view
	 * @param array     $atts
	 *
	 * @return \GV\Entry|string
	 */
	protected function get_entry_or_message( $entry_id, \GV\View $view = null, $atts = array() ) {

		$return_filter = 'gravityview/shortcodes/'. $this->name .'/output';

		switch( $entry_id ) {
			case 'last':
				if ( gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
					/**
					 * @todo Remove once we refactor the use of get_view_entries_parameters.
					 *
					 * Since we're using \GF_Query shorthand initialization we have to reverse the order parameters here.
					 */
					add_filter( 'gravityview_get_entries', $filter = function ( $parameters, $args, $form_id ) {
						if ( ! empty( $parameters['sorting'] ) ) {
							/**
							 * Reverse existing sorts.
							 */
							$sort              = &$parameters['sorting'];
							$sort['direction'] = $sort['direction'] == 'RAND' ?: ( $sort['direction'] == 'ASC' ? 'DESC' : 'ASC' );
						} else {
							/**
							 * Otherwise, sort by date_created.
							 */
							$parameters['sorting'] = array(
								'key'        => 'id',
								'direction'  => 'ASC',
								'is_numeric' => true
							);
						}

						return $parameters;
					}, 10, 3 );
					$entries = $view->get_entries( null );
					remove_filter( 'gravityview_get_entries', $filter );
				} else {
					$entries = $view->get_entries( null );

					/** If a sort already exists, reverse it. */
					if ( $sort = end( $entries->sorts ) ) {
						$entries = $entries->sort( new \GV\Entry_Sort( $sort->field, $sort->direction == \GV\Entry_Sort::RAND ?: ( $sort->direction == \GV\Entry_Sort::ASC ? \GV\Entry_Sort::DESC : \GV\Entry_Sort::ASC ) ), $sort->mode );
					} else {
						/** Otherwise, sort by date_created */
						$entries = $entries->sort( new \GV\Entry_Sort( \GV\Internal_Field::by_id( 'id' ), \GV\Entry_Sort::ASC ), \GV\Entry_Sort::NUMERIC );
					}
				}

				if ( ! $entry = $entries->first() ) {
					return apply_filters( $return_filter, '', $view, null, $atts );
				}
				break;
			case 'first':
				if ( ! $entry = $view->get_entries( null )->first() ) {
					return apply_filters( $return_filter, '', $view, null, $atts );
				}
				break;
			default:
				if ( ! $entry = \GV\GF_Entry::by_id( $entry_id ) ) {
					gravityview()->log->error( 'Entry #{entry_id} not found', array( 'entry_id' => $atts['entry_id'] ) );

					return apply_filters( $return_filter, '', $view, null, $atts );
				}
		}

		return $entry;
	}

	/**
	 *
	 * @param \GV\View $view
	 * @param \GV\Entry $entry
	 * @param array $atts
	 *
	 * @return string|null
	 */
	protected function restrict_access( \GV\View $view, \GV\Entry $entry, $atts = array() ) {

		$return_filter = 'gravityview/shortcodes/'. $this->name .'/output';

		if ( (int) $view->form->ID !== (int) $entry['form_id'] ) {
			gravityview()->log->error( 'Entry does not belong to view (form mismatch)' );
			return apply_filters( $return_filter, '', $view, $entry, $atts );
		}

		if ( post_password_required( $view->ID ) ) {
			gravityview()->log->notice( 'Post password is required for View #{view_id}', array( 'view_id' => $view->ID ) );
			return apply_filters( $return_filter, get_the_password_form( $view->ID ), $view, $entry, $atts );
		}

		if ( ! $view->form  ) {
			gravityview()->log->notice( 'View #{id} has no form attached to it.', array( 'id' => $view->ID ) );

			/**
			 * This View has no data source. There's nothing to show really.
			 * ...apart from a nice message if the user can do anything about it.
			 */
			if ( \GVCommon::has_cap( array( 'edit_gravityviews', 'edit_gravityview' ), $view->ID ) ) {
				$return = __( sprintf( 'This View is not configured properly. Start by <a href="%s">selecting a form</a>.', esc_url( get_edit_post_link( $view->ID, false ) ) ), 'gravityview' );
				return apply_filters( $return_filter, $return, $view, $entry, $atts );
			}

			return apply_filters( $return_filter, '', $view, $entry, $atts );
		}

		/** Private, pending, draft, etc. */
		$public_states = get_post_stati( array( 'public' => true ) );
		if ( ! in_array( $view->post_status, $public_states ) && ! \GVCommon::has_cap( 'read_gravityview', $view->ID ) ) {
			gravityview()->log->notice( 'The current user cannot access this View #{view_id}', array( 'view_id' => $view->ID ) );
			return apply_filters( $return_filter, '', $view, $entry, $atts );
		}

		/** Unapproved entries. */
		if ( $entry['status'] != 'active' ) {
			gravityview()->log->notice( 'Entry ID #{entry_id} is not active', array( 'entry_id' => $entry->ID ) );
			return apply_filters( $return_filter, '', $view, $entry, $atts );
		}

		if ( $view->settings->get( 'show_only_approved' ) ) {
			if ( ! \GravityView_Entry_Approval_Status::is_approved( gform_get_meta( $entry->ID, \GravityView_Entry_Approval::meta_key ) )  ) {
				gravityview()->log->error( 'Entry ID #{entry_id} is not approved for viewing', array( 'entry_id' => $entry->ID ) );
				return apply_filters( $return_filter, '', $view, $entry, $atts );
			}
		}

		return null;
	}

	/**
	 * Register this shortcode class with the WordPress Shortcode API.
	 *
	 * @internal

	 * @return \GV\Shortcode|null The only internally registered instance of this shortcode, or null on error.
	 */
	public static function add() {
		$shortcode = new static();
		if ( shortcode_exists( $shortcode->name ) ) {
			if ( empty( self::$shortcodes[ $shortcode->name ] ) ) {
				gravityview()->log->error( 'Shortcode [{shortcode}] has already been registered elsewhere.', array( 'shortcode' => $shortcode->name ) );
				return null;
			}
		} else {
			add_shortcode( $shortcode->name, array( $shortcode, 'callback' ) );
			self::$shortcodes[ $shortcode->name ] = $shortcode;
		}

		return self::$shortcodes[ $shortcode->name ];
	}

	/**
	 * Unregister this shortcode.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public static function remove() {
		$shortcode = new static();
		unset( self::$shortcodes[$shortcode->name] );
		remove_shortcode( $shortcode->name );
	}

	/**
	 * Parse a string of content and figure out which ones there are.
	 *
	 * Only registered shortcodes (via add_shortcode) will show up.
	 * Returned order is not guaranteed.
	 *
	 * @param string $content Some post content to search through.
	 *
	 * @internal
	 *
	 * @return \GV\Shortcode[] An array of \GV\Shortcode (and subclass) instances.
	 */
	public static function parse( $content ) {
		$shortcodes = array();

		/**
		 * The matches contains:
		 *
		 * 1 - An extra [ to allow for escaping shortcodes with double [[]]
		 * 2 - The shortcode name
		 * 3 - The shortcode argument list
		 * 4 - The self closing /
		 * 5 - The content of a shortcode when it wraps some content.
		 * 6 - An extra ] to allow for escaping shortcodes with double [[]]
		 */
		preg_match_all( '/' . get_shortcode_regex() . '/', $content, $matches, PREG_SET_ORDER );

		foreach ( $matches as $shortcode ) {
			$shortcode_name = $shortcode[2];

			$shortcode_atts = shortcode_parse_atts( $shortcode[3] );
			$shortcode_content = $shortcode[5];

			/** This is a registered GravityView shortcode. */
			if ( !empty( self::$shortcodes[$shortcode_name] ) ) {
				$shortcode = clone self::$shortcodes[$shortcode_name];
			} else {
				/** This is some generic shortcode. */
				$shortcode = new self;
				$shortcode->name = $shortcode_name;
			}

			$shortcode->atts = $shortcode_atts;
			$shortcode->content = $shortcode_content;

			/** Merge inner shortcodes. */
			$shortcodes = array_merge( $shortcodes, array( $shortcode ), self::parse( $shortcode_content ) );
		}

		return $shortcodes;
	}
}
