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

    function __construct( $name = '', $field = array(), $curr_value = NULL ) {

        $this->name = $name;

        $defaults = self::get_field_defaults();

        // Backward compatibility
        if( !empty( $field['choices'] ) ) {
        	$field['options'] = $field['choices'];
        	unset( $field['choices'] );
        }

        $this->field =  wp_parse_args( $field, $defaults );

        $this->value = is_null( $curr_value ) ? $this->field['value'] : $curr_value;

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
            'desc' => '',
            'value' => NULL,
            'label' => '',
            'left_label' => NULL,
            'id' => NULL,
            'type'  => 'text',
            'options' => NULL,
            'merge_tags' => true,
            'class' => '',
            'tooltip' => NULL,
            'requires' => NULL
        );
    }


    function get_tooltip() {
        if( !function_exists('gform_tooltip') ) {
            return NULL;
        }

        return !empty( $this->field['tooltip'] ) ? ' '.gform_tooltip( $this->field['tooltip'] , false, true ) : NULL;
    }

    /**
     * Build input id based on the name
     * @return string
     */
    function get_field_id() {
        if( isset( $this->field['id'] ) ) {
            return esc_attr( $this->field['id'] );
        }
        return esc_attr( sanitize_html_class( $this->name ) );
    }

    /**
     * Retrieve field label
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
		return ! empty( $this->field['left_label'] ) ? esc_html( trim( $this->field['left_label'] ) ) : NULL;
	}

    /**
     * Retrieve field label class
     * @return string
     */
    function get_label_class() {
        return 'gv-label-'. sanitize_html_class( $this->field['type'] );
    }


    /**
     * Retrieve field description
     * @return string
     */
    function get_field_desc() {
        return !empty( $this->field['desc'] ) ? '<span class="howto">'. $this->field['desc'] .'</span>' : '';
    }


    /**
     * Verify if field should have merge tags
     * @return boolean
     */
    function show_merge_tags() {
        // Show the merge tags if the field is a list view
        $is_list = preg_match( '/_list-/ism', $this->name );
        // Or is a single entry view
        $is_single = preg_match( '/single_/ism', $this->name );

        return ( $is_single || $is_list );
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
    function render_setting( $override_input = NULL ) {

        if( !empty( $this->field['full_width'] ) ) { ?>
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
        <?php }

    }

    /**
     * important! Override this class
     * outputs the input html part
     */
    function render_input( $override_input ) {
        echo '';
    }

}
