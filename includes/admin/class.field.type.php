<?php

/**
 * Modify option field type by extending this class
 */
abstract class GravityView_FieldType {

	/**
	 * Field form html `name`
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Field settings
	 *
	 * @var array
	 */
	protected $field;

	/**
	 * Field current value
	 *
	 * @var mixed
	 */
	protected $value;

	function __construct( $name = '', $field = array(), $curr_value = null ) {

		$this->name = $name;

		$defaults = self::get_field_defaults();

		// Backward compatibility
		if ( ! empty( $field['choices'] ) ) {
			$field['options'] = $field['choices'];
			unset( $field['choices'] );
		}

		$this->field = wp_parse_args( $field, $defaults );

		$this->value = is_null( $curr_value ) || '' === $curr_value ? $this->field['value'] : $curr_value;
	}

	/**
	 * Returns the default details for a field option
	 *
	 * - default    // default option value, in case nothing is defined (@deprecated)
	 * - desc       // option description
	 * - value      // the option default value
	 * - label      // the option label
	 * - left_label // In case of checkboxes, left label will appear on the left of the checkbox
	 * - id         // the field id
	 * - type       // the option type ( text, checkbox, select, ... )
	 * - options    // when type is select, define the select options ('choices' is @deprecated)
	 * - merge_tags // if the option supports merge tags feature
	 * - class      // (new) define extra classes for the field
	 * - tooltip    //
	 *
	 * @return array
	 */
	public static function get_field_defaults() {
		return array(
			'desc'       => '',
			'value'      => null,
			'label'      => '',
			'left_label' => null,
			'id'         => null,
			'type'       => 'text',
			'options'    => null,
			'merge_tags' => true,
			'class'      => '',
			'tooltip'    => null,
			'requires'   => null,
		);
	}


	function get_tooltip() {
		if ( ! function_exists( 'gform_tooltip' ) ) {
			return null;
		}

		$article = wp_parse_args(
			\GV\Utils::get( $this->field, 'article', array() ),
			array(
				'id'   => '',
				'type' => 'modal',
				'url'  => '#',
			)
		);

		return ! empty( $this->field['tooltip'] ) ? ' ' . $this->tooltip( $this->field['tooltip'], false, true, $article ) : null;
	}

	/**
	 * Displays the tooltip
	 *
	 * @since 2.8.1
	 *
	 * @global $__gf_tooltips
	 *
	 * @param string $name      The name of the tooltip to be displayed
	 * @param string $css_class Optional. The CSS class to apply toi the element. Defaults to empty string.
	 * @param bool   $return    Optional. If the tooltip should be returned instead of output. Defaults to false (output)
	 * @param array  $article   Optional. Details about support doc article connected to the tooltip. {
	 *   @type string $id   Unique ID of article for Beacon API
	 *   @type string $url  URL of support doc article
	 *   @type string $type Type of Beacon element to open. {@see https://developer.helpscout.com/beacon-2/web/javascript-api/#beaconarticle}
	 * }
	 *
	 * @return string
	 */
	function tooltip( $name, $css_class = '', $return = false, $article = array() ) {
		global $__gf_tooltips; // declared as global to improve WPML performance

		$css_class = empty( $css_class ) ? 'tooltip' : $css_class;
		/**
		 * Filters the tooltips available
		 *
		 * @param array $__gf_tooltips Array containing the available tooltips
		 */
		$__gf_tooltips = apply_filters( 'gform_tooltips', $__gf_tooltips );

		// AC: the $name parameter is a key when it has only one word. Maybe try to improve this later.
		$parameter_is_key = 1 == count( explode( ' ', $name ) );

		$tooltip_text  = $parameter_is_key ? rgar( $__gf_tooltips, $name ) : $name;
		$tooltip_class = isset( $__gf_tooltips[ $name ] ) ? "tooltip_{$name}" : '';
		$tooltip_class = esc_attr( $tooltip_class );

		/**
		 * Below this line has been modified by GravityView.
		 */

		if ( empty( $tooltip_text ) && empty( $article['id'] ) ) {
			return '';
		}

		$url         = isset( $article['url'] ) ? $article['url'] : '#';
		$atts        = 'onclick="return window.Beacon === undefined || typeof window.Beacon === \'undefined\';" onkeypress="return window.Beacon === undefined || typeof window.Beacon === \'undefined\';"';
		$anchor_text = '<i class=\'fa fa-question-circle\'></i>';
		$css_class   = gravityview_sanitize_html_class( 'gf_tooltip ' . $css_class . ' ' . $tooltip_class );

		$tooltip = sprintf(
			'<a href="%s" %s class="%s" title="%s" role="button">%s</a>',
			esc_url( $url ),
			$atts,
			$css_class,
			esc_attr( $tooltip_text ),
			$anchor_text
		);

		/**
		 * Modify the tooltip HTML before outputting
		 *
		 * @internal
		 * @see GravityView_Support_Port::maybe_add_article_to_tooltip()
		 */
		$tooltip = apply_filters( 'gravityview/tooltips/tooltip', $tooltip, $article, $url, $atts, $css_class, $tooltip_text, $anchor_text );

		if ( ! $return ) {
			echo $tooltip;
		}

		return $tooltip;
	}

	/**
	 * Build input id based on the name
	 *
	 * @return string
	 */
	function get_field_id() {
		if ( isset( $this->field['id'] ) ) {
			return esc_attr( $this->field['id'] );
		}
		return esc_attr( sanitize_html_class( $this->name ) );
	}

	/**
	 * Retrieve field label
	 *
	 * @return string
	 */
	function get_field_label() {
		return esc_html( trim( $this->field['label'] ) );
	}

	/**
	 * Retrieve field left label
	 *
	 * @since 1.7
	 *
	 * @return string
	 */
	function get_field_left_label() {
		return ! empty( $this->field['left_label'] ) ? esc_html( trim( $this->field['left_label'] ) ) : null;
	}

	/**
	 * Retrieve field label class
	 *
	 * @return string
	 */
	function get_label_class() {
		return 'gv-label-' . sanitize_html_class( $this->field['type'] );
	}


	/**
	 * Retrieve field description
	 *
	 * @return string
	 */
	function get_field_desc() {
		return ! empty( $this->field['desc'] ) ? '<span class="howto">' . $this->field['desc'] . '</span>' : '';
	}


	/**
	 * Verify if field should have merge tags
	 *
	 * @return boolean
	 */
	function show_merge_tags() {

		// Show the merge tags if the field is a list view
		$is_list = preg_match( '/_list-/ism', $this->name );

		// Or is a single entry view
		$is_single = preg_match( '/single_/ism', $this->name );

		// And the field settings don't say not to show merge tags.
		$not_false = false !== rgar( $this->field, 'show_merge_tags', null );

		return ( $is_single || $is_list ) && $not_false;
	}



	/**
	 * important! Override this class
	 * outputs the field option html
	 */
	function render_option() {
		// to replace on each field
	}

	/**
	 * important! Override this class if needed
	 * outputs the field setting html
	 */
	function render_setting( $override_input = null ) {

		if ( ! empty( $this->field['full_width'] ) ) { ?>
			<th scope="row" colspan="2">
				<div>
					<label for="<?php echo $this->get_field_id(); ?>">
						<?php echo $this->get_field_label() . $this->get_tooltip(); ?>
					</label>
				</div>
				<?php $this->render_input( $override_input ); ?>
			</th>
		<?php } else { ?>
			<th scope="row">
				<label for="<?php echo $this->get_field_id(); ?>">
					<?php echo $this->get_field_label() . $this->get_tooltip(); ?>
				</label>
			</th>
			<td>
				<?php $this->render_input( $override_input ); ?>
			</td>
			<?php
		}
	}

	/**
	 * important! Override this class
	 * outputs the input html part
	 */
	function render_input( $override_input ) {
		echo '';
	}
}
