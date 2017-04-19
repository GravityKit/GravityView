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
	 * @var string The default search label used by the search widget, if not set
	 */
	public $default_search_label;

	/**
	 * `standard`, `advanced`, `post`, `pricing`, `meta`, `gravityview`
	 * @since 1.15.2
	 * @type string The group belongs to this field in the field picker
	 */
	public $group;

	/**
	 * @internal Not yet implemented
	 * @type boolean Can the field be searched?
	 * @since 1.15.2
	 */
	public $is_searchable = true;

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
	 * @var null|string The key used to search and sort entry meta in Gravity Forms. Used if the field stores data as custom entry meta.
	 * @see https://www.gravityhelp.com/documentation/article/gform_entry_meta/
	 * @since TODO
	 */
	public $entry_meta_key = null;

	/**
	 * @var string|array Optional. The callback function after entry meta is updated, only used if $entry_meta_key is set.
	 * @see https://www.gravityhelp.com/documentation/article/gform_entry_meta/
	 * @since TODO
	 */
	var $entry_meta_update_callback = null;

	/**
	 * @var bool Whether to show meta when set to true automatically adds the column to the entry list, without having to edit and add the column for display
	 * @since TODO
	 */
	var $entry_meta_is_default_column = false;

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
	 * @since 1.16.2.2 Changed access to public (previously, protected)
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
		add_filter( sprintf( 'gravityview_template_%s_options', $this->name ), array( &$this, 'field_options' ), 10, 5 );

		add_filter( 'gravityview/sortable/field_blacklist', array( $this, '_filter_sortable_fields' ), 1 );

		if( $this->entry_meta_key ) {
			add_filter( 'gform_entry_meta', array( $this, 'add_entry_meta' ) );
			add_filter( 'gravityview/common/sortable_fields', array( $this, 'add_sortable_field' ), 10, 2 );
		}

		if( $this->_custom_merge_tag ) {
			add_filter( 'gform_custom_merge_tags', array( $this, '_filter_gform_custom_merge_tags' ), 10, 4 );
			add_filter( 'gform_replace_merge_tags', array( $this, '_filter_gform_replace_merge_tags' ), 10, 7 );
		}

		if( 'meta' === $this->group || '' !== $this->default_search_label ) {
			add_filter( 'gravityview_search_field_label', array( $this, 'set_default_search_label' ), 10, 3 );
		}

		/**
		 * Auto-assign label from Gravity Forms label, if exists
		 * @since 1.20
		 */
		if( empty( $this->label ) && ! empty( $this->_gf_field_class_name ) && class_exists( $this->_gf_field_class_name ) ) {
			$this->label = ucfirst( GF_Fields::get( $this->name )->get_form_editor_field_title() );
		}

		GravityView_Fields::register( $this );
	}

	/**
	 * Add the field to the Filter & Sort available fields
	 *
	 * @since TODO
	 *
	 * @param array $fields Sub-set of GF form fields that are sortable
	 *
	 * @return array Modified $fields array to include approval status in the sorting dropdown
	 */
	public function add_sortable_field( $fields ) {

		$added_field = array(
			'label' => $this->label,
			'type'  => $this->name
		);

		$fields["{$this->entry_meta_key}"] = $added_field;

		return $fields;
	}

	/**
	 * Allow setting a default search label for search fields based on the field type
	 *
	 * Useful for entry meta "fields" that don't have Gravity Forms labels, like `created_by`
	 *
	 * @since 1.17.3
	 *
	 * @param string $label Existing label text, sanitized.
	 * @param array $gf_field Gravity Forms field array, as returned by `GFFormsModel::get_field()`
	 * @param array $field Field setting as sent by the GV configuration - has `field`, `input` (input type), and `label` keys
	 *
	 * @return string
	 */
	function set_default_search_label( $label = '', $gf_field = null, $field = array() ) {

		if( $this->name === $field['field'] && '' === $label ) {
			$label = esc_html( $this->default_search_label );
		}

		return $label;
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
	 * @see GFCommon::replace_variables()
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

	/**
	 * Add the custom entry meta key to make it searchable and sortable
	 *
	 * @see https://www.gravityhelp.com/documentation/article/gform_entry_meta/
	 *
	 * @param array $entry_meta Array of custom entry meta keys with associative arrays
	 *
	 * @return mixed
	 */
	function add_entry_meta( $entry_meta ) {

		if( ! isset( $entry_meta["{$this->entry_meta_key}"] ) ) {

			$added_meta = array(
				'label'             => $this->label,
				'is_numeric'        => $this->is_numeric,
				'is_default_column' => $this->entry_meta_is_default_column,
			);

			if ( $this->entry_meta_update_callback && is_callable( $this->entry_meta_update_callback ) ) {
				$added_meta['update_entry_meta_callback'] = $this->entry_meta_update_callback;
			}

			$entry_meta["{$this->entry_meta_key}"] = $added_meta;

		} else {
			do_action( 'gravityview_log_error', __METHOD__ . ' Entry meta already set: ' . $this->entry_meta_key, $entry_meta["{$this->entry_meta_key}"] );
		}

		return $entry_meta;
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
	public function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		$this->_field_options = $field_options;
		$this->_field_id = $field_id;

		return $field_options;
	}

	/**
	 * Check whether the `enableChoiceValue` flag is set for a GF field
	 *
	 * Gets the current form ID, gets the field at that ID, then checks for the enableChoiceValue value.
	 *
	 * @access protected
	 *
	 * @uses GFAPI::get_form
	 *
	 * @since 1.17
	 *
	 * @return bool True: Enable Choice Value is active for field; False: not active, or form invalid, or form not found.
	 */
	protected function is_choice_value_enabled() {

		// If "Add Field" button is processing, get the Form ID
		$connected_form = rgpost( 'form_id' );

		// Otherwise, get the Form ID from the Post page
		if( empty( $connected_form ) ) {
			$connected_form = gravityview_get_form_id( get_the_ID() );
		}

		if( empty( $connected_form ) ) {
			do_action( 'gravityview_log_error', sprintf( '%s: Form not found for form ID "%s"', __METHOD__, $connected_form ) );
			return false;
		}

		$form = GFAPI::get_form( $connected_form );

		if ( ! $form ) {
			do_action( 'gravityview_log_error', sprintf( '%s: Form not found for field ID of "%s", when checking for a form with ID of "%s"', __METHOD__, $this->_field_id, $connected_form ) );
			return false;
		}

		$field = gravityview_get_field( $form, $this->_field_id );

		return ! empty( $field->enableChoiceValue );
	}

}
