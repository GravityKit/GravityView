<?php

/**
 * Shortcode to handle showing/hiding content in merge tags. Works great with GravityView Custom Content fields
 */
class GVLogic_Shortcode {

	private static $SUPPORTED_SCALAR_OPERATORS = array( 'is', 'isnot', 'contains', 'starts_with', 'ends_with' );

	private static $SUPPORTED_NUMERIC_OPERATORS = array( 'greater_than', 'less_than' );

	private static $SUPPORTED_ARRAY_OPERATORS = array( 'in', 'not_in', 'isnot', 'contains' );

	private static $SUPPORTED_CUSTOM_OPERATORS = array( 'equals', 'greater_than_or_is', 'greater_than_or_equals', 'less_than_or_is', 'less_than_or_equals', 'not_contains' );

	/**
	 * Attributes passed to the shortcode
	 * @var array
	 */
	var $passed_atts;

	/**
	 * Content inside the shortcode, displayed if matched
	 * @var string
	 */
	var $passed_content;

	/**
	 * Parsed attributes
	 * @var array
	 */
	var $atts = array();

	/**
	 * Parsed content, shown if matched
	 * @var string
	 */
	var $content = '';

	/**
	 * Content shown if not matched
	 * This is set by having `[else]` inside the $content block
	 * @var string
	 */
	var $else_content = '';

	/**
	 * The current shortcode name being processed
	 * @var string
	 */
	var $shortcode = 'gvlogic';

	/**
	 * The left side of the comparison
	 * @var string
	 */
	var $if = '';

	/**
	 * The right side of the comparison
	 * @var string
	 */
	var $comparison = '';

	/**
	 * The comparison operator
	 * @since 1.21.5
	 * @since 2.0 Changed default from "is" to "isnot"
	 * @var string
	 */
	var $operation = 'isnot';

	/**
	 * Does the comparison pass?
	 * @var bool
	 */
	var $is_match = false;

	/**
	 * @var GVLogic_Shortcode
	 */
	private static $instance;

	/**
	 * Instantiate!
	 * @return GVLogic_Shortcode
	 */
	public static function get_instance() {

		if( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Add the WordPress hooks
	 * @return void
	 */
	private function __construct() {
		$this->add_hooks();
	}

	/**
	 * Register the shortcode
	 * @return void
	 */
	private function add_hooks() {
		add_shortcode( 'gvlogic', array( $this, 'shortcode' ) );
		add_shortcode( 'gvlogicelse', array( $this, 'shortcode' ) );
	}

	/**
	 * Get array of supported operators
	 * @param bool $with_values
	 *
	 * @return array
	 */
	private function get_operators( $with_values = false ) {

		$operators = array_merge( self::$SUPPORTED_ARRAY_OPERATORS, self::$SUPPORTED_NUMERIC_OPERATORS, self::$SUPPORTED_SCALAR_OPERATORS, self::$SUPPORTED_CUSTOM_OPERATORS );

		if( $with_values ) {
			$operators_with_values = array();
			foreach( $operators as $key ) {
				$operators_with_values[ $key ] = '';
			}
			return $operators_with_values;
		} else {
			return $operators;
		}
	}

	/**
	 * Set the operation for the shortcode.
	 * @param string $operation
	 *
	 * @return bool True: it's an allowed operation type and was added. False: invalid operation type
	 */
	private function set_operation( $operation = 'isnot' ) {

		$operators = $this->get_operators( false );

		if( !in_array( $operation, $operators ) ) {
			gravityview()->log->debug( ' Attempted to add invalid operation type. {operation}', array( 'operation' => $operation ) );
			return false;
		}

		$this->operation = $operation;
		return true;
	}

	/**
	 * Set the operation and comparison for the shortcode
	 *
	 * Loop through each attribute passed to the shortcode and see if it's a valid operator. If so, set it.
	 * Example: [gvlogic if="{example}" greater_than="5"]
	 * `greater_than` will be set as the operator
	 * `5` will be set as the comparison value
	 *
	 * @return bool True: we've got an operation and comparison value; False: no, we don't
	 */
	private function setup_operation_and_comparison() {

		if ( empty( $this->atts ) ) {
			return true;
		}

		foreach ( $this->atts as $key => $value ) {

			$valid = $this->set_operation( $key == 'else' ? 'isnot' : $key );

			if ( $valid ) {
				$this->comparison = $key == 'else' ? '' : $value;
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array $atts User defined attributes in shortcode tag.
	 * @param null $content
	 * @param string $shortcode_tag
	 *
	 * @return string|null
	 */
	public function shortcode( $atts = array(), $content = NULL, $shortcode_tag = '' ) {

		// Don't process except on frontend
		if ( gravityview()->request->is_admin() ) {
			return null;
		}

		if( empty( $atts ) ) {
			gravityview()->log->error( '$atts are empty.', array( 'data' => $atts ) );
			return null;
		}

		$this->passed_atts = $atts;
		$this->passed_content = $content;
		$this->content = '';
		$this->else_content = '';
		$this->atts = array();
		$this->shortcode = $shortcode_tag;

		$this->parse_atts();

		// We need an "if"
		if( false === $this->if ) {
			gravityview()->log->error( '$atts->if is empty.', array( 'data' => $this->passed_atts ) );
			return null;
		}

		$setup = $this->setup_operation_and_comparison();

		// We need an operation and comparison value
		if( ! $setup ) {
			gravityview()->log->error( 'No valid operators were passed.', array( 'data' => $this->atts ) );
			return null;
		}

		// Check if it's a match
		$this->set_is_match();

		// Set the content and else_content
		$this->set_content_and_else_content();

		// Return the value!
		$output = $this->get_output();

		$this->reset();

		return $output;
	}

	/**
	 * Restore the original settings for the shortcode
	 *
	 * @since 2.0 Needed because $atts can now be empty
	 *
	 * @return void
	 */
	private function reset() {
		$this->operation = 'isnot';
		$this->comparison = '';
		$this->passed_atts = array();
		$this->passed_content = '';
	}

	/**
	 * Does the if and the comparison match?
	 * @uses GVCommon::matches_operation
	 *
	 * @return void
	 */
	private function set_is_match() {
		$this->is_match = GVCommon::matches_operation( $this->if, $this->comparison, $this->operation );
	}

	/**
	 * Get the output for the shortcode, based on whether there's a matched value
	 *
	 * @return string HTML/Text output of the shortcode
	 */
	private function get_output() {

		if( $this->is_match ) {
			$output = $this->content;
		} else {
			$output = $this->else_content;
		}

		// Get recursive!
		$output = do_shortcode( $output );

		if ( class_exists( 'GFCommon' ) ) {
			$output = GFCommon::replace_variables( $output, array(), array(), false, true, false );
		}

		/**
		 * @filter `gravityview/gvlogic/output` Modify the [gvlogic] output
		 * @param string $output HTML/text output
		 * @param GVLogic_Shortcode $this This class
		 */
		$output = apply_filters('gravityview/gvlogic/output', $output, $this );

		gravityview()->log->debug( 'Output: ', array( 'data' => $output ) );

		return $output;
	}

	/**
	 * Check for `[else]` tag inside the shortcode content. If exists, set the else_content variable.
	 * If not, use the `else` attribute passed by the shortcode, if exists.
	 *
	 * @return void
	 */
	private function set_content_and_else_content() {

		$passed_content = $this->passed_content;

		list( $before_else, $after_else ) = array_pad( explode( '[else]', $passed_content ), 2, NULL );
		list( $before_else_if, $after_else_if ) = array_pad( explode( '[else', $passed_content ), 2, NULL );

		$else_attr = isset( $this->atts['else'] ) ? $this->atts['else'] : NULL;
		$else_content = isset( $after_else ) ? $after_else : $else_attr;

		// The content is everything OTHER than the [else]
		$this->content = $before_else_if;

		if ( ! $this->is_match ) {
			if( $elseif_content = $this->process_elseif( $before_else ) ) {
				$this->else_content = $elseif_content;
			} else {
				$this->else_content = $else_content;
			}
		}
	}

	/**
	 * Handle additional conditional logic inside the [else] pseudo-shortcode
	 *
	 * @since 1.21.2
	 *
	 * @param string $before_else Shortcode content before the [else] tag (if it exists)
	 *
	 * @return bool|string False: No [else if] statements found. Otherwise, return the matched content.
	 */
	private function process_elseif( $before_else ) {

		$regex = get_shortcode_regex( array( 'else' ) );

		// 2. Check if there are any ELSE IF statements
		preg_match_all( '/' . $regex . '/', $before_else . '[/else]', $else_if_matches, PREG_SET_ORDER );

		// 3. The ELSE IF statements that remain should be processed to see if they are valid
		foreach ( $else_if_matches as $key => $else_if_match ) {

			// If $else_if_match[5] exists and has content, check for more shortcodes
			preg_match_all( '/' . $regex . '/', $else_if_match[5] . '[/else]', $recursive_matches, PREG_SET_ORDER );

			// If the logic passes, this is the value that should be used for $this->else_content
			$else_if_value = $else_if_match[5];
			$check_elseif_match = $else_if_match[0];

			// Retrieve the value of the match that is currently being checked, without any other [else] tags
			if( ! empty( $recursive_matches[0][0] ) ) {
				$else_if_value = str_replace( $recursive_matches[0][0], '', $else_if_value );
				$check_elseif_match = str_replace( $recursive_matches[0][0], '', $check_elseif_match );
			}

			$check_elseif_match = str_replace( '[else', '[gvlogicelse', $check_elseif_match );
			$check_elseif_match = str_replace( '[/else', '[/gvlogicelse', $check_elseif_match );

			// Make sure to close the tag
			if ( '[/gvlogicelse]' !== substr( $check_elseif_match, -14, 14 ) ) {
				$check_elseif_match .= '[/gvlogicelse]';
			}

			// The shortcode returned a value; it was a match
			if ( $result = do_shortcode( $check_elseif_match ) ) {
				return $else_if_value;
			}

			// Process any remaining [else] tags
			return $this->process_elseif( $else_if_match[5] );
		}

		return false;
	}

	/**
	 * Process the attributes passed to the shortcode. Make sure they're valid
	 * @return void
	 */
	private function parse_atts() {

		$supported = array(
			'if' => false,
			'else' => false,
		);

		$supported_args = $supported + $this->get_operators( true );

		// Whittle down the attributes to only valid pairs
		$this->atts = shortcode_atts( $supported_args, $this->passed_atts, $this->shortcode );

		// Only keep the passed attributes after making sure that they're valid pairs
		$this->atts = array_intersect_key( $this->passed_atts, $this->atts );

		// Strip whitespace if it's not default false
		$this->if = ( isset( $this->atts['if'] ) && is_string( $this->atts['if'] ) ) ? trim( $this->atts['if'] ) : false;

		/**
		 * @action `gravityview/gvlogic/parse_atts/after` Modify shortcode attributes after it's been parsed
		 * @see https://gist.github.com/zackkatz/def9b295b80c4ae109760ffba200f498 for an example
		 * @since 1.21.5
		 * @param GVLogic_Shortcode $this The GVLogic_Shortcode instance
		 */
		do_action( 'gravityview/gvlogic/parse_atts/after', $this );

		// Make sure the "if" isn't processed in self::setup_operation_and_comparison()
		unset( $this->atts['if'] );
	}
}

GVLogic_Shortcode::get_instance();
