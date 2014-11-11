<?php

class GV_GFEntryDetail {

    /**
     * Gets stored entry data and combines it in to $_POST array.
     *
     * Reason: If a form field doesn't exist in the $_POST data,
     * its value will be cleared from the DB. Since some form
     * fields could be hidden, we need to make sure existing
     * vales are passed through $_POST.
     *
     * @access public
     * @param int $view_id
     * @param array $entry
     * @return void
     */
    public static function combine_update_existing ( $view_id, $entry ) {

        // Get all fields for form
        $view_data = new GravityView_View_Data;
        $fields = $view_data->get_fields( $view_id );

        $field_pairs = array();

        //Fetch existing save data for this entry
        foreach( $fields[ 'directory_table-columns' ] as $k => $field ){
            $field_pairs[ 'input_' . $field['id'] ] = RGFormsModel::get_lead_field_value ( $entry, $field );
        }

        //If the field doesn't exist, merge it in to $_POST
        $_POST = array_merge( $field_pairs, $_POST );

    }

    /**
     * A modified version of the Gravity Form method.
     * Generates the form responsible for editing a Gravity
     * Forms entry.
     *
     * @access public
     * @param array $fields
     * @param array $properties
     * @return void
     */
    public static function lead_detail_edit( $form, $lead, $view_id ){

        $form = apply_filters( "gform_admin_pre_render_" . $form["id"], apply_filters( "gform_admin_pre_render", $form ) );
        $form_id = $form["id"];
        ?>
        <div class="postbox">
            <h3>
                    <label for="name"><?php _e( "Details", "gravityforms" ); ?></label>
            </h3>
            <div class="inside">
                <table class="form-table entry-details">
                    <tbody>
                    <?php

                    // Get all fields for form
                    $view_data = new GravityView_View_Data;
                    $properties = $view_data->get_fields( $view_id );

                    // By default, allow editing all fields
                    $fields = $form['fields'];

                    if( !empty( $properties['edit_edit-fields'] ) ) {

                    	// Hide fields depending on admin settings
                    	$fields = self::filter_fields( $form['fields'], $properties['edit_edit-fields'] );

                    }

                    foreach( $fields as $field ){

                        $td_id = "field_" . $form_id . "_" . $field['id'];
                        $value = RGFormsModel::get_lead_field_value( $lead, $field );
                        $label = esc_html( GFCommon::get_label( $field ) );
                        $input = GV_GFCommon::get_field_input( $field, $value, $lead['id'], $form_id );
                        $error_class = rgget( 'failed_validation', $field ) ? "gfield_error" : "";

                        $validation_message = ( rgget('failed_validation', $field ) && !empty( $field['validation_message'] ) ) ? sprintf("<div class='gfield_description validation_message'>%s</div>", $field['validation_message'] ) : '';

                        if( rgar( $field, 'descriptionPlacement') == 'above' ) {
                            $input = $validation_message . $input;
                        } else {
                            $input = $input . $validation_message;
                        }

                        //Add required indicator
                        $required = ( $field['isRequired'] == 1 ) ? '<span class="required">*</span>' : '';

                        // custom class as defined on field details
                        $custom_class = empty( $field['gvCustomClass'] ) ? '' : ' class="'. esc_attr( $field['gvCustomClass'] ) .'"';

                        switch( RGFormsModel::get_input_type( $field ) ){

                            case 'section' :
                                ?>
                                <tr valign="top"<?php echo $custom_class; ?>>
                                        <td class="detail-view">
                                                <div style="margin-bottom:10px; border-bottom:1px dotted #ccc;"><h2 class="detail_gsection_title"><?php echo $label; ?></h2></div>
                                        </td>
                                </tr>
                                <?php

                            break;

                            case 'captcha':
                            case 'html':
                            case 'password':
                                //ignore certain fields
                            break;

                            default :

                                $content =
                                    '<tr valign="top"'. $custom_class .'>
                                        <td class="detail-view '.$error_class.'" id="'. $td_id .'">
                                            <label class="detail-label">' . $label . $required . '</label>' . $input . '
                                        </td>
                                    </tr>';

                                $content = apply_filters( 'gravityview_edit_entry_field_content', $content, $field, $value, $lead['id'], $form['id'] );

                                echo $content;
                            break;
                        }
                    }
                    ?>
                    </tbody>
                </table>
                <br/>
                <div class="gform_footer">
                    <input type="hidden" name="gform_unique_id" value="" />
                    <input type="hidden" name="gform_uploaded_files" id="gform_uploaded_files_<?php echo $form_id; ?>" value="" />
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Filter area fields based on specified conditions
     *
     * @access private
     * @param array $fields
     * @param array $properties
     * @return array $fields
     */
    static public function filter_fields( $fields, $properties ) {

        if( empty( $fields ) || !is_array( $fields ) ) {
            return $fields;
        }

        $edit_fields = array();

        foreach ( $properties as $k => $prop ) {

            foreach ( $fields as $k2 => $field ) {

                if( $prop['id'] == $field['id'] ){

                    if( self::is_field_editable( $prop ) ) {
                       $edit_fields[] = self::merge_field_properties( $field, $prop );
                    }

                } elseif( !empty( $field['inputs'] ) ){

                    //If any inputs for the field are not editable, disable that field
                    //All inputs for that field will be disabled.
                    foreach ( $field['inputs'] as $k3 => $input ) {

                        if( $prop['id'] == $input['id'] ){

                            if( self::is_field_editable( $prop ) ) {
                                $edit_fields[] = self::merge_field_properties( $field, $prop );
                            }

                        }

                    }

                }

            }

        }

        return $edit_fields;

    }


    /**
     * Check wether a certain field should not be presented based on its own properties.
     *
     * @access private
     * @param array $properties
     * @return true (field should be hidden) or false (field should be presented)
     */
    static private function is_field_editable( $properties ) {

        if( current_user_can( $properties['allow_edit_cap'] ) ) {
            return true;
        }

        return false;
    }

    /**
     * Override GF Form field properties with the ones defined on the View
     * @param  [type] $field GF Form field object
     * @param  [type] $prop  GV field options
     * @return array
     */
    static private function merge_field_properties( $field, $prop ) {

        if( empty( $prop['show_label'] ) ) {
            $field['label'] = '';
        } elseif ( !empty( $prop['custom_label'] ) ) {
            $field['label'] = $prop['custom_label'];
        }

        if( !empty( $prop['custom_class'] ) ) {
             $field['gvCustomClass'] = gravityview_sanitize_html_class( $prop['custom_class'] );
        }

        return $field;

    }


}
