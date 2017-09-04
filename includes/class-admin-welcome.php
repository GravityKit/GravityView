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

                <p style="text-align:center; padding-top: 1em;"><a class="button button-primary button-hero" href="http://docs.gravityview.co/category/24-category">Read more: Setting Up Your First View</a></p>
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

			<div class="changelog point-releases" style="border-bottom: 0">

				<div class="feature-section col two-col" style="margin:0; padding: 0;">
					<div class="col col-1">
                        <div class="media-container"><img alt="{current_post}" src="<?php echo plugins_url( 'assets/images/screenshots/current_post.png', GRAVITYVIEW_FILE ); ?>" style="border: none"></div>
                        <h4 class="higher">New <code>{current_post}</code> Merge Tag</h4>
                        <p>It may not sound like much, but it's powerful! Use it together with the <code>[gvlogic]</code> shortcode to show or hide content based on the page your View is embedded on. Use with the Advanced Filter Extension.</p>
                        <p><a href="https://docs.gravityview.co/article/412-currentpost-merge-tag" class="button-primary button button-large">Learn more about using the Merge Tag</a></p>
                    </div>
                    <div class="col col-2">
                        <div class="media-container"><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gravityview&page=gravityview_settings' ) ); ?>"><img alt="Beta!" src="<?php echo plugins_url( 'assets/images/screenshots/beta-program.jpg', GRAVITYVIEW_FILE ); ?>" style="border: none"></a></div>
                        <h4 class="higher">Beta Program</h4>
                        <p>We have a new Beta Program that gives you access to the latest pre-release versions of GravityView. We&rsquo;ve got big updates coming, and by opting-in, you&rsquo;ll get access early.</p>
                        <p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gravityview&page=gravityview_settings' ) ); ?>" class="button button-primary button-large">Turn on &ldquo;Become a Beta Tester&rdquo; in Settings</a></p>
					</div>
				</div>

				<div class="headline-feature" style="max-width: 100%">
					<h2 style="border-bottom: 1px solid #ccc; padding-bottom: 1em; margin-bottom: 0;"><?php esc_html_e( 'What&rsquo;s New', 'gravityview' ); ?></h2>
				</div>

                <h3>1.22 on September 4, 2017</h3>

                <ul>
                    <li>Added: Support for Gravity Forms 2.3</li>
                    <li>Fixed: Inline Edit plugin not working when displaying a single entry</li>
                    <li>Fixed: Featured Entries plugin not adding correct CSS selector to the single entry container</li>
                </ul>

                <p><strong>Developer Updates:</strong></p>

                <ul>
                    <li>Modified: Template files <code>list-header.php</code>, <code>list-single.php</code>, <code>table-header.php</code>, <code>table-single.php</code></li>
                    <li>Fixed: When <code>GRAVITYVIEW_LICENSE_KEY</code> constant is defined, it will always be used, and the license field will be disabled</li>
                    <li>Fixed: List View and Table View templates have more standardized CSS selectors for single &amp; multiple contexts (<a href="http://docs.gravityview.co/article/63-css-guide">Learn more</a>)</li>
                    <li>Fixed: Permalink issue when embedding a View on a page, then making it the site&#39;s Front Page</li>
                    <li>Fixed: Transient cache issues when invalidating cache</li>
                    <li>Fixed: <code>gv_empty()</code> now returns false for an array with all empty values</li>
                    <li>Fixed: Delay plugin compatibility checks until <code>plugins_loaded</code></li>
                </ul>

                <h3>1.21.5.3 on July 24, 2017</h3>

                <ul>
                    <li>Fixed: For some field types, the value &quot;No&quot; would be interpreted as <code>false</code></li>
                    <li>Fixed: In Edit Entry, when editing a form that has a Post Custom Field field type—configured as checkboxes—file upload fields would not be saved</li>
                    <li>Fixed: If a form connected to a View is in the trash, there will be an error when editing the View</li>
                    <li>Fixed: Embedding single entries with WordPress 4.8</li>
                    <li>Fixed: Fatal error when using older version of WPML</li>
                </ul>


                <h3>1.21.5.2 on June 26, 2017</h3>

                <ul>
                    <li>Tweak: Improved plugin speed by reducing amount of information logged</li>
                    <li>Fixed: Duplicate descriptions on the settings screen</li>
                    <li>Fixed: Our &quot;No-Conflict Mode&quot; made the settings screen look bad. Yes, we recognize the irony.</li>
                    <li>Updated: Translations - thank you, translators!

                        <ul>
                            <li>Turkish translation by <a href="https://www.transifex.com/accounts/profile/suhakaralar/">@suhakaralar</a></li>
                            <li>Dutch translations by Thom</li>
                        </ul></li>
                </ul>

                <h3>1.21.5.1 on June 13, 2017</h3>

                <ul>
                    <li>Modified: We stopped allowing any HTML in Paragraph Text fields in 1.21.5, but this functionality was used by lots of people. We now use a different function to allow safe HTML by default.</li>
                    <li>Added: `gravityview/fields/textarea/allowed_kses` filter to modify the allowed HTML to be displayed.</li>
                </ul>

                
                <h3>1.21.5 on June 8, 2017</h3>

                <ul>
                    <li>Added: The <code>{current_post}</code> Merge Tag adds information about the current post. <a href="http://docs.gravityview.co/article/412-currentpost-merge-tag">Read more about it</a>.</li>
                    <li>Added: <code>gravityview/gvlogic/parse_atts/after</code> action to modify <code>[gvlogic]</code> shortcode attributes after it&#39;s been parsed</li>
                    <li>Added: A new setting to opt-in for access to the latest pre-release versions of GravityView (in <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gravityview&page=gravityview_settings' ) ); ?>">Views &gt; Settings)</a></li>
                    <li>Added: Support for Restrict Content Pro when in &quot;No-Conflict Mode&quot;</li>
                    <li>Fixed: Saving an entry could strip the entry creator information. Now, when the entry creator is not in the &quot;Change Entry Creator&quot; users list, we add them back in to the list.</li>
                    <li>Fixed: Potential security issue</li>
                    <li>Fixed: Multiple notifications could sometimes be sent when editing an entry in GravityView.</li>
                    <li>Fixed: Gravity Forms tooltip scripts being loaded admin-wide.</li>
                </ul>

                <h3>1.21.4 on April 13, 2017</h3>

                <ul>
                    <li>Fixed: &quot;Enable sorting by column&quot; not visible when using table-based View Presets</li>
                    <li>Fixed: Error activating the plugin when Gravity Forms is not active</li>
                    <li>Fixed: Numeric sorting</li>
                    <li>Fixed: Compatibility issue with WPML 3.6.1 and lower</li>
                    <li>Tweak: When using <code>?cache</code> to disable entries caching, cached data is removed</li>
                </ul>

                <h3>1.21.3 on April 4, 2017</h3>

                <ul>
                    <li>Fixed: Post Images stopped working in Edit Entry</li>
                    <li>Fixed: Conflict with our Social Sharing &amp; SEO Extension</li>
                    <li>Fixed: Unable to search for a value of <code>0</code></li>
                    <li>Fixed: Inaccurate search results when using the <code>search_field</code> and <code>search_value</code> settings in the <code>[gravityview]</code> shortcode

                        <ul>
                            <li>The search mode will now always be set to <code>all</code> when using these settings</li>
                        </ul></li>
                </ul>

                <p><strong>Developer Updates:</strong></p>

                <ul>
                    <li>We decided to not throw exceptions in the new <code>gravityview()</code> wrapper function. Instead, we will log errors via Gravity Forms logging.</li>
                </ul>


                <h3>1.21.2 on March 31, 2017</h3>

                <ul>
                    <li>Added: Support for embedding <code>[gravityview]</code> shortcodes in Advanced Custom Fields (ACF) fields</li>
                    <li>Fixed: PHP warnings and notices</li>
                </ul>

                
                <h3>1.21.1 on March 30, 2017</h3>

                <ul>
                    <li>Fixed: Advanced Filters no longer filtered 😕</li>
                    <li>Fixed: Fatal error when viewing Single Entry with a Single Entry Title setting that included Merge Tags</li>
                    <li>Fixed: Cache wasn&#39;t cleared when an entry was created using Gravity Forms API (thanks Steve with Gravity Flow!)</li>
                </ul>


                <h3>1.21 on March 29, 2017</h3>

                <ul>
                    <li>Fixed: Edit Entry compatibility with Gravity Forms 2.2</li>
                    <li>Fixed: Single Entry not accessible when filtering a View by Gravity Flow&#39;s &quot;Final Status&quot; field</li>
                    <li>Fixed: Needed to re-save permalink settings for Single Entry and Edit Entry to work</li>
                    <li>Fixed: Incorrect pagination calculations when passing <code>offset</code> via the <code>[gravityview]</code> shortcode</li>
                </ul>

                <p><strong>Developer Updates:</strong></p>

                <ul>
                    <li>Modified: <code>GVCommon::check_entry_display()</code> now returns WP_Error instead of <code>false</code> when an error occurs. This allows for additional information to be passed.</li>
                    <li>Added: <code>gravityview/search-all-split-words</code> filter to change search behavior for the &quot;Search All&quot; search input. Default (<code>true</code>) converts words separated by spaces into separate search terms. <code>false</code> will search whole word.</li>
                    <li>Much progress has been made on the <code>gravityview()</code> wrapper function behind the scenes. Getting closer to parity all the time.</li>
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
						<li class="wp-person">Spanish translation by <a href="https://www.transifex.com/accounts/profile/jorgepelaez/">@jorgepelaez</a>, <a href="https://www.transifex.com/accounts/profile/luisdiazvenero/">@luisdiazvenero</a>, <a href="https://www.transifex.com/accounts/profile/josemv/">@josemv</a>, <a href="https://www.transifex.com/accounts/profile/janolima/">@janolima</a> and <a href="https://www.transifex.com/accounts/profile/matrixmercury/">@matrixmercury</a></li>
						<li class="wp-person">Swedish translation by <a href="https://www.transifex.com/accounts/profile/adamrehal/">@adamrehal</a></li>
						<li class="wp-person">Indonesian translation by <a href="https://www.transifex.com/accounts/profile/sariyanta/">@sariyanta</a></li>
						<li class="wp-person">Norwegian translation by <a href="https://www.transifex.com/accounts/profile/aleksanderespegard/">@aleksanderespegard</a></li>
						<li class="wp-person">Danish translation by <a href="https://www.transifex.com/accounts/profile/jaegerbo/">@jaegerbo</a></li>
						<li class="wp-person">Chinese translation by <a href="https://www.transifex.com/user/profile/michaeledi/">@michaeledi</a></li>
						<li class="wp-person">Persian translation by <a href="https://www.transifex.com/user/profile/azadmojtaba/">@azadmojtaba</a></li>
						<li class="wp-person">Russian translation by <a href="https://www.transifex.com/user/profile/gkovaleff/">@gkovaleff</a></li>
						<li class="wp-person">Code contributions by <a href="https://github.com/ryanduff">@ryanduff</a>, <a href="https://github.com/dmlinn">@dmlinn</a>, <a href="https://github.com/mgratch">@mgratch</a>, and <a href="https://github.com/stevehenty">@stevehenty</a></li>
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
