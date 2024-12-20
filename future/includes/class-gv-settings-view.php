<?php
namespace GV;

use GravityKit\GravityView\Foundation\Settings\Framework as SettingsFramework;

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
	 *
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
	 * @param bool    $detailed Whether to return detailed setting meta information or just the value.
	 * @param string  $group Retrieve settings of a particular group.
	 *
	 * @api
	 * @since 2.0
	 *
	 * @return array The default settings along with their values.
	 *      @param string  $label Setting label shown in admin
	 *      @param string  $type Gravity Forms field type
	 *      @param string  $group The field group the setting is associated with. Default: "default"
	 *      @param mixed   $value The default value for the setting
	 *      @param string  $tooltip Tooltip displayed for the setting
	 *      @param boolean $show_in_shortcode Whether to show the setting in the shortcode configuration modal
	 *      @param array   $options Array of values to use when generating select, multiselect, radio, or checkboxes fields
	 *      @param boolean $full_width True: Display the input and label together when rendering. False: Display label and input in separate columns when rendering.
	 */
	public static function defaults( $detailed = false, $group = null ) {
		$default_settings = array_merge(
			array(
				'id'                          => array(
					'label'             => __( 'View ID', 'gk-gravityview' ),
					'type'              => 'number',
					'group'             => 'default',
					'value'             => null,
					'tooltip'           => null,
					'show_in_shortcode' => false,
				),
				'page_size'                   => array(
					'label'             => __( 'Number of entries per page', 'gk-gravityview' ),
					'tooltip'           => esc_html__( 'Enter the number of entries to display per page. Set to negative one (-1) to display all entries.', 'gk-gravityview' ),
					'type'              => 'number',
					'class'             => 'small-text',
					'group'             => 'default',
					'value'             => 25,
					'show_in_shortcode' => true,
					'min'               => -1,
				),
				'offset'                      => array(
					'label'             => __( 'Offset entries starting from', 'gk-gravityview' ),
					'type'              => 'number',
					'class'             => 'small-text',
					'group'             => 'default',
					'value'             => 0,
					'show_in_shortcode' => true,
				),
				'lightbox'                    => array(
					'label'             => __( 'Enable lightbox for images', 'gk-gravityview' ),
					'type'              => 'checkbox',
					'group'             => 'default',
					'value'             => 1,
					'tooltip'           => __( 'If enabled, images will open full-size in a "lightbox". A lightbox displays images and videos by filling the screen and dimming out the rest of the web page.', 'gk-gravityview' ),
					'show_in_shortcode' => true,
					'article'           => array(
						'id'  => '5e9a1f8904286364bc98931f',
						'url' => 'https://docs.gravitykit.com/article/705-view-settings-enable-lightbox-for-images',
					),
				),
				'show_only_approved'          => array(
					'label'             => __( 'Show only approved entries', 'gk-gravityview' ),
					'type'              => 'checkbox',
					'group'             => 'default',
					'desc'              => __( 'By default, only approved entries are displayed in a View. When enabled, this setting prevents unapproved or disapproved entries from appearing in results. If disabled, entries with all approval statuses will be visible, including disapproved entries.', 'gk-gravityview' ),
					'tooltip'           => false,
					'value'             => 1,
					'show_in_shortcode' => true,
					'article'           => array(
						'id'  => '5bad1a33042863158cc6d396',
						'url' => 'https://docs.gravitykit.com/article/490-entry-approval-gravity-forms',
					),
				),
				'caching'                     => array(
					'label'             => __( 'Enable caching', 'gk-gravityview' ),
					'type'              => 'checkbox',
					'group'             => 'default',
					'value'             => gravityview()->plugin->settings->get( 'caching' ),
					'desc'              => strtr(
						esc_html_x( 'Turn caching on or off to improve performance. Default settings are configured in [url]GravityView Caching Settings[/url].', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
						[
							'[url]'  => '<a href="' . esc_url( SettingsFramework::get_instance()->get_plugin_settings_url( Plugin_Settings::SETTINGS_PLUGIN_ID ) . '&s=1' ) . '">',
							'[/url]' => '</a>',
						]
					),
					'show_in_shortcode' => false,
					'article'           => array(
						'id'  => '54c67bb6e4b051242988550a',
						'url' => 'https://docs.gravitykit.com/article/58-about-gravityview-caching',
					),
				),
				'caching_entries'             => array(
					'label'             => __( 'Entry Cache Duration', 'gk-gravityview' ),
					'tooltip'           => esc_html__( 'Specify the duration, in seconds, that entry data should remain cached before being refreshed. A shorter duration ensures more up-to-date data, while a longer duration improves performance.', 'gk-gravityview' ),
					'type'              => 'number',
					'group'             => 'default',
					'value'             => gravityview()->plugin->settings->get( 'caching_entries' ),
					'show_in_shortcode' => false,
					'requires'          => 'caching=1',
					'min'               => 1,
				),
				'no_entries_options'          => array(
					'label'             => __( 'No Entries Behavior', 'gk-gravityview' ),
					'type'              => 'select',
					'desc'              => __( 'Choose what happens when a View has no entries visible to the current user.', 'gk-gravityview' ),
					'group'             => 'default',
					'options'           => array(
						'0' => __( 'Show a Message', 'gk-gravityview' ),
						'1' => __( 'Display a Form', 'gk-gravityview' ),
						'2' => __( 'Redirect to URL', 'gk-gravityview' ),
						'3' => __( 'Hide the View', 'gk-gravityview' ),
					),
					'value'             => '0',
					'show_in_shortcode' => true,
				),
				'no_results_text'             => array(
					'label'             => __( 'No Entries Message', 'gk-gravityview' ),
					'type'              => 'text',
					'group'             => 'default',
					'desc'              => esc_html__( 'The text to display when there are no entries to show. HTML and shortcodes are allowed.', 'gk-gravityview' ),
					'tooltip'           => false,
					'value'             => '',
					'placeholder'       => esc_html__( 'No entries match your request.', 'gk-gravityview' ),
					'show_in_shortcode' => true,
					'class'             => 'widefat',
					'requires'          => 'no_entries_options=0',
					'full_width'        => true,
				),
				'no_entries_form'             => array(
					'label'             => __( 'No Entries Form', 'gk-gravityview' ),
					'type'              => 'select',
					'desc'              => __( 'Show a Gravity Forms form if there are no entries to show in the View.', 'gk-gravityview' ),
					'group'             => 'default',
					'requires'          => 'no_entries_options=1',
					'options'           => \GVCommon::get_forms_as_options(),
					'value'             => esc_attr( \GV\Utils::_POST( 'post' ) ? gravityview_get_form_id( \GV\Utils::_POST( 'post' ) ) : \GV\Utils::_GET( 'form_id', '' ) ),
					'show_in_shortcode' => true,
				),
				'no_entries_form_title'       => array(
					'label'             => __( 'Form Title', 'gk-gravityview' ),
					'type'              => 'checkbox',
					'group'             => 'default',
					'requires'          => 'no_entries_options=1',
					'value'             => 1,
					'show_in_shortcode' => true,
				),
				'no_entries_form_description' => array(
					'label'             => __( 'Form Description', 'gk-gravityview' ),
					'type'              => 'checkbox',
					'group'             => 'default',
					'requires'          => 'no_entries_options=1',
					'value'             => 1,
					'show_in_shortcode' => true,
				),
				'no_entries_redirect'         => array(
					'label'       => __( 'No Entries Redirect URL', 'gk-gravityview' ),
					'group'       => 'default',
					'desc'        => __( 'If there are no entries to show, the user will be taken to this URL.', 'gk-gravityview' ),
					'type'        => 'text',
					'class'       => 'code widefat',
					'value'       => '',
					'placeholder' => 'https://www.example.com/{field:1}',
					'requires'    => 'no_entries_options=2',
					'validation'  => self::validate_url_with_tags(),
				),
				'no_search_results_text'      => array(
					'label'             => __( '"No Search Results" Text', 'gk-gravityview' ),
					'type'              => 'text',
					'group'             => 'default',
					'desc'              => '',
					'tooltip'           => false,
					'value'             => '',
					'placeholder'       => __( 'This search returned no results.', 'gk-gravityview' ),
					'show_in_shortcode' => true,
					'class'             => 'widefat',
					'full_width'        => true,
				),
				'admin_show_all_statuses'     => array(
					'label'             => __( 'Show all entries to administrators', 'gk-gravityview' ),
					'desc'              => __( 'Administrators will be able to see entries with any approval status.', 'gk-gravityview' ),
					'tooltip'           => __( 'Logged-out visitors and non-administrators will only see approved entries, while administrators will see entries with all statuses. This makes it easier for administrators to moderate entries from a View.', 'gk-gravityview' ),
					'requires'          => 'show_only_approved',
					'type'              => 'checkbox',
					'group'             => 'default',
					'value'             => 0,
					'show_in_shortcode' => false,
				),
				'hide_until_searched'         => array(
					'label'             => __( 'Hide View data until search is performed', 'gk-gravityview' ),
					'type'              => 'checkbox',
					'group'             => 'default',
					'tooltip'           => __( 'When enabled it will only show any View entries after a search is performed.', 'gk-gravityview' ),
					'value'             => 0,
					'show_in_shortcode' => false,
					'article'           => array(
						'id'  => '5c772fa02c7d3a0cb9320a84',
						'url' => 'https://docs.gravitykit.com/article/536-how-to-hide-results-and-only-display-them-if-a-search-is-performed',
					),
				),
				'hide_empty'                  => array(
					'label'             => __( 'Hide empty fields', 'gk-gravityview' ),
					'group'             => 'default',
					'type'              => 'checkbox',
					'desc'              => __( 'When enabled, empty fields will be not be displayed. If disabled, fields and their labels will be displayed with no content.', 'gk-gravityview' ),
					'value'             => 1,
					'tooltip'           => false,
					'show_in_shortcode' => false,
				),
				'hide_empty_single'           => array(
					'label'             => __( 'Hide empty fields', 'gk-gravityview' ),
					'group'             => 'default',
					'type'              => 'checkbox',
					'desc'              => __( 'When enabled, empty fields will be not be displayed. If disabled, fields and their labels will be displayed with no content.', 'gk-gravityview' ),
					'value'             => 1,
					'tooltip'           => false,
					'show_in_shortcode' => false,
				),
				'edit_feeds'                  => array(
					'label'             => __( 'Feeds', 'gk-gravityview' ),
					'group'             => 'default',
					'type'              => 'checkbox',
					'value'             => array(),
					'show_in_shortcode' => false,
				),
				'user_edit'                   => array(
					'label'             => __( 'Allow User Edit', 'gk-gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'Allow logged-in users to edit entries they created.', 'gk-gravityview' ) . ' ' . sprintf( __( 'Administrators are able to %s regardless of this setting.', 'gk-gravityview' ), _x( 'edit entries', 'an action that admins can perform', 'gk-gravityview' ) ),
					'value'             => 0,
					'tooltip'           => __( 'Display "Edit Entry" fields to non-administrator users if they created the entry. Edit Entry fields will always be displayed to site administrators.', 'gk-gravityview' ),
					'type'              => 'checkbox',
					'show_in_shortcode' => true,
					'article'           => array(
						'id'  => '54c67bbbe4b07997ea3f3f6b',
						'url' => 'https://docs.gravitykit.com/article/77-user-edit-allow-users-to-edit-their-own-entries',
					),
				),
				'unapprove_edit'              => array(
					'label'             => __( 'Unapprove Entries After Edit', 'gk-gravityview' ),
					'group'             => 'default',
					'requires'          => 'user_edit',
					'desc'              => __( 'When an entry is edited by a non-administrator, reset the approval status to "Unapproved".', 'gk-gravityview' ),
					'tooltip'           => __( 'If the "Show only approved entries" setting is enabled, the entry will need to be re-approved by an administrator before it is shown in the View.', 'gk-gravityview' ),
					'value'             => 0,
					'type'              => 'checkbox',
					'show_in_shortcode' => true,
					'article'           => array(
						'id'  => '5ddd81d504286364bc923957',
						'url' => 'https://docs.gravitykit.com/article/657-unapproving-edited-entries-automatically',
					),
				),
				'user_delete'                 => array(
					'label'             => __( 'Allow User Delete', 'gk-gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'Allow logged-in users to delete entries they created.', 'gk-gravityview' ) . ' ' . sprintf( __( 'Administrators are able to %s regardless of this setting.', 'gk-gravityview' ), _x( 'delete entries', 'an action that admins can perform', 'gk-gravityview' ) ),
					'value'             => 0,
					'tooltip'           => __( 'Display "Delete Entry" fields to non-administrator users if they created the entry. Delete Entry fields will always be displayed to site administrators.', 'gk-gravityview' ),
					'type'              => 'checkbox',
					'show_in_shortcode' => true,
					'article'           => array(
						'id'  => '54c67bb9e4b0512429885512',
						'url' => 'https://docs.gravitykit.com/article/66-configuring-delete-entry',
					),
				),
				'user_duplicate'              => array(
					'label'             => __( 'Allow User Duplicate', 'gk-gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'Allow logged-in users to duplicate entries they created.', 'gk-gravityview' ) . ' ' . sprintf( __( 'Administrators are able to %s regardless of this setting.', 'gk-gravityview' ), _x( 'duplicate entries', 'an action that admins can perform', 'gk-gravityview' ) ),
					'value'             => 0,
					'tooltip'           => __( 'Display "Duplicate Entry" fields to non-administrator users if they created the entry. Duplicate Entry fields will always be displayed to site administrators.', 'gk-gravityview' ),
					'article'           => array(
						'id'  => '5df11eb704286364bc92bf36',
						'url' => 'https://docs.gravitykit.com/article/66-configuring-delete-entry',
					),
					'type'              => 'checkbox',
					'show_in_shortcode' => true,
				),
				'sort_field'                  => array(
					'label'             => __( 'Sort by field', 'gk-gravityview' ),
					'type'              => 'select',
					'desc'              => __( 'By default, entries are sorted by Entry ID.', 'gk-gravityview' ),
					'value'             => '',
					'group'             => 'sort',
					'options'           => array(
						'id'           => __( 'Default', 'gk-gravityview' ),
						'date_created' => __( 'Date Created', 'gk-gravityview' ),
					),
					'show_in_shortcode' => true,
					'article'           => array(
						'id'  => '54c67bbbe4b051242988551a',
						'url' => 'https://docs.gravitykit.com/article/74-sorting-results-by-field-value',
					),
				),
				'sort_direction'              => array(
					'label'             => __( 'Sort direction', 'gk-gravityview' ),
					'type'              => 'select',
					'value'             => 'ASC',
					'group'             => 'sort',
					'options'           => array(
						'ASC'  => __( 'ASC', 'gk-gravityview' ),
						'DESC' => __( 'DESC', 'gk-gravityview' ),
						'RAND' => __( 'Random', 'gk-gravityview' ),
					),
					'show_in_shortcode' => true,
					'article'           => array(
						'id'  => '5c9d338a2c7d3a1544617f9b',
						'url' => 'https://docs.gravitykit.com/article/570-sorting-by-multiple-columns',
					),
				),
				'sort_field_2'                => array(
					'label'             => __( 'Sort by secondary field', 'gk-gravityview' ),
					'type'              => 'select',
					'value'             => '',
					'group'             => 'sort',
					'options'           => array(
						'id'           => __( 'Default', 'gk-gravityview' ),
						'date_created' => __( 'Date Created', 'gk-gravityview' ),
					),
					'requires_not'      => 'sort_direction][=RAND', // ][ is for toggleRequired, so it ends in []
					'show_in_shortcode' => true,
					'article'           => array(
						'id'  => '5c9d338a2c7d3a1544617f9b',
						'url' => 'https://docs.gravitykit.com/article/570-sorting-by-multiple-columns',
					),
				),
				'sort_direction_2'            => array(
					'label'             => __( 'Secondary sort direction', 'gk-gravityview' ),
					'type'              => 'select',
					'value'             => 'ASC',
					'group'             => 'sort',
					'options'           => array(
						'ASC'  => __( 'ASC', 'gk-gravityview' ),
						'DESC' => __( 'DESC', 'gk-gravityview' ),
					),
					'requires_not'      => 'sort_direction][=RAND', // ][ is for toggleRequired, so it ends in []
					'show_in_shortcode' => true,
					'article'           => array(
						'id'  => '5c9d338a2c7d3a1544617f9b',
						'url' => 'https://docs.gravitykit.com/article/570-sorting-by-multiple-columns',
					),
				),
				'sort_columns'                => array(
					'label'             => __( 'Enable sorting by column', 'gk-gravityview' ),
					'left_label'        => __( 'Column Sorting', 'gk-gravityview' ),
					'type'              => 'checkbox',
					'value'             => false,
					'group'             => 'sort',
					'tooltip'           => null,
					'show_in_shortcode' => true,
					'show_in_template'  => array(
						'default_table',
						'preset_business_data',
						'preset_issue_tracker',
						'preset_resume_board',
						'preset_job_board',
					),
					'article'           => array(
						'id'  => '54ee1246e4b034c37ea91c11',
						'url' => 'https://docs.gravitykit.com/article/230-enabling-the-table-column-sorting-feature',
					),
				),
				'start_date'                  => array(
					'label'             => __( 'Filter by Start Date', 'gk-gravityview' ),
					'class'             => 'gv-datepicker',
					'desc'              => __( 'Show entries submitted after this date. Supports relative dates, such as "-1 week" or "-1 month".', 'gk-gravityview' ),
					'type'              => 'text',
					'value'             => '',
					'group'             => 'filter',
					'show_in_shortcode' => true,
					'article'           => array(
						'id'  => '54c67bbbe4b0512429885520',
						'url' => 'https://docs.gravitykit.com/article/79-using-relative-start-dates-and-end-dates',
					),
				),
				'end_date'                    => array(
					'label'             => __( 'Filter by End Date', 'gk-gravityview' ),
					'class'             => 'gv-datepicker',
					'desc'              => __( 'Show entries submitted before this date. Supports relative dates, such as "now" or "-3 days".', 'gk-gravityview' ),
					'type'              => 'text',
					'value'             => '',
					'group'             => 'filter',
					'show_in_shortcode' => true,
					'article'           => array(
						'id'  => '54c67bbbe4b0512429885520',
						'url' => 'https://docs.gravitykit.com/article/79-using-relative-start-dates-and-end-dates',
					),
				),
				'class'                       => array(
					'label'             => __( 'CSS Class', 'gk-gravityview' ),
					'desc'              => __( 'CSS class to add to the wrapping HTML container.', 'gk-gravityview' ),
					'group'             => 'default',
					'type'              => 'text',
					'value'             => '',
					'show_in_shortcode' => false,
				),
				'search_value'                => array(
					'label'             => __( 'Search Value', 'gk-gravityview' ),
					'desc'              => __( 'Define a default search value for the View', 'gk-gravityview' ),
					'type'              => 'text',
					'value'             => '',
					'group'             => 'filter',
					'show_in_shortcode' => false,
				),
				'search_field'                => array(
					'label'             => __( 'Search Field', 'gk-gravityview' ),
					'desc'              => __( 'If Search Value is set, you can define a specific field to search in. Otherwise, all fields will be searched.', 'gk-gravityview' ),
					'type'              => 'text',
					'value'             => '',
					'group'             => 'filter',
					'show_in_shortcode' => false,
				),
				'search_operator'             => array(
					'label'             => __( 'Search Operator', 'gk-gravityview' ),
					'type'              => 'operator',
					'value'             => 'contains',
					'group'             => 'filter',
					'show_in_shortcode' => false,
				),
				'single_title'                => array(
					'label'             => __( 'Single Entry Title', 'gk-gravityview' ),
					'type'              => 'text',
					'desc'              => __( 'When viewing a single entry, change the title of the page to this setting. Otherwise, the title will not change between the Multiple Entries and Single Entry views.', 'gk-gravityview' ),
					'group'             => 'default',
					'value'             => '',
					'show_in_shortcode' => false,
					'full_width'        => true,
					'article'           => array(
						'id'  => '54c67bcee4b07997ea3f3f9a',
						'url' => 'https://docs.gravitykit.com/article/121-changing-the-single-entry-page-title',
					),
				),
				'back_link_label'             => array(
					'label'             => __( 'Back Link Label', 'gk-gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'The text of the link that returns to the multiple entries view.', 'gk-gravityview' ),
					'type'              => 'text',
					'value'             => '',
					'placeholder'       => __( '&larr; Go back', 'gk-gravityview' ),
					'class'             => 'widefat',
					'merge_tags'        => 'force',
					'show_in_shortcode' => false,
					'full_width'        => true,
				),
				'edit_redirect'               => array(
					'label'   => __( 'Redirect After Editing', 'gk-gravityview' ),
					'group'   => 'default',
					'desc'    => __( 'The page to redirect to after editing an entry.', 'gk-gravityview' ),
					'type'    => 'select',
					'value'   => '',
					'options' => array(
						''  => __( 'Stay on Edit Entry', 'gk-gravityview' ),
						'0' => __( 'Redirect to Single Entry', 'gk-gravityview' ),
						'1' => __( 'Redirect to Multiple Entries', 'gk-gravityview' ),
						'2' => __( 'Redirect to URL', 'gk-gravityview' ),
					),
					'article' => array(
						'id'  => '5e9a3e0c2c7d3a7e9aeb2efb',
						'url' => 'https://docs.gravitykit.com/article/707-view-settings-redirect-after-editing',
					),
				),
				'edit_return_context'         => array(
					'label'             => __( 'Editing Returns To&hellip;', 'gk-gravityview' ),
					'type'              => 'radio',
					'desc'              => __( 'After editing an entry or clicking Cancel, where should the user be sent?', 'gk-gravityview' ),
					'group'             => 'default',
					'value'             => 'single',
					'options'           => array(
						'multiple' => __( 'Multiple Entries', 'gk-gravityview' ),
						'single'   => __( 'Single Entry', 'gk-gravityview' ),
						'custom'   => __( 'Other URL', 'gk-gravityview' ),
					),
					'show_in_shortcode' => false,
					'full_width'        => true,
					'article'           => array(
						'id'  => '5e9a3e0c2c7d3a7e9aeb2efb',
						'url' => 'https://docs.gravitykit.com/article/707-view-settings-redirect-after-editing',
					),
				),
				'edit_redirect_url'           => array(
					'label'       => __( 'Edit Entry Redirect URL', 'gk-gravityview' ),
					'group'       => 'default',
					'desc'        => __( 'After editing an entry, the user will be taken to this URL.', 'gk-gravityview' ),
					'type'        => 'text',
					'class'       => 'code widefat',
					'value'       => '',
					'placeholder' => 'https://www.example.com/landing-page/',
					'requires'    => 'edit_redirect=2',
					'merge_tags'  => 'force',
					'validation'  => self::validate_url_with_tags(),
				),
				'action_label_update'         => array(
					'label'      => __( 'Update Button Text', 'gk-gravityview' ),
					'group'      => 'default',
					'desc'       => '',
					'type'       => 'text',
					'value'      => _x( 'Update', 'Button to update an entry the user is editing', 'gk-gravityview' ),
					'merge_tags' => 'force',
				),
				'edit_cancel_lightbox_action' => array(
					'label'     => __( 'Cancel Link Action', 'gk-gravityview' ),
					'tooltip'   => __( 'Choose what happens when you click Cancel while editing an entry in a lightbox.', 'gk-gravityview' ),
					'type'      => 'select',
					'hidden' => 1,
					'value'     => 'close_lightbox',
					'options'   => array(
						'close_lightbox'           => __( 'Close Lightbox', 'gk-gravityview' ),
						'redirect_to_single_entry' => __( 'Redirect to Single Entry', 'gk-gravityview' ),
					),
				),
				'action_label_cancel'         => array(
					'label'      => __( 'Cancel Link Text', 'gk-gravityview' ),
					'group'      => 'default',
					'desc'       => '',
					'type'       => 'text',
					'value'      => _x( 'Cancel', 'Shown when the user decides not to edit an entry', 'gk-gravityview' ),
					'merge_tags' => 'force',
				),
				'action_label_next'           => array(
					'label'      => __( 'Next Page Button Text', 'gk-gravityview' ),
					'group'      => 'default',
					'desc'       => __( 'Only shown when multi-page forms are enabled.', 'gk-gravityview' ),
					'type'       => 'text',
					'value'      => __( 'Next', 'Show the next page in a multi-page form', 'gk-gravityview' ),
					'merge_tags' => 'force',
				),
				'action_label_previous'       => array(
					'label'      => __( 'Previous Page Button Text', 'gk-gravityview' ),
					'group'      => 'default',
					'desc'       => __( 'Only shown when multi-page forms are enabled.', 'gk-gravityview' ),
					'type'       => 'text',
					'value'      => __( 'Previous', 'Show the previous page in a multi-page form', 'gk-gravityview' ),
					'merge_tags' => 'force',
				),
				'action_label_delete'         => array(
					'label'      => __( 'Delete Link Text', 'gk-gravityview' ),
					'group'      => 'default',
					'desc'       => '',
					'type'       => 'text',
					'value'      => __( 'Delete', 'Button label to delete an entry from the Edit Entry screen', 'gk-gravityview' ),
					'merge_tags' => 'force',
				),
				'edit_locking'                => array(
					'label'      => __( 'Enable Edit Locking', 'gk-gravityview' ),
					'group'      => 'default',
					'desc'       => __( 'Prevent multiple users from editing the same entry at the same time.', 'gk-gravityview' ),
					'type'       => 'checkbox',
					'full_width' => true,
					'class'      => 'code widefat',
					'value'      => true,
					'article'    => array(
						'id'  => '5e4449d72c7d3a7e9ae7a54c',
						'url' => 'https://docs.gravitykit.com/article/676-entry-locking',
					),
				),
				'delete_redirect'             => array(
					'label'   => __( 'Redirect After Deleting', 'gk-gravityview' ),
					'group'   => 'default',
					'desc'    => __( 'The page to redirect to after deleting an entry.', 'gk-gravityview' ),
					'type'    => 'select',
					'value'   => '1',
					'options' => array(
						\GravityView_Delete_Entry::REDIRECT_TO_MULTIPLE_ENTRIES_VALUE => __( 'Redirect to Multiple Entries', 'gk-gravityview' ),
						\GravityView_Delete_Entry::REDIRECT_TO_URL_VALUE              => __( 'Redirect to URL', 'gk-gravityview' ),
					),
				),
				'delete_redirect_url'         => array(
					'label'       => __( 'Delete Entry Redirect URL', 'gk-gravityview' ),
					'group'       => 'default',
					'desc'        => __( 'After deleting an entry, the user will be taken to this URL.', 'gk-gravityview' ),
					'type'        => 'text',
					'class'       => 'code widefat',
					'value'       => '',
					'placeholder' => 'https://www.example.com/landing-page/',
					'requires'    => 'delete_redirect=' . \GravityView_Delete_Entry::REDIRECT_TO_URL_VALUE,
					'merge_tags'  => 'force',
					'validation'  => self::validate_url_with_tags(),
				),
				'is_secure'                   => [
					'label' => __( 'Enable Enhanced Security', 'gk-gravityview' ),
					'desc'  => __( 'This will require a <code>secret</code> attribute on all shortcodes and blocks connected to this View, including <code>[gravityview]</code>, <code>[gvfield]</code> and <code>[gventry]</code>.', 'gk-gravityview' ),
					'type'  => 'checkbox',
					'value' => 0,
				],
				'embed_only'                  => array(
					'label'             => __( 'Prevent Direct Access', 'gk-gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'Only allow access to this View when embedded using the block or shortcode.', 'gk-gravityview' ),
					'type'              => 'checkbox',
					'value'             => '',
					'tooltip'           => false,
					'show_in_shortcode' => false,
					'full_width'        => true,
					'article'           => array(
						'id'   => '5590376ce4b027e1978eb8d0',
						'type' => 'modal',
						'url'  => 'https://docs.gravitykit.com/article/288-how-gravityview-security-works',
					),
				),
				'custom_css'                  => array(
					'label'             => __( 'Custom CSS', 'gk-gravityview' ),
					'group'             => 'default',
					// translators: Do not translate the words inside the square brackets ([]); they are replaced.
					'desc'              => strtr(
					// translators: Do not translate the words inside the square brackets ([]); they are replaced.
						esc_html__( 'CSS added here will be placed inside [style] tags in the page&rsquo;s [head], after GravityView styles.', 'gk-gravityview' ),
						array(
							'[style]' => '<code>' . esc_html( '<style>' ) . '</code>',
							'[head]'  => '<code>' . esc_html( '<head>' ) . '</code>',
						)
					),
					'type'              => 'textarea',
					'rows'              => 15,
					'class'             => 'code widefat',
					'codemirror'        => array(
						'mode' => 'css',
					),
					'value'             => '',
					'tooltip'           => false,
					'merge_tags'        => false,
					'show_in_shortcode' => false,
					'full_width'        => true,
					'article'           => array(
						'id'   => '6527426e44252e4a513e9d35',
						'type' => 'modal',
						'url'  => 'https://docs.gravitykit.com/article/962-view-settings-custom-code',
					),
				),
				'custom_javascript'           => array(
					'label'             => __( 'Custom JavaScript', 'gk-gravityview' ),
					'group'             => 'default',
					'desc'              => strtr(
						// translators: Do not translate the words inside the square brackets ([]); they are replaced.
						esc_html__( 'JavaScript added here will be placed inside [script] tags in the page&rsquo;s footer, after GravityView scripts.', 'gk-gravityview' ),
						array(
							'[script]' => '<code>' . esc_html( '<script>' ) . '</code>',
						)
					),
					'type'              => 'textarea',
					'rows'              => 15,
					'class'             => 'code widefat',
					'codemirror'        => array(
						'mode' => 'javascript',
					),
					'merge_tags'        => false,
					'value'             => '',
					'tooltip'           => false,
					'show_in_shortcode' => false,
					'full_width'        => true,
					'article'           => array(
						'id'   => '6527426e44252e4a513e9d35',
						'type' => 'modal',
						'url'  => 'https://docs.gravitykit.com/article/962-view-settings-custom-code',
					),
				),
			),
			( gravityview()->plugin->supports( Plugin::FEATURE_REST ) && ( gravityview()->plugin->settings->get( 'rest_api' ) ) ) ?
				array(
					'rest_disable' => array(
						'label'             => __( 'Prevent REST Access', 'gk-gravityview' ),
						'group'             => 'default',
						'desc'              => __( 'Disable REST access to this View.', 'gk-gravityview' ),
						'type'              => 'checkbox',
						'value'             => '',
						'tooltip'           => false,
						'show_in_shortcode' => false,
						'full_width'        => true,
					),
				) : array(),
			( gravityview()->plugin->supports( Plugin::FEATURE_REST ) && ( ! gravityview()->plugin->settings->get( 'rest_api' ) ) ) ?
				array(
					'rest_enable' => array(
						'label'             => __( 'Allow REST Access', 'gk-gravityview' ),
						'group'             => 'default',
						'desc'              => __( 'Enable REST access to this View.', 'gk-gravityview' ),
						'type'              => 'checkbox',
						'value'             => '',
						'tooltip'           => false,
						'show_in_shortcode' => false,
						'full_width'        => true,
					),
				) : array(),
			array(
				'csv_enable' => array(
					'label'             => __( 'Allow Export', 'gk-gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'Enable users to download data as a CSV or TSV file.', 'gk-gravityview' ),
					'type'              => 'checkbox',
					'value'             => '',
					'tooltip'           => __( 'If enabled, entries can be exported for this View by adding "/csv/" or "/tsv/" to the View URL. Each configured field will be a column in the exported file.', 'gk-gravityview' ),
					'show_in_shortcode' => false,
					'full_width'        => true,
					'article'           => array(
						'id'  => '5bad2a0c042863158cc6d4ac',
						'url' => 'https://docs.gravitykit.com/article/491-csv-export',
					),
				),
			),
			array(
				'csv_nolimit' => array(
					'label'             => __( 'Show All In File', 'gk-gravityview' ),
					'group'             => 'default',
					'desc'              => __( 'Do not limit the number of entries output in the file.', 'gk-gravityview' ),
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
		 * Modify the default settings for new Views.
		 *
		 * @deprecated
		 * @see filter `gravityview/view/settings/defaults`
		 *
		 * @param array $default_args Array of default args.
		 */
		$default_settings = apply_filters( 'gravityview_default_args', $default_settings );

		/**
		 * Modify the default settings for new Views.
		 *
		 * @param array $default_settings Array of default settings.
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
		$_this    = &$this;
		return array_combine(
			$defaults,
			array_map(
				function ( $key ) use ( $_this ) {
					return $_this->get( $key );
				},
				$defaults
			)
		);
	}

	/**
	 * Validates URLs with merge tags.
	 *
	 * Valid format examples:
	 * http://foo.bar/{field:1}
	 * https://foo.bar/{field:1}
	 * https://{field:1}
	 * https://foo.bar/{field:1}/{another:2}
	 * https://foo.bar/{field:1}:8080?name=value#fragment
	 * http://foo.bar
	 * https://foo.bar/path/to/resource
	 * http://192.168.0.1:8080/query?name=value#fragment
	 * https://[2001:db8::1]:443/resource
	 * https://2001:0db8:85a3:0000:0000:8a2e:0370:7334/path/to?name=value#fragment
	 * {field:1}
	 * {field:1}/path/to?name=value#fragment
	 *
	 * Invalid examples:
	 * htp://foo.bar - Misspelled protocol http (should not match).
	 * foo.bar - Missing protocol (http:// or https://) or leading //.
	 * https://foo - Incomplete domain (e.g., .com).
	 * http://foo - Same as above; incomplete domain.
	 * foo.bar/{field:1} - Missing protocol (http:// or https://) or leading //.
	 * foo - No protocol, domain, or valid structure.
	 *
	 * @since 2.33
	 *
	 * @return array
	 **/
	private static function validate_url_with_tags() {
		return [
			[
				'rule'    => 'required',
				'message' => __( 'Field is required', 'gk-gravityview' ),
			],
			[
				'rule'    => "matches:^s*((https?:\/\/)((\S+\.+\S+)|(\[?(\S+:)+\S+\]?)|({.*}.*))?(?::\d+)?({.*}.*)?|({.*}.*))\s*$",
				'message' => __( 'Must be a valid URL. Can contain merge tags.', 'gk-gravityview' ),
			],
		];
	}
}
