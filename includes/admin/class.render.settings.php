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
 * @since 1.2
 */

class GravityView_Render_Settings {

	/**
	 * Get the default options for a standard field.
	 *
	 * @param  string      $field_type  Type of field options to render (`field` or `widget`)
	 * @param  string      $template_id Table slug
	 * @param  float       $field_id    GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
	 * @param  string      $context     What context are we in? Example: `single` or `directory`
	 * @param  string      $input_type  (textarea, list, select, etc.)
	 * @return array       Array of field options with `label`, `value`, `type`, `default` keys
	 */
	public static function get_default_field_options( $field_type, $template_id, $field_id, $context, $input_type ) {

		$field_options = array();

		if( 'field' === $field_type ) {

			// Default options - fields
			$field_options = array(
				'show_label' => array(
					'type' => 'checkbox',
					'label' => __( 'Show Label', 'gravityview' ),
					'value' => true,
				),
				'custom_label' => array(
					'type' => 'text',
					'label' => __( 'Custom Label:', 'gravityview' ),
					'value' => '',
					'merge_tags' => true,
				),
				'custom_class' => array(
					'type' => 'text',
					'label' => __( 'Custom CSS Class:', 'gravityview' ),
					'desc' => __( 'This class will be added to the field container', 'gravityview'),
					'value' => '',
					'merge_tags' => true,
					'tooltip' => 'gv_css_merge_tags',
				),
				'only_loggedin' => array(
					'type' => 'checkbox',
					'label' => __( 'Make visible only to logged-in users?', 'gravityview' ),
					'value' => ''
				),
				'only_loggedin_cap' => array(
					'type' => 'select',
					'label' => __( 'Make visible for:', 'gravityview' ),
					'options' => self::get_cap_choices( $template_id, $field_id, $context, $input_type ),
					'class' => 'widefat',
					'value' => 'read',
				),
			);

			// Match Table as well as DataTables
			if( preg_match( '/table/ism', $template_id ) && 'directory' === $context ) {
				$field_options['width'] = array(
					'type' => 'number',
					'label' => __('Percent Width', 'gravityview'),
					'desc' => __( 'Leave blank for column width to be based on the field content.', 'gravityview'),
					'class' => 'code widefat',
					'value' => '',
				);
			}

		}

		/**
		 * @filter `gravityview_template_{$field_type}_options` Filter the field options by field type. Filter names: `gravityview_template_field_options` and `gravityview_template_widget_options`
		 * @param[in,out] array    Array of field options with `label`, `value`, `type`, `default` keys
		 * @param[in]  string      $template_id Table slug
		 * @param[in]  float       $field_id    GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
		 * @param[in]  string      $context     What context are we in? Example: `single` or `directory`
		 * @param[in]  string      $input_type  (textarea, list, select, etc.)
		 */
		$field_options = apply_filters( "gravityview_template_{$field_type}_options", $field_options, $template_id, $field_id, $context, $input_type );

		/**
		 * @filter `gravityview_template_{$input_type}_options` Filter the field options by input type (`$input_type` examples: `textarea`, `list`, `select`, etc.)
		 * @param[in,out] array    Array of field options with `label`, `value`, `type`, `default` keys
		 * @param[in]  string      $template_id Table slug
		 * @param[in]  float       $field_id    GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
		 * @param[in]  string      $context     What context are we in? Example: `single` or `directory`
		 * @param[in]  string      $input_type  (textarea, list, select, etc.)
		 */
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
			'read' => __( 'Any Logged-In User', 'gravityview' ),
			'publish_posts' => __( 'Author Or Higher', 'gravityview' ),
			'gravityforms_view_entries' => __( 'Can View Gravity Forms Entries', 'gravityview' ),
			'delete_others_posts' => __( 'Editor Or Higher', 'gravityview' ),
			'gravityforms_edit_entries' => __( 'Can Edit Gravity Forms Entries', 'gravityview' ),
			'manage_options' => __( 'Administrator', 'gravityview' ),
		);

		if( is_multisite() ) {
			$select_cap_choices['manage_network'] = __('Multisite Super Admin', 'gravityview' );
		}

		/**
		 * @filter `gravityview_field_visibility_caps` Modify the capabilities shown in the field dropdown
		 * @see http://docs.gravityview.co/article/96-how-to-modify-capabilities-shown-in-the-field-only-visible-to-dropdown
		 * @since  1.0.1
		 * @param  array $select_cap_choices Associative rray of role slugs with labels ( `manage_options` => `Administrator` )
		 * @param  string $template_id Optional. View slug
		 * @param  string $field_id    Optional. GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
		 * @param  string $context     Optional. What context are we in? Example: `single` or `directory`
		 * @param  string $input_type  Optional. (textarea, list, select, etc.)
		 */
		$select_cap_choices = apply_filters('gravityview_field_visibility_caps', $select_cap_choices, $template_id, $field_id, $context, $input_type );

		return $select_cap_choices;
	}


	/**
	 * Render Field Options html (shown through a dialog box)
	 *
	 * @see GravityView_Ajax::get_field_options
	 * @see GravityView_Admin_Views::render_active_areas
	 *
	 * @access public
	 * @param string $field_type field / widget
	 * @param string $template_id
	 * @param string $field_id
	 * @param string $field_label
	 * @param string $area
	 * @param string $uniqid (default: '')
	 * @param string $current (default: '')
	 * @param string $context (default: 'single')
	 * @param array $item Field or widget array that's being rendered
	 *
	 * @return string HTML of dialog box
	 */
	public static function render_field_options( $field_type, $template_id, $field_id, $field_label, $area, $input_type = NULL, $uniqid = '', $current = '', $context = 'single', $item = array() ) {

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

		$output .= '<div class="gv-dialog-options" title="'. esc_attr( sprintf( __( 'Options: %s', 'gravityview' ) , strip_tags( html_entity_decode( $field_label ) ) ) ) .'">';

		/**
		 * @since 1.8
		 */
		if( !empty( $item['subtitle'] ) ) {
			$output .= '<div class="subtitle">' . $item['subtitle'] . '</div>';
		}

		foreach( $options as $key => $option ) {

			$value = isset( $current[ $key ] ) ? $current[ $key ] : NULL;

			$field_output = self::render_field_option( $name_prefix . '['. $key .']' , $option, $value);

			// The setting is empty
			if( empty( $field_output ) ) {
				continue;
			}

			switch( $option['type'] ) {
				// Hide hidden fields
				case 'hidden':
					$output .= '<div class="gv-setting-container gv-setting-container-'. esc_attr( $key ) . ' screen-reader-text">'. $field_output . '</div>';
					break;
				default:
					$output .= '<div class="gv-setting-container gv-setting-container-'. esc_attr( $key ) . '">'. $field_output .'</div>';
			}
		}

		// close options window
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

		$output = '';

		/**
		 * @deprecated setting index 'default' was replaced by 'value'
		 * @see GravityView_FieldType::get_field_defaults
		 */
		if( !empty( $option['default'] ) && empty( $option['value'] ) ) {
			$option['value'] = $option['default'];
			_deprecated_function( 'GravityView_FieldType::get_field_defaults', '1.1.7', '[value] instead of [default] when defining the setting '. $name .' details' );
		}

		// prepare to render option field type
		if( isset( $option['type'] ) ) {

			$type_class = self::load_type_class( $option );

			if( class_exists( $type_class ) ) {

				/** @var GravityView_FieldType $render_type */
				$render_type = new $type_class( $name, $option, $curr_value );

				ob_start();

				$render_type->render_option();

				$output = ob_get_clean();

				/**
				 * @filter `gravityview/option/output/{option_type}` Modify the output for a GravityView setting.\n
				 * `$option_type` is the type of setting (`radio`, `text`, etc.)
				 * @param[in,out] string $output field class name
				 * @param[in] array $option  option field data
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
	 * @param  string $override_input   [description]
	 * @param  string $name             [description]
	 * @param  string $id               [description]
	 * @return void                   [description]
	 */
	public static function render_setting_row( $key = '', $current_settings = array(), $override_input = null, $name = 'template_settings[%s]', $id = 'gravityview_se_%s' ) {

		$setting = GravityView_View_Data::get_default_arg( $key, true );

		// If the key doesn't exist, there's something wrong.
		if( empty( $setting ) ) { return; }

		/**
		 * @deprecated setting index 'name' was replaced by 'label'
		 * @see GravityView_FieldType::get_field_defaults
		 */
		if( isset( $setting['name'] ) && empty( $setting['label'] ) ) {
			$setting['label'] = $setting['name'];
			_deprecated_function( 'GravityView_FieldType::get_field_defaults', '1.1.7', '[label] instead of [name] when defining the setting '. $key .' details' );
		}

		$name = esc_attr( sprintf( $name, $key ) );
		$setting['id'] = esc_attr( sprintf( $id, $key ) );
		$setting['tooltip'] = 'gv_' . $key;

		// Use default if current setting isn't set.
		$curr_value = isset( $current_settings[ $key ] ) ? $current_settings[ $key ] : $setting['value'];

		// default setting type = text
		$setting['type'] = empty( $setting['type'] ) ? 'text' : $setting['type'];

		// merge tags
		if( !isset( $setting['merge_tags'] ) ) {
			if( $setting['type'] === 'text' ) {
				$setting['merge_tags'] = true;
			} else {
				$setting['merge_tags'] = false;
			}
		}

		$output = '';

		// render the setting
		$type_class = self::load_type_class( $setting );
		if( class_exists( $type_class ) ) {
			/** @var GravityView_FieldType $render_type */
			$render_type = new $type_class( $name, $setting, $curr_value );
			ob_start();
			$render_type->render_setting( $override_input );
			$output = ob_get_clean();
		}

		// Check if setting is specific for a template
		if( !empty( $setting['show_in_template'] ) ) {
			if( !is_array( $setting['show_in_template'] ) ) {
				$setting['show_in_template'] = array( $setting['show_in_template'] );
			}
			$show_if = ' data-show-if="'. implode( ' ', $setting['show_in_template'] ).'"';
		} else {
			$show_if = '';
		}

		if( ! empty( $setting['requires'] ) ) {
			$show_if .= sprintf( ' data-requires="%s"', $setting['requires'] );
		}

		// output
		echo '<tr valign="top" '. $show_if .'>' . $output . '</tr>';

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
		 * @filter `gravityview/setting/class/{field_type}`
		 * @param string $class_suffix  field class suffix; `GravityView_FieldType_{$class_suffix}`
		 * @param array $field   field data
		 */
		$type_class = apply_filters( "gravityview/setting/class/{$field['type']}", 'GravityView_FieldType_' . $field['type'], $field );

		if( !class_exists( $type_class ) ) {

			/**
			 * @filter `gravityview/setting/class_file/{field_type}`
			 * @param string  $field_type_include_path field class file path
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





	/**
	 * @deprecated 1.2
	 * Render the HTML for a checkbox input to be used on the field & widgets options
	 * @param  string $name , name attribute
	 * @param  string $current current value
	 * @return string         html tags
	 */
	public static function render_checkbox_option( $name = '', $id = '', $current = '' ) {

		_deprecated_function( __METHOD__, '1.2', 'GravityView_FieldType_checkbox::render_input' );

		$output  = '<input name="'. esc_attr( $name ) .'" type="hidden" value="0">';
		$output .= '<input name="'. esc_attr( $name ) .'" id="'. esc_attr( $id ) .'" type="checkbox" value="1" '. checked( $current, '1', false ) .' >';

		return $output;
	}


	/**
	 * @deprecated 1.2
	 * Render the HTML for an input text to be used on the field & widgets options
	 * @param  string $name    Unique name of the field. Exampe: `fields[directory_list-title][5374ff6ab128b][custom_label]`
	 * @param  string $current [current value]
	 * @param string $add_merge_tags Add merge tags to the input?
	 * @param array $args Field settings, including `class` key for CSS class
	 * @return string         [html tags]
	 */
	public static function render_text_option( $name = '', $id = '', $current = '', $add_merge_tags = NULL, $args = array() ) {

		_deprecated_function( __METHOD__, '1.2', 'GravityView_FieldType_text::render_input' );

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

	/**
	 * @deprecated 1.2
	 * Render the HTML for an textarea input to be used on the field & widgets options
	 * @param  string $name    Unique name of the field. Exampe: `fields[directory_list-title][5374ff6ab128b][custom_label]`
	 * @param  string $current [current value]
	 * @param string|boolean $add_merge_tags Add merge tags to the input?
	 * @param array $args Field settings, including `class` key for CSS class
	 * @return string         [html tags]
	 */
	public static function render_textarea_option( $name = '', $id = '', $current = '', $add_merge_tags = NULL, $args = array() ) {

		_deprecated_function( __METHOD__, '1.2', 'GravityView_FieldType_textarea::render_input' );

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

		return '<textarea name="'. esc_attr( $name ) .'" id="'. esc_attr( $id ) .'" class="'.esc_attr( $class ).'">'. esc_textarea( $current ) .'</textarea>';
	}

	/**
	 *
	 * Render the HTML for a select box to be used on the field & widgets options
	 * @deprecated 1.2
	 * @param  string $name    [name attribute]
	 * @param  array $choices [select options]
	 * @param  string $current [current value]
	 * @return string          [html tags]
	 */
	public static function render_select_option( $name = '', $id = '', $choices, $current = '' ) {

		_deprecated_function( __METHOD__, '1.2', 'GravityView_FieldType_select::render_input' );

		$output = '<select name="'. $name .'" id="'. $id .'">';
		foreach( $choices as $value => $label ) {
			$output .= '<option value="'. esc_attr( $value ) .'" '. selected( $value, $current, false ) .'>'. esc_html( $label ) .'</option>';
		}
		$output .= '</select>';

		return $output;
	}


}
