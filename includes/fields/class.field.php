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

	function __construct() {

		// Modify the field options based on the name of the field type
		add_filter( sprintf( 'gravityview_template_%s_options', $this->name ), array( &$this, 'field_options' ) );
	}

	/**
	 * Tap in here to modify field options.
	 *
	 * Here's an example:
	 *
	 * <code>
	 * $field_options['name_display'] = array(
	 * 	'type' => 'select',
	 * 	'label' => __( 'User Format', 'gravity-view' ),
	 * 	'desc' => __( 'How should the User information be displayed?', 'gravity-view'),
	 * 	'choices' => array(
	 * 		array(
	 *		 	'value' => 'display_name',
	 *		  	'label' => __('Display Name (Example: "Ellen Ripley")', 'gravity-view'),
	 *		),
	 *  	array(
	 *			'value' => 'user_login',
	 *			'label' => __('Username (Example: "nostromo")', 'gravity-view')
	 *		),
	 * 	 'default' => 'display_name'
	 * );
	 * </code>
	 *
	 * @filter default text
	 * @action default text
	 * @param  [type]      $field_options [description]
	 * @param  [type]      $template_id   [description]
	 * @param  [type]      $field_id      [description]
	 * @param  [type]      $context       [description]
	 * @param  [type]      $input_type    [description]
	 * @return [type]                     [description]
	 */
	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {
		return $field_options;
	}

}
