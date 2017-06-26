<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The View Settings class.
 */
class View_Settings extends Settings {

	/**
	 * Retrieve the default View settings.
	 *
	 * @param bool $detailed Whether to return detailed setting meta information or just the value.
	 * @param string $group Retrieve settings of a particular group.
	 *
	 * @api
	 * @since future
	 *
	 * @return array The default settings along with their values.
	 *      @param[out] string $label Setting label shown in admin
	 *      @param[out] string $type Gravity Forms field type
	 *      @param[out] string $group The field group the setting is associated with. Default: "default"
	 *      @param[out] mixed  $value The default value for the setting
	 *      @param[out] string $tooltip Tooltip displayed for the setting
	 *      @param[out] boolean $show_in_shortcode Whether to show the setting in the shortcode configuration modal
	 *      @param[out] array  $options Array of values to use when generating select, multiselect, radio, or checkboxes fields
	 *      @param[out] boolean $full_width True: Display the input and label together when rendering. False: Display label and input in separate columns when rendering.
	 */
	public static function defaults( $detailed = false, $group = null ) {
		/**
		 * @filter `gravityview_default_args` Modify the default settings for new Views
		 * @param[in,out] array $default_settings Array of default settings.
		 * @deprecated
		 * @see filter `gravityview/view/settings/defaults`
		 */
		$default_settings = apply_filters( 'gravityview_default_args', array(
			'id' => array(
				'label' => __('View ID', 'gravityview'),
				'type' => 'number',
				'group'	=> 'default',
				'value' => NULL,
				'tooltip' => NULL,
				'show_in_shortcode' => false,
			),
			'page_size' => array(
				'label' 	=> __('Number of entries per page', 'gravityview'),
				'type' => 'number',
				'class'	=> 'small-text',
				'group'	=> 'default',
				'value' => 25,
				'show_in_shortcode' => true,
			),
			'offset' => array(
				'label' 	=> __('Offset entries starting from', 'gravityview'),
				'type' => 'number',
				'class'	=> 'small-text',
				'group'	=> 'default',
				'value' => 0,
				'show_in_shortcode' => true,
			),
			'lightbox' => array(
				'label' => __( 'Enable lightbox for images', 'gravityview' ),
				'type' => 'checkbox',
				'group'	=> 'default',
				'value' => 1,
				'tooltip' => NULL,
				'show_in_shortcode' => true,
			),
			'show_only_approved' => array(
				'label' => __( 'Show only approved entries', 'gravityview' ),
				'type' => 'checkbox',
				'group'	=> 'default',
				'value' => 0,
				'show_in_shortcode' => true,
			),
			'admin_show_all_statuses' => array(
				'label' => __( 'Show all entries to administrators', 'gravityview' ),
				'desc'	=> __('Administrators will be able to see entries with any approval status.', 'gravityview'),
				'tooltip' => __('Logged-out visitors and non-administrators will only see approved entries, while administrators will see entries with all statuses. This makes it easier for administrators to moderate entries from a View.', 'gravityview'),
				'requires' => 'show_only_approved',
				'type' => 'checkbox',
				'group'	=> 'default',
				'value' => 0,
				'show_in_shortcode' => false,
			),
			'hide_until_searched' => array(
				'label' => __( 'Hide View data until search is performed', 'gravityview' ),
				'type' => 'checkbox',
				'group'	=> 'default',
				'tooltip' => __( 'When enabled it will only show any View entries after a search is performed.', 'gravityview' ),
				'value' => 0,
				'show_in_shortcode' => false,
			),
			'hide_empty' => array(
				'label' 	=> __( 'Hide empty fields', 'gravityview' ),
				'group'	=> 'default',
				'type'	=> 'checkbox',
				'value' => 1,
				'show_in_shortcode' => false,
			),
			'user_edit' => array(
				'label'	=> __( 'Allow User Edit', 'gravityview' ),
				'group'	=> 'default',
				'desc'	=> __('Allow logged-in users to edit entries they created.', 'gravityview'),
				'value'	=> 0,
				'tooltip' => __('Display "Edit Entry" fields to non-administrator users if they created the entry. Edit Entry fields will always be displayed to site administrators.', 'gravityview'),
				'type'	=> 'checkbox',
				'show_in_shortcode' => true,
			),
			'user_delete' => array(
				'label'	=> __( 'Allow User Delete', 'gravityview' ),
				'group'	=> 'default',
				'desc'	=> __('Allow logged-in users to delete entries they created.', 'gravityview'),
				'value'	=> 0,
				'tooltip' => __('Display "Delete Entry" fields to non-administrator users if they created the entry. Delete Entry fields will always be displayed to site administrators.', 'gravityview'),
				'type'	=> 'checkbox',
				'show_in_shortcode' => true,
			),
			'sort_field' => array(
				'label'	=> __('Sort by field', 'gravityview'),
				'type' => 'select',
				'value' => '',
				'group'	=> 'sort',
				'options' => array(
					'' => __( 'Default', 'gravityview'),
					'date_created' => __( 'Date Created', 'gravityview'),
				),
				'show_in_shortcode' => true,
			),
			'sort_direction' => array(
				'label' 	=> __('Sort direction', 'gravityview'),
				'type' => 'select',
				'value' => 'ASC',
				'group'	=> 'sort',
				'options' => array(
					'ASC' => __('ASC', 'gravityview'),
					'DESC' => __('DESC', 'gravityview'),
					//'RAND' => __('Random', 'gravityview'),
				),
				'show_in_shortcode' => true,
			),
			'sort_columns' => array(
				'label' 	=> __( 'Enable sorting by column', 'gravityview' ),
				'left_label' => __( 'Column Sorting', 'gravityview' ),
				'type' => 'checkbox',
				'value' => false,
				'group'	=> 'sort',
				'tooltip' => NULL,
				'show_in_shortcode' => true,
				'show_in_template' => array( 'default_table', 'preset_business_data', 'preset_issue_tracker', 'preset_resume_board', 'preset_job_board' ),
			),
			'start_date' => array(
				'label' 	=> __('Filter by Start Date', 'gravityview'),
				'class'	=> 'gv-datepicker',
				'desc'	=> __('Show entries submitted after this date. Supports relative dates, such as "-1 week" or "-1 month".', 'gravityview' ),
				'type' => 'text',
				'value' => '',
				'group'	=> 'filter',
				'show_in_shortcode' => true,
			),
			'end_date' => array(
				'label' 	=> __('Filter by End Date', 'gravityview'),
				'class'	=> 'gv-datepicker',
				'desc'	=> __('Show entries submitted before this date. Supports relative dates, such as "now" or "-3 days".', 'gravityview' ),
				'type' => 'text',
				'value' => '',
				'group'	=> 'filter',
				'show_in_shortcode' => true,
			),
			'class' => array(
				'label' 	=> __('CSS Class', 'gravityview'),
				'desc'	=> __('CSS class to add to the wrapping HTML container.', 'gravityview'),
				'group'	=> 'default',
				'type' => 'text',
				'value' => '',
				'show_in_shortcode' => false,
			),
			'search_value' => array(
				'label' 	=> __('Search Value', 'gravityview'),
				'desc'	=> __('Define a default search value for the View', 'gravityview'),
				'type' => 'text',
				'value' => '',
				'group'	=> 'filter',
				'show_in_shortcode' => false,
			),
			'search_field' => array(
				'label' 	=> __('Search Field', 'gravityview'),
				'desc'	=> __('If Search Value is set, you can define a specific field to search in. Otherwise, all fields will be searched.', 'gravityview'),
				'type' => 'number',
				'value' => '',
				'group'	=> 'filter',
				'show_in_shortcode' => false,
			),
			'single_title' => array(
				'label'	=> __('Single Entry Title', 'gravityview'),
				'type'	=> 'text',
				'desc'	=> __('When viewing a single entry, change the title of the page to this setting. Otherwise, the title will not change between the Multiple Entries and Single Entry views.', 'gravityview'),
				'group'	=> 'default',
				'value'	=> '',
				'show_in_shortcode' => false,
				'full_width' => true,
			),
			'back_link_label' => array(
				'label'	=> __('Back Link Label', 'gravityview'),
				'group'	=> 'default',
				'desc'	=> __('The text of the link that returns to the multiple entries view.', 'gravityview'),
				'type'	=> 'text',
				'value'	=> '',
				'show_in_shortcode' => false,
				'full_width' => true,
			),
			'embed_only' => array(
				'label'	=> __('Prevent Direct Access', 'gravityview'),
				'group'	=> 'default',
				'desc'	=> __('Only allow access to this View when embedded using the shortcode.', 'gravityview'),
				'type'	=> 'checkbox',
				'value'	=> '',
				'tooltip' => false,
				'show_in_shortcode' => false,
				'full_width' => true,
			),
			'post_id' => array(
				'type' => 'number',
				'value' => '',
				'show_in_shortcode' => false,
			),
		) );

		/**
		 * @filter `gravityview/view/defaults` Modify the default settings for new Views
		 * @param[in,out] array $default_settings Array of default settings.
		 */
		$default_settings = apply_filters( 'gravityview/view/settings/defaults', $default_settings );

		// By default, we only want the key => value pairing, not the whole array.
		if ( ! $detailed ) {
			$defaults = array();
			foreach( $default_settings as $key => $value ) {
				$defaults[ $key ] = $value['value'];
			}
			return $defaults;

		// But sometimes, we want all the details.
		} else {
			foreach ($default_settings as $key => $value) {

				// If the $group argument is set for the method,
				// ignore any settings that aren't in that group.
				if ( ! empty( $group ) && is_string( $group ) ) {
					if ( empty( $value['group'] ) || $value['group'] !== $group ) {
						unset( $default_settings[ $key ] );
					}
				}
			}
			return $default_settings;
		}
	}

	/**
	 * Mass update values from the allowed ones.
	 *
	 * @api
	 * @since future
	 *
	 * @param array An array of settings to update.
	 * @return void
	 */
	public function update( $settings ) {
		foreach ( $settings as $key => $value ) {
			$this->set( $key, $value );
		}
	}

	/**
	 * Turn to an $atts array as used around the old codebase.
	 *
	 * @internal
	 * @deprecated
	 *
	 * @return array
	 */
	public function as_atts() {
		$defaults = array_keys( self::defaults() );
		$_this = &$this;
		return array_combine( $defaults, array_map( function( $key ) use ( $_this ) {
			return $_this->get( $key );
		}, $defaults ) );
	}
}
