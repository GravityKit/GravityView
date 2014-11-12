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
    public static function combine_update_existing( $original_entry, $form_id ) {

        $form = gravityview_get_form( $form_id );

        foreach ( $original_entry as $field_id => $value ) {

        	$field = RGFormsModel::get_field( $form, $field_id );

        	// Get the value of the field, including $_POSTed value
        	$value = RGFormsModel::get_field_value( $field );

        	$posted_entry[ $field_id ] = ( is_array( $value ) && isset( $value[ $field_id ] ) ) ? $value[ $field_id ] : $value;

        	continue;
        }

        // Remove empty
        $posted_entry = array_filter( $posted_entry );

        // If the field doesn't exist, merge it in to $_POST
        $_POST = array_merge( $posted_entry, $_POST );

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
                    <label for="name"><?php esc_html_e( "Details", "gravityforms" ); ?></label>
            </h3>
            <div class="inside">
                <table class="form-table entry-details">
                    <tbody>
                    <?php

                    // Get all fields for form
                    $properties = GravityView_View_Data::getInstance()->get_fields( $view_id );

                    // If edit tab not yet configured, show all fields
                    $edit_fields = !empty( $properties['edit_edit-fields'] ) ? $properties['edit_edit-fields'] : NULL;

                    // Hide fields depending on admin settings
                    $fields = self::filter_fields( $form['fields'], $edit_fields );

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
                        $required = ( !empty( $field['isRequired'] ) ) ? '<span class="required">*</span>' : '';

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
     * @uses GravityView_Edit_Entry::user_can_edit_field() Check caps
     * @access private
     * @param array $fields
     * @param array $configured_fields
     * @return array $fields
     */
    static public function filter_fields( $fields, $configured_fields ) {

        if( empty( $fields ) || !is_array( $fields ) ) {
            return $fields;
        }

        $edit_fields = array();


        // The Edit tab has not been configured, so we return all fields by default.
        if( empty( $configured_fields ) ) {
        	return $fields;
        }

        // The edit tab has been configured, so we loop through to configured settings
    	foreach ( $configured_fields as $configured_field ) {

    	    foreach ( $fields as $field ) {

    	    	if( intval( $configured_field['id'] ) === intval( $field['id'] ) ){

    	    		if( GravityView_Edit_Entry::user_can_edit_field( $configured_field, false ) ) {
    	               $edit_fields[] = self::merge_field_properties( $field, $configured_field );
    	            }

    	        }

    	    }

    	}

        return $edit_fields;

    }

    /**
     * Override GF Form field properties with the ones defined on the View
     * @param  array $field GF Form field object
     * @param  array $setting  GV field options
     * @return array
     */
    static private function merge_field_properties( $field, $field_setting ) {

    	$return_field = $field;

        if( empty( $field_setting['show_label'] ) ) {
            $return_field['label'] = '';
        } elseif ( !empty( $field_setting['custom_label'] ) ) {
            $return_field['label'] = $field_setting['custom_label'];
        }

        if( !empty( $field_setting['custom_class'] ) ) {
             $return_field['gvCustomClass'] = gravityview_sanitize_html_class( $field_setting['custom_class'] );
        }

        return $return_field;

    }


}
