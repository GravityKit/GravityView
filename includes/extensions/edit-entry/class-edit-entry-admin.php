<?php
/**
 * GravityView Edit Entry - Admin logic
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}


class GravityView_Edit_Entry_Admin {

    protected $loader;

    function __construct( GravityView_Edit_Entry $loader ) {
        $this->loader = $loader;
    }

    function load() {

        if( !is_admin() ) {
            return;
        }

        // Add Edit Link as a default field, outside those set in the Gravity Form form
        add_filter( 'gravityview_entry_default_fields', array( $this, 'add_default_field' ), 10, 3 );

        // For the Edit Entry Link, you don't want visible to all users.
        add_filter( 'gravityview_field_visibility_caps', array( $this, 'modify_visibility_caps' ), 10, 5 );

        // Modify the field options based on the name of the field type
        add_filter( 'gravityview_template_edit_link_options', array( $this, 'edit_link_field_options' ), 10, 5 );

        // add tooltips
        add_filter( 'gravityview_tooltips', array( $this, 'tooltips') );

        // custom fields' options for zone EDIT
        add_filter( 'gravityview_template_field_options', array( $this, 'field_options' ), 10, 5 );
    }

    /**
     * Add Edit Link as a default field, outside those set in the Gravity Form form
     * @param array $entry_default_fields Existing fields
     * @param  string|array $form form_ID or form object
     * @param  string $zone   Either 'single', 'directory', 'header', 'footer'
     */
    function add_default_field( $entry_default_fields, $form = array(), $zone = '' ) {

        if( $zone !== 'edit' ) {

            $entry_default_fields['edit_link'] = array(
                'label' => __('Edit Entry', 'gravityview'),
                'type' => 'edit_link',
                'desc'	=> __('A link to edit the entry. Visible based on View settings.', 'gravityview'),
            );

        }

        return $entry_default_fields;
    }

    /**
     * Change wording for the Edit context to read Entry Creator
     *
     * @param  array 	   $visibility_caps        Array of capabilities to display in field dropdown.
     * @param  string      $field_type  Type of field options to render (`field` or `widget`)
     * @param  string      $template_id Table slug
     * @param  float       $field_id    GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
     * @param  string      $context     What context are we in? Example: `single` or `directory`
     * @param  string      $input_type  (textarea, list, select, etc.)
     * @return array                   Array of field options with `label`, `value`, `type`, `default` keys
     */
    function modify_visibility_caps( $visibility_caps = array(), $template_id = '', $field_id = '', $context = '', $input_type = '' ) {

        $caps = $visibility_caps;

        // If we're configuring fields in the edit context, we want a limited selection
        if( $context === 'edit' ) {

            // Remove other built-in caps.
            unset( $caps['publish_posts'], $caps['gravityforms_view_entries'], $caps['delete_others_posts'] );

            $caps['read'] = _x('Entry Creator','User capability', 'gravityview');
        }

        return $caps;
    }

    /**
     * Add "Edit Link Text" setting to the edit_link field settings
     * @param  [type] $field_options [description]
     * @param  [type] $template_id   [description]
     * @param  [type] $field_id      [description]
     * @param  [type] $context       [description]
     * @param  [type] $input_type    [description]
     * @return [type]                [description]
     */
    function edit_link_field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

        // Always a link, never a filter
        unset( $field_options['show_as_link'], $field_options['search_filter'] );

        // Edit Entry link should only appear to visitors capable of editing entries
        unset( $field_options['only_loggedin'], $field_options['only_loggedin_cap'] );

        $add_option['edit_link'] = array(
            'type' => 'text',
            'label' => __( 'Edit Link Text', 'gravityview' ),
            'desc' => NULL,
            'value' => __('Edit Entry', 'gravityview'),
            'merge_tags' => true,
        );

        return array_merge( $add_option, $field_options );
    }

    /**
     * Add tooltips
     * @param  array $tooltips Existing tooltips
     * @return array           Modified tooltips
     */
    function tooltips( $tooltips ) {

        $return = $tooltips;

        $return['allow_edit_cap'] = array(
            'title' => __('Limiting Edit Access', 'gravityview'),
            'value' => __('Change this setting if you don\'t want the user who created the entry to be able to edit this field.', 'gravityview'),
        );

        return $return;
    }

    /**
     * Manipulate the fields' options for the EDIT ENTRY screen
     * @param  [type] $field_options [description]
     * @param  [type] $template_id   [description]
     * @param  [type] $field_id      [description]
     * @param  [type] $context       [description]
     * @param  [type] $input_type    [description]
     * @return [type]                [description]
     */
    function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

        // We only want to modify the settings for the edit context
        if( 'edit' !== $context ) {

            /**
             * @since 1.8.4
             */
            $field_options['new_window'] = array(
                'type' => 'checkbox',
                'label' => __( 'Open link in a new tab or window?', 'gravityview' ),
                'value' => false,
            );

            return $field_options;
        }

        //  Entry field is only for logged in users
        unset( $field_options['only_loggedin'], $field_options['only_loggedin_cap'] );

        $add_options = array(
            'allow_edit_cap' => array(
                'type' => 'select',
                'label' => __( 'Make field editable to:', 'gravityview' ),
                'choices' => GravityView_Render_Settings::get_cap_choices( $template_id, $field_id, $context, $input_type ),
                'tooltip' => 'allow_edit_cap',
                'class' => 'widefat',
                'value' => 'read', // Default: entry creator
            ),
        );

        return array_merge( $field_options, $add_options );
    }


} // end class