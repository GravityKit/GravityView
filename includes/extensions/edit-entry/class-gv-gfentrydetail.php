<?php

class GV_GFEntryDetail{

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

          // Hide fields depending on admin settings
          $fields = self::filter_fields( $form['fields'], $properties["directory_table-columns"] );

          foreach( $fields as $field ){

            $field_id = $field["id"];

            switch( RGFormsModel::get_input_type( $field ) ){

              case "section" :
                ?>
                <tr valign="top">
                    <td class="detail-view">
                        <div style="margin-bottom:10px; border-bottom:1px dotted #ccc;"><h2 class="detail_gsection_title"><?php echo esc_html( GFCommon::get_label( $field ) ) ?></h2></div>
                    </td>
                </tr>
                <?php

              break;

              case "captcha":
              case "html":
              case "password":
                //ignore certain fields
              break;

              default :
                $value = RGFormsModel::get_lead_field_value( $lead, $field );
                $td_id = "field_" . $form_id . "_" . $field_id;
                
                $content = apply_filters( "gform_field_content", $content, $field, $value, $lead["id"], $form["id"] );

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
            <input type="hidden" name="gform_uploaded_files" id='gform_uploaded_files_<?php echo $form_id; ?>' value="" />
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

    foreach ( $properties as $k => $prop ) {

      if( ! empty( $fields ) ){
        foreach ($fields as $k2 => $field) {

          if( $prop['id'] == $field['id'] ){

            $properties = array_merge( $prop, $field );

            if( self::hide_field_check_conditions( $properties ) ) {
              unset( $fields[ $k2 ] );
            }

          } elseif( !empty( $field['inputs'] ) ){

            //If any inputs for the field are not editable, disable that field
            //All inputs for that field will be disabled.
            foreach ( $field['inputs'] as $k3 => $input ) {

              if( $prop['id'] == $input['id'] ){

                $properties = array_merge( $prop, $input );

                if( self::hide_field_check_conditions( $properties ) ) {
                  unset( $fields[ $k2 ] );
                }

              }

            }

          }

        }

      }

    }

    return $fields;

  }


  /**
   * Check wether a certain field should not be presented based on its own properties.
   *
   * @access private
   * @param array $properties
   * @return true (field should be hidden) or false (field should be presented)
   */
  static private function hide_field_check_conditions( $properties ) {

    if( $properties['allow_edit'] != 1 || !current_user_can( $properties['allow_edit_cap'] ) ) {
      return true;
    }

    return false;
  }
}
