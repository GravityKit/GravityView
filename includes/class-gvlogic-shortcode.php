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
	 * @var string
	 */
	var $operation = 'is';

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
	function add_hooks() {
		add_shortcode( 'gvlogic', array( $this, 'shortcode' ) );
	}

	/**
	 * Get array of supported operators
	 * @param bool $with_values
	 *
	 * @return array
	 */
	function get_operators( $with_values = false ) {

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
	function set_operation( $operation = '' ) {

		if( empty( $operation ) ) {
			return false;
		}

		$operators = $this->get_operators( false );

		if( !in_array( $operation, $operators ) ) {
			do_action( 'gravityview_log_debug', __METHOD__ .' Attempted to add invalid operation type.', $operation );
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

		foreach( $this->atts as $key => $value ) {

			$valid = $this->set_operation( $key );

			if( $valid ) {
				$this->comparison = $value;
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
		if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) && gravityview()->request->is_admin() ) {
			return null;
			/** Deprecated in favor of gravityview()->request->is_admin(). */
		} else if ( GravityView_Plugin::is_admin() ) {
			return null;
		}

		if( empty( $atts ) ) {
			do_action( 'gravityview_log_error', __METHOD__.' $atts are empty.', $atts );
			return null;
		}

		$this->passed_atts = $atts;
		$this->passed_content = $content;
		$this->shortcode = $shortcode_tag;

		$this->parse_atts();

		// We need an "if"
		if( false === $this->if ) {
			do_action( 'gravityview_log_error', __METHOD__.' $atts->if is empty.', $this->passed_atts );
			return null;
		}

		$setup = $this->setup_operation_and_comparison();

		// We need an operation and comparison value
		if( ! $setup ) {
			do_action( 'gravityview_log_error', __METHOD__.' No valid operators were passed.', $this->atts );
			return null;
		}

		// Set the content and else_content
		$this->set_content_and_else_content();

		// Check if it's a match
		$this->set_is_match();

		// Return the value!
		$output = $this->get_output();

		return $output;
	}

	/**
	 * Does the if and the comparison match?
	 * @uses GVCommon::matches_operation
	 *
	 * @return boolean True: yep; false: nope
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

		/**
		 * @filter `gravityview/gvlogic/output` Modify the [gvlogic] output
		 * @param string $output HTML/text output
		 * @param GVLogic_Shortcode $this This class
		 */
		$output = apply_filters('gravityview/gvlogic/output', $output, $this );

		do_action( 'gravityview_log_debug', __METHOD__ .' Output: ', $output );

		return $output;
	}

	/**
	 * Check for `[else]` tag inside the shortcode content. If exists, set the else_content variable.
	 * If not, use the `else` attribute passed by the shortcode, if exists.
	 *
	 * @todo allow for chains of [else if="{another field:123}" is="example"] - requires registering [else] shortcode...
	 * @return void
	 */
	private function set_content_and_else_content() {

		$content = explode( '[else]', $this->passed_content );

		$this->content = $content[0];

		$else_attr = isset( $this->atts['else'] ) ? $this->atts['else'] : NULL;

		$this->else_content = isset( $content[1] ) ? $content[1] : $else_attr;
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
		$this->atts = function_exists( 'array_intersect_key' ) ? array_intersect_key( $this->passed_atts, $this->atts ) : $this->atts;

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
