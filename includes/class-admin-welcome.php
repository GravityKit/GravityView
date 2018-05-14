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
        <style type="text/css" media="screen" xmlns="http://www.w3.org/1999/html">
		/*<![CDATA[*/
		.update-nag { display: none; }
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

		<div class="about-wrap">

            <h2 class="about-headline-callout">Configuring a View</h2>

            <div class="feature-video"  style="text-align:center;">
                <iframe height="315" src="https://www.youtube-nocookie.com/embed/WrXsZhqKRY8?rel=0&amp;showinfo=0" frameborder="0" allowfullscreen></iframe>

                <p style="text-align:center; padding-top: 1em;"><a class="button button-primary button-hero" href="https://docs.gravityview.co/category/24-category">Read more: Setting Up Your First View</a></p>
            </div>

			<div class="feature-section two-col">
				<div class="col">
					<h3>Create a View</h3>

                    <ol class="ol-decimal">
						<li>Go to <a href="<?php echo admin_url('post-new.php?post_type=gravityview'); ?>">Views &gt; New View</a></li>
						<li>If you want to <strong>create a new form</strong>, click the "Use a Form Preset" button</li>
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
                <div class="col">
                    <h4>What is a View?</h4>
                    <p>When a form is submitted in Gravity Forms, an entry is created. Without GravityView, Gravity Forms entries are visible only in the WordPress dashboard, and only to users with permission.</p>
                    <p>GravityView allows you to display entries on the front of your site. In GravityView, when you arrange the fields you want displayed and save the configuration, it's called a "View".</p>
                </div>
			</div>

            <hr />

            <div class="feature-section two-col">
                <div class="col">
                    <h3>Embed Views in Posts &amp; Pages</h3>
                    <p>Views don&rsquo;t need to be embedded in a post or page, but you can if you want. Embed Views using the "Add View" button above your content editor.</p>
                </div>
                <div class="col">
                    <img src="<?php echo plugins_url( 'assets/images/screenshots/add-view-button.png', GRAVITYVIEW_FILE ); ?>" />
                </div>
            </div>

            <hr />

			<div class="feature-section two-col">
                <div class="col">
                    <h3>Configure Multiple Entry, Single Entry, and Edit Entry Layouts</h3>

                    <p>You can configure what fields are displayed in <strong>Multiple Entry</strong>, <strong>Single Entry</strong>, and <strong>Edit Entry</strong> modes. These can be configured by clicking on the tabs in "View Configuration."</p>

                    <ul class="ul-disc">
                        <li>Click "+ Add Field" to add a field to a zone</li>
                        <li>Click the name of the field you want to display</li>
                        <li>Once added, fields can be dragged and dropped to be re-arranged. Hover over the field until you see a cursor with four arrows, then drag the field.</li>
                        <li>Click the <a href="#" style="text-decoration:none;"><i class="dashicons dashicons-admin-generic"></i></a> gear icon on each field to configure the <strong>Field Settings</strong></li>
                    </ul>
                </div>
                <div class="col">
                    <img src="<?php echo plugins_url( 'assets/images/screenshots/add-field.png', GRAVITYVIEW_FILE ); ?>" alt="Add a field dialog box" />
                </div>
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

            <div class="feature-section one-col">
                <div class="col">
                    <h2>18 months in the making, made possible thanks to your support.</h2>

                    <blockquote>
                        <p class="lead-description" style="text-align: left">We at GravityView are proud to share Version 2.0 with you: we have been working on this update since 2016! Although most of the changes aren&rsquo;t visible, <strong>GravityView has a brand-new engine</strong> that will power the plugin into the future! &#128640;
                            <cite style="display: block; padding-top: .25em;">&ndash; Zack with GravityView</cite></p>
                    </blockquote>

                    <p style="margin: 1em;"><em style="font-size: 1.1em;"><strong>A special thanks to <a href="https://codeseekah.com">Gennady</a></strong> for your tireless pursuit of better code, insistence on backward compatibility, and your positive attitude. &#128079;</em></p>
                </div>
            </div>

			<div class="changelog point-releases" style="border-bottom: 0">

                <div class="headline-feature" style="max-width: 100%">
					<h2 style="border-bottom: 1px solid #ccc; padding-bottom: 1em; margin-bottom: 0; margin-top: 0"><?php esc_html_e( 'What&rsquo;s New', 'gravityview' ); ?></h2>
				</div>

                <div style="font-size: 1.1em;">
                    <h3>Version 2.0 on May 8, 2018</h3>

                    <p><strong>New functionality</strong></p>

                    <ul>
                        <li><code>[gventry]</code>: embed entries in a post, page or a View (<a href="https://docs.gravityview.co/article/462-gvfield-embed-gravity-forms-field-values">learn more</a>)</li>
                        <li><code>[gvfield]</code>: embed single field values (<a href="https://docs.gravityview.co/article/462-gvfield-embed-gravity-forms-field-values">learn more</a>)</li>
                        <li><a href="https://docs.gravityview.co/article/350-merge-tag-modifiers">Many new Merge Tag modifiers</a> - These enable powerful new abilities when using the Custom Content field!</li>
                        <li>Use oEmbed with Custom Content fields - easily embed YouTube videos, Tweets (and much more) on your Custom Content field</li>
                        <li>"Is Starred" field - display whether an entry is "Starred" in Gravity Forms or not, and star/unstar it from the front end of your site</li>
                        <li>Added Bosnian, Iranian, and Canadian French translations, updated many others (thank you all!)</li>
                    </ul>

                    <p><strong>Smaller changes</strong></p>

                    <ul>
                        <li>Added <code>{gv_entry_link}</code> Merge Tag, alias of <code>[gv_entry_link]</code> shortcode in <code>{gv_entry_link:[post id]:[action]}</code> format. This allows you to use <code>{gv_entry_link}</code> inside HTML tags, where you are not able to use the <code>[gv_entry_link]</code> shortcode.</li>
                        <li>Default <code>[gvlogic]</code> comparison is now set to <code>isnot=""</code>; this way, you can just use <code>[gvlogic if="{example:1}"]</code> instead of <code>[gvlogic if="{example:1}" isnot=""]</code> to check if a field has a value.</li>
                    </ul>

                    <p><strong>Developer Updates</strong></p>

                    <p>This release is the biggest <strong>ever</strong> for developers! Even so, we have taken great care to provide backward compatibility with GravityView 1.x. Other than increasing the minimum version of PHP to 5.3, <strong>no breaking changes were made.</strong></p>

                    <ul>
                        <li>We have rewritten the plugin from the ground up. <a href="https://github.com/gravityview/GravityView/wiki/The-Future-of-GravityView">Learn all about it here</a>.</li>
                        <li>New REST API! Fetch GravityView details and entries using the WordPress REST API endpoint. It's disabled by default, but can be enabled or disabled globally on GravityView Settings screen, or per-View in View Settings. <a href="https://github.com/gravityview/GravityView/wiki/REST-API">Learn about the endpoints</a>.</li>
                        <li>New <code>gravityview()</code> API wrapper function, now used for easy access to everything you could want</li>
                        <li>New template structure (<a href="https://github.com/gravityview/GravityView/wiki/Template-Migration">learn how to migrate your custom template files</a>)</li>
                        <li>We have gotten rid of global state; actions and filters are now passed a <code>$context</code> argument, a <a href="https://github.com/gravityview/GravityView/blob/2.0/future/includes/class-gv-context-template.php"><code>\GV\Template_Context</code> object</a></li>
                        <li>When HTML 5 is enabled in Gravity Forms, now the Search All field will use <code>type="search"</code></li>
                        <li><em>Countless</em> new filters and actions! Additional documentation will be coming, both on <a href="https://docs.gravityview.co">docs.gravityview.co</a> as well as <a href="https://codex.gravityview.co">codex.gravityview.co</a>.</li>
                    </ul>
                </div>

                <h3>2.0.4 on May 12, 2018</h3>

                <ul>
                    <li>Fixed: Slow front-end performance, affecting all layout types</li>
                    <li>Fixed: Search not performing properly</li>
                    <li>Fixed: "Enable sorting by column" option for Table layouts</li>
                    <li>GravityView will require Gravity Forms 2.3 in the future; please make sure you&rsquo;re using the latest version of Gravity Forms!</li>
                </ul>

                <p><strong>Developer Updates</strong></p>

                <ul>
                    <li>Fixed: <code>GravityView_frontend::get_view_entries()</code> search generation</li>
                    <li>Fixed: <code>gravityview_get_template_settings()</code> not returning settings</li>
                    <li>Tweak: Cache View and Field magic getters into variables for less overhead.</li>
                </ul>


                <h3>2.0.3 on May 10, 2018</h3>

                <ul>
                    <li>Fixed: Compatibility with <code>[gravitypdf]</code> shortcode</li>
                    <li>Fixed: When using <code>[gravityview]</code> shortcode, the <code>page_size</code> setting wasn't being respected</li>
                    <li>Fixed: <code>[gravityview detail="last_entry" /]</code> not returning the correct entry</li>
                    <li>Fixed: Widgets not being properly rendered when using oEmbed</li>
                    <li>Fixed: Note fields not rendering properly</li>
                </ul>

                <p><strong>Developer Notes</strong></p>

                <ul>
                    <li>Fixed: <code>GravityView_View::getInstance()</code> not returning information about a single entry</li>
                    <li>Added: <code>gravityview/shortcode/detail/$key</code> filter</li>
                </ul>

                <h3>2.0.1 &amp; 2.0.2 on May 9, 2018</h3>
                <ul>
                    <li>Fixed: Widgets not displayed when a View is embedded</li>
                    <li>Fixed: Saving new settings can cause fatal error</li>
                    <li>Fixed: Prevent commonly-used front end function from creating an error in the Dashboard</li>
                    <li>Fixed: Hide labels if "Show Label" is not checked</li>
                    <li>Fixed: CSS borders on List layout</li>
                    <li>Fixed: Error when fetching GravityView Widget with DataTables Extension 2.2</li>
                    <li>Fixed: Fail gracefully when GravityView Maps is installed on a server running PHP 5.2.4</li>
                </ul>

                <h3>1.22.6 on April 4, 2018</h3>

                <ul>
                    <li>Fixed: Line breaks being added to <code>[gvlogic]</code> shortcode output</li>
                    <li>Fixed: Gravity Forms 2.3 compatibility notice</li>
                    <li>Fixed: &quot;The ID is required.&quot; message when configuring the GravityView Search WordPress widget</li>
                    <li>Fixed: Slashes were being added to Post Image details</li>
                </ul>

                <p><strong>Developer Updates:</strong></p>

                <ul>
                    <li>Added <code>gravityview/edit_entry/reveal_hidden_field</code> filter, which allows you to prevent Hidden fields from becoming Text fields in Edit Entry context</li>
                    <li>Added <code>gravityview/edit_entry/field_visibility</code> filter to set field visibility on Edit Entry (default is always &quot;visible&quot;)</li>
                </ul>

                <h3>1.22.6 on April 4, 2018</h3>

                <ul>
                    <li>Fixed: Line breaks being added to <code>[gvlogic]</code> shortcode output</li>
                    <li>Fixed: Gravity Forms 2.3 compatibility notice</li>
                    <li>Fixed: &quot;The ID is required.&quot; message when configuring the GravityView Search WordPress widget</li>
                    <li>Fixed: Slashes were being added to Post Image details</li>
                </ul>

                <p><strong>Developer Updates:</strong></p>

                <ul>
                    <li>Added <code>gravityview/edit_entry/reveal_hidden_field</code> filter, which allows you to prevent Hidden fields from becoming Text fields in Edit Entry context</li>
                    <li>Added <code>gravityview/edit_entry/field_visibility</code> filter to set field visibility on Edit Entry (default is always &quot;visible&quot;)</li>
                </ul>


                <h3>1.22.5 on January 25, 2018</h3>

                <ul>
                    <li>Improves support for <a href="https://gravityview.co/extensions/diy-layout/">DIY Layout</a>, a layout for designers &amp; developers to take full advantage of GravityView</li>
                    <li>Tweak: Show &quot;Embed Shortcode&quot; helper if a View has widgets configured but not Fields</li>
                    <li>Fixed: Add Note support for Gravity Forms 2.3 (it&#39;s coming soon)</li>
                    <li>Fixed: <code>tabindex</code> not properly set for Update/Cancel/Delete buttons in Edit Entry</li>
                    <li>Fixed: Hide Yoast SEO Content &amp; SEO Analysis functionality when editing a View</li>
                    <li>Fixed: Line breaks were being added to Custom Content fields and widgets, even when &quot;Automatically add paragraphs to content&quot; wasn&#39;t checked</li>
                </ul>

                <p><strong>Developer Updates:</strong></p>

                <ul>
                    <li>Add <code>$nl2br</code>, <code>$format</code>, <code>$aux_data</code> parameters to <code>GravityView_API::replace_variables()</code> to be consistent with <code>GFCommon::replace_variables()</code></li>
                </ul>

                <h3>1.22.4?</h3>

                <p>Yes, we skipped a minor release (1.22.4 exists only in our hearts). Thanks for noticing!</p>

                <h3>1.22.3 on December 21, 2017</h3>

                <ul>
                    <li>Added: Support for displaying files uploaded using the Gravity Forms Dropbox Addon (thanks, @mgratch and @ViewFromTheBox!)</li>
                    <li>Added: Merge Tags now are replaced when in <code>[gvlogic]</code> shortcodes not in a View</li>
                    <li>Fixed: Filtering by date in Advanced Filters prevented single entries from being visible</li>
                    <li>Fixed: <code>gravityview/capabilities/allow_logged_out</code> filter wasn&#39;t living up to its name (allowing logged-out visitors to edit entries)</li>
                </ul>

                <p><strong>Developer Updates:</strong></p>

                <ul>
                    <li>Modified: We&#39;re reverting changes made to Advanced Custom Field plugin compatibility</li>
                    <li>Added: <code>gravityview/fields/fileupload/file_path</code> filter in <code>class-gravityview-field-fileupload.php</code></li>
                    <li>Modified: Removed <code>!important</code> from the CSS height rule for the <code>.gv-notes .gv-note-add textarea</code> rule</li>
                </ul>

				<p style="text-align: center">
					<a href="https://gravityview.co/changelog/" class="aligncenter button button-primary button-hero" style="margin: 0 auto; display: inline-block; text-transform: capitalize"><?php esc_html_e( 'View change history', 'gravityview' ); ?></a>
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

            <style>
                .feature-section h3 a {
                    text-decoration: none;
                    display: inline-block;
                    margin-left: .2em;
                    line-height: 1em;
                }
            </style>
			<div class="feature-section col three-col">

				<div class="col">
					<h3>Zack Katz <a href="https://twitter.com/zackkatz"><span class="dashicons dashicons-twitter" title="Follow Zack on Twitter"></span></a> <a href="https://katz.co" title="View Zack&rsquo;s website"><span class="dashicons dashicons-admin-site"></span></a></h3>
					<h4 style="font-weight:0; margin-top:0">Project Lead &amp; Developer</h4>
					<p><img style="float:left; margin: 0 15px 10px 0;" src="<?php echo plugins_url( 'assets/images/zack.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Zack has been developing integrations with Gravity Forms since 2009. He runs GravityView and lives with his wife (and cat) in <a href="https://wikipedia.org/wiki/Denver">Denver, Colorado</a>.</p>
				</div>

                <div class="col">
					<h3>Rafael Ehlers <a href="https://twitter.com/rafaehlers" title="Follow Rafael on Twitter"><span class="dashicons dashicons-twitter"></span></a> <a href="https://heropress.com/essays/journey-resilience/" title="View Rafael&rsquo;s WordPress Journey"><span class="dashicons dashicons-admin-site"></span></a></p></h3>
					<h4 style="font-weight:0; margin-top:0">Project Manager, Support Lead &amp; Customer&nbsp;Advocate</h4>
					<p><img style="margin: 0 15px 10px 0;"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/rafael.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Rafael helps guide GravityView development priorities and keep us on track. He&rsquo;s the face of our customer support and helps customers get the most out of the product. Rafael hails from <a href="https://wikipedia.org/wiki/Porto_Alegre">Porto Alegre, Brazil</a>.</p>
				</div>

                <div class="col last-feature">
                    <h3>Gennady Kovshenin <a href="https://twitter.com/soulseekah" title="Follow Gennady on Twitter"><span class="dashicons dashicons-twitter"></span></a> <a href="https://codeseekah.com" title="View Gennady&rsquo;s Blog"><span class="dashicons dashicons-admin-site"></span></a></h3>
                    <h4 style="font-weight:0; margin-top:0">Core Developer</h4>
                    <p><img style="margin: 0 15px 10px 0;"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/gennady.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Gennady works on the GravityView core, improving everything behind the scenes. He is an active member of the WordPress community and loves exotic tea. Gennady lives and runs long distances in <a href="https://wikipedia.org/wiki/Magnitogorsk" rel="external">Magnitogorsk, Russia</a>.</p>
                </div>
			</div>

			<hr class="clear" />

			<div class="feature-section">
				<div>
					<h2><?php esc_attr_e( 'Contributors', 'gravityview' ); ?></h2>

					<ul class="wp-people-group">
						<li class="wp-person">Core &amp; Extension development by <a href="http://tinygod.pt" class="block">Luis Godinho</a> and <a href="https://codeseekah.com" class="block">Gennady Kovshenin</a></li>
						<li class="wp-person">Bengali translation by <a href="https://www.transifex.com/accounts/profile/tareqhi/">@tareqhi</a></li>
						<li class="wp-person">German translation by <a href="https://www.transifex.com/user/profile/hubert123456/">@hubert123456</a>, <a href="https://www.transifex.com/accounts/profile/seschwarz/">@seschwarz</a>, <a href="https://www.transifex.com/accounts/profile/abdmc/">@abdmc</a>, <a href="https://www.transifex.com/accounts/profile/deckerweb/">@deckerweb</a></li>
						<li class="wp-person">Turkish translation by <a href="https://www.transifex.com/accounts/profile/suhakaralar/">@suhakaralar</a></li>
						<li class="wp-person">Dutch translation by <a href="https://www.transifex.com/accounts/profile/leooosterloo/">@leooosterloo</a>, <a href="https://www.transifex.com/accounts/profile/Weergeven/">@Weergeven</a>, and <a href="https://www.transifex.com/accounts/profile/erikvanbeek/">@erikvanbeek</a>, and <a href="https://www.transifex.com/user/profile/SilverXp/">Thom (@SilverXp)</a></li>
						<li class="wp-person">Hungarian translation by <a href="https://www.transifex.com/accounts/profile/dbalage/">@dbalage</a> and <a href="https://www.transifex.com/accounts/profile/Darqebus/">@Darqebus</a></li>
						<li class="wp-person">Italian translation by <a href="https://www.transifex.com/accounts/profile/Lurtz/">@Lurtz</a> and <a href="https://www.transifex.com/accounts/profile/ClaraDiGennaro/">@ClaraDiGennaro</a></li>
						<li class="wp-person">French translation by <a href="https://www.transifex.com/accounts/profile/franckt/">@franckt</a> and <a href="https://www.transifex.com/accounts/profile/Newbdev/">@Newbdev</a></li>
						<li class="wp-person">Portuguese translation by <a href="https://www.transifex.com/accounts/profile/luistinygod/">@luistinygod</a>, <a href="https://www.transifex.com/accounts/profile/marlosvinicius.info/">@marlosvinicius</a>, and <a href="https://www.transifex.com/user/profile/rafaehlers/">@rafaehlers</a></li>
						<li class="wp-person">Romanian translation by <a href="https://www.transifex.com/accounts/profile/ArianServ/">@ArianServ</a></li>
						<li class="wp-person">Finnish translation by <a href="https://www.transifex.com/accounts/profile/harjuja/">@harjuja</a></li>
						<li class="wp-person">Spanish translation by <a href="https://www.transifex.com/accounts/profile/jorgepelaez/">@jorgepelaez</a>, <a href="https://www.transifex.com/accounts/profile/luisdiazvenero/">@luisdiazvenero</a>, <a href="https://www.transifex.com/accounts/profile/josemv/">@josemv</a>, <a href="https://www.transifex.com/accounts/profile/janolima/">@janolima</a> and <a href="https://www.transifex.com/accounts/profile/matrixmercury/">@matrixmercury</a>, <a href="https://www.transifex.com/user/profile/jplobaton/">@jplobaton</a></li>
						<li class="wp-person">Swedish translation by <a href="https://www.transifex.com/accounts/profile/adamrehal/">@adamrehal</a></li>
						<li class="wp-person">Indonesian translation by <a href="https://www.transifex.com/accounts/profile/sariyanta/">@sariyanta</a></li>
						<li class="wp-person">Norwegian translation by <a href="https://www.transifex.com/accounts/profile/aleksanderespegard/">@aleksanderespegard</a></li>
						<li class="wp-person">Danish translation by <a href="https://www.transifex.com/accounts/profile/jaegerbo/">@jaegerbo</a></li>
						<li class="wp-person">Chinese translation by <a href="https://www.transifex.com/user/profile/michaeledi/">@michaeledi</a></li>
                        <li class="wp-person">Persian translation by <a href="https://www.transifex.com/user/profile/azadmojtaba/">@azadmojtaba</a>, <a href="https://www.transifex.com/user/profile/amirbe/">@amirbe</a>, <a href="https://www.transifex.com/user/profile/Moein.Rm/">@Moein.Rm</a></li>
						<li class="wp-person">Russian translation by <a href="https://www.transifex.com/user/profile/gkovaleff/">@gkovaleff</a></li>
						<li class="wp-person">Code contributions by <a href="https://github.com/ryanduff">@ryanduff</a>, <a href="https://github.com/dmlinn">@dmlinn</a>, <a href="https://github.com/mgratch">@mgratch</a>, <a href="https://github.com/ViewFromTheBox">@ViewFromTheBox</a>, <a href="https://github.com/stevehenty">@stevehenty</a>, and <a href="https://github.com/naomicbush">@naomicbush</a></li>
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
					<li>GravityView uses free vector art by <a href="https://www.vecteezy.com">vecteezy.com</a></li>
					<li><a href="https://github.com/jnicol/standalone-phpenkoder">PHPEnkoder</a> script encodes the email addresses.</li>
					<li>The Duplicate View functionality is based on the excellent <a href="https://lopo.it/duplicate-post-plugin/">Duplicate Post plugin</a> by Enrico Battocchi</li>
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
