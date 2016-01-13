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
	var $name;

	/**
	 * @internal Not yet implemented
	 * @since 1.15.2
	 * @type string The description of the field in the field picker
	 */
	var $description;

	/**
	 * @since 1.15.2
	 * @type string The label of the field in the field picker
	 */
	var $label;

	/**
	 * `standard`, `advanced`, `post`, `pricing`, `meta`, `gravityview`
	 * @internal Not yet implemented
	 * @since 1.15.2
	 * @type string The group belongs to this field in the field picker
	 */
	var $group;

	/**
	 * @internal Not yet implemented
	 * @type boolean Can the field be searched?
	 * @since 1.15.2
	 */
	var $is_searchable;

	/**
	 * @internal Not yet implemented
	 * @type array $search_operators The type of search operators available for this field
	 * @since 1.15.2
	 */
	var $search_operators;

	/**
	 * @type boolean Can the field be sorted in search?
	 * @since 1.15.2
	 */
	var $is_sortable = true;

	/**
	 * @type boolean Is field content number-based?
	 * @since 1.15.2
	 */
	var $is_numeric;

	/**
	 * @internal Not yet implemented
	 * @todo implement supports_context() method
	 * The contexts in which a field is available. Some fields aren't editable, for example.
	 * - `singular` is an alias for both `single` and `edit`
	 * - `multiple` is an alias for `directory` (backward compatibility)
	 * @type array
	 * @since 1.15.2
	 */
	var $contexts = array( 'single', 'multiple', 'edit', 'export' );

	/**
	 * @internal Not yet implemented
	 * @since 1.15.2
	 * @type string The name of a corresponding Gravity Forms GF_Field class, if exists
	 */
	protected $_gf_field_class_name;

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

	function __construct() {

		/**
		 * If this is a Gravity Forms field, use their labels. Spare our translation team!
		 */
		if( ! empty( $this->_gf_field_class_name ) && class_exists( $this->_gf_field_class_name ) ) {
			/** @var GF_Field $GF_Field */
			$GF_Field = new $this->_gf_field_class_name;
			$this->label = $GF_Field->get_form_editor_field_title();
		}

		// Modify the field options based on the name of the field type
		add_filter( sprintf( 'gravityview_template_%s_options', $this->name ), array( &$this, 'field_options' ), 10, 5 );

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
				$text = str_replace( $full_tag, $entry[ $tag ], $text );
			} else {
				$text = '';
			}
		}

		return $text;
	}

	/**
	 * Add custom merge tags to merge tag options. DO NOT OVERRIDE.
	 *
	 * @internal Not to be overridden by fields
	 *
	 * @since 1.8.4
	 *
	 * @param array $existing_merge_tags
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
	 * @param  [type]      $field_options [description]
	 * @param  [type]      $template_id   [description]
	 * @param  [type]      $field_id      [description]
	 * @param  [type]      $context       [description]
	 * @param  [type]      $input_type    [description]
	 * @return [type]                     [description]
	 */
	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		$this->_field_options = $field_options;
		$this->_field_id = $field_id;

		return $field_options;
	}

}
