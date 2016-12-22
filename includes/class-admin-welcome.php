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

		echo gravityview_get_floaty( 132 );
		?>

		<h1><?php printf( esc_html__( 'Welcome to GravityView %s', 'gravityview' ), $display_version ); ?></h1>
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

		<div class="wrap">

			<div style="text-align:center; padding-top: 1em;">
				<h2>Read more articles on using GravityView</h2>
				<p><a class="button button-primary button-hero" href="http://docs.gravityview.co/category/24-category">Getting Started Articles</a></p>
			</div>

			<div class="about-wrap"><h2 class="about-headline-callout">Configuring a View</h2></div>

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
					<p><img src="<?php echo plugins_url( 'assets/images/screenshots/add-view-button.png', GRAVITYVIEW_FILE ); ?>" class="gv-welcome-screenshots" />Views don&rsquo;t need to be embedded in a post or page, but you can if you want. Embed Views using the "Add View" button above your content editor.</p>
				</div>
			</div>

			<div class="feature-section clear">
				<h2>Configure Multiple Entry, Single Entry, and Edit Entry Layouts</h2>

                <p><img src="<?php echo plugins_url( 'assets/images/screenshots/add-field.png', GRAVITYVIEW_FILE ); ?>" alt="Add a field dialog box" class="gv-welcome-screenshots" />
                    You can configure what fields are displayed in <strong>Multiple Entry</strong>, <strong>Single Entry</strong>, and <strong>Edit Entry</strong> modes. These can be configured by clicking on the tabs in "View Configuration."
                </p>

				<ul class="ul-disc">
					<li>Click "+ Add Field" to add a field to a zone</li>
                    <li>Click the name of the field you want to display</li>
					<li>Once added, fields can be dragged and dropped to be re-arranged. Hover over the field until you see a cursor with four arrows, then drag the field.</li>
					<li>Click the <a href="#" style="text-decoration:none;"><i class="dashicons dashicons-admin-generic"></i></a> gear icon on each field to configure the <strong>Field Settings</strong></li>
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
						<div class="media-container" style="min-height:81px; border: none;"><img alt="Front-end entry approval" src="<?php echo plugins_url( 'assets/images/screenshots/frontend-approval.gif', GRAVITYVIEW_FILE ); ?>"></div>
						<h4 class="higher">Front-end Entry Approval</h4>
                        <p><strong>It&rsquo;s here!</strong>: you can now approve entries from the front of the site. It's easier than ever: just add the "Approve Entries" field to your View and get started.</p>
                        <p><a href="https://docs.gravityview.co/article/390-entry-approval" class="button-primary button button-large">Learn how to set it up!</a></p>
					</div>

					<div class="col col-2 last-feature">
						<div class="media-container" style="min-height:81px; border: none;"><img src="<?php echo plugins_url( 'assets/images/screenshots/show-all-entries-setting.png', GRAVITYVIEW_FILE ); ?>" alt="Show all entries to administrators setting"></div>
						<h4 class="higher">Show All Entries to Administrators</h4>
						<p>We added a setting to make front-end moderation easy. The new "Show all entries to administrators" setting allows administrators to see entries with any approval status, while everyone else will only see approved entries. It makes moderating entries easy.</p>
					</div>
				</div>

				<div class="headline-feature" style="max-width: 100%">
					<h2 style="border-bottom: 1px solid #ccc; padding-bottom: 1em; margin-bottom: 0;"><?php esc_html_e( 'What&rsquo;s New', 'gravityview' ); ?></h2>
				</div>

                <h3>1.19.2 on December 21, 2016</h3>

                <ul>
                    <li>Added: Search Bar now supports displaying State and Country fields as Select, List, or Radio input types (before, only text fields)</li>
                    <li>Fixed: Single entries not accessible when a View has filters based on Gravity Forms &quot;Advanced&quot; fields like Address and Name</li>
                    <li>Added: There is now a warning when a View tab has not been configured. The question &quot;Why aren&#39;t my entries showing up?&quot; is often due to a lack of configuration.</li>
                    <li>Added: Notice for future PHP requirements.
                        <ul>
                            <li>Reminder: GravityView will soon require PHP 5.3. 97.6% of sites are already compatible.</li>
                        </ul>
                    </li>
                    <li>Fixed: Conflict with another plugin that prevented the Field Settings from being reachable in the Edit View screen</li>
                    <li>Fixed: GravityView widgets repeating twice for some customers</li>
                </ul>

                <p><strong>Developer Notes:</strong></p>

                <ul>
                    <li>Added: <code>GravityView_View::getContextFields()</code> method allows fetching the fields configured for each View context (<code>directory</code>, <code>single</code>, <code>edit</code>)

                        <ul>
                            <li>Modified: <code>templates/list-body.php</code> and <code>templates/list-single.php</code> to add a check for context fields before rendering</li>
                        </ul></li>
                    <li>Added: <code>$field_id</code> as fourth argument passed to <code>gravityview/extension/search/input_type</code> filter</li>
                    <li>Added: Added <code>$cap</code> and <code>$object_id</code> parameters to <code>GVCommon::generate_notice()</code> to be able to check caps before displaying a notice</li>
                </ul>

                <h3>1.19.1 on November 15, 2016</h3>

                <ul>
                    <li>Fixed: When creating a new View, the "form doesn't exist" warning would display</li>
                </ul>

                <h3>1.19 on November 14, 2016</h3>

                <ul>
                    <li>New: <strong>Front-end entry moderation</strong>! You can now approve and disapprove entries from the front of a View - <a href="https://docs.gravityview.co/article/390-entry-approval">learn how to use front-end entry approval</a>

                        <ul>
                            <li>Add entry moderation to your View with the new &quot;Approve Entries&quot; field</li>
                            <li>Displaying the current approval status by using the new &quot;Approval Status&quot; field</li>
                            <li>Views have a new &quot;Show all entries to administrators&quot; setting. This allows administrators to see entries with any approval status. <a href="http://docs.gravityview.co/article/390-entry-approval#clarify-step-16">Learn how to use this new setting</a></li>
                        </ul></li>
                    <li>Fixed: Approval values not updating properly when using the &quot;Approve/Reject&quot; and &quot;User Opt-In&quot; fields</li>
                    <li>Tweak: Show inactive forms in the Data Source form dropdown</li>
                    <li>Tweak: If a View is connected to a form that is in the trash or does not exist, an error message is now shown</li>
                    <li>Tweak: Don&#39;t show &quot;Lost in space?&quot; message when searching existing Views</li>
                </ul>

                <p><strong>Developer Notes:</strong></p>

                <ul>
                    <li>Added: <code>field-approval.css</code> CSS file. <a href="http://docs.gravityview.co/article/388-front-end-approval-css">Learn how to override the design here</a>.</li>
                    <li>Modified: Removed the bottom border on the &quot;No Results&quot; text (<code>.gv-no-results</code> CSS selector)</li>
                    <li>Fixed: Deprecated <code>get_bloginfo()</code> usage</li>
                </ul>

                <h3>1.18.1 on November 3, 2016</h3>

				<ul>
					<li>Updated: 100% Chinese translation—thank you <a href="https://www.transifex.com/user/profile/michaeledi/">Michael Edi</a>!</li>
					<li>Fixed: Entry approval not working when using <a href="http://docs.gravityview.co/article/57-customizing-urls">custom entry slugs</a></li>
					<li>Fixed: <code>Undefined index: is_active</code> warning is shown when editing entries with User Registration Addon active</li>
					<li>Fixed: Strip extra whitespace in Entry Note field templates</li>
				</ul>

				<h3>1.18 on October 11, 2016</h3>

                <ul>
                    <li>Updated minimum requirements: WordPress 3.5, Gravity Forms 1.9.14</li>
                    <li>Modified: Entries that are unapproved (not approved or disapproved) are shown as yellow circles</li>
                    <li>Added: Shortcut to create a View for an existing form</li>
                    <li>Added: Entry Note emails now have a message &quot;This note was sent from {url}&quot; to provide context for the note recipient</li>
                    <li>Fixed: Edit Entry did not save other field values when Post fields were in the Edit Entry form</li>
                    <li>Fixed: When using &quot;Start Fresh&quot; View presets, form fields were not being added to the &quot;Add Field&quot; field picker</li>
                    <li>Fixed: Hidden visible inputs were showing in the &quot;Add Field&quot; picker (for example, the &quot;Middle Name&quot; input was hidden in the Name field, but showing as an option)</li>
                    <li>Fixed: Fatal error when editing Post Content and Post Image fields</li>
                    <li>Fixed: Lightbox images not loading</li>
                    <li>Fixed: Lightbox loading indicator displaying below the overlay</li>
                    <li>Fixed: &quot;New form created&quot; message was not shown when saving a draft using a &quot;Start Fresh&quot; View preset</li>
                    <li>Gravity Forms User Registration Addon changes:

                        <ul>
                            <li>Gravity Forms User Registration 2.0 is no longer supported</li>
                            <li>Fixed Processing &quot;Update User&quot; feeds</li>
                            <li>Fixed: Inactive User Registration feeds were being processed</li>
                            <li>Fixed: User Registration &quot;Update User&quot; feeds were being processed, even if the Update Conditions weren&#39;t met</li>
                            <li>Fixed: Unable to use <code>gravityview/edit_entry/user_registration/trigger_update</code> filter</li>
                        </ul></li>
                    <li>Fixed: Prevent negative entry counts when approving and disapproving entries</li>
                    <li>Fixed: PHP notice when WooCommerce Memberships is active</li>
                    <li>Tweak: Entry Note emails now have paragraphs automatically added to them</li>
                    <li>Tweak: When the global &quot;Show Support Port&quot; setting is &quot;Hide&quot;, always hide; if set to &quot;Show&quot;, respect each user&#39;s Support Port display preference</li>
                </ul>

                <p><strong>Developer Notes</strong></p>

                <ul>
                    <li>Migrated <code>is_approved</code> entry meta values; statuses are now managed by the <code>GravityView_Entry_Approval_Status</code> class

                        <ul>
                            <li>&quot;Approved&quot; =&gt; <code>1</code>, use <code>GravityView_Entry_Approval_Status::APPROVED</code> constant</li>
                            <li>&quot;0&quot; =&gt; <code>2</code>, use <code>GravityView_Entry_Approval_Status::DISAPPROVED</code> constant</li>
                            <li>Use <code>$new_value = GravityView_Entry_Approval_Status::maybe_convert_status( $old_value )</code> to reliably translate meta values</li>
                        </ul></li>
                    <li>Added: <code>GVCommon::get_entry_id()</code> method to get the entry ID from a slug or ID</li>
                    <li>Added: <code>gravityview_go_back_url</code> filter to modify the link URL used for the single entry back-link in <code>gravityview_back_link()</code> function</li>
                    <li>Added: <code>gravityview/field/notes/wpautop_email</code> filter to disable <code>wpautop()</code> on Entry Note emails</li>
                    <li>Added: <code>$email_footer</code> to the <code>gravityview/field/notes/email_content</code> filter content</li>
                    <li>Modified: <code>note-add-note.php</code> template: added <code>current-url</code> hidden field</li>
                    <li>Modified: <code>list-single.php</code> template file: added <code>.gv-grid-col-1-3</code> CSS class to the <code>.gv-list-view-content-image</code> container</li>
                    <li>Fixed: Mask the Entry ID in the link to lightbox files</li>
                </ul>

                <h3>1.17.4 on September 7, 2016</h3>

                <ul>
                    <li>Added: Support for editing <a href="https://gravitywiz.com/documentation/gp-unique-id/">Gravity Perks Unique ID</a> fields</li>
                    <li>Fixed: Issue searching and sorting fields with multiple inputs (like names)</li>
                    <li>Fixed: Restore Gravity Forms Quiz Addon details in the field picker</li>
                </ul>


                <p><strong>Developer Notes</strong></p>

                <ul>
                    <li>Added: <code>gravityview_get_directory_widgets()</code>, <code>gravityview_set_directory_widgets()</code> wrapper functions to get and set View widget configurations</li>
                    <li>Added: Second <code>$apply_filter</code> parameter to <code>GVCommon::get_directory_fields()</code> function to set whether or not to apply the <code>gravityview/configuration/fields</code> filter</li>
                </ul>

                <h3>1.17.3 on August 31, 2016</h3>

				<ul>
					<li>Added: Search Bar support for Gravity Forms Survey fields: filter by survey responses</li>
					<li>Added: Search Bar support for Gravity Flow: search entries by the current Step, Step Status, or Workflow Status</li>
					<li>Added: <code>[gvlogic]</code> and other shortcodes now can be used inside Email field settings content</li>
					<li>Added: Support for embedding Views in the front page of a site; the <a href="https://github.com/gravityview/gravityview-front-page-views">GravityView - Allow Front Page Views plugin</a> is no longer required</li>
					<li>Tweak: In Edit View, holding down the option (or alt) key while switching forms allows you to change forms without resetting field configurations - this is useful if you want to switch between duplicate forms</li>
					<li>Fixed: Restored correct Gravity Flow status and workflow values</li>
					<li>Fixed: Conflict when editing an entry in Gravity Flow</li>
					<li>Fixed: Tooltip title text of the field and widget "gear" icon</li>
					<li>Changed the plugin author from "Katz Web Services, Inc." to "GravityView" - it seemed like it was time!</li>
				</ul>


				<p><strong>Developer Notes</strong></p>

				<ul>
					<li>Modified: <code>gravityview_get_forms()</code> function and <code>GVCommon::get_forms()</code> method to be compatible with <code>GFAPI::get_forms()</code>. Now accepts <code>$active</code> and <code>$trash</code> arguments, as well as returning all form data (not just <code>id</code> and <code>title</code> keys)</li>
					<li>Modified: <code>template/fields/post_image.php</code> file to use <code>gravityview_get_link()</code> to generate the anchor link</li>
					<li>Modified: <code>rel="noopener noreferrer"</code> now added to all links generated using <code>gravityview_get_link()</code> with <code>target="_blank"</code>. This fixes a generic security issue (not specific to GravityView) when displaying links to submitted websites and "Open link in new window" is checked - <a href="https://dev.to/ben/the-targetblank-vulnerability-by-example">read more about it here</a></li>
					<li>Modified: Don't convert underscores to periods if not numeric in <code>GravityView_Widget_Search::prepare_field_filter()</code> - this fixes searching entry meta</li>
					<li>Modified: Added third <code>gravityview_search_field_label</code> parameter: <code>$field</code> - it's the field configuration array passed by the Search Bar</li>
					<li>Modified: HTML tags are now stripped from Email field body and subject content</li>
					<li>Modified: Moved <code>GravityView_Admin_View_Item</code>, <code>GravityView_Admin_View_Field</code>, and <code>GravityView_Admin_View_Widget</code> to their own files</li>
					<li>Added: Deprecation notices for methods that haven't been used since Version 1.2!</li>
				</ul>


				<h3>1.17.2 on August 9, 2016</h3>

				<ul>
					<li>Fixed: "Start Fresh" fails when there are no pre-existing forms in Gravity Forms</li>
					<li>Added: Support for embedding Views in Ultimate Member profile tabs</li>
					<li>Fixed: File Upload fields potentially displaying PHP warnings</li>
					<li>Fixed: Check plugin and theme existence before loading hooks</li>
					<li>Fixed: "Hide empty fields" not working when "Make Phone Number Clickable" is checked for Phone fields</li>
					<li>Fixed: Potential PHP warning when adding Password fields in Edit View</li>
					<li>Fixed: Dutch (Netherlands) <code>nl_NL</code> translation file fixed</li>
					<li>Fixed: Divi theme shortcode buttons and modal form added to Edit View screen</li>
					<li>Fixed: Possible for Approve Entries checkbox to use the wrong Form ID</li>
					<li>Fixed: Search issues with special characters

						<ul>
							<li>Searches that contained ampersands <code>&amp;</code> were not working</li>
							<li>Searches containing plus signs <code>+</code> were not working</li>
							<li>The "Select" Search Bar input type would not show the active search if search term contained an <code>&amp;</code></li>
						</ul>
					</li>
					<li>Fixed: Multisite issue: when Users are logged-in but not added to any sites, they aren't able to see View content</li>
					<li>Fixed: Never show GravityView Toolbar menu to users who aren't able to edit Views, Forms, or Entries</li>
					<li>Fixed: Allow passing <code>post_id</code> in <code>[gravityview]</code> shortcode</li>
					<li>Tweak: Use system fonts instead of Open Sans in the admin</li>
					<li>Modified: The default setting for "No-Conflict Mode" is now "On". GravityView <em>should look good</em> on your site!</li>
				</ul>


				<p><strong>Developer Notes:</strong></p>

				<ul>
					<li>Added: <code>gravityview_view_saved</code> action, triggered after a View has been saved in the admin</li>
					<li>Modified: Changed the Phone field template to use <code>gravityview_get_link()</code> to generate the anchor tag</li>
					<li>Added: <code>gravityview/common/get_entry_id_from_slug/form_id</code> filter to modify the form ID used to generate entry slugs, in order to avoid hash collisions with data from other forms</li>
				</ul>

				<h3>1.17.1 on June 27</h3>

				<ul>
					<li>Fixed: Entry approval with Gravity Forms 2.0

						<ul>
							<li>Added: Approved/Disapproved filters to Gravity Forms "Entries" page</li>
							<li>Fixed: Bulk Approve/Disapprove</li>
							<li>Fixed: Approve column and Bulk Actions not visible on Gravity Forms Entries page</li>
							<li>Tweak: Improved speed of approving/disapproving entries</li>
						</ul>
					</li>
					<li>Fixed: "Reply To" reference fixed in <code>GVCommon::send_email()</code> function</li>
					<li>Added: Improved logging for creation of Custom Slug hash ids</li>
					<li>Translations updated:

						<ul>
							<li>Updated Chinese translation by <a href="https://www.transifex.com/user/profile/michaeledi/">@michaeledi</a></li>
							<li>Updated Persian translation by <a href="https://www.transifex.com/user/profile/azadmojtaba/">@azadmojtaba</a></li>
						</ul>
					</li>
				</ul>

				<h3>1.17 on June 14</h3>

				<ul>
					<li>Added: Entry Notes field

						<ul>
							<li>Add and delete Entry Notes from the frontend</li>
							<li>Allows users to email Notes when they are added</li>
							<li>Display notes to logged-out users</li>
							<li>New <a href="http://docs.gravityview.co/article/311-gravityview-capabilities">user capabilities</a> to limit access (<code>gravityview_add_entry_notes</code>, <code>gravityview_view_entry_notes</code>, <code>gravityview_delete_entry_notes</code>, <code>gravityview_email_entry_notes</code>)</li>
						</ul>
					</li>
					<li>Added: Merge Tag modifiers - now set a maximum length of content, and automatically add paragraphs to Merge Tags. <a href="https://docs.gravityview.co/article/350-merge-tag-modifiers">Read how to use the new Merge Tag modifiers</a>.

						<ul>
							<li><code>:maxwords:{number}</code> - Limit output to a set number of words</li>
							<li><code>:wpautop</code> - Automatically add line breaks and paragraphs to content</li>
							<li><code>:timestamp</code> - Convert dates into timestamp values</li>
						</ul>
					</li>
					<li>Modified: Major changes to the Search Bar design</li>
					<li>Added: Field setting to display the input value, label, or check mark, depending on field type. Currently supported: Checkbox, Radio, Drop Down fields.</li>
					<li>Added: RTL ("right to left") language support in default and List template styles (Added: <code>gv-default-styles-rtl.css</code> and <code>list-view-rtl.css</code> stylesheets)</li>
					<li>Added: Option to make Phone numbers click-to-call</li>
					<li>Added: GravityView parent menu to Toolbar; now you can edit the form connected to a View directly from the View

						<ul>
							<li>Changed: Don't show Edit View in the Admin Bar; it's now under the GravityView parent menu</li>
							<li>Fixed: Don't remove Edit Post/Page admin bar menu item</li>
						</ul>
					</li>
					<li>Added: Support for <a href="https://gravityflow.io">Gravity Flow</a> "Workflow Step" and Workflow "Final Status" fields</li>
					<li>Added: Support for Password fields. You probably shouldn't display them (in most cases!) but now you <em>can</em></li>
					<li>Modified: When deleting/trashing entries with GravityView, the connected posts created by Gravity Forms will now also be deleted/trashed</li>
					<li>Edit Entry improvements

						<ul>
							<li>Added: Edit Entry now fully supports <a href="https://www.gravityhelp.com/documentation/article/create-content-template/">Gravity Forms Content Templates</a></li>
							<li>Fixed: Edit Entry didn't pre-populate List inputs if they were part of a Post Custom Field field type</li>
							<li>Fixed: Updating Post Image fields in Edit Entry when the field is not set to "Featured Image" in Gravity Forms</li>
							<li>Fixed: "Rank" and "Ratings" Survey Field types not being displayed properly in Edit Entry</li>
							<li>Fixed: Signature field not displaying existing signatures in Edit Entry</li>
							<li>Fixed: Post Category fields will now update to show the Post's current categories</li>
							<li>Fixed: Allow multiple Post Category fields in Edit Entry</li>
							<li>Fixed: PHP warning caused when a form had "Anti-spam honeypot" enabled</li>
						</ul>
					</li>
					<li>Fixed: When inserting a GravityView shortcode using the "Add View" button, the form would flow over the window</li>
					<li>Fixed: <a href="https://churchthemes.com">Church Themes</a> theme compatibility</li>
					<li>Fixed: Inactive and expired licenses were being shown the wrong error message</li>
					<li>Fixed: Moving domains would prevent GravityView from updating</li>
					<li>Fixed: When using the User Opt-in field together with the View setting "Show Only Approved Entries", entries weren't showing</li>
					<li>Fixed: If a label is set for Search Bar "Link" fields, use the label. Otherwise, "Show only:" will be used</li>
					<li>Fixed: Showing the first column of a List field was displaying all the field's columns</li>
				</ul>


				<p><strong>Developer Notes</strong></p>

				<ul>
					<li>Templates changed:

						<ul>
							<li><code>list-single.php</code> and <code>list-body.php</code>: changed <code>#gv_list_{entry_id}</code> to <code>#gv_list_{entry slug}</code>. If using custom entry slugs, the ID attribute will change. Otherwise, no change.</li>
							<li><code>list-body.php</code>: Removed <code>id</code> attribute from entry title <code>&lt;h3&gt;</code></li>
						</ul>
					</li>
					<li>Added: Override GravityView CSS files by copying them to a template's <code>/gravityview/css/</code> sub-directory</li>
					<li>Added: <code>gravityview_css_url()</code> function to check for overriding CSS files in templates</li>
					<li>Added: <code>gravityview_use_legacy_search_style</code> filter; return <code>true</code> to use previous Search Bar stylesheet</li>
					<li>Major CSS changes for the Search Bar.

						<ul>
							<li>Search inputs <code>&lt;div&gt;</code>s now have additional CSS classes based on the input type: <code>.gv-search-field-{input_type}</code> where <code>{input_type}</code> is:
								<code>search_all</code> (search everything text box), <code>link</code>, <code>date</code>, <code>checkbox</code> (list of checkboxes), <code>single_checkbox</code>, <code>text</code>, <code>radio</code>, <code>select</code>,
								<code>multiselect</code>, <code>date_range</code>, <code>entry_id</code>, <code>entry_date</code></li>
							<li>Added <code>gv-search-date-range</code> CSS class to containers that have date ranges</li>
							<li>Moved <code>gv-search-box-links</code> CSS class from the <code>&lt;p&gt;</code> to the <code>&lt;div&gt;</code> container</li>
							<li>Fixed: <code>&lt;label&gt;</code> <code>for</code> attribute was missing quotes</li>
						</ul>
					</li>
					<li>Added:

						<ul>
							<li><code>gravityview/edit_entry/form_fields</code> filter to modify the fields displayed in Edit Entry form</li>
							<li><code>gravityview/edit_entry/field_value_{field_type}</code> filter to change the value of an Edit Entry field for a specific field type</li>
							<li><code>gravityview/edit-entry/render/before</code> action, triggered before the Edit Entry form is rendered</li>
							<li><code>gravityview/edit-entry/render/after</code> action, triggered after the Edit Entry form is rendered</li>
						</ul>
					</li>
					<li>Fixed: PHP Warning for certain hosting <code>open_basedir</code> configurations</li>
					<li>Added: <code>gravityview/delete-entry/delete-connected-post</code> Filter to modify behavior when entry is deleted. Return false to prevent posts from being deleted or trashed when connected entries are deleted or trashed. See <code>gravityview/delete-entry/mode</code> filter to modify the default behavior, which is "delete".</li>
					<li>Added: <code>gravityview/edit_entry/post_content/append_categories</code> filter to modify whether post categories should be added to or replaced?</li>
					<li>Added: <code>gravityview/common/get_form_fields</code> filter to modify fields used in the "Add Field" selector, View "Filters" dropdowns, and Search Bar</li>
					<li>Added: <code>gravityview/search/searchable_fields</code> filter to modify fields used in the Search Bar field dropdown</li>
					<li>Added: <code>GVCommon::send_email()</code>, a public alias of <code>GFCommon::send_email()</code></li>
					<li>Added: <code>GravityView_Field_Notes</code> class, with lots of filters to modify output</li>
					<li>Added: <code>$field_value</code> parameter to <code>gravityview_get_field_label()</code> function and <code>GVCommon::get_field_label()</code> method</li>
					<li>Added: <code>$force</code> parameter to <code>GravityView_Plugin::frontend_actions()</code> to force including files</li>
					<li>Modified: Added second parameter <code>$entry</code> to <code>gravityview/delete-entry/trashed</code> and <code>gravityview/delete-entry/deleted</code> actions</li>
					<li>Fixed: An image with no <code>src</code> output a broken HTML <code>&lt;img&gt;</code> tag</li>
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
					<h3>Zack Katz</h3>
					<h4 style="font-weight:0; margin-top:0">Project Lead &amp; Developer</h4>
					<p></p>
					<p><img style="float:left; margin: 0 15px 10px 0;" src="<?php echo plugins_url( 'assets/images/zack.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Zack has been developing integrations with Gravity Forms since 2009. He is the President of Katz Web Services and lives with his wife (and cat) in Denver, Colorado.</p>
					<p><a href="https://katz.co">View Zack&rsquo;s website</a></p>
				</div>

				<div class="col last-feature">
					<h3>Rafael Ehlers</h3>
					<h4 style="font-weight:0; margin-top:0">Project Manager, Support Lead &amp; Customer Advocate</h4>
					<p><img style="margin: 0 15px 10px 0;"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/rafael.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Rafael helps guide GravityView development priorities and keep us on track. He&rsquo;s the face of our customer support and helps customers get the most out of the product. Rafael hails from Porto Alegre, Brazil.</p>
					<p><a href="http://heropress.com/essays/journey-resilience/">View Rafael&rsquo;s WordPress Journey</a></p>
				</div>
			</div>

			<hr class="clear" />

			<div class="feature-section">
				<div>
					<h2><?php esc_attr_e( 'Contributors', 'gravityview' ); ?></h2>

					<ul class="wp-people-group">
						<li class="wp-person">Core &amp; Extension development by <a href="http://tinygod.pt" class="block">Luis Godinho</a></li>
						<li class="wp-person">Bengali translation by <a href="https://www.transifex.com/accounts/profile/tareqhi/">@tareqhi</a></li>
						<li class="wp-person">German translation by <a href="https://www.transifex.com/user/profile/hubert123456/">@hubert123456</a>, <a href="https://www.transifex.com/accounts/profile/seschwarz/">@seschwarz</a>, <a href="https://www.transifex.com/accounts/profile/abdmc/">@abdmc</a>, <a href="https://www.transifex.com/accounts/profile/deckerweb/">@deckerweb</a></li>
						<li class="wp-person">Turkish translation by <a href="https://www.transifex.com/accounts/profile/suhakaralar/">@suhakaralar</a></li>
						<li class="wp-person">Dutch translation by <a href="https://www.transifex.com/accounts/profile/leooosterloo/">@leooosterloo</a>, <a href="https://www.transifex.com/accounts/profile/Weergeven/">@Weergeven</a>, and <a href="https://www.transifex.com/accounts/profile/erikvanbeek/">@erikvanbeek</a></li>
						<li class="wp-person">Hungarian translation by <a href="https://www.transifex.com/accounts/profile/dbalage/">@dbalage</a> and <a href="https://www.transifex.com/accounts/profile/Darqebus/">@Darqebus</a></li>
						<li class="wp-person">Italian translation by <a href="https://www.transifex.com/accounts/profile/Lurtz/">@Lurtz</a> and <a href="https://www.transifex.com/accounts/profile/ClaraDiGennaro/">@ClaraDiGennaro</a></li>
						<li class="wp-person">French translation by <a href="https://www.transifex.com/accounts/profile/franckt/">@franckt</a> and <a href="https://www.transifex.com/accounts/profile/Newbdev/">@Newbdev</a></li>
						<li class="wp-person">Portuguese translation by <a href="https://www.transifex.com/accounts/profile/luistinygod/">@luistinygod</a> and <a href="https://www.transifex.com/accounts/profile/marlosvinicius.info/">@marlosvinicius</a></li>
						<li class="wp-person">Romanian translation by <a href="https://www.transifex.com/accounts/profile/ArianServ/">@ArianServ</a></li>
						<li class="wp-person">Finnish translation by <a href="https://www.transifex.com/accounts/profile/harjuja/">@harjuja</a></li>
						<li class="wp-person">Spanish translation by <a href="https://www.transifex.com/accounts/profile/jorgepelaez/">@jorgepelaez</a>, <a href="https://www.transifex.com/accounts/profile/luisdiazvenero/">@luisdiazvenero</a>, <a href="https://www.transifex.com/accounts/profile/josemv/">@josemv</a>, <a href="https://www.transifex.com/accounts/profile/janolima/">@janolima</a> and <a href="https://www.transifex.com/accounts/profile/matrixmercury/">@matrixmercury</a></li>
						<li class="wp-person">Swedish translation by <a href="https://www.transifex.com/accounts/profile/adamrehal/">@adamrehal</a></li>
						<li class="wp-person">Indonesian translation by <a href="https://www.transifex.com/accounts/profile/sariyanta/">@sariyanta</a></li>
						<li class="wp-person">Norwegian translation by <a href="https://www.transifex.com/accounts/profile/aleksanderespegard/">@aleksanderespegard</a></li>
						<li class="wp-person">Danish translation by <a href="https://www.transifex.com/accounts/profile/jaegerbo/">@jaegerbo</a></li>
						<li class="wp-person">Chinese translation by <a href="https://www.transifex.com/user/profile/michaeledi/">@michaeledi</a></li>
						<li class="wp-person">Persian translation by <a href="https://www.transifex.com/user/profile/azadmojtaba/">@azadmojtaba</a></li>
						<li class="wp-person">Russian translation by <a href="https://www.transifex.com/user/profile/gkovaleff/">@gkovaleff</a></li>
						<li class="wp-person">Code contributions by <a href="https://github.com/ryanduff">@ryanduff</a>, <a href="https://github.com/dmlinn">@dmlinn</a>, and <a href="https://github.com/mgratch">@mgratch</a></li>
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
					<li><a href="https://github.com/10up/flexibility">Flexibility</a> - Adds support for CSS flexbox to Internet Explorer 8 &amp; 9</li>
					<li><a href="https://github.com/GaryJones/Gamajo-Template-Loader">Gamajo Template Loader</a> - makes it easy to load template files with user overrides</li>
					<li><a href="https://github.com/carhartl/jquery-cookie">jQuery Cookie plugin</a> - Access and store cookie values with jQuery</li>
					<li><a href="https://katz.si/gf">Gravity Forms</a> - If Gravity Forms weren't such a great plugin, GravityView wouldn't exist!</li>
					<li>GravityView uses icons made by Freepik, Adam Whitcroft, Amit Jakhu, Zurb, Scott de Jonge, Yannick, Picol, Icomoon, TutsPlus, Dave Gandy, SimpleIcon from <a href="http://www.flaticon.com" title="Flaticon">www.flaticon.com</a></li>
					<li>GravityView uses free vector art by <a href="http://www.vecteezy.com">vecteezy.com</a></li>
					<li><a href="https://github.com/jnicol/standalone-phpenkoder">PHPEnkoder</a> script encodes the email addresses.</li>
					<li>The Duplicate View functionality is based on the excellent <a href="http://lopo.it/duplicate-post-plugin/">Duplicate Post plugin</a> by Enrico Battocchi</li>
					<li>Browser testing by <a href="https://www.browserstack.com">BrowserStack</a></li>
					<li><a href="https://easydigitaldownloads.com/downloads/software-licensing/">Easy Digital Downloads</a> makes auto-upgrades possible</li>
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
