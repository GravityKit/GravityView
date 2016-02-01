<?php
/**
 * @file class-gravityview-field.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Modify field settings by extending this class.
 */
abstract class GravityView_Field {

	/**
	 * The name of the GravityView field type
	 * Example: `created_by`, `text`, `fileupload`, `address`, `entry_link`
	 * @var string
	 */
	public $name;

	/**
	 * @internal Not yet implemented
	 * @since 1.15.2
	 * @type string The description of the field in the field picker
	 */
	public $description;

	/**
	 * @since 1.15.2
	 * @type string The label of the field in the field picker
	 */
	public $label;

	/**
	 * `standard`, `advanced`, `post`, `pricing`, `meta`, `gravityview`
	 * @internal Not yet implemented
	 * @since 1.15.2
	 * @type string The group belongs to this field in the field picker
	 */
	public $group;

	/**
	 * @internal Not yet implemented
	 * @type boolean Can the field be searched?
	 * @since 1.15.2
	 */
	public $is_searchable;

	/**
	 * @internal Not yet implemented
	 * @type array $search_operators The type of search operators available for this field
	 * @since 1.15.2
	 */
	public $search_operators;

	/**
	 * @type boolean Can the field be sorted in search?
	 * @since 1.15.2
	 */
	public $is_sortable = true;

	/**
	 * @type boolean Is field content number-based?
	 * @since 1.15.2
	 */
	public $is_numeric;

	/**
	 * @internal Not yet implemented
	 * @todo implement supports_context() method
	 * The contexts in which a field is available. Some fields aren't editable, for example.
	 * - `singular` is an alias for both `single` and `edit`
	 * - `multiple` is an alias for `directory` (backward compatibility)
	 * @type array
	 * @since 1.15.2
	 */
	public $contexts = array( 'single', 'multiple', 'edit', 'export' );

	/**
	 * @since 1.15.2
	 * @since TODO Changed access to public (previously, protected)
	 * @type string The name of a corresponding Gravity Forms GF_Field class, if exists
	 */
	public $_gf_field_class_name;

	/**
	 * @var string The field ID being requested
	 * @since 1.14
	 */
	protected $_field_id = '';

	/**
	 * @var string Field options to be rendered
	 * @since 1.14
	 */
	protected $_field_options = array();

	/**
	 * @var bool|string Name of merge tag (without curly brackets), if the field has custom GravityView merge tags to add. Otherwise, false.
	 * @since 1.16
	 */
	protected $_custom_merge_tag = false;

	/**
	 * GravityView_Field constructor.
	 */
	public function __construct() {

		// Modify the field options based on the name of the field type
		//add_filter( sprintf( 'gravityview_template_%s_options', $this->name ), array( &$this, 'field_options' ), 10, 5 );

		add_filter( 'gravityview/sortable/field_blacklist', array( $this, '_filter_sortable_fields' ), 1 );

		if( $this->_custom_merge_tag ) {
			add_filter( 'gform_custom_merge_tags', array( $this, '_filter_gform_custom_merge_tags' ), 10, 4 );
			add_filter( 'gform_replace_merge_tags', array( $this, '_filter_gform_replace_merge_tags' ), 10, 7 );
		}

		GravityView_Fields::register( $this );
	}


	/**
	 * Match the merge tag in replacement text for the field.  DO NOT OVERRIDE.
	 *
	 * @see replace_merge_tag Override replace_merge_tag() to handle any matches
	 *
	 * @since 1.16
	 *
	 * @param string $text Text to replace
	 * @param array $form Gravity Forms form array
	 * @param array $entry Entry array
	 * @param bool $url_encode Whether to URL-encode output
	 *
	 * @return string Original text if {_custom_merge_tag} isn't found. Otherwise, replaced text.
	 */
	public function _filter_gform_replace_merge_tags( $text, $form = array(), $entry = array(), $url_encode = false, $esc_html = false  ) {

		/**
		 * This prevents the gform_replace_merge_tags filter from being called twice, as defined in:
		 * @see GFCommon::replace_variables()
		 * @see GFCommon::replace_variables_prepopulate()
		 * @todo Remove eventually: Gravity Forms fixed this issue in 1.9.14
		 */
		if( false === $form ) {
			return $text;
		}

		// Is there is field merge tag? Strip whitespace off the ned, too.
		preg_match_all( '/{' . preg_quote( $this->_custom_merge_tag ) . ':?(.*?)(?:\s)?}/ism', $text, $matches, PREG_SET_ORDER );

		// If there are no matches, return original text
		if ( empty( $matches ) ) {
			return $text;
		}

		return $this->replace_merge_tag( $matches, $text, $form, $entry, $url_encode, $esc_html );
	}

	/**
	 * Run GravityView filters when using GFCommon::replace_variables()
	 *
	 * Instead of adding multiple hooks, add all hooks into this one method to improve speed
	 *
	 * @since 1.8.4
	 *
	 * @param array $matches Array of Merge Tag matches found in text by preg_match_all
	 * @param string $text Text to replace
	 * @param array|bool $form Gravity Forms form array. When called inside {@see GFCommon::replace_variables()} (now deprecated), `false`
	 * @param array|bool $entry Entry array.  When called inside {@see GFCommon::replace_variables()} (now deprecated), `false`
	 * @param bool $url_encode Whether to URL-encode output
	 * @param bool $esc_html Whether to apply `esc_html()` to output
	 *
	 * @return mixed
	 */
	public function replace_merge_tag( $matches = array(), $text = '', $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		foreach( $matches as $match ) {

			$full_tag = $match[0];

			// Strip the Merge Tags
			$tag = str_replace( array( '{', '}'), '', $full_tag );

			// Replace the value from the entry, if exists
			if( isset( $entry[ $tag ] ) ) {

				$value = $entry[ $tag ];

				if( is_callable( array( $this, 'get_content') ) ) {
					$value = $this->get_content( $value );
				}

				$text = str_replace( $full_tag, $value, $text );
			}
		}

		unset( $value, $tag, $full_tag );

		return $text;
	}

	/**
	 * Add custom merge tags to merge tag options. DO NOT OVERRIDE.
	 *
	 * @internal Not to be overridden by fields
	 *
	 * @since 1.8.4
	 *
	 * @param array $custom_merge_tags
	 * @param int $form_id GF Form ID
	 * @param GF_Field[] $fields Array of fields in the form
	 * @param string $element_id The ID of the input that Merge Tags are being used on
	 *
	 * @return array Modified merge tags
	 */
	public function _filter_gform_custom_merge_tags( $custom_merge_tags = array(), $form_id, $fields = array(), $element_id = '' ) {

		$form = GVCommon::get_form( $form_id );

		$field_merge_tags = $this->custom_merge_tags( $form, $fields );

		return array_merge( $custom_merge_tags, $field_merge_tags );
	}

	/**
	 * Add custom Merge Tags to Merge Tag options, if custom Merge Tags exist
	 *
	 * Should be overridden if there's more than one Merge Tag to add or if the Merge Tag isn't {_custom_merge_tag}
	 *
	 * @since 1.16
	 *
	 * @param array $form GF Form array
	 * @param GF_Field[] $fields Array of fields in the form
	 *
	 * @return array Merge tag array with `label` and `tag` keys based on class `label` and `_custom_merge_tag` variables
	 */
	protected function custom_merge_tags( $form = array(), $fields = array() ) {

		// Use variables to make it unnecessary for other fields to override
		$merge_tags = array(
			array(
				'label' => $this->label,
				'tag' => '{' . $this->_custom_merge_tag . '}',
			),
		);

		return $merge_tags;
	}

	/**
	 * Use field settings to modify whether a field is sortable
	 *
	 * @see GravityView_frontend::is_field_sortable
	 * @since 1.15.3
	 *
	 * @param array $not_sortable Existing field types that aren't sortable
	 *
	 * @return array
	 */
	public function _filter_sortable_fields( $not_sortable ) {

		if( ! $this->is_sortable ) {
			$not_sortable[] = $this->name;
		}

		return $not_sortable;
	}

	private function field_support_options() {
		$options = array(
			'link_to_post' => array(
				'type' => 'checkbox',
				'label' => __( 'Link to the post', 'gravityview' ),
				'desc' => __( 'Link to the post created by the entry.', 'gravityview' ),
				'value' => false,
			),
			'link_to_term' => array(
				'type' => 'checkbox',
				'label' => __( 'Link to the category or tag', 'gravityview' ),
				'desc' => __( 'Link to the current category or tag. "Link to single entry" must be unchecked.', 'gravityview' ),
				'value' => false,
			),
			'dynamic_data' => array(
				'type' => 'checkbox',
				'label' => __( 'Use the live post data', 'gravityview' ),
				'desc' => __( 'Instead of using the entry data, instead use the current post data.', 'gravityview' ),
				'value' => true,
			),
			'date_display' => array(
				'type' => 'text',
				'label' => __( 'Override Date Format', 'gravityview' ),
				'desc' => sprintf( __( 'Define how the date is displayed (using %sthe PHP date format%s)', 'gravityview'), '<a href="https://codex.wordpress.org/Formatting_Date_and_Time">', '</a>' ),
				/**
				 * @filter `gravityview_date_format` Override the date format with a [PHP date format](https://codex.wordpress.org/Formatting_Date_and_Time)
				 * @param[in,out] null|string $date_format Date Format (default: null)
				 */
				'value' => apply_filters( 'gravityview_date_format', null )
			),
			'new_window' => array(
				'type' => 'checkbox',
				'label' => __( 'Open link in a new tab or window?', 'gravityview' ),
				'value' => false,
			),
		);

		/**
		 * @filter `gravityview_field_support_options` Modify the settings that a field supports
		 * @param array $options Options multidimensional array with each key being the input name, with each array setting having `type`, `label`, `desc` and `value` (default values) keys
		 */
		return apply_filters( 'gravityview_field_support_options', $options );
	}

	function add_field_support( $key = '', &$field_options ) {

		$options = $this->field_support_options();

		if( isset( $options[ $key ] ) ) {
			$field_options[ $key ] = $options[ $key ];
		}

		return $field_options;
	}

	/**
	 * Tap in here to modify field options.
	 *
	 * Here's an example:
	 *
	 * <pre>
	 * $field_options['name_display'] = array(
	 * 	'type' => 'select',
	 * 	'label' => __( 'User Format', 'gravityview' ),
	 * 	'desc' => __( 'How should the User information be displayed?', 'gravityview'),
	 * 	'choices' => array(
	 * 		array(
	 *		 	'value' => 'display_name',
	 *		  	'label' => __('Display Name (Example: "Ellen Ripley")', 'gravityview'),
	 *		),
	 *  	array(
	 *			'value' => 'user_login',
	 *			'label' => __('Username (Example: "nostromo")', 'gravityview')
	 *		),
	 * 	 'value' => 'display_name'
	 * );
	 * </pre>
	 *
	 * @param  array      $field_options [description]
	 * @param  string      $template_id   [description]
	 * @param  string      $field_id      [description]
	 * @param  string      $context       [description]
	 * @param  string      $input_type    [description]
	 * @return array                     [description]
	 */
	public function field_options( $field_options, $template_id, $field_id, $context ) {

		/*$this->_field_options = $field_options;
		$this->_field_id = $field_id;*/

		return $field_options;
	}


	public function get_options( $field_id, $template_id, $context ) {

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
						'options' => self::get_cap_choices( $this->name, $field_id, $template_id, $context ),
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

		/**
		 * @filter `gravityview_template_{$field_type}_options` Filter the field options by field type. Filter names: `gravityview_template_field_options` and `gravityview_template_widget_options`
		 * @param[in,out] array    Array of field options with `label`, `value`, `type`, `default` keys
		 * @param[in]  string      $template_id Table slug
		 * @param[in]  float       $field_id    GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
		 * @param[in]  string      $context     What context are we in? Example: `single` or `directory`
		 * @param[in]  string      $input_type  (textarea, list, select, etc.)
		 */
		$field_options = apply_filters( 'gravityview_template_field_options', $field_options, $template_id, $field_id, $context, $this->name );

		/**
		 * @filter `gravityview_template_{$input_type}_options` Filter the field options by input type (`$input_type` examples: `textarea`, `list`, `select`, etc.)
		 * @param[in,out] array    Array of field options with `label`, `value`, `type`, `default` keys
		 * @param[in]  string      $template_id Table slug
		 * @param[in]  float       $field_id    GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
		 * @param[in]  string      $context     What context are we in? Example: `single` or `directory`
		 * @param[in]  string      $input_type  (textarea, list, select, etc.)
		 */
		$field_options = apply_filters( "gravityview_template_{$this->name}_options", $field_options, $template_id, $field_id, $context, $this->name );

		return $this->field_options( $field_options, $template_id, $field_id, $context );

	}

	/**
	 * Get capabilities options for GravityView
	 *
	 * Parameters are only to pass to the filter.
	 *
	 * @param  string $name  		Optional. (textarea, list, select, etc.)
	 * @param  string $field_id    	Optional. GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
	 * @param  string $template_id 	Optional. Template slug
	 * @param  string $context     	Optional. What context are we in? Example: `single` or `directory`
	 * @return array Associative array, with the key being the capability and the value being the label shown.
	 */
	static public function get_cap_choices( $name = '', $field_id = '', $template_id = '', $context = '' ) {

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
		$select_cap_choices = apply_filters( 'gravityview_field_visibility_caps', $select_cap_choices, $template_id, $field_id, $context, $name );

		return $select_cap_choices;
	}

}
