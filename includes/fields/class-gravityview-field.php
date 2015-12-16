<?php

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
	 * @internal Not yet implemented
	 * @type boolean Is field content number-based?
	 * @since 1.15.2
	 */
	var $is_numeric;

	/**
	 * @var null|string The key used to search and sort entry meta in Gravity Forms. Used if the field stores data as custom entry meta.
	 * @see https://www.gravityhelp.com/documentation/article/gform_entry_meta/
	 * @since TODO
	 */
	var $entry_meta_key = null;

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

	function __construct() {

		// Modify the field options based on the name of the field type
		add_filter( sprintf( 'gravityview_template_%s_options', $this->name ), array( &$this, 'field_options' ), 10, 5 );

		add_filter( 'gravityview/sortable/field_blacklist', array( $this, '_filter_sortable_fields' ), 1 );

		if( ! empty( $this->entry_meta_key ) ) {
			add_filter( 'gform_entry_meta', array( $this, 'add_entry_meta' ) );
		}
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

	/**
	 * Use field settings to modify whether a field is sortable
	 *
	 * @see GravityView_frontend::is_field_sortable
	 * @since TODO
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
