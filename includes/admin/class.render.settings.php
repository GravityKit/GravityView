<?php
/**
 * Renders field/widget options and view settings
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityView <hello@gravityview.co>
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.2
 */

class GravityView_Render_Settings {

	/**
	 * Get available field groups.
	 *
	 * @since 2.10
	 *
	 * @return array
	 */
	public static function get_field_groups() {

		return array(
			'field'      => _x( 'Field', 'Denotes the name under which certain field settings are grouped', 'gravityview' ),
			'display'    => _x( 'Display', 'Denotes the name under which certain field settings are grouped', 'gravityview' ),
			'label'      => _x( 'Label', 'Denotes the name under which certain field settings are grouped', 'gravityview' ),
			'visibility' => _x( 'Visibility', 'Denotes the name under which certain field settings are grouped', 'gravityview' ),
			'advanced'   => _x( 'Advanced', 'Denotes the name under which certain field settings are grouped', 'gravityview' ),
			'default'    => _x( 'Default', 'Denotes the name under which certain field settings are grouped', 'gravityview' ),
		);
	}

	/**
	 * Get the default options for a standard field.
	 *
	 * @since 2.10 added $grouped parameter
	 *
	 * @param  string      $field_type  Type of field options to render (`field` or `widget`)
	 * @param  string      $template_id Layout slug (`default_table`, `default_list`, `datatables_table`, etc.
	 * @param  float       $field_id    GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
	 * @param  string      $context     What context are we in? Example: `single` or `directory`
	 * @param  string      $input_type  (textarea, list, select, etc.)
	 * @param  int         $form_id     The form ID. @since develop
	 * @param  bool        $grouped     Whether to group the field settings by `group` key
	 *
	 * @return array       Array of field options with `label`, `value`, `type`, `default` keys
	 */
	public static function get_default_field_options( $field_type, $template_id, $field_id, $context, $input_type, $form_id, $grouped = false ) {

		$field_options = array();

		$is_table_layout = preg_match( '/table/ism', $template_id );

		if( 'field' === $field_type ) {

			// Default options - fields
			$field_options = array(
				'show_label' => array(
					'type' => 'checkbox',
					'label' => __( 'Show Label', 'gravityview' ),
					'value' => ! empty ( $is_table_layout ),
					'priority' => 1000,
					'group' => 'label',
				),
				'custom_label' => array(
					'type' => 'text',
					'label' => __( 'Custom Label:', 'gravityview' ),
					'value' => '',
					'merge_tags' => true,
					'class'      => 'widefat',
					'priority' => 1100,
					'requires' => 'show_label',
					'group' => 'label',
				),
				'custom_class' => array(
					'type'       => 'text',
					'label'      => __( 'Custom CSS Class:', 'gravityview' ),
					'desc'       => __( 'This class will be added to the field container', 'gravityview' ),
					'value'      => '',
					'merge_tags' => true,
					'tooltip'    => 'gv_css_merge_tags',
					'class'      => 'widefat code',
					'priority' => 5000,
					'group' => 'advanced',
				),
				'only_loggedin' => array(
					'type' => 'checkbox',
					'label' => __( 'Make visible only to logged-in users?', 'gravityview' ),
					'value' => '',
					'priority' => 4000,
					'group' => 'visibility',
				),
				'only_loggedin_cap' => array(
					'type' => 'select',
					'label' => __( 'Make visible for:', 'gravityview' ),
					'options' => self::get_cap_choices( $template_id, $field_id, $context, $input_type ),
					'class' => 'widefat',
					'value' => 'read',
					'priority' => 4100,
					'requires' => 'only_loggedin',
					'group' => 'visibility',
				),
			);

			// Match Table as well as DataTables
			if( $is_table_layout && 'directory' === $context ) {
				$field_options['width'] = array(
					'type' => 'number',
					'label' => __('Percent Width', 'gravityview'),
					'desc' => __( 'Leave blank for column width to be based on the field content.', 'gravityview'),
					'class' => 'code widefat',
					'value' => '',
					'priority' => 200,
					'group' => 'display',
				);
			}

		}

		// Remove suffix ":" from the labels to standardize style. Using trim() instead of rtrim() for i18n.
		foreach ( $field_options as $key => $field_option ) {
			$field_options[ $key ]['label'] = trim( $field_options[ $key ]['label'], ':' );
		}

		/**
		 * @filter `gravityview_template_{$field_type}_options` Filter the field options by field type. Filter names: `gravityview_template_field_options` and `gravityview_template_widget_options`
		 * @param[in,out] array    Array of field options with `label`, `value`, `type`, `default` keys
		 * @param[in]  string      $template_id Table slug
		 * @param[in]  float       $field_id    GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
		 * @param[in]  string      $context     What context are we in? Example: `single` or `directory`
		 * @param[in]  string      $input_type  (textarea, list, select, etc.)
		 * @param[in]  int         $form_id     The form ID. {@since 2.5}
		 */
		$field_options = apply_filters( "gravityview_template_{$field_type}_options", $field_options, $template_id, $field_id, $context, $input_type, $form_id );

		/**
		 * @filter `gravityview_template_{$input_type}_options` Filter the field options by input type (`$input_type` examples: `textarea`, `list`, `select`, etc.)
		 * @param[in,out] array    Array of field options with `label`, `value`, `type`, `default` keys
		 * @param[in]  string      $template_id Table slug
		 * @param[in]  float       $field_id    GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
		 * @param[in]  string      $context     What context are we in? Example: `single` or `directory`
		 * @param[in]  string      $input_type  (textarea, list, select, etc.)
		 * @param[in]  int         $form_id     The form ID. {@since 2.5}
		 */
		$field_options = apply_filters( "gravityview_template_{$input_type}_options", $field_options, $template_id, $field_id, $context, $input_type, $form_id );

		if ( $grouped ) {

			$option_groups = array();

			foreach ( $field_options as $key => $field_option ) {

				// TODO: Add filter to override instead of doing inline.
				switch ( $key ) {
					case 'show_as_link':
						$_group = 'display';
						$field_option['priority'] = 100;
						break;
					default:
						$_group = \GV\Utils::get( $field_option, 'group', 'display' );
						break;
				}

				$option_groups[ $_group ][ $key ] = $field_option;
			}

			foreach ( $option_groups as & $option_group ) {
				uasort( $option_group, array( __CLASS__, '_sort_by_priority' ) );
			}

			$field_options = array();
			foreach ( self::get_field_groups() as $group_key => $group_name  ) {
				$field_options[ $group_key ] = \GV\Utils::get( $option_groups, $group_key, array() );
			}

		} else {
			uasort( $field_options, array( __CLASS__, '_sort_by_priority' ) );
		}

		return $field_options;
	}

	/**
	 * Sort field settings by the `priority` key
	 *
	 * Default priority is 10001. Lower is higher.
	 *
	 * @since 3.0
	 * @internal
	 *
	 * @param array $a
	 * @param array $b
	 */
	static public function _sort_by_priority( $a, $b ) {

		$a_priority = \GV\Utils::get( $a, 'priority', 10001 );
		$b_priority = \GV\Utils::get( $b, 'priority', 10001 );

		if ( $a_priority === $b_priority ) {
			return 0;
		}

		return ( $a_priority < $b_priority ) ? - 1 : 1;
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
		 * @see https://docs.gravityview.co/article/96-how-to-modify-capabilities-shown-in-the-field-only-visible-to-dropdown
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
	 * @param string $form_id
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
	public static function render_field_options( $form_id, $field_type, $template_id, $field_id, $field_label, $area, $input_type = NULL, $uniqid = '', $current = '', $context = 'single', $item = array() ) {

		if( empty( $uniqid ) ) {
			//generate a unique field id
			$uniqid = uniqid('', false);
		}

		$grouped = ( 'field' === $field_type );

		// get field/widget options
		$option_groups = self::get_default_field_options( $field_type, $template_id, $field_id, $context, $input_type, $form_id, $grouped );

		if( ! $grouped ) {
			$option_groups = array( $option_groups );
		}

		$option_groups = array_filter( $option_groups );

		// two different post arrays, depending of the field type
		$name_prefix = $field_type .'s' .'['. $area .']['. $uniqid .']';

		// build output
		$hidden_fields  = '<input type="hidden" class="field-key" name="'. $name_prefix .'[id]" value="'. esc_attr( $field_id ) .'">';
		$hidden_fields .= '<input type="hidden" class="field-label" name="'. $name_prefix .'[label]" value="'. esc_attr( $field_label ) .'">';

		$form_title = '';
		if ( $form_id ) {
			$hidden_fields .= '<input type="hidden" class="field-form-id" name="'. $name_prefix .'[form_id]" value="'. esc_attr( $form_id ) .'">';
			$form = GVCommon::get_form( $form_id );
			$form_title = $form['title'];
		}

		// If there are no options, return what we got.
		if ( empty( $option_groups ) ) {
			return $hidden_fields . '<!-- No Options -->'; // The HTML comment is here for checking if the output is empty in render_label()
		}

		$settings_title = esc_attr( sprintf( __( '%s Settings', 'gravityview' ) , strip_tags( html_entity_decode( $field_label ) ) ) );

		$field_details = '';

		// Get the pretty name for the input type
		$gv_field = GravityView_Fields::get( $input_type );

		if( $gv_field ) {
			$input_type_label = $gv_field->label;
		} else {
			$input_type_label = $input_type;
		}

		$field_settings = '';
		foreach ( $option_groups as $group_key => $option_group ) {

			if ( empty( $option_group ) ) {
				continue;
			}

			if ( $grouped ) {
				$group_name     = rgar( self::get_field_groups(), $group_key, '' );
				$field_settings .= '<fieldset class="item-settings-group item-settings-group-' . esc_attr( $group_key ) . '">';
				$field_settings .= '<legend>' . esc_attr( $group_name ) . '</legend>';
			}

			foreach ( $option_group as $key => $option ) {

				$value = isset( $current[ $key ] ) ? $current[ $key ] : null;

				$field_output = self::render_field_option( $name_prefix . '[' . $key . ']', $option, $value );

				// The setting is empty
				if ( empty( $field_output ) ) {
					continue;
				}

				$show_if = '';
				if ( ! empty( $option['requires'] ) ) {
					$show_if .= sprintf( ' data-requires="%s"', $option['requires'] );
				}

				if ( ! empty( $option['requires_not'] ) ) {
					$show_if .= sprintf( ' data-requires-not="%s"', $option['requires_not'] );
				}

				switch ( $option['type'] ) {
					// Hide hidden fields
					case 'hidden':
						$field_settings .= '<div class="gv-setting-container gv-setting-container-' . esc_attr( $key ) . ' screen-reader-text">' . $field_output . '</div>';
						break;
					default:
						$field_settings .= '<div class="gv-setting-container gv-setting-container-' . esc_attr( $key ) . '" ' . $show_if . '>' . $field_output . '</div>';
				}
			}

			if ( $grouped ) {
				$field_settings .= '</fieldset>';
			}
		}

		$item_details = '';
		$subtitle = '';

		if( 'field' === $field_type ) {
			$subtitle = ! empty( $item['subtitle'] ) ? '<div class="subtitle">' . $item['subtitle'] . '</div>' : '';

			$item_details .= '
			<div class="gv-field-details--container">
				<label class="gv-field-details--toggle">' . esc_html__( 'Field Details', 'gravityview' ) .' <i class="dashicons dashicons-arrow-down"></i></label>
				<section class="gv-field-details gv-field-details--closed">';

				if ( $field_id && is_numeric( $field_id ) ) {
				$item_details .= '
					<div class="gv-field-detail gv-field-detail--field">
						<span class="gv-field-detail--label">' . esc_html__( 'Field ID', 'gravityview' ) .'</span><span class="gv-field-detail--value">#{{field_id}}</span>
					</div>';
			    }

				$item_details .= '
					<div class="gv-field-detail gv-field-detail--type">
						<span class="gv-field-detail--label">' . esc_html_x( 'Type', 'The type of field being configured (eg: "Single Line Text")', 'gravityview' ) .'</span><span class="gv-field-detail--value">{{input_type_label}}</span>
					</div>';

				if( $form_id ) {
					$item_details .= '
					<div class="gv-field-detail gv-field-detail--form">
						<span class="gv-field-detail--label">' . esc_html__( 'Form', 'gravityview' ) .'</span><span class="gv-field-detail--value">{{form_title}} (#{{form_id}})</span>
					</div>';
				}
				$item_details .= '
				</section>
			</div>';
		} else {
			$widget_details_content = rgar( $item, 'description', '' );
			if ( ! empty( $item['subtitle'] ) ) {
				$widget_details_content .= ( '' !== $widget_details_content ) ? "\n\n" . $item['subtitle'] : $item['subtitle'];
			}

			// Intentionally not escaping to allow HTML.
			$item_details = '<div class="gv-field-details--container">' . wpautop( trim( $widget_details_content ) ) . '</div>';
		}

$template = <<<EOD
		<div class="gv-dialog-options" title="{{settings_title}}">
			{{item_details}}
			{{subtitle}}
			{{field_settings}}
			{{hidden_fields}}
		</div>
EOD;

		$output = $template;

		$replacements = array(
			'settings_title',
			'hidden_fields',
			'subtitle',
			'field_settings',
			'item_details',
			'input_type_label',
			'field_id',
			'form_title',
			'form_id',
		);

		foreach ( $replacements as $replacement ) {
			$output = str_replace( '{{' . $replacement . '}}', ${$replacement}, $output );
		}

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
	public static function render_field_option( $name = '', $option = array(), $curr_value = NULL ) {

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

				/** @type GravityView_FieldType $render_type */
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

		$settings = \GV\View_Settings::with_defaults( true );

		// If the key doesn't exist, there's something wrong.
		if ( ! $setting = $settings->get( $key ) ) {
			return;
		}

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
			/** @type GravityView_FieldType $render_type */
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

		if( ! empty( $setting['requires_not'] ) ) {
			$show_if .= sprintf( ' data-requires-not="%s"', $setting['requires_not'] );
		}

		// output
		echo '<tr style="vertical-align: top;" '. $show_if .'>' . $output . '</tr>';

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

		if( class_exists( $type_class ) ) {
			return $type_class;
		}

		/**
		 * @filter `gravityview/setting/class_file/{field_type}`
		 * @param string  $field_type_include_path field class file path
		 * @param array $field  field data
		 */
		$class_file = apply_filters( "gravityview/setting/class_file/{$field['type']}", GRAVITYVIEW_DIR . "includes/admin/field-types/type_{$field['type']}.php", $field );

		if( $class_file && file_exists( $class_file ) ) {
			require_once( $class_file );
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
