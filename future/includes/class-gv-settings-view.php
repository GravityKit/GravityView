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
	 * Retrieve an instance of the settings with default values.
	 * @param bool $detailed Whether to return detailed setting meta information or just the value.
	 *
	 * @api
	 * @since 2.0
	 *
	 * @return \GV\View_Settings
	 */
	public static function with_defaults( $detailed = false ) {
		$settings = new self();
		$settings->update( self::defaults( $detailed ) );
		return $settings;
	}

	/**
	 * Retrieve the default View settings.
	 *
	 * @param bool $detailed Whether to return detailed setting meta information or just the value.
	 * @param string $group Retrieve settings of a particular group.
	 *
	 * @api
	 * @since 2.0
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

		$default_settings = array_merge(
			array(
				'id' => array(
					'label'             => __( 'View ID', 'gravityview' ),
					'type'              => 'number',
					'group'             => 'default',
					'value'             => null,
					'tooltip'           => null,
					'show_in_shortcode' => false,
				),
				'page_size' => array(
					'label'             => __( 'Number of entries per page', 'gravityview' ),
					'type'              => 'number',
					'class'             => 'small-text',
					'group'             => 'default',
					'value'             => 25,
					'show_in_shortcode' => true,
				),
				'offset' => array(
					'label'             => __( 'Offset entries starting from', 'gravityview' ),
					'type'              => 'number',
					'class'             => 'small-text',
					'group'             => 'default',
					'value'             => 0,
					'show_in_shortcode' => true,
				),
				'lightbox' => array(
					'label'             => __( 'Enable lightbox for images', 'gravityview' ),
					'type'              => 'checkbox',
					'group'             => 'default',
					'value'             => 1,
					'tooltip'           => __( 'If enabled, images will open full-size in a "lightbox". A lightbox displays images and videos by filling the screen and dimming out the rest of the web page.', 'gravityview' ),
					'show_in_shortcode' => true,
					'article'           => array(
						'id' => '5e9a1f8904286364bc98931f',
						'url' => 'https://docs.gravityview.co/article/705-view-settings-enable-lightbox-for-images',
					),
				),
				'show_only_approved'    => array(
					'label'             => __( 'Show only approved entries', 'gravityview' ),
					'type'              => 'checkbox',
					'group'             => 'default',
					'desc'              => __( 'By default, only approved entries are displayed in a View. When enabled, this setting prevents unapproved or disapproved entries from appearing in results. If disabled, entries with all approval statuses will be visible, including disapproved entries.', 'gravityview' ),
					'tooltip'           => false,
					'value'             => 1,
					'show_in_shortcode' => true,
					'article'           => array(
						'id' => '5bad1a33042863158cc6d396',
						'url' => 'https://docs.gravityview.co/article/490-entry-approval-gravity-forms',
					),
				),
				'no_results_text'       => array(
					'label'             => __( '"No Results" Text', 'gravityview' ),
					'type'              => 'text',
					'group'             => 'default',
					'desc'              => '',
					'tooltip'           => false,
					'value'             => '',
					'placeholder'       => __( 'No entries match your request.', 'gravityview' ),
					'show_in_shortcode' => true,
					'class'             => 'widefat',
					'full_width'        => true,
				),
				'no_search_results_text' => array(
					'label'             => __( '"No Search Results" Text', 'gravityview' ),
					'type'              => 'text',
					'group'             => 'default',
					'desc'              => '',
					'tooltip'           => false,
					'value'             => '',
					'placeholder'       => __( 'This search returned no results.', 'gravityview' ),
					'show_in_shortcode' => true,
					'class'             => 'widefat',
					'full_width'        => true,
				),
				'admin_show_all_statuses' => array(
					'label'             => __( 'Show all entries to administrators', 'gravityview' ),
					'desc'              => __( 'Administrators will be able to see entries with any approval status.', 'gravityview' ),
					'tooltip'           => __( 'Logged-out visitors and non-administrators will only see approved entries, while administrators will see entries with all statuses. This makes it easier for administrators to moderate entries from a View.', 'gravityview' ),
					'requires'          => 'show_only_approved',
					'type'              => 'checkbox',
					'group'             => 'default',
					'value'             => 0,
					'show_in_shortcode' => false,
				),
				'hide_until_searched' => array(
					'label'             => __( 'Hide View data until search is performed', 'gravityview' ),
					'type'              => 'checkbox',
					'group'             => 'default',
					'tooltip'           => __( 'When enabled it will only show any View entries after a search is performed.', 'gravityview' ),
					'value'             => 0,
					'show_in_shortcode' => false,
					'article'           => array(
						'id' => '5c772fa02c7d3a0cb9320a84',
						'url' => 'https://docs.gravityview.co/article/536-how-to-hide-results-and-only-display-them-if-a-search-is-performed',
					),
				),
				'hide_empty' => array(
					'label'             => __( 'Hide empty fields', 'gravityview' ),
					'group'             => 'default',
					'type'              => 'checkbox',
					'desc'              => __( 'When enabled, empty fields will be not be displayed. If disabled, fields and their labels will be displayed with no content.', 'gravityview' ),
					'value'             => 1,
					'tooltip'           => false,
					'show_in_shortcode' => false,
				),
				'hide_empty_single' => array(
					'label'             => __( 'Hide empty fields', 'gravityview' ),
					'group'             => 'default',
					'type'              => 'checkbox',
					'desc'              => __( 'When enabled, empty fields will be not be displayed. If disabled, fields and their labels will be displayed with no content.', 'gravityview' ),
					'value'             => 1,
					'tooltip'           => false,
					'show_in_shortcode' => false,
				),
				'edit_feeds' => array(
					'label'             => __( 'Feeds', 'gravityview' ),
					'group'             => 'default',
					'type'              => 'checkbox',
					'value'             => array(),
					'show_in_shortcode' => false,
				),
				'user_edit' => array(
					'label'             => __( 'Allow User Edit', 'gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'Allow logged-in users to edit entries they created.', 'gravityview' ) . ' ' . sprintf( __( 'Administrators are able to %s regardless of this setting.', 'gravityview' ), _x( 'edit entries', 'an action that admins can perform', 'gravityview' ) ),
					'value'             => 0,
					'tooltip'           => __( 'Display "Edit Entry" fields to non-administrator users if they created the entry. Edit Entry fields will always be displayed to site administrators.', 'gravityview' ),
					'type'              => 'checkbox',
					'show_in_shortcode' => true,
					'article'           => array(
						'id' => '54c67bbbe4b07997ea3f3f6b',
						'url' => 'https://docs.gravityview.co/article/77-user-edit-allow-users-to-edit-their-own-entries',
					),
				),
				'unapprove_edit' => array(
					'label'             => __( 'Unapprove Entries After Edit', 'gravityview' ),
					'group'             => 'default',
					'requires'          => 'user_edit',
					'desc'              => __( 'When an entry is edited by a non-administrator, reset the approval status to "Unapproved".', 'gravityview' ),
					'tooltip'           => __( 'If the "Show only approved entries" setting is enabled, the entry will need to be re-approved by an administrator before it is shown in the View.', 'gravityview' ),
					'value'             => 0,
					'type'              => 'checkbox',
					'show_in_shortcode' => true,
					'article'           => array(
						'id' => '5ddd81d504286364bc923957',
						'url' => 'https://docs.gravityview.co/article/657-unapproving-edited-entries-automatically',
					),
				),
				'user_delete' => array(
					'label'             => __( 'Allow User Delete', 'gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'Allow logged-in users to delete entries they created.', 'gravityview' ) . ' ' . sprintf( __( 'Administrators are able to %s regardless of this setting.', 'gravityview' ), _x( 'delete entries', 'an action that admins can perform', 'gravityview' ) ),
					'value'             => 0,
					'tooltip'           => __( 'Display "Delete Entry" fields to non-administrator users if they created the entry. Delete Entry fields will always be displayed to site administrators.', 'gravityview' ),
					'type'              => 'checkbox',
					'show_in_shortcode' => true,
					'article'           => array(
						'id' => '54c67bb9e4b0512429885512',
						'url' => 'https://docs.gravityview.co/article/66-configuring-delete-entry',
					),
				),
				'user_duplicate' => array(
					'label'             => __( 'Allow User Duplicate', 'gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'Allow logged-in users to duplicate entries they created.', 'gravityview' ) . ' ' . sprintf( __( 'Administrators are able to %s regardless of this setting.', 'gravityview' ), _x( 'duplicate entries', 'an action that admins can perform', 'gravityview' ) ),
					'value'             => 0,
					'tooltip'           => __( 'Display "Duplicate Entry" fields to non-administrator users if they created the entry. Duplicate Entry fields will always be displayed to site administrators.', 'gravityview' ),
					'article'           => array(
						'id' => '5df11eb704286364bc92bf36',
						'url' => 'https://docs.gravityview.co/article/66-configuring-delete-entry',
					),
					'type'              => 'checkbox',
					'show_in_shortcode' => true,
				),
				'sort_field' => array(
					'label'             => __( 'Sort by field', 'gravityview' ),
					'type'              => 'select',
					'desc'              => __( 'By default, entries are sorted by Entry ID.', 'gravityview' ),
					'value'             => '',
					'group'             => 'sort',
					'options'           => array(
						''             => __( 'Default', 'gravityview' ),
						'date_created' => __( 'Date Created', 'gravityview' ),
					),
					'show_in_shortcode' => true,
					'article'           => array(
						'id' => '54c67bbbe4b051242988551a',
						'url' => 'https://docs.gravityview.co/article/74-sorting-results-by-field-value',
					),
				),
				'sort_direction' => array(
					'label'             => __( 'Sort direction', 'gravityview' ),
					'type'              => 'select',
					'value'             => 'ASC',
					'group'             => 'sort',
					'options'           => array(
						'ASC'  => __( 'ASC', 'gravityview' ),
						'DESC' => __( 'DESC', 'gravityview' ),
						'RAND' => __( 'Random', 'gravityview' ),
					),
					'show_in_shortcode' => true,
					'article'           => array(
						'id' => '5c9d338a2c7d3a1544617f9b',
						'url' => 'https://docs.gravityview.co/article/570-sorting-by-multiple-columns',
					),
				),
				'sort_field_2' => array(
					'label'             => __( 'Sort by secondary field', 'gravityview' ),
					'type'              => 'select',
					'value'             => '',
					'group'             => 'sort',
					'options'           => array(
						''             => __( 'Default', 'gravityview' ),
						'date_created' => __( 'Date Created', 'gravityview' ),
					),
					'requires_not'          => 'sort_direction][=RAND', // ][ is for toggleRequired, so it ends in []
					'show_in_shortcode' => true,
					'article'           => array(
						'id' => '5c9d338a2c7d3a1544617f9b',
						'url' => 'https://docs.gravityview.co/article/570-sorting-by-multiple-columns',
					),
				),
				'sort_direction_2' => array(
					'label'             => __( 'Secondary sort direction', 'gravityview' ),
					'type'              => 'select',
					'value'             => 'ASC',
					'group'             => 'sort',
					'options'           => array(
						'ASC'  => __( 'ASC', 'gravityview' ),
						'DESC' => __( 'DESC', 'gravityview' ),
					),
					'requires_not'      => 'sort_direction][=RAND', // ][ is for toggleRequired, so it ends in []
					'show_in_shortcode' => true,
					'article'           => array(
						'id' => '5c9d338a2c7d3a1544617f9b',
						'url' => 'https://docs.gravityview.co/article/570-sorting-by-multiple-columns',
					),
				),
				'sort_columns' => array(
					'label'             => __( 'Enable sorting by column', 'gravityview' ),
					'left_label'        => __( 'Column Sorting', 'gravityview' ),
					'type'              => 'checkbox',
					'value'             => false,
					'group'             => 'sort',
					'tooltip'           => null,
					'show_in_shortcode' => true,
					'show_in_template'  => array( 'default_table', 'preset_business_data', 'preset_issue_tracker', 'preset_resume_board', 'preset_job_board' ),
					'article'           => array(
						'id' => '54ee1246e4b034c37ea91c11',
						'url' => 'https://docs.gravityview.co/article/230-enabling-the-table-column-sorting-feature',
					),
				),
				'start_date' => array(
					'label'             => __( 'Filter by Start Date', 'gravityview' ),
					'class'             => 'gv-datepicker',
					'desc'              => __( 'Show entries submitted after this date. Supports relative dates, such as "-1 week" or "-1 month".', 'gravityview' ),
					'type'              => 'text',
					'value'             => '',
					'group'             => 'filter',
					'show_in_shortcode' => true,
					'article'           => array(
						'id' => '54c67bbbe4b0512429885520',
						'url' => 'https://docs.gravityview.co/article/79-using-relative-start-dates-and-end-dates',
					),
				),
				'end_date' => array(
					'label'             => __( 'Filter by End Date', 'gravityview' ),
					'class'             => 'gv-datepicker',
					'desc'              => __( 'Show entries submitted before this date. Supports relative dates, such as "now" or "-3 days".', 'gravityview' ),
					'type'              => 'text',
					'value'             => '',
					'group'             => 'filter',
					'show_in_shortcode' => true,
					'article'           => array(
						'id' => '54c67bbbe4b0512429885520',
						'url' => 'https://docs.gravityview.co/article/79-using-relative-start-dates-and-end-dates',
					),
				),
				'class' => array(
					'label'             => __( 'CSS Class', 'gravityview' ),
					'desc'              => __( 'CSS class to add to the wrapping HTML container.', 'gravityview' ),
					'group'             => 'default',
					'type'              => 'text',
					'value'             => '',
					'show_in_shortcode' => false,
				),
				'search_value' => array(
					'label'             => __( 'Search Value', 'gravityview' ),
					'desc'              => __( 'Define a default search value for the View', 'gravityview' ),
					'type'              => 'text',
					'value'             => '',
					'group'             => 'filter',
					'show_in_shortcode' => false,
				),
				'search_field' => array(
					'label'             => __( 'Search Field', 'gravityview' ),
					'desc'              => __( 'If Search Value is set, you can define a specific field to search in. Otherwise, all fields will be searched.', 'gravityview' ),
					'type'              => 'text',
					'value'             => '',
					'group'             => 'filter',
					'show_in_shortcode' => false,
				),
				'search_operator' => array(
					'label'             => __( 'Search Operator', 'gravityview' ),
					'type'              => 'operator',
					'value'             => 'contains',
					'group'             => 'filter',
					'show_in_shortcode' => false,
				),
				'single_title' => array(
					'label'             => __( 'Single Entry Title', 'gravityview' ),
					'type'              => 'text',
					'desc'              => __( 'When viewing a single entry, change the title of the page to this setting. Otherwise, the title will not change between the Multiple Entries and Single Entry views.', 'gravityview' ),
					'group'             => 'default',
					'value'             => '',
					'show_in_shortcode' => false,
					'full_width'        => true,
					'article'           => array(
						'id' => '54c67bcee4b07997ea3f3f9a',
						'url' => 'https://docs.gravityview.co/article/121-changing-the-single-entry-page-title',
					),
				),
				'back_link_label' => array(
					'label'             => __( 'Back Link Label', 'gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'The text of the link that returns to the multiple entries view.', 'gravityview' ),
					'type'              => 'text',
					'value'             => '',
					'placeholder'       => __( '&larr; Go back', 'gravityview' ),
					'class'             => 'widefat',
					'merge_tags'        => 'force',
					'show_in_shortcode' => false,
					'full_width'        => true,
				),
				'edit_redirect' => array(
					'label'             => __( 'Redirect After Editing', 'gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'The page to redirect to after editing an entry.', 'gravityview' ),
					'type'              => 'select',
					'value'             => '',
					'options'           => array(
						'' => __( 'Stay on Edit Entry', 'gravityview' ),
						'0'  => __( 'Redirect to Single Entry', 'gravityview' ),
						'1' => __( 'Redirect to Multiple Entries', 'gravityview' ),
						'2' => __( 'Redirect to URL', 'gravityview' ),
					),
					'article'           => array(
						'id' => '5e9a3e0c2c7d3a7e9aeb2efb',
						'url' => 'https://docs.gravityview.co/article/707-view-settings-redirect-after-editing',
					),
				),
				'edit_return_context' => array(
					'label'             => __( 'Editing Returns To&hellip;', 'gravityview' ),
					'type'              => 'radio',
					'desc'              => __( 'After editing an entry or clicking Cancel, where should the user be sent?', 'gravityview' ),
					'group'             => 'default',
					'value'             => 'single',
					'options'           => array(
						'multiple' => __( 'Multiple Entries', 'gravityview' ),
						'single'   => __( 'Single Entry', 'gravityview' ),
						'custom'   => __( 'Other URL', 'gravityview' ),
					),
					'show_in_shortcode' => false,
					'full_width'        => true,
					'article'           => array(
						'id' => '5e9a3e0c2c7d3a7e9aeb2efb',
						'url' => 'https://docs.gravityview.co/article/707-view-settings-redirect-after-editing',
					),
				),
				'edit_redirect_url' => array(
					'label'             => __( 'Edit Entry Redirect URL', 'gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'After editing an entry, the user will be taken to this URL.', 'gravityview' ),
					'type'              => 'text',
					'class'             => 'code widefat',
					'value'             => '',
					'placeholder'       => 'https://www.example.com/landing-page/',
					'requires'          => 'edit_redirect=2',
					'merge_tags'        => 'force',
				),
				'action_label_update' => array(
					'label'             => __( 'Update Button Text', 'gravityview' ),
					'group'             => 'default',
					'desc'              => '',
					'type'              => 'text',
					'value'             => _x( 'Update', 'Button to update an entry the user is editing', 'gravityview' ),
					'merge_tags'        => 'force',
				),
				'action_label_cancel' => array(
					'label'             => __( 'Cancel Link Text', 'gravityview' ),
					'group'             => 'default',
					'desc'              => '',
					'type'              => 'text',
					'value'             => _x( 'Cancel', 'Shown when the user decides not to edit an entry', 'gravityview' ),
					'merge_tags'        => 'force',
				),
				'action_label_next' => array(
					'label'             => __( 'Next Page Button Text', 'gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'Only shown when multi-page forms are enabled.', 'gravityview' ),
					'type'              => 'text',
					'value'             => __( 'Next', 'Show the next page in a multi-page form', 'gravityview' ),
					'merge_tags'        => 'force',
				),
				'action_label_previous' => array(
					'label'             => __( 'Previous Page Button Text', 'gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'Only shown when multi-page forms are enabled.', 'gravityview' ),
					'type'              => 'text',
					'value'             => __( 'Previous', 'Show the previous page in a multi-page form', 'gravityview' ),
					'merge_tags'        => 'force',
				),
				'action_label_delete' => array(
					'label'             => __( 'Delete Link Text', 'gravityview' ),
					'group'             => 'default',
					'desc'              => '',
					'type'              => 'text',
					'value'             => __( 'Delete', 'Button label to delete an entry from the Edit Entry screen', 'gravityview' ),
					'merge_tags'        => 'force',
				),
				'edit_locking' => array(
					'label'             => __( 'Enable Edit Locking', 'gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'Prevent multiple users from editing the same entry at the same time.', 'gravityview' ),
					'type'              => 'checkbox',
					'full_width'        => true,
					'class'             => 'code widefat',
					'value'             => true,
					'article'           => array(
						'id' => '5e4449d72c7d3a7e9ae7a54c',
						'url' => 'https://docs.gravityview.co/article/676-entry-locking',
					),
				),
				'delete_redirect' => array(
					'label'             => __( 'Redirect After Deleting', 'gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'The page to redirect to after deleting an entry.', 'gravityview' ),
					'type'              => 'select',
					'value'             => '1',
					'options'           => array(
						\GravityView_Delete_Entry::REDIRECT_TO_MULTIPLE_ENTRIES_VALUE  => __( 'Redirect to Multiple Entries', 'gravityview' ),
						\GravityView_Delete_Entry::REDIRECT_TO_URL_VALUE  => __( 'Redirect to URL', 'gravityview' ),
					),
				),
				'delete_redirect_url' => array(
					'label'             => __( 'Delete Entry Redirect URL', 'gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'After deleting an entry, the user will be taken to this URL.', 'gravityview' ),
					'type'              => 'text',
					'class'             => 'code widefat',
					'value'             => '',
					'placeholder'       => 'https://www.example.com/landing-page/',
					'requires'          => 'delete_redirect=' . \GravityView_Delete_Entry::REDIRECT_TO_URL_VALUE,
					'merge_tags'        => 'force',
				),
				'embed_only' => array(
					'label'             => __( 'Prevent Direct Access', 'gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'Only allow access to this View when embedded using the shortcode.', 'gravityview' ),
					'type'              => 'checkbox',
					'value'             => '',
					'tooltip'           => false,
					'show_in_shortcode' => false,
					'full_width'        => true,
				),
			),
			( gravityview()->plugin->supports( Plugin::FEATURE_REST ) && ( gravityview()->plugin->settings->get( 'rest_api' ) === '1' ) ) ?
			array(
				'rest_disable'          => array(
					'label'             => __( 'Prevent REST Access', 'gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'Disable REST access to this View.', 'gravityview' ),
					'type'              => 'checkbox',
					'value'             => '',
					'tooltip'           => false,
					'show_in_shortcode' => false,
					'full_width'        => true,
				),
			) : array(),
			( gravityview()->plugin->supports( Plugin::FEATURE_REST ) && ( gravityview()->plugin->settings->get( 'rest_api' ) !== '1' ) ) ?
			array(
				'rest_enable'           => array(
					'label'             => __( 'Allow REST Access', 'gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'Enable REST access to this View.', 'gravityview' ),
					'type'              => 'checkbox',
					'value'             => '',
					'tooltip'           => false,
					'show_in_shortcode' => false,
					'full_width'        => true,
				),
			) : array(),
			array(
				'csv_enable'            => array(
					'label'             => __( 'Allow Export', 'gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'Enable users to download data as a CSV or TSV file.', 'gravityview' ),
					'type'              => 'checkbox',
					'value'             => '',
					'tooltip'           => __( 'If enabled, entries can be exported for this View by adding "/csv/" or "/tsv/" to the View URL. Each configured field will be a column in the exported file.', 'gravityview' ),
					'show_in_shortcode' => false,
					'full_width'        => true,
					'article'          => array(
						'id' => '5bad2a0c042863158cc6d4ac',
						'url' => 'https://docs.gravityview.co/article/491-csv-export',
					),
				),
			),
			array(
				'csv_nolimit'           => array(
					'label'             => __( 'Show all in file', 'gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'Do not limit the number of entries output in the file.', 'gravityview' ),
					'type'              => 'checkbox',
					'value'             => '',
					'tooltip'           => false,
					'show_in_shortcode' => false,
					'full_width'        => true,
					'requires'          => 'csv_enable=1',
				),
			),
			array(
				'post_id' => array(
					'type'              => 'number',
					'value'             => '',
					'show_in_shortcode' => false,
				),
			)
		);

		/**
		 * @filter `gravityview_default_args` Modify the default settings for new Views
		 * @param[in,out] array $default_args Array of default args.
		 * @deprecated
		 * @see filter `gravityview/view/settings/defaults`
		 */
		$default_settings = apply_filters( 'gravityview_default_args', $default_settings );

		/**
		 * @filter `gravityview/view/defaults` Modify the default settings for new Views
		 * @param[in,out] array $default_settings Array of default settings.
		 */
		$default_settings = apply_filters( 'gravityview/view/settings/defaults', $default_settings );

		// By default, we only want the key => value pairing, not the whole array.
		if ( ! $detailed ) {
			$defaults = array();
			foreach ( $default_settings as $key => $value ) {
				$defaults[ $key ] = $value['value'];
			}
			return $defaults;

		// But sometimes, we want all the details.
		} else {
			foreach ( $default_settings as $key => $value ) {

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
		return array_combine(
			$defaults,
			array_map(
				function( $key ) use ( $_this ) {
					return $_this->get( $key );
				},
				$defaults
			)
		);
	}
}
