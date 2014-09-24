<?php

/**
 * Modify option field type by extending this class
 */
abstract class GravityView_FieldType {

    //field form html name
    private $name;

    // field settings
    private $field;

    // field current value
    private $value;

    function __construct( $name, $field = array(), $curr_value = NULL ) {

        $this->name = $name;
        $this->field = $field;
        $this->value = $curr_value;

    }

    /**
     * Returns the default details for a field option
     *
     * - default    // default option value, in case nothing is defined (deprecated)
     * - desc       // option description
     * - value      // the option default value
     * - label      // the option label
     * - id         // the field id
     * - type       // the option type ( text, checkbox, select, ... )
     * - options    // when type is select, define the select options ('choices' is deprecated)
     * - merge_tags // if the option supports merge tags feature
     * - class      // (new) define extra classes for the field
     * - tooltip    //
     * - section    // (new) ?
     * - callback   // (new) define a special render callback function
     * - condition  // (new) ?
     *
     * @return array
     */
    public static function get_field_option_defaults() {
        return array(
            'default' => '',
            'desc' => '',
            'value' => NULL,
            'label' => '',
            'type'  => 'text',
            'choices' => NULL,
            'merge_tags' => true,
            'class' => '',
            'tooltip' => NULL,

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
        $is_single = preg_match( '/single_/ism', $name );

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

        if( !empty( $this->field['full_width'] ) ) : ?>
            <td scope="row" colspan="2">
                <div>
                    <label for="<?php echo $this->get_field_id(); ?>">
                        <?php echo $this->get_field_label() . $this->get_tooltip() . $this->get_field_desc(); ?>
                    </label>
                </div>
                <?php self::render_input( $override_input ); ?>
            </td>
        <?php else: ?>
            <td scope="row">
                <label for="<?php echo $this->get_field_id(); ?>">
                    <?php echo $this->get_field_label() . $this->get_tooltip() . $this->get_field_desc(); ?>
                </label>
            </td>
            <td>
                <?php self::render_input( $override_input ); ?>
            </td>
        <?php endif;

    }

    /**
     * important! Override this class
     * outputs the input html part
     */
    function render_input( $override_input ) {
        echo '';
    }

}
