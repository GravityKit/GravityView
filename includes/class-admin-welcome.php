<?php
/**
 * Welcome Page Class
 *
 * @package   GravityView
 * @author    Zack Katz <zack@gravityview.co>
 * @link      https://gravityview.co
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

			<div class="feature-section two-col has-2-columns is-fullwidth">
				<div class="col column">
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
				<div class="col column">
					<h4>What is a View?</h4>
					<p>When a form is submitted in Gravity Forms, an entry is created. Without GravityView, Gravity Forms entries are visible only in the WordPress dashboard, and only to users with permission.</p>
					<p>GravityView allows you to display entries on the front of your site. In GravityView, when you arrange the fields you want displayed and save the configuration, it's called a "View".</p>
				</div>
			</div>

			<hr />

			<div class="feature-section two-col has-2-columns is-fullwidth">
				<div class="col column">
					<h3>Embed Views in the Block Editor</h3>
					<p>Embed Views using the "Add Shortcode" button above your content editor. <a href="https://docs.gravityview.co/article/73-using-the-shortcode">Learn how to use the <code>[gravityview]</code> shortcode.</a></p>
				</div>
				<div class="col column">
					<img src="<?php echo plugins_url( 'assets/images/screenshots/shortcode-block.png', GRAVITYVIEW_FILE ); ?>" alt="Screenshot of the Shortcode block" />
				</div>
			</div>

			<div class="feature-section two-col has-2-columns is-fullwidth">
				<div class="col column">
					<h3>Embed Views in Classic Editor</h3>
					<p>Views don&rsquo;t need to be embedded in a post or page, but you can if you want. Embed Views using the "Add View" button above your content editor.</p>
				</div>
				<div class="col column">
					<img src="<?php echo plugins_url( 'assets/images/screenshots/add-view-button.png', GRAVITYVIEW_FILE ); ?>" alt="Screenshot of Add View button" />
				</div>
			</div>

			<hr />

			<div class="feature-section two-col has-2-columns is-fullwidth">
				<div class="col column">
					<h3>Configure Multiple Entry, Single Entry, and Edit Entry Layouts</h3>

					<p>You can configure what fields are displayed in <strong>Multiple Entry</strong>, <strong>Single Entry</strong>, and <strong>Edit Entry</strong> modes. These can be configured by clicking on the three associated tabs when editing a View.</p>

					<ul class="ul-disc">
						<li>Click "+ Add Field" to add a field to a zone</li>
						<li>Click the name of the field you want to display</li>
						<li>Once added, fields can be dragged and dropped to be re-arranged. Hover over the field until you see a cursor with four arrows, then drag the field.</li>
						<li>Click the <i class="dashicons dashicons-admin-generic"></i> gear icon on each field to configure the <strong>Field Settings</strong></li>
					</ul>
				</div>
				<div class="col column">
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

			<div class="changelog point-releases" style="margin-top: 3em; border-bottom: 0">

				<div class="headline-feature" style="max-width: 100%">
					<h2 style="border-bottom: 1px solid #ccc; padding-bottom: 1em; margin-bottom: 0; margin-top: 0"><?php esc_html_e( 'What&rsquo;s New', 'gravityview' ); ?></h2>
				</div>

				<h3>2.14.4 on April 27, 2022</h3>

				<ul>
					<li>Added: Search Bar support for the <a
								href='https://www.gravityforms.com/add-ons/chained-selects/'>Chained Selects</a> field
						type
					</li>
					<li>Improved: Plugin updater script now supports auto-updates and better supports multisite
						installations
					</li>
					<li>Improved: If a View does not support joined forms, log as a notice, not an error</li>
					<li>Fixed: Merge Tag picker behavior when using Gravity Forms 2.6</li>
					<li>Fixed: Deleting a file when editing an entry as a non-administrator user on Gravity Forms 2.6.1
						results in a server error
					</li>
					<li>Fixed: When The Events Calendar Pro plugin is active, Views became un-editable</li>
					<li>Tweak: Additional translation strings related to View editing</li>
				</ul>

				<p>Note: We will be requiring Gravity Forms 2.5 and WordPress 5.3 in the near future; please upgrade!</p>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Added: Search URLs now support <code>input_{field ID}</code> formats as well as <code>filter_{field
							ID}</code>; the following will both be treated the same:
						<ul>
							<li><code>/view/example/?filter_3=SEARCH</code></li>
							<li><code>/view/example/?input_3=SEARCH</code></li>
						</ul>
					</li>
					<li>Added: In the admin, CSS classes are now added to the <code>body</code> tag based on Gravity
						Forms version. See <code>GravityView_Admin_Views::add_gf_version_css_class()</code></li>
					<li>Modified: Allow non-admin users with 'edit entry' permissions to delete uploaded files</li>
					<li>Updated: EDD<em>SL</em>Plugin_Updater script to version 1.9.1</li>
				</ul>

				<h3>2.14.3 on March 24, 2022</h3>

				<ul>
					<li>Added: Support for displaying WebP images</li>
					<li>Improved: Internal logging of notices and errors</li>
					<li>Fixed: Images hosted on Dropbox sometimes would not display properly on the Safari browser. Thanks, Kevin M. Dean!</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Added: <code>GravityView_Image::get_image_extensions()</code> static method to fetch full list of extension types interpreted as images by GravityView.</li>
					<li>Added: <code>webp</code> as a valid image extension</li>
				</ul>

				<h3>2.14.2.1 on March 11, 2022</h3>

				<ul>
					<li>Fixed: Empty values in search widget fields may return incorrect results</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>
						Added: <code>gravityview/search/ignore-empty-values</code> filter to control strict matching of empty field values
					</li>

				</ul>

				<h3>2.14.2 on March 10, 2022</h3>

				<ul>
					<li>Fixed: Potential fatal error on PHP 8 when exporting View entries in CSV and TSV formats</li>
					<li>Fixed: Search widget would cause a fatal error when the Number field is used with the "is" operator</li>
					<li>Fixed: Search widget returning incorrect results when a field value is blank and the operator is set to "is"</li>
					<li>Fixed: Gravity Forms widget icon not showing</li>
					<li>Fixed: Gravity Forms widget not displaying available forms when the View is saved</li>
				</ul>

				<h3>2.14.1 on January 25, 2022</h3>

				<ul>
					<li>Tested with WordPress 5.9</li>
					<li>Improved: The <a href='https://wordpress.org/plugins/members/'>Members plugin</a> now works with
						No-Conflict Mode enabled
					</li>
					<li>Improved: Performance when saving Views with many fields</li>
					<li>Improved: Performance when loading the Edit View screen when a View has many fields</li>
					<li>Fixed: Gravity Forms widget used in the View editor would initialize on all admin pages</li>
					<li>Fixed: PHP notice when editing an entry in Gravity Forms that was created by user that no longer
						exists
					</li>
					<li>Fixed: Error activating on sites that use the Danish language</li>
					<li>Fixed: Entry approval scripts not loading properly when using Full Site Editing themes in
						WordPress 5.9
					</li>
					<li>Updated: TrustedLogin client to Version 1.2, which now supports logins for WordPress Multisite
						installations
					</li>
					<li>Updated: Polish translation. Thanks, Dariusz!</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Modified: Refactored drag &amp; drop in the View editor to improve performance: we only
						initialize drag &amp; drop on the active tab instead of globally.
						<ul>
							<li>Added: <code>gravityview/tab-ready</code> jQuery trigger to <code>body</code> when each
								GravityView tab is ready (drag &amp; drop initialized). <a
										href='https://gist.github.com/zackkatz/a2844e9f6b68879e79ba7d6f66ba0850'>See
									example of binding to this event</a>.
							</li>
						</ul>
					</li>
				</ul>

				<h3>2.14 on December 21, 2021</h3>

				<p>This would be a minor version update (2.13.5), except that we renamed many functions. See 'Developer
					Updates' for this release below.</p>

				<ul>
					<li>Added: <code>{is_starred}</code> Merge Tag. <a
								href='https://docs.gravityview.co/article/820-the-isstarred-merge-tag'>Learn more about
							using <code>{is_starred}</code></a></li>
					<li>Fixed: Media files uploaded to Dropbox were not properly embedded</li>
					<li>Fixed: JavaScript error when trying to edit entry's creator</li>
					<li>Fixed: Recent Entries widget would cause a fatal error on WP 5.8 or newer</li>
					<li>Fixed: When using Multiple Forms, editing an entry in a joined form now works properly if the
						"Edit Entry" tab has not been configured
					</li>
					<li>Fixed: View settings not hiding automatically on page load</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<p>We renamed all instances of <code>blacklist</code> to <code>blocklist</code> and
					<code>whitelist</code> to <code>allowlist</code>. All methods and filters have been deprecated using
					<code>apply_filters_deprecated()</code> and <code>_deprecated_function()</code>. <a
							href="https://docs.gravityview.co/article/816-renamed-filters-methods-in-2-14">See a
						complete list of modified methods and filters</a>.</p>

				<h3>2.13.4 on November 4, 2021</h3>

				<ul>
					<li>Fixed: View scripts and styles would not load when manually outputting the contents of the
						<code>[gravityview]</code> shortcode
					</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Added: <code>gravityview/shortcode/before-processing</code> action that runs before the
						GravityView shortcode is processed
					</li>
					<li>Added: <code>gravityview/edit_entry/cancel_onclick</code> filter to modify the 'Back' link
						<code>onclick</code> HTML attribute
						<ul>
							<li>Modified: <code>/includes/extensions/edit-entry/partials/form-buttons.php</code> file to
								add the filter
							</li>
						</ul>
					</li>
				</ul>

				<h3>2.13.3 on October 14, 2021</h3>

				<ul>
					<li>Fixed: Edit Entry would not accept zero as a value for a Number field marked as required</li>
					<li>Modified: Refined the capabilities assigned to GravityView support when access is granted using
						TrustedLogin. Now our support will be able to debug theme-related issues and use the <a
								href='https://wordpress.org/plugins/code-snippets/'>Code Snippets</a> plugin.
					</li>
				</ul>

				<h3>2.13.2 on October 7, 2021</h3>

				<ul>
					<li>Fixed: Entry Approval not working when using DataTables in responsive mode (requires DataTables
						2.4.9 or newer).
					</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Updated: Upgraded to <a href='https://fancyapps.com/docs/ui/fancybox'>Fancybox 4</a>.</li>
					<li>Updated: <a href='https://github.com/trustedlogin/client'>TrustedLogin Client</a> to Version
						1.0.2.
					</li>
					<li>Modified: Added Code Snippets CSS file to No Conflict allow list.</li>
					<li>Modified: Moved internal (but public) method <code>GravityView_Admin_ApproveEntries::process_bulk_action</code>
						to new <code>GravityView_Bulk_Actions</code> class.
					</li>
				</ul>

				<h3>2.13.1 on September 27, 2021</h3>

				<ul>
					<li>Improved: Views now load faster due to improved template caching.</li>
					<li>Added: Ability to configure an 'Admin Label' for Custom Content widgets. This makes it easier to
						see your widget configuration a glance.
					</li>
					<li>Fixed: Issue where non-support users may see a 'Revoke TrustedLogin' admin bar link.</li>
				</ul>

				<h3>2.13 on September 23, 2021</h3>

				<ul>
					<li>Added: Integrated with TrustedLogin, the easiest
						&amp; most secure way to grant access to your website. <a
								href='https://www.trustedlogin.com/about/easy-and-safe/'>Learn more about
							TrustedLogin</a>.
						<ul>
							<li>Need to share access with support? Click the new 'Grant Support Access' link in the
								'Views' menu.
							</li>
						</ul>
					</li>
				</ul>

				<p style="text-align: center;">
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
				.about-wrap .cols {
					display: flex;
					flex-wrap: wrap;
					flex-direction: row;
					justify-content: space-between;
				}
				.col {
					width: 45%;
					margin-right: 5%;
				}
			</style>
			<div class="cols">

				<div class="col">
					<h3>Zack Katz <a href="https://twitter.com/zackkatz"><span class="dashicons dashicons-twitter" title="Follow Zack on Twitter"></span></a> <a href="https://katz.co" title="View Zack&rsquo;s website"><span class="dashicons dashicons-admin-site"></span></a></h3>
					<h4 style="font-weight:0; margin-top:0">Project Lead &amp; Developer</h4>
					<p><img alt="Zack Katz" style="float:left; margin: 0 15px 10px 0;" src="<?php echo plugins_url( 'assets/images/zack.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Zack has been developing integrations with Gravity Forms since 2009. He runs GravityView and lives with his wife (and cat) in <a href="https://wikipedia.org/wiki/Denver">Denver, Colorado</a>.</p>
				</div>

				<div class="col">
					<h3>Rafael Ehlers <a href="https://twitter.com/rafaehlers" title="Follow Rafael on Twitter"><span class="dashicons dashicons-twitter"></span></a> <a href="https://heropress.com/essays/journey-resilience/" title="View Rafael&rsquo;s WordPress Journey"><span class="dashicons dashicons-admin-site"></span></a></h3>
					<h4 style="font-weight:0; margin-top:0">Project Manager, Support Lead &amp; Customer&nbsp;Advocate</h4>
					<p><img alt="Rafael Ehlers" style="margin: 0 15px 10px 0;"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/rafael.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Rafael helps guide GravityView development priorities and keep us on track. He&rsquo;s the face of our customer support and helps customers get the most out of the product. Rafael hails from <a href="https://wikipedia.org/wiki/Porto_Alegre">Porto Alegre, Brazil</a>.</p>
				</div>

				<div class="col">
					<h3>Vlad K.</h3>
					<h4 style="font-weight:0; margin-top:0">Core Developer</h4>
					<p><img alt="Vlad K." style="margin: 0 15px 10px 0;"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/vlad.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Vlad, while being the &ldquo;new kid on the block&rdquo; at GravityView, is not new to WordPress, having previously worked on the top newsletter plugin. He&rsquo;s a full-stack developer who focuses on GravityView's user-facing code in the Dashboard and front end. Vlad comes from Russia and lives in Canada.</p>
				</div>

				<div class="col">
					<h3>Rafael Bennemann <a href="https://twitter.com/rafaelbe" title="Follow Rafael on Twitter"><span class="dashicons dashicons-twitter"></span></a></h3>
					<h4 style="font-weight:0; margin-top:0">Support Specialist</h4>
					<p><img alt="Rafael Bennemann" style="margin: 0 15px 10px 0;"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/rafaelb.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Rafael dedicated most of his adult life to helping people and companies take their ideas to the web, first as a developer and now as a Customer Advocate at GravityView. He will do his best to help you too, all the while sipping a <a href="https://en.wikipedia.org/wiki/Spritz_Veneziano">Spritz Veneziano</a> in Northern Italy, where he currently lives with his family.</p>
				</div>
			</div>

			<hr class="clear" />

			<div class="feature-section">
				<div>
					<h2><?php esc_attr_e( 'Contributors', 'gravityview' ); ?></h2>

					<ul class="wp-people-group">
						<li class="wp-person">Core &amp; Extension development by <a href="http://tinygod.pt" class="block">Luis Godinho</a>, <a href="https://codeseekah.com" class="block">Gennady Kovshenin</a>, and <a href="https://mrcasual.com" class="block">Vlad K.</a></li>
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
						<li class="wp-person">Russian translation by <a href="https://www.transifex.com/user/profile/gkovaleff/">@gkovaleff</a>, <a href="https://www.transifex.com/user/profile/awsswa59/">@awsswa59</a></li>
						<li class="wp-person">Polish translation by <a href="https://www.transifex.com/user/profile/dariusz.zielonka/">@dariusz.zielonka</a></li>
						<li class="wp-person">Accessibility contributions by <a href="https://github.com/RianRietveld">@RianRietveld</a></li>
						<li class="wp-person">Code contributions by <a href="https://github.com/ryanduff">@ryanduff</a>, <a href="https://github.com/dmlinn">@dmlinn</a>, <a href="https://github.com/mgratch">@mgratch</a>, <a href="https://github.com/ViewFromTheBox">@ViewFromTheBox</a>, <a href="https://github.com/stevehenty">@stevehenty</a>, <a href="https://github.com/naomicbush">@naomicbush</a>, and <a href="https://github.com/mrcasual">@mrcasual</a></li>
					</ul>

					<h4><?php esc_attr_e( 'Want to contribute?', 'gravityview' ); ?></h4>
					<p><?php echo sprintf( esc_attr__( 'If you want to contribute to the code, %syou can on Github%s. If your contributions are accepted, you will be thanked here.', 'gravityview'), '<a href="https://github.com/gravityview/GravityView">', '</a>' ); ?></p>
				</div>
			</div>

			<hr class="clear" />

			<div class="changelog">

				<h4>Thanks to the following open-source software:</h4>

				<ul>
					<li><a href="https://datatables.net/">DataTables</a> - amazing tool for table data display. Many thanks!</li>
					<li><a href="https://github.com/10up/flexibility">Flexibility</a> - Adds support for CSS flexbox to Internet Explorer 8 &amp; 9</li>
					<li><a href="https://github.com/GaryJones/Gamajo-Template-Loader">Gamajo Template Loader</a> - makes it easy to load template files with user overrides</li>
					<li><a href="https://github.com/carhartl/jquery-cookie">jQuery Cookie plugin</a> - Access and store cookie values with jQuery</li>
					<li><a href="https://gravityview.co/gravityforms">Gravity Forms</a> - If Gravity Forms weren't such a great plugin, GravityView wouldn't exist!</li>
					<li>GravityView uses icons made by Freepik, Adam Whitcroft, Amit Jakhu, Zurb, Scott de Jonge, Yannick, Picol, Icomoon, TutsPlus, Dave Gandy, SimpleIcon from <a href="https://www.flaticon.com" title="Flaticon">www.flaticon.com</a></li>
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
