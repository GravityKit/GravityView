<?php
/**
 * Welcome Page Class
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * GravityView_Welcome Class
 *
 * A general class for About page.
 *
 * @since 1.0
 */
class GravityView_Welcome {

	/**
	 * @var string The capability users should have to view the page
	 */
	public $minimum_capability = 'gravityview_getting_started';

	/**
	 * Get things started
	 *
	 * @since 1.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menus'), 200 );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'welcome'    ) );
		add_filter( 'gravityview_is_admin_page', array( $this, 'is_dashboard_page'), 10, 2 );
	}

	/**
	 * Register the Dashboard Pages which are later hidden but these pages
	 * are used to render the Welcome pages.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function admin_menus() {

		// Add help page to GravityView menu
		add_submenu_page(
			'edit.php?post_type=gravityview',
			__('GravityView: Getting Started', 'gravityview'),
			__('Getting Started', 'gravityview'),
			$this->minimum_capability,
			'gv-getting-started',
			array( $this, 'getting_started_screen' )
		);

		// Changelog Page
		add_submenu_page(
			'edit.php?post_type=gravityview',
			__( 'Changelog', 'gravityview' ),
			__( 'Changelog', 'gravityview' ),
			$this->minimum_capability,
			'gv-changelog',
			array( $this, 'changelog_screen' )
		);

		// Credits Page
		add_submenu_page(
			'edit.php?post_type=gravityview',
			__( 'Credits', 'gravityview' ),
			__( 'Credits', 'gravityview' ),
			$this->minimum_capability,
			'gv-credits',
			array( $this, 'credits_screen' )
		);

	}

	/**
	 * Is this page a GV dashboard page?
	 *
	 * @return boolean  $is_page   True: yep; false: nope
	 */
	public function is_dashboard_page($is_page = false, $hook = NULL) {
		global $plugin_page;

		if($is_page) { return $is_page; }

		return in_array( $plugin_page, array( 'gv-about', 'gv-credits', 'gv-getting-started' ) );
	}

	/**
	 * Hide Individual Dashboard Pages
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function admin_head() {
		global $plugin_page;

		remove_submenu_page( 'edit.php?post_type=gravityview', 'gv-credits' );
		remove_submenu_page( 'edit.php?post_type=gravityview', 'gv-changelog' );

		if( !$this->is_dashboard_page() ) { return; }

		?>
		<style type="text/css" media="screen">
		/*<![CDATA[*/

		.update-nag { display: none; }
		.clear { clear: both; display: block; width: 100%; }
		.gv-welcome-screenshots {
			float: right;
			clear:right;
			max-width:50%;
			border: 1px solid #ccc;
			margin: 0 10px 10px 1.25rem!important;
		}
		/*]]>*/
		</style>
		<?php
	}

	/**
	 * Navigation tabs
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function tabs() {
		global $plugin_page;

		// Don't fetch -beta, etc.
		list( $display_version ) = explode( '-', GravityView_Plugin::version );

		$selected = !empty( $plugin_page ) ? $plugin_page : 'gv-getting-started';
		?>

		<h1><img class="alignleft" src="<?php echo plugins_url( 'assets/images/astronaut-200x263.png', GRAVITYVIEW_FILE ); ?>" width="100" height="132" /><?php printf( esc_html__( 'Welcome to GravityView %s', 'gravityview' ), $display_version ); ?></h1>
		<div class="about-text"><?php esc_html_e( 'Thank you for installing GravityView. Beautifully display your Gravity Forms entries.', 'gravityview' ); ?></div>

		<h2 class="nav-tab-wrapper clear">
			<a class="nav-tab <?php echo $selected == 'gv-getting-started' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-getting-started', 'post_type' => 'gravityview'), 'edit.php' ) ) ); ?>">
				<?php _e( "Getting Started", 'gravityview' ); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'gv-changelog' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-changelog', 'post_type' => 'gravityview'), 'edit.php' ) ) ); ?>">
				<?php _e( "List of Changes", 'gravityview' ); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'gv-credits' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-credits', 'post_type' => 'gravityview'), 'edit.php' ) ) ); ?>">
				<?php _e( 'Credits', 'gravityview' ); ?>
			</a>
		</h2>
		<?php
	}

	/**
	 * Render About Screen
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function getting_started_screen() {
	?>
		<div class="wrap about-wrap">
			<?php $this->tabs(); ?>
		</div>

		<div class="wrap about-wrap">

			<div style="text-align:center; padding-top: 1em;">
				<h2>Read more articles on using GravityView</h2>
				<p><a class="button button-primary button-hero" href="http://docs.gravityview.co/category/24-category">Getting Started Articles</a></p>
			</div>

			<div class="changelog"><h2 class="about-headline-callout">Configuring a View</h2></div>

			<div class="feature-section col two-col" style="margin-top:1em;">

				<div>

					<h2>Create a View</h2>

					<ol class="ol-decimal">
						<li>Go to <a href="<?php echo admin_url('post-new.php?post_type=gravityview'); ?>">Views &gt; New View</a></li>
						<li>If you want to <strong>create a new form</strong>, click the "Start Fresh" button</li>
						<li>If you want to <strong>use an existing form&rsquo;s entries</strong>, select from the dropdown.</li>
						<li>Select the type of View you would like to create. There are two core types of Views: <strong>Table</strong> and <strong>Listing</strong>.
							<ul class="ul-square">
								<li><strong>Table Views</strong> output entries as tables; a grid of data.</li>
								<li><strong>Listing Views</strong> display entries in a more visual layout.</li>
							</ul>
						</li>
                        <li>On the View Configuration metabox, click on the "+Add Field" button to add form fields to the active areas of your View. These are the fields that will be displayed in the frontend.</li>
					</ol>
				</div>

				<div class="last-feature">
				<h2>Embed Views in Posts &amp; Pages</h2>
					<p><img src="<?php echo plugins_url( 'assets/images/screenshots/add-view-button.png', GRAVITYVIEW_FILE ); ?>" class="gv-welcome-screenshots" height="35" width="97" />Views don&rsquo;t need to be embedded in a post or page, but you can if you want. Embed Views using the "Add View" button above your content editor.</p>
				</div>

			</div>

			<div class="feature-section clear">
				<h2>Configure Multiple Entry, Single Entry, and Edit Entry Layouts</h2>
				<p><img src="<?php echo plugins_url( 'assets/images/screenshots/add-field.png', GRAVITYVIEW_FILE ); ?>" alt="Add a field dialog box" class="gv-welcome-screenshots" />You can configure what fields are displayed in <strong>Multiple Entry</strong>, <strong>Single Entry</strong>, and <strong>Edit Entry</strong> modes. These can be configured by clicking on the tabs in "View Configuration."</p>

				<ul class="ul-disc">
					<li>Click "+ Add Field" to add a field to a zone</li>
					<li>Fields can be dragged and dropped to be re-arranged. Hover over the field until you see a cursor with four arrows, then drag the field.</li>
					<li>Click the <a href="#" style="text-decoration:none;"><i class="dashicons dashicons-admin-generic"></i></a> gear icon on each field to configure the <strong>Field Settings</strong>:
					<ul class="ul-square">
						<li><em>Custom Label</em>: Change how the label is shown on the website. Default: the name of the field</li>
						<li><em>Custom CSS Class</em>: Add additional CSS classes to the field container</li>
						<li><em>Use this field as a search filter</em>: Allow searching the text of a field, or narrowing visible results using the field.</li>
						<li><em>Only visible to logged in users with role</em>: Make certain fields visible only to users who are logged in.</li>
					</ul>
					</li>
				</ul>
			</div>

			<div class="clear">
				<h2>What is a View?</h2>
				<p>When a form is submitted in Gravity Forms, an entry is created. Without GravityView, Gravity Forms entries are visible only in the WordPress dashboard, and only to users with permission.</p>

				<p>GravityView allows you to display entries on the front of your site. In GravityView, when you arrange the fields you want displayed and save the configuration, it's called a "View".</p>
			</div>
		</div>
		<?php
	}


	/**
	 * Render Changelog Screen
	 *
	 * @since 1.0.1
	 * @return void
	 */
	public function changelog_screen() {

	?>
		<div class="wrap about-wrap">

			<?php $this->tabs(); ?>

			<div class="changelog point-releases" style="border-bottom: 0">

				<div class="feature-section col two-col" style="margin:0; padding: 0;">
					<div class="col col-1">
						<div class="media-container" style="min-height:81px; border: none;"><img src="<?php echo plugins_url( 'assets/images/screenshots/pricing-merge-tags.jpg', GRAVITYVIEW_FILE ); ?>" alt="Pricing merge tags"></div>
						<h4 class="higher">Pricing Merge Tags</h4>
						<p>GravityView now offers better support for pricing Merge Tags to show details about orers. <a href="http://docs.gravityview.co/article/76-merge-tags">Read more about the Merge Tags</a>.</p>
					</div>

					<div class="col col-2 last-feature">
						<div class="media-container" style="margin-top: 2em; min-height:80px;"><img src="<?php echo plugins_url( 'assets/images/screenshots/developing-future.jpg', GRAVITYVIEW_FILE ); ?>" alt="Developing the Future"></div>
						<h4 class="higher">Laying the Groundwork</h4>
						<p>We&rsquo;re working on a great new View Builder to make it easier than ever to configure View layouts. This release includes lots of behind-the-scenes&nbsp;changes.</p>
					</div>
				</div>

				<div class="headline-feature" style="max-width: 100%">
					<h2 style="border-bottom: 1px solid #ccc; padding-bottom: 1em; margin-bottom: 0;">What&rsquo;s New</h2>
				</div>

				<h3 id="toc_0">1.16 on January 14</h3>

				<ul>
					<li>Happy New Year! We have big things planned for GravityView in 2016, including a new View Builder. Stay tuned :-)</li>
					<li>Added: Merge Tags. <a href="http://docs.gravityview.co/article/76-merge-tags">See all GravityView Merge Tags</a>

						<ul>
							<li><code>{date_created}</code> The date an entry was created. <a href="http://docs.gravityview.co/article/331-date-created-merge-tag">Read how to use it here</a>.</li>
							<li><code>{payment_date}</code> The date the payment was received. Formatted using <a href="http://docs.gravityview.co/article/331-date-created-merge-tag">the same modifiers</a> as <code>{date_created}</code></li>
							<li><code>{payment_status}</code> The current payment status of the entry (ie &quot;Processing&quot;, &quot;Pending&quot;, &quot;Active&quot;, &quot;Expired&quot;, &quot;Failed&quot;, &quot;Cancelled&quot;, &quot;Approved&quot;, &quot;Reversed&quot;, &quot;Refunded&quot;, &quot;Voided&quot;)</li>
							<li><code>{payment_method}</code> The way the entry was paid for (ie &quot;Credit Card&quot;, &quot;PayPal&quot;, etc.)</li>
							<li><code>{payment_amount}</code> The payment amount, formatted as the currency (ie <code>$75.25</code>). Use <code>{payment_amount:raw}</code> for the un-formatted number (ie <code>75.25</code>)</li>
							<li><code>{currency}</code> The currency with which the entry was submitted (ie &quot;USD&quot;, &quot;EUR&quot;)</li>
							<li><code>{is_fulfilled}</code> Whether the order has been fulfilled. Displays &quot;Not Fulfilled&quot; or &quot;Fulfilled&quot;</li>
							<li><code>{transaction_id}</code> the ID of the transaction returned by the payment gateway</li>
							<li><code>{transaction_type}</code> Indicates the transaction type of the entry/order. &quot;Single Payment&quot; or &quot;Subscription&quot;.</li>
						</ul></li>
					<li>Fixed: Custom merge tags not being replaced properly by GravityView</li>
					<li>Fixed: Connected form links were not visible in the Data Source metabox</li>
					<li>Fixed: Inaccurate &quot;Key missing&quot; error shown when license key is invalid</li>
					<li>Fixed: Search Bar could show &quot;undefined&quot; search fields when security key has expired. Now, a helpful message will appear.</li>
					<li>Tweak: Only show Add View button to users who are able to publish Views</li>
					<li>Tweak: Reduce the number of database calls by fetching forms differently</li>
					<li>Tweak: Only show license key notices to users who have capability to edit settings, and only on GravityView pages</li>
					<li>Tweak: Improved load time of Views screen in the admin</li>
					<li>Tweak: Make sure entry belongs to correct form before displaying</li>
					<li>Tweak: Removed need for one database call per displayed entry</li>
					<li>Translations, thanks to:

						<ul>
							<li>Brazilian Portuguese by <a href="https://www.transifex.com/accounts/profile/marlosvinicius.info/">@marlosvinicius</a></li>
							<li>Mexican Spanish by <a href="https://www.transifex.com/accounts/profile/janolima/">@janolima</a></li>
						</ul></li>
				</ul>

				<h4>Developer Notes:</h4>

				<ul>
					<li>New: Added <code>get_content()</code> method to some <code>GravityView_Fields</code> subclasses. We plan on moving this to the parent class soon. This allows us to not use <code>/templates/fields/</code> files for every field type.</li>
					<li>New: <code>GVCommon::format_date()</code> function formats entry and payment dates in more ways than <code>GFCommon::format_date</code></li>
					<li>New: <code>gravityview_get_terms_choices()</code> function generates array of categories ready to be added to Gravity Forms $choices array</li>
					<li>New: <code>GVCommon::has_product_field()</code> method to check whether a form has product fields</li>
					<li>New: Added <code>add_filter( &#39;gform_is_encrypted_field&#39;, &#39;__return_false&#39; );</code> before fetching entries</li>
					<li>Added: <code>gv-container-{view id}</code> CSS class to <code>gv_container_class()</code> function output. This will be added to View container <code>&lt;div&gt;</code>s</li>
					<li>Added: <code>$group</code> parameter to <code>GravityView_Fields::get_all()</code> to get all fields in a specified group</li>
					<li>Added: <code>gravityview_field_entry_value_{field_type}_pre_link</code> filter to modify field values before &quot;Show As Link&quot; setting is applied</li>
					<li>Added: Second parameter <code>$echo</code> (boolean) to <code>gv_container_class()</code></li>
					<li>Added: Use the <code>$is_sortable</code> <code>GravityView_Field</code> variable to define whether a field is sortable. Overrides using the  <code>gravityview/sortable/field_blacklist</code> filter.</li>
					<li>Fixed: <code>gv_container_class()</code> didn&#39;t return value</li>
					<li>Fixed: Don&#39;t add link to empty field value</li>
					<li>Fixed: Strip extra whitespace in <code>gravityview_sanitize_html_class()</code></li>
					<li>Fixed: Don&#39;t output widget structural HTML if there are no configured widgets</li>
					<li>Fixed: Empty HTML <code>&lt;h4&gt;</code> label container output in List layout, even when &quot;Show Label&quot; was unchecked</li>
					<li>Fixed: Fetching the current entry can improperly return an empty array when using <code>GravityView_View-&gt;getCurrentEntry()</code> in DataTables extension</li>
					<li>Fixed: <code>gravityview/sortable/formfield_{form}_{field_id}</code> filter <a href="http://docs.gravityview.co/article/231-how-to-disable-the-sorting-control-on-one-table-column">detailed here</a></li>
					<li>Fixed: <code>gravityview/sortable/field_blacklist</code> filter docBlock fixed</li>
					<li>Tweak: Set <code>max-width: 50%</code> for <code>div.gv-list-view-content-image</code></li>
					<li>Tweak: Moved <code>gv_selected()</code> to <code>helper-functions.php</code> from <code>class-api.php</code></li>
				</ul>


				<h3>1.15.2 on December 3</h3>

				<ul>
					<li>Fixed: Approval column not being added properly on the Form Entries screen for Gravity Forms 1.9.14.18+</li>
					<li>Fixed: Select, multi-select, radio, checkbox, and post category field types should use exact match search</li>
					<li>Fixed: Cannot delete entry notes from Gravity Forms Entry screen</li>
					<li>Fixed: Date Range search field label not working</li>
					<li>Fixed: Date Range searches did not include the &quot;End Date&quot; day</li>
					<li>Fixed: Support Port docs not working on HTTPS sites</li>
					<li>Fixed: When deleting an entry, only show &quot;Entry Deleted&quot; message for the deleted entry&#39;s View</li>
					<li>Fixed: &quot;Open link in a new tab or window?&quot; setting for Paragraph Text fields</li>
					<li>Fixed: Custom Labels not being used as field label in the View Configuration screen

						<ul>
							<li>Tweak: Custom Labels will be used as the field label, even when the &quot;Show Label&quot; checkbox isn&#39;t checked</li>
						</ul></li>
					<li>Tweak: Show available plugin updates, even when license is expired</li>
					<li>Tweak: Improve spacing of the Approval column on the Entries screen</li>
					<li>Tweak: Added support for new accessibility labels added in WordPress 4.4</li>
				</ul>

				<p><strong>Developer Notes:</strong></p>

				<ul>
					<li>Fixed: Make <code>gravityview/fields/fileupload/link_atts</code> filter available when not using lightbox with File Uploads field</li>
					<li>Renamed files:

						<ul>
							<li><code>includes/fields/class.field.php</code> =&gt; <code>includes/fields/class-gravityview-field.php</code></li>
							<li><code>includes/class-logging.php</code> =&gt; <code>includes/class-gravityview-logging.php</code></li>
							<li><code>includes/class-image.php</code> =&gt; <code>includes/class-gravityview-image.php</code></li>
							<li><code>includes/class-migrate.php</code> =&gt; <code>includes/class-gravityview-migrate.php</code></li>
							<li><code>includes/class-change-entry-creator.php</code> =&gt; <code>includes/class-gravityview-change-entry-creator.php</code></li>
						</ul></li>
					<li>New: <code>gravityview/delete-entry/verify_nonce</code> Override Delete Entry nonce validation. Return true to declare nonce valid.</li>
					<li>New: <code>gravityview/entry_notes/add_note</code> filter to modify GravityView note properties before being added</li>
					<li>New: <code>gravityview_post_type_supports</code> filter to modify <code>gravityview</code> post type support values</li>
					<li>New: <code>gravityview_publicly_queryable</code> filter to modify whether Views be accessible using <code>example.com/?post_type=gravityview</code>. Default: Whether the current user has <code>read_private_gravityviews</code> capability (Editor or Administrator by default)</li>
				</ul>


				<h3>1.15.1 on October 27</h3>

				<ul>
					<li>New: Use <code>{get}</code> Merge Tags as <code>[gravityview]</code> attributes</li>
					<li>Fixed: Edit Entry and Delete Entry links weren't working in DataTables</li>
					<li>Fixed: Some Gravity Forms Merge Tags weren't working, like <code>{embed_post:post_title}</code></li>
					<li>Fixed: Display Checkbox and Radio field labels in the Search Bar

						<ul>
							<li>New: If you prefer how the searches looked before the labels were visible, you can set the "Label" for the search field to a blank space. That will hide the label.</li>
							<li>Removed extra whitespace from search field <code>&lt;label&gt;</code>s</li>
						</ul>
					</li>
					<li>Fixed: Update the required Gravity Forms version to 1.9.9.10</li>
					<li>Fixed: Section fields should not be affected by "Hide empty fields" View setting</li>
					<li>Fixed: Add ability to check post custom fields for <code>[gravityview]</code> shortcode. This fixes issues with some themes and page builder plugins.</li>
					<li>Fixed: Return type wasn't boolean for <code>has_gravityview_shortcode()</code> function</li>
					<li>Tweak: Improve notifications logic

						<ul>
							<li>Only show notices to users with appropriate capabilities</li>
							<li>Allow dismissing all notices</li>
							<li>Clear dismissed notices when activating the plugin</li>
							<li>Fixed showing notice to enter license key</li>
						</ul>
					</li>
					<li>Tweak: Added previously-supported <code>{created_by:roles}</code> Merge Tag to available tags dropdown</li>
					<li>Tweak: Allow overriding <code>gravityview_sanitize_html_class()</code> function</li>
					<li>Tweak: Make <code>GravityView_Merge_Tags::replace_get_variables()</code> method public</li>
					<li>Tweak: Rename <code>GravityView_Merge_Tags::_gform_replace_merge_tags()</code> method <code>GravityView_Merge_Tags::replace_gv_merge_tags()</code> for clarity</li>
				</ul>


				<h3>1.15 on October 15</h3>

				<ul>
					<li>Added: <code>{get}</code> Merge Tag that allows passing data via URL to be safely displayed in Merge Tags. <a href="http://docs.gravityview.co/article/314-the-get-merge-tag">Learn how this works</a>.

						<ul>
							<li>Example: When adding <code>?first-name=Floaty</code> to a URL, the Custom Content <code>My name is {get:first-name}</code> would be replaced with <code>My name is Floaty</code></li>
						</ul>
					</li>
					<li>Added: GravityView Capabilities: restrict access to GravityView functionality to certain users and roles. <a href="http://docs.gravityview.co/article/311-gravityview-capabilities">Learn more</a>.

						<ul>
							<li>Fixed: Users without the ability to create Gravity Forms forms are able to create a new form via "Start Fresh"</li>
							<li>Only add the Approve Entries column if user has the <code>gravityview_moderate_entries</code> capability (defaults to Editor role or higher)</li>
							<li>Fixed: Contributors now have access to the GravityView "Getting Started" screen</li>
						</ul>
					</li>
					<li>Added: <code>[gv_entry_link]</code> shortcode to link directly to an entry. <a href="http://docs.gravityview.co/article/287-edit-entry-and-delete-entry-shortcodes">Learn more</a>.

						<ul>
							<li>Existing <code>[gv_delete_entry_link]</code> and <code>[gv_edit_entry_link]</code> shortcodes will continue to work</li>
						</ul>
					</li>
					<li>Added: Ability to filter View by form in the Admin. <a href="http://docs.gravityview.co/article/313-the-views-list-on-the-dashboard">Learn more</a>.</li>
					<li>Added: Option to delete GravityView data when the plugin is uninstalled, then deleted. <a href="http://docs.gravityview.co/article/312-how-to-delete-the-gravityview-data-when-the-plugin-is-uninstalled">Learn more</a>.</li>
					<li>Added: New support "Beacon" to easily search documentation and ask support questions</li>
					<li>Added: Clear search button to the Search Widget (WP widget)</li>
					<li>Fixed: <code>number_format()</code> PHP warning on blank Number fields</li>
					<li>Fixed: <code>{created_by}</code> merge tags weren't being escaped using <code>esc_html()</code></li>
					<li>Fixed: Checkmark icons weren't always available when displaying checkbox input field</li>
					<li>Fixed: When "Shorten Link Display" was enabled for Website fields, "Link Text" wasn't respected</li>
					<li>Fixed: Only process "Create" Gravity Forms User Registration Addon feeds, by default the user role and the user display name format persist</li>
					<li>Fixed: Error with List field  <code>Call to undefined method GF_Field::get_input_type()</code></li>
					<li>Fixed: BuddyPress/bbPress <code>bbp_setup_current_user()</code> warning</li>
					<li>Fixed: <code>gravityview_is_admin_page()</code> wasn't recognizing the Settings page as a GravityView admin page</li>
					<li>Fixed: Custom Content Widgets didn't replace Merge Tags</li>
					<li>Fixed: PHP Warnings</li>
					<li>Fixed: WordPress Multisite fatal error when Gravity Forms not Network Activated</li>
					<li>Tweak: Don't show Data Source column in Views screen to users who don't have permissions to see any of the data anyway</li>
					<li>Tweak: Entry notes are now created using <code>GravityView_Entry_Notes</code> class</li>
					<li>Tweak: Improved automated code testing</li>
					<li>Tweak: Added <code>gravityview/support_port/display</code> filter to enable/disable displaying Support Port</li>
					<li>Tweak: Added <code>gravityview/support_port/show_profile_setting</code> filter to disable adding the Support Port setting on User Profile pages</li>
					<li>Tweak: Removed <code>gravityview/admin/display_live_chat</code> filter</li>
					<li>Tweak: Removed <code>gravityview_settings_capability</code> filter</li>
					<li>Tweak: Escape form name in dropdowns</li>
				</ul>

				<p style="text-align: center">
					<a href="https://gravityview.co/changelog/" class="aligncenter button button-secondary button-hero" style="margin: 0 auto; display: inline-block;">View All Changes</a>
				</p>

				<div class="clear"></div>
			</div>

		</div>
	<?php
	}

	/**
	 * Render Credits Screen
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function credits_screen() { ?>
		<div class="wrap about-wrap">

			<?php $this->tabs(); ?>

			<p class="about-description"><?php _e( 'GravityView is brought to you by:', 'gravityview' ); ?></p>

			<div class="feature-section col two-col">

				<div class="col">
					<h2>Zack Katz</h2>
					<h4 style="font-weight:0; margin-top:0">Project Lead &amp; Developer</h4>
					<p></p>
					<p><img style="float:left; margin: 0 15px 10px 0;" src="<?php echo plugins_url( 'assets/images/zack.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Zack has been developing integrations with Gravity Forms since 2009. He is the President of Katz Web Services and lives with his wife (and cat) in Denver, Colorado.</p>
					<p><a href="https://katz.co">View Zack&rsquo;s website</a></p>
				</div>

				<div class="col last-feature">
					<h2>Luis Godinho</h2>
					<h4 style="font-weight:0; margin-top:0">Developer &amp; Support</h4>
					<p><img style="margin: 0 15px 10px 0;"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/luis.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Luis is a WordPress developer passionate about WordPress. He is a co-founder and partner of GOMO, a digital agency located in Lisbon, Portugal.</p>
					<p><a href="http://tinygod.pt">View Luis&rsquo;s website</a></p>
				</div>

			</div>

			<hr class="clear" />

			<div class="feature-section">
				<div>
					<h2><?php esc_attr_e( 'Contributors', 'gravityview' ); ?></h2>

					<ul class="wp-people-group">
						<li class="wp-person">Bengali translation by <a href="https://www.transifex.com/accounts/profile/tareqhi/">@tareqhi</a></li>
						<li class="wp-person">German translation by <a href="https://www.transifex.com/accounts/profile/seschwarz/">@seschwarz</a>, <a href="https://www.transifex.com/accounts/profile/abdmc/">@abdmc</a>, and <a href="https://www.transifex.com/accounts/profile/deckerweb/">@deckerweb</a></li>
						<li class="wp-person">Turkish translation by <a href="https://www.transifex.com/accounts/profile/suhakaralar/">@suhakaralar</a></li>
						<li class="wp-person">Dutch translation by <a href="https://www.transifex.com/accounts/profile/leooosterloo/">@leooosterloo</a>, <a href="https://www.transifex.com/accounts/profile/Weergeven/">@Weergeven</a>, and <a href="https://www.transifex.com/accounts/profile/erikvanbeek/">@erikvanbeek</a></li>
						<li class="wp-person">Hungarian translation by <a href="https://www.transifex.com/accounts/profile/dbalage/">@dbalage</a> and <a href="https://www.transifex.com/accounts/profile/Darqebus/">@Darqebus</a></li>
						<li class="wp-person">Italian translation by <a href="https://www.transifex.com/accounts/profile/Lurtz/">@Lurtz</a> and <a href="https://www.transifex.com/accounts/profile/ClaraDiGennaro/">@ClaraDiGennaro</a></li>
						<li class="wp-person">French translation by <a href="https://www.transifex.com/accounts/profile/franckt/">@franckt</a> and <a href="https://www.transifex.com/accounts/profile/Newbdev/">@Newbdev</a></li>
						<li class="wp-person">Portuguese translation by <a href="https://www.transifex.com/accounts/profile/luistinygod/">@luistinygod</a> and <a href="https://www.transifex.com/accounts/profile/marlosvinicius.info/">@marlosvinicius</a></li>
						<li class="wp-person">Romanian translation by <a href="https://www.transifex.com/accounts/profile/ArianServ/">@ArianServ</a></li>
						<li class="wp-person">Finnish translation by <a href="https://www.transifex.com/accounts/profile/harjuja/">@harjuja</a></li>
						<li class="wp-person">Spanish translation by <a href="https://www.transifex.com/accounts/profile/jorgepelaez/">@jorgepelaez</a>, <a href="https://www.transifex.com/accounts/profile/luisdiazvenero/">@luisdiazvenero</a>, <a href="https://www.transifex.com/accounts/profile/josemv/">@josemv</a>, and <a href="https://www.transifex.com/accounts/profile/janolima/">@janolima</a></li>
						<li class="wp-person">Swedish translation by <a href="https://www.transifex.com/accounts/profile/adamrehal/">@adamrehal</a></li>
						<li class="wp-person">Indonesian translation by <a href="https://www.transifex.com/accounts/profile/sariyanta/">@sariyanta</a></li>
						<li class="wp-person">Norwegian translation by <a href="https://www.transifex.com/accounts/profile/aleksanderespegard/">@aleksanderespegard</a></li>
						<li class="wp-person">Danish translation by <a href="https://www.transifex.com/accounts/profile/jaegerbo/">@jaegerbo</a></li>
						<li class="wp-person">Code contributions by <a href="https://github.com/ryanduff">@ryanduff</a> and <a href="https://github.com/dmlinn">@dmlinn</a></li>
					</ul>

					<h4><?php esc_attr_e( 'Want to contribute?', 'gravityview' ); ?></h4>
					<p><?php echo sprintf( esc_attr__( 'If you want to contribute to the code, %syou can on Github%s. If your contributions are accepted, you will be thanked here.', 'gravityview'), '<a href="https://github.com/katzwebservices/GravityView">', '</a>' ); ?></p>
				</div>
			</div>

			<hr class="clear" />

			<div class="changelog">

				<h4>Thanks to the following open-source software:</h4>

				<ul>
					<li><a href="http://datatables.net/">DataTables</a> - amazing tool for table data display. Many thanks!</li>
					<li><a href="https://github.com/GaryJones/Gamajo-Template-Loader">Gamajo Template Loader</a> - makes it easy to load template files with user overrides</li>
					<li><a href="https://github.com/carhartl/jquery-cookie">jQuery Cookie plugin</a> - Access and store cookie values with jQuery</li>
					<li><a href="http://katz.si/gf">Gravity Forms</a> - If Gravity Forms weren't such a great plugin, GravityView wouldn't exist!</li>
					<li>GravityView uses icons made by Freepik, Adam Whitcroft, Amit Jakhu, Zurb, Scott de Jonge, Yannick, Picol, Icomoon, TutsPlus, Dave Gandy, SimpleIcon from <a href="http://www.flaticon.com" title="Flaticon">www.flaticon.com</a></li>
					<li>GravityView uses free vector art by <a href="http://www.vecteezy.com">vecteezy.com</a></li>
					<li><a href="https://github.com/jnicol/standalone-phpenkoder">PHPEnkoder</a> script encodes the email addresses.</li>
					<li>The Duplicate View functionality is based on the excellent <a href="http://lopo.it/duplicate-post-plugin/">Duplicate Post plugin</a> by Enrico Battocchi</li>
				</ul>

			</div>

		</div>
	<?php
	}


	/**
	 * Sends user to the Welcome page on first activation of GravityView as well as each
	 * time GravityView is upgraded to a new version
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function welcome() {
		global $plugin_page;

		// Bail if we're just editing the plugin
		if( $plugin_page === 'plugin-editor.php' ) { return; }

		// Bail if no activation redirect
		if ( ! get_transient( '_gv_activation_redirect' ) ) { return; }

		// Delete the redirect transient
		delete_transient( '_gv_activation_redirect' );

		$upgrade = get_option( 'gv_version_upgraded_from' );

		// Don't do anything if they've already seen the new version info
		if( $upgrade === GravityView_Plugin::version ) {
			return;
		}

		// Add "Upgraded From" Option
		update_option( 'gv_version_upgraded_from', GravityView_Plugin::version );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) { return; }

		// First time install
		if( ! $upgrade ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=gravityview&page=gv-getting-started' ) ); exit;
		}
		// Update
		else {
			wp_safe_redirect( admin_url( 'edit.php?post_type=gravityview&page=gv-changelog' ) ); exit;
		}
	}
}
new GravityView_Welcome;
