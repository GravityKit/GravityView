<?php
/**
 * Renders field/widget options and view settings
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.1.7
 */

class GravityView_Render_Settings {

     private static $setting_row_alt = false;

    /**
     * Get the default options for a standard field.
     *
     * @param  string      $field_type  Type of field options to render (`field` or `widget`)
     * @param  string      $template_id Table slug
     * @param  float       $field_id    GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
     * @param  string      $context     What context are we in? Example: `single` or `directory`
     * @param  string      $input_type  (textarea, list, select, etc.)
     * @return array       Array of field options with `label`, `value`, `type`, `default` keys
     *
     * @filter gravityview_template_{$field_type}_options Filter the field options by field type
     *     - gravityview_template_field_options
     *     - gravityview_template_widget_options
     *
     * @filter gravityview_template_{$input_type}_options Filter the field options by input type (textarea, list, select, etc.)
     */
    public static function get_default_field_options( $field_type, $template_id, $field_id, $context, $input_type ) {

        $field_options = array();

        if( 'field' === $field_type ) {

            // Default options - fields
            $field_options = array(
                'show_label' => array(
                    'type' => 'checkbox',
                    'label' => __( 'Show Label', 'gravity-view' ),
                    'value' => preg_match('/table/ism', $template_id), // If the view template is table, show label as default. Otherwise, don't
                ),
                'custom_label' => array(
                    'type' => 'text',
                    'label' => __( 'Custom Label:', 'gravity-view' ),
                    'value' => '',
                    'merge_tags' => true,
                ),
                'custom_class' => array(
                    'type' => 'text',
                    'label' => __( 'Custom CSS Class:', 'gravity-view' ),
                    'desc' => __( 'This class will be added to the field container', 'gravity-view'),
                    'value' => '',
                    'merge_tags' => true,
                    'tooltip' => 'gv_css_merge_tags',
                ),
                'only_loggedin' => array(
                    'type' => 'checkbox',
                    'label' => __( 'Make visible only to logged-in users?', 'gravity-view' ),
                    'value' => ''
                ),
                'only_loggedin_cap' => array(
                    'type' => 'select',
                    'label' => __( 'Make visible for:', 'gravity-view' ),
                    'options' => self::get_cap_choices( $template_id, $field_id, $context, $input_type ),
                    'class' => 'widefat',
                    'value' => 'read',
                ),
            );

        } elseif( 'widget' === $field_type ) {

        }

        // hook to inject template specific field/widget options
        $field_options = apply_filters( "gravityview_template_{$field_type}_options", $field_options, $template_id, $field_id, $context, $input_type );

        // hook to inject template specific input type options (textarea, list, select, etc.)
        $field_options = apply_filters( "gravityview_template_{$input_type}_options", $field_options, $template_id, $field_id, $context, $input_type );

        return $field_options;
    }

    /**
     * Get capabilities options for GravityView
     *
     * Parameters are only to pass to the filter.
     *
     * @param  string $template_id Optional. View slug
     * @param  string $field_id    Optional. GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
     * @param  string $context     Optional. What context are we in? Example: `single` or `directory`
     * @param  string $input_type  Optional. (textarea, list, select, etc.)
     * @return array Associative array, with the key being the capability and the value being the label shown.
     */
    static public function get_cap_choices( $template_id = '', $field_id = '', $context = '', $input_type = '' ) {

        $select_cap_choices = array(
            'read' => __( 'Any Logged-In User', 'gravity-view' ),
            'publish_posts' => __( 'Author Or Higher', 'gravity-view' ),
            'gravityforms_view_entries' => __( 'Can View Gravity Forms Entries', 'gravity-view' ),
            'delete_others_posts' => __( 'Editor Or Higher', 'gravity-view' ),
            'gravityforms_edit_entries' => __( 'Can Edit Gravity Forms Entries', 'gravity-view' ),
            'manage_options' => __( 'Administrator', 'gravity-view' ),
        );

        if( is_multisite() ) {
            $select_cap_choices['manage_network'] = __('Multisite Super Admin', 'gravity-view' );
        }

        /**
         * Modify the capabilities shown in the field dropdown
         * @link  https://github.com/zackkatz/GravityView/wiki/How-to-modify-capabilities-shown-in-the-field-%22Only-visible-to...%22-dropdown
         * @since  1.0.1
         */
        $select_cap_choices = apply_filters('gravityview_field_visibility_caps', $select_cap_choices, $template_id, $field_id, $context, $input_type );

        return $select_cap_choices;
    }


    /**
     * Render Field Options html (shown through a dialog box)
     *
     * @access public
     * @param string $template_id
     * @param string $field_id
     * @param string $field_label
     * @param string $area
     * @param string $uniqid (default: '')
     * @param string $current (default: '')
     * @param string $context (default: 'single')
     * @return void
     */
    public static function render_field_options( $field_type, $template_id, $field_id, $field_label, $area, $input_type = NULL, $uniqid = '', $current = '', $context = 'single' ) {

        if( empty( $uniqid ) ) {
            //generate a unique field id
            $uniqid = uniqid('', false);
        }

        // get field/widget options
        $options = self::get_default_field_options( $field_type, $template_id, $field_id, $context, $input_type );

        // two different post arrays, depending of the field type
        $name_prefix = $field_type .'s' .'['. $area .']['. $uniqid .']';

        // build output
        $output = '';
        $output .= '<input type="hidden" class="field-key" name="'. $name_prefix .'[id]" value="'. esc_attr( $field_id ) .'">';
        $output .= '<input type="hidden" class="field-label" name="'. $name_prefix .'[label]" value="'. esc_attr( $field_label ) .'">';

        // If there are no options, return what we got.
        if(empty($options)) {

            // This is here for checking if the output is empty in render_label()
            $output .= '<!-- No Options -->';

            return $output;
        }

        $output .= '<div class="gv-dialog-options" title="'. esc_attr( sprintf( __( 'Options: %s', 'gravity-view' ), $field_label ) ) .'">';
        $output .= '<ul>';

        foreach( $options as $key => $details ) {
            $value = isset( $current[ $key ] ) ? $current[ $key ] : NULL;
            $output .= '<li>'. self::render_field_option( $name_prefix . '['. $key .']' , $details, $value) .'</li>';
        }

        // close options window
        $output .= '</ul>';
        $output .= '</div>';

        return $output;

    }



    /**
     * Handle rendering a field option form element
     *
     * @param  string     $name    Input `name` attribute
     * @param  array      $option  Associative array of options. See the $defaults variable for available keys.
     * @param  mixed      $curr_value Current value of option
     * @return string     HTML output of option
     */
    public static function render_field_option( $name = '', $option, $curr_value = NULL ) {

        $defaults = GravityView_FieldType::get_field_option_defaults();

        $option = wp_parse_args( $option, $defaults );

        $output = '';

        if( is_null( $curr_value ) ) {
            $curr_value = $option['value'];
        }

        // prepare to render option field type

        if( isset( $option['type'] ) ) {

            $type_class = self::load_type_class( $option );

            if( class_exists( $type_class ) ) {

                $render_type = new $type_class( $name, $option, $curr_value );

                ob_start();

                $render_type->render_option();

                $output = ob_get_clean();

                /**
                 * @filter 'gravityview/option/output/{option_type}'
                 * @param string         field class name
                 * @param array $option  option field data
                 */
                $output = apply_filters( "gravityview/option/output/{$option['type']}" , $output, $option );
            }

        } // isset option[type]

        return $output;
    }






    /**
     * Output a table row for view settings
     * @param  string $key              The key of the input
     * @param  array  $current_settings Associative array of current settings to use as input values, if set. If not set, the defaults are used.
     * @param  [type] $override_input   [description]
     * @param  string $name             [description]
     * @param  string $id               [description]
     * @return [type]                   [description]
     */
    public static function render_setting_row( $key = '', $current_settings = array(), $override_input = null, $name = 'template_settings[%s]', $id = 'gravityview_se_%s' ) {

        $setting = GravityView_View_Data::get_default_arg( $key, true );

        // If the key doesn't exist, there's something wrong.
        if( empty( $setting ) ) { return; }

        $name = esc_attr( sprintf( $name, $key ) );
        $setting['id'] = esc_attr( sprintf( $id, $key ) );
        $setting['tooltip'] = 'gv_' . $key;

        // Use default if current setting isn't set.
        $curr_value = isset( $current_settings[ $key ] ) ? $current_settings[ $key ] : $setting['value'];

        // default setting type = text
        $setting['type'] = empty( $setting['type'] ) ? 'text' : $setting['type'];

        // render the setting
        $type_class = self::load_type_class( $setting );
        if( class_exists( $type_class ) ) {
            $render_type = new $type_class( $name, $setting, $curr_value );
            ob_start();
            $render_type->render_setting( $override_input );
            $output = ob_get_clean();
        }

        $tr_wrap = self::$setting_row_alt ? '<tr valign="top">' : '<tr valign="top" class="alt">';
        self::$setting_row_alt = self::$setting_row_alt ? false : true;

        echo $tr_wrap . $output . '</tr>';

    }


    /**
     * Given a field type calculates the php class. If not found try to load it.
     * @param  array $field
     * @return string type class name
     */
    public static function load_type_class( $field = NULL ) {

        if( empty( $field['type'] ) ) {
            return NULL;
        }

        /**
         * @filter 'gravityview/setting/class/{field_type}'
         * @param string         field class name
         * @param array $field   field data
         */
        $type_class = apply_filters( "gravityview/setting/class/{$field['type']}", 'GravityView_FieldType_' . $field['type'], $field );

        if( !class_exists( $type_class ) ) {

            /**
             * @filter 'gravityview/setting/class_file/{field_type}'
             * @param string         field class file path
             * @param array $field  field data
             */
            $class_file = apply_filters( "gravityview/setting/class_file/{$field['type']}", GRAVITYVIEW_DIR . "includes/admin/field-types/type_{$field['type']}.php", $field );

            if( $class_file ) {
                if( file_exists( $class_file ) ) {
                    require_once( $class_file );
                }
            }

        }

        return $type_class;
    }













    /** @deprecated
     * Render the HTML for a checkbox input to be used on the field & widgets options
     * @param  string $name , name attribute
     * @param  string $current current value
     * @return string         html tags
     */
    public static function render_checkbox_option( $name = '', $id = '', $current = '' ) {

        $output  = '<input name="'. esc_attr( $name ) .'" type="hidden" value="0">';
        $output .= '<input name="'. esc_attr( $name ) .'" id="'. esc_attr( $id ) .'" type="checkbox" value="1" '. checked( $current, '1', false ) .' >';

        return $output;
    }


    /**@deprecated
     * Render the HTML for an input text to be used on the field & widgets options
     * @param  string $name    Unique name of the field. Exampe: `fields[directory_list-title][5374ff6ab128b][custom_label]`
     * @param  string $current [current value]
     * @param  string $desc   Option description
     * @param string $add_merge_tags Add merge tags to the input?
     * @return string         [html tags]
     */
    public static function render_text_option( $name = '', $id = '', $current = '', $add_merge_tags = NULL, $args = array() ) {

        // Show the merge tags if the field is a list view
        $is_list = ( preg_match( '/_list-/ism', $name ));

        // Or is a single entry view
        $is_single = ( preg_match( '/single_/ism', $name ));
        $show = ( $is_single || $is_list );

        $class = '';
        // and $add_merge_tags is not false
        if( $show && $add_merge_tags !== false || $add_merge_tags === 'force' ) {
            $class = 'merge-tag-support mt-position-right mt-hide_all_fields ';
        }

        $class .= !empty( $args['class'] ) ? $args['class'] : 'widefat';
        $type = !empty( $args['type'] ) ? $args['type'] : 'text';

        return '<input name="'. esc_attr( $name ) .'" id="'. esc_attr( $id ) .'" type="'.esc_attr($type).'" value="'. esc_attr( $current ) .'" class="'.esc_attr( $class ).'">';
    }

    /**@deprecated
     * Render the HTML for an textarea input to be used on the field & widgets options
     * @param  string $name    Unique name of the field. Exampe: `fields[directory_list-title][5374ff6ab128b][custom_label]`
     * @param  string $current [current value]
     * @param  string $desc   Option description
     * @param string $add_merge_tags Add merge tags to the input?
     * @return string         [html tags]
     */
    public static function render_textarea_option( $name = '', $id = '', $current = '', $add_merge_tags = NULL, $args = array() ) {

        // Show the merge tags if the field is a list view
        $is_list = ( preg_match( '/_list-/ism', $name ));

        // Or is a single entry view
        $is_single = ( preg_match( '/single_/ism', $name ));
        $show = ( $is_single || $is_list );

        $class = '';
        // and $add_merge_tags is not false
        if( $show && $add_merge_tags !== false || $add_merge_tags === 'force' ) {
            $class = 'merge-tag-support mt-position-right mt-hide_all_fields ';
        }

        $class .= !empty( $args['class'] ) ? 'widefat '.$args['class'] : 'widefat';
        $type = !empty( $args['type'] ) ? $args['type'] : 'text';

        return '<textarea name="'. esc_attr( $name ) .'" id="'. esc_attr( $id ) .'" class="'.esc_attr( $class ).'">'. esc_textarea( $current ) .'</textarea>';
    }

    /**@deprecated
     * Render the HTML for a select box to be used on the field & widgets options
     * @param  string $name    [name attribute]
     * @param  array $choices [select options]
     * @param  string $current [current value]
     * @return string          [html tags]
     */
    public static function render_select_option( $name = '', $id = '', $choices, $current = '' ) {

        $output = '<select name="'. $name .'" id="'. $id .'">';
        foreach( $choices as $value => $label ) {
            $output .= '<option value="'. esc_attr( $value ) .'" '. selected( $value, $current, false ) .'>'. esc_html( $label ) .'</option>';
        }
        $output .= '</select>';

        return $output;
    }


} // end class GravityView_Field_Options