<?php
/**
 * Welcome Page Class
 *
 * @package   GravityView
 * @author    Zack Katz <zack@gravitykit.com>
 * @link      https://www.gravitykit.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		add_action( 'gk/foundation/initialized', array( $this, 'admin_menus' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'welcome' ) );
		add_filter( 'gravityview_is_admin_page', array( $this, 'is_dashboard_page' ), 10, 2 );
	}

	/**
	 * Register the Dashboard Pages which are later hidden but these pages
	 * are used to render the Welcome pages.
	 *
	 * @since 1.0
	 *
	 * @return void
	 *
	 * @param \GravityKit\GravityView\Foundation\Core|GravityKitFoundation $foundation
	 */
	public function admin_menus( $foundation ) {
		if ( $foundation::helpers()->core->is_network_admin() ) {
			return;
		}

		/** @var \GravityKit\GravityView\Foundation\WP\AdminMenu $admin_menu */
		$admin_menu = $foundation::admin_menu();

		// Changelog Page
		$admin_menu::add_submenu_item(
			array(
				'id'         => 'gv-changelog',
				'page_title' => __( 'Changelog', 'gk-gravityview' ),
				'menu_title' => __( 'Changelog', 'gk-gravityview' ),
				'capability' => $this->minimum_capability,
				'callback'   => array( $this, 'changelog_screen' ),
				'order'      => 40,
				'hide'       => true,
			),
			'center'
		);

		// Changelog Page
		$admin_menu::add_submenu_item(
			array(
				'id'         => 'gv-credits',
				'page_title' => __( 'Credits', 'gk-gravityview' ),
				'menu_title' => __( 'Credits', 'gk-gravityview' ),
				'capability' => $this->minimum_capability,
				'callback'   => array( $this, 'credits_screen' ),
				'order'      => 50,
				'hide'       => true,
			),
			'center'
		);

		// Add Getting Started page to GravityView menu
		$admin_menu::add_submenu_item(
			array(
				'id'                                 => 'gv-getting-started',
				'page_title'                         => __( 'GravityView: Getting Started', 'gk-gravityview' ),
				'menu_title'                         => __( 'Getting Started', 'gk-gravityview' ),
				'capability'                         => $this->minimum_capability,
				'callback'                           => array( $this, 'getting_started_screen' ),
				'order'                              => 60, // Make it the last so that the border divider remains
				'exclude_from_top_level_menu_action' => true,
			),
			'center'
		);
	}

	/**
	 * Is this page a GV dashboard page?
	 *
	 * @return boolean  $is_page   True: yep; false: nope
	 */
	public function is_dashboard_page( $is_page = false, $hook = null ) {
		global $pagenow;

		if ( empty( $_GET['page'] ) ) {
			return $is_page;
		}

		if ( ! $pagenow ) {
			return $is_page;
		}

		return 'admin.php' === $pagenow && in_array( $_GET['page'], array( 'gv-changelog', 'gv-credits', 'gv-getting-started' ), true );
	}

	/**
	 * Hide Individual Dashboard Pages
	 *
	 * @since 1.0
	 * @return void
	 */
	public function admin_head() {
		if ( ! $this->is_dashboard_page() ) {
			return;
		}

		?>
		<style>
		.update-nag { display: none; }
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
		list( $display_version ) = explode( '-', GV_PLUGIN_VERSION );

		$selected = ! empty( $plugin_page ) ? $plugin_page : 'gv-getting-started';

		echo gravityview_get_floaty( 132 );
		?>

		<h1><?php printf( esc_html__( 'Welcome to GravityView %s', 'gk-gravityview' ), $display_version ); ?></h1>
		<div class="about-text"><?php esc_html_e( 'Thank you for installing GravityView. Beautifully display your Gravity Forms entries.', 'gk-gravityview' ); ?></div>

		<h2 class="nav-tab-wrapper clear">
			<a class="nav-tab <?php echo 'gv-getting-started' == $selected ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-getting-started' ), 'admin.php' ) ) ); ?>">
				<?php esc_html_e( 'Getting Started', 'gk-gravityview' ); ?>
			</a>
			<a class="nav-tab <?php echo 'gv-changelog' == $selected ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-changelog' ), 'admin.php' ) ) ); ?>">
				<?php esc_html_e( 'List of Changes', 'gk-gravityview' ); ?>
			</a>
			<a class="nav-tab <?php echo 'gv-credits' == $selected ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-credits' ), 'admin.php' ) ) ); ?>">
				<?php esc_html_e( 'Credits', 'gk-gravityview' ); ?>
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
				<iframe width='560' height='315'
						src='https://www.youtube-nocookie.com/embed/videoseries?list=PLuSpaefk_eAP_OXQVWQVtX0fQ17J8cn09'
						frameborder='0'
						allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share'
						allowfullscreen></iframe>

				<p style="text-align:center; padding-top: 1em;"><a class="button button-primary button-hero" href="https://docs.gravitykit.com/article/380-how-to-setup-your-first-view" rel="noopener noreferrer external" target="_blank">Read more: Setting Up Your First View<span class='screen-reader-text'> <?php esc_attr_e( 'This link opens in a new window.', 'gk-gravityview' ); ?></span></a></p>
			</div>

			<div class="feature-section two-col has-2-columns is-fullwidth">
				<div class="col column">
					<h3>Create a View</h3>

					<ol class="ol-decimal">
						<li>Go to the GravityKit menu and click on <a href="<?php echo admin_url( 'post-new.php?post_type=gravityview' ); ?>">New View</a></li>
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
					<p>Embed Views using the "Add Shortcode" button above your content editor. <a href="https://docs.gravitykit.com/article/73-using-the-shortcode">Learn how to use the <code>[gravityview]</code> shortcode.</a></p>
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
					<h2 style="border-bottom: 1px solid #ccc; padding-bottom: 1em; margin-bottom: 0; margin-top: 0"><?php esc_html_e( 'What&rsquo;s New', 'gk-gravityview' ); ?></h2>
				</div>

				<?php
				/**
				 * Include changelog entries for two MINOR versions. Prune beyond that.
				 *
				 * Examples:
				 *  - If 4.28.3, include to 4.26.
				 *  - If 4.28, include to 4.26.
				 */
				?>
				<h3>2.31.1 on November 8, 2024</h3>

				<p>This hotfix release resolves display issues with certain View layouts.</p>

				<h4>🐛 Fixed</h4>

				<ul>
					<li>Rendering issue affecting certain View layouts, such as Maps, introduced in the previous release.</li>
				</ul>

				<h3>2.31 on November 4, 2024</h3>

				<p>This release introduces <a href="https://docs.gravitykit.com/article/1027-dynamic-widget-placement?utm_source=gravityview&utm_medium=changelog&utm_campaign=release">flexible widget positioning</a> in Views, enhances entry-in-a-lightbox functionality, and adds support for the Gravity Forms 2.9+ Image Choice field. It also addresses compatibility issues with LiteSpeed, Divi, and LifterLMS, along with various other fixes and improvements.</p>

				<h4>🚀 Added</h4>

				<ul>
					<li>Ability to position widgets in the View editor using predefined layouts, offering a range of single or multi-column configurations with varying widths.</li>
					<li>View setting to control what happens when a user clicks the Cancel link when editing an entry in the lightbox.</li>
					<li>Support for the upcoming Image Choice field in Gravity Forms 2.9+.</li>
				</ul>

				<h4>🐛 Fixed</h4>

				<ul>
					<li>GravityView tab not displaying in certain cases under GravityKit > Settings menu.</li>
					<li>Widgets could not be configured after being added to a new, unsaved View.</li>
					<li>Compatibility with the Divi theme that prevented the Signature field from being edited on the Edit Entry screen.</li>
					<li>Conflict with the LiteSpeed plugin that caused a fatal error when redirecting users after duplicating an entry.</li>
					<li>JavaScript enqueued in the site's footer was not executed when editing an entry in the lightbox.</li>
					<li>It was not possible to add new entry notes when viewing a single entry in the lightbox.</li>
					<li>Validation error displayed when adding merge tags to the Entry Slug setting input in the View editor.</li>
					<li>The search box in the Change Entry Creator field did not return results when editing an entry on the Forms > Entries screen.</li>
					<li>Fatal error when activating LifterLMS with GravityView active.</li>
					<li>Searching across all fields not working as expected when the search value contains special characters or accents (e.g., ä, ß, İ).</li>
				</ul>

				<h4>🔧 Updated</h4>

				<ul>
					<li><a href="https://www.gravitykit.com/foundation/">Foundation</a> to version 1.2.20.</li>
				</ul>

				<h4>💻 Developer Updates</h4>

				<ul>
					<li>Added <code>gk/gravityview/lightbox/entry/before-output</code> action that fires before the entry content is output in the lightbox.</li>
					<li>Added <code>gk/gravityview/lightbox/entry/output/head-before</code> action that fires after the <code>&lt;head&gt;</code> tag is opened.</li>
					<li>Added <code>gk/gravityview/lightbox/entry/output/head-after</code> action that fires before the <code>&lt;/head&gt;</code> tag is closed.</li>
					<li>Added <code>gk/gravityview/lightbox/entry/output/content-before</code> action that fires after the <code>&lt;body&gt;</code> tag is opened and before the content is rendered.</li>
					<li>Added <code>gk/gravityview/lightbox/entry/output/content-after</code> action that fires after the content is rendered and before the footer.</li>
					<li>Added <code>gk/gravityview/lightbox/entry/output/footer-after</code> action that fires after the footer and before the closing <code>&lt;/body&gt;</code> tag.</li>
					<li>Added <code>gravityview/fields/image_choice/image_markup</code> filter to modify the Image Choice field (Gravity Forms 2.9+) markup.</li>
					<li>Added <code>gravityview/fields/image_choice/output_label</code> filter to control whether to display the value or label of an Image Choice field.</li>
				</ul>

				<h3>2.30.1 on October 15, 2024</h3>

				<p>This hotfix release resolves an issue with the Multiple Forms extension.</p>

				<h4>🐛 Fixed</h4>

				<ul>
					<li>Fatal error in the View editor when using joined data from multiple forms.</li>
				</ul>

				<h3>2.30 on October 14, 2024</h3>

				<p>This release adds the ability to change the entry creator from the Edit Entry screen, improves upload handling, fixes various bugs, and updates internal components.</p>

				<h4>🚀 Added</h4>

				<ul>
					<li>Entry creator can now be changed from the Edit Entry screen.</li>
					<li><code>{now}</code>, <code>{yesterday}</code>, and <code>{tomorrow}</code> relative date merge tags.</li>
				</ul>

				<h4>✨ Improved</h4>

				<ul>
					<li>Handling of multi-file uploads on the Edit Entry screen.</li>
				</ul>

				<h4>🐛 Fixed</h4>

				<ul>
					<li>Entry loading inside a lightbox did not work in some cases when BuddyPress was active.</li>
					<li>Resending notifications from the Entries screen did not work when sending to all entries filtered by approval status.</li>
					<li>Conflict with the Wordfence plugin caused a fatal error when redirecting users after deleting an entry.</li>
					<li>Fatal error when rendering a GravityView View field with a non-existent View ID.</li>
					<li>Survey field (Rating type) values were displayed in reverse order when a View was embedded inside another View.</li>
					<li>Unexpected scrolling in the View editor after adding a field.</li>
					<li>PHP notice when rendering a View with a field associated with an inactive add-on.</li>
					<li>Entry duplication not working on the Entries page.</li>
				</ul>

				<h4>🔧 Updated</h4>

				<ul>
					<li><a href="https://www.gravitykit.com/foundation/">Foundation</a> to version 1.2.19.</li>
				</ul>

				<h3>2.29 on October 1, 2024</h3>

				<p>This release introduces a much-requested <a href="https://docs.gravitykit.com/article/1020-opening-and-editing-entry-details-in-a-lightbox-modal-popup">lightbox feature</a> for displaying and editing entries, settings for customizing View URLs, new options for <a href="https://docs.gravitykit.com/article/1021-show-name-fields-as-initials">displaying Name field initials</a> and Custom Content fields in full width, and a merge tag modifier to show date field values in a human-readable format. Several bugs have also been fixed.</p>

				<h4>🚀 Added</h4>

				<ul>
					<li>Ability to edit and display entries inside a lightbox.</li>
					<li>Global and individual View settings to customize the URL structure for all or specific Views.</li>
					<li><code>:human</code> merge tag modifier for date fields to display in human-readable format (e.g., <em>10 minutes ago</em>, <em>5 days from now</em>).</li>
					<li>Option to display the Name field value as initials.</li>
					<li>Option to display Custom Content field full width on the Single Entry screen.</li>
				</ul>

				<h4>🐛 Fixed</h4>

				<ul>
					<li>Clearing search removed all URL query parameters and, in some cases, redirected to the homepage.</li>
					<li>Searching the View added duplicate search parameters to the URL.</li>
					<li>PHP 8.2 deprecation notice related to dynamic property creation.</li>
					<li>Entries not displaying when a View using DataTables was embedded in a Single Entry page with the List layout.</li>
					<li>PHP warning when displaying a View with an Event field without an active Gravity Forms Event Fields Add-On.</li>
					<li>Sorting entries in random order was not working.</li>
					<li>Multi Select field values starting with a square bracket were not displayed as selected on the Edit Entry screen.</li>
				</ul>

				<h4>🔧 Updated</h4>

				<ul>
					<li><a href="https://www.gravitykit.com/foundation/">Foundation</a> to version 1.2.18.</li>
				</ul>

				<h4>💻 Developer Updates</h4>

				<ul>
					<li>Added <code>gk/gravityview/field/name/display</code> filter to modify the Name field display value.</li>
					<li>Added <code>gk/gravityview/permalinks/reserved-terms</code> filter to modify the list of reserved terms that are excluded from permalinks.</li>
				</ul>

				<p style="text-align: center;">
					<a href="https://www.gravitykit.com/changelog/" class="aligncenter button button-primary button-hero" style="margin: 0 auto; display: inline-block; text-transform: capitalize"><?php esc_html_e( 'View change history', 'gk-gravityview' ); ?></a>
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
	public function credits_screen() {

		?>
		<div class="wrap about-wrap">

			<?php $this->tabs(); ?>

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
				.col h4 {
					font-weight: 400;
					margin-top: 0;
				}
				.cols .col p img {
					float: left;
					margin: 0 15px 10px 0;
					max-width: 200px;
					border-radius: 20px;
				}
			</style>

			<h2><?php _e( 'GravityView is brought to you by:', 'gk-gravityview' ); ?></h2>

			<div class="cols">

				<div class="col">
					<h3>Zack Katz <a href="https://x.com/zackkatz"><span class="dashicons dashicons-twitter" title="Follow Zack on X"></span></a> <a href="https://katz.co" title="View Zack&rsquo;s website"><span class="dashicons dashicons-admin-site"></span></a></h3>
					<h4>Project Lead &amp; Developer</h4>
					<p><img alt="Zack Katz" src="<?php echo plugins_url( 'assets/images/team/Zack.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Zack has been developing WordPress plugins since 2008 and has been a huge Gravity Forms fan from the start. Zack is co-owner of GravityKit and he lives with his wife in Leverett, Massachusetts. He can&rsquo;t wait for the next episode of <a href="https://atp.fm">ATP</a> or <a href="https://www.flophousepodcast.com">The Flop House</a> podcasts.</p>
				</div>

				<div class="col">
					<h3>Rafael Ehlers <a href="https://twitter.com/rafaehlers" title="Follow Rafael on Twitter"><span class="dashicons dashicons-twitter"></span></a> <a href="https://heropress.com/essays/journey-resilience/" title="View Rafael&rsquo;s WordPress Journey"><span class="dashicons dashicons-admin-site"></span></a></h3>
					<h4>Project Manager, Support Lead &amp; Customer&nbsp;Advocate</h4>
					<p><img alt="Rafael Ehlers"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/team/Ehlers.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Rafael helps guide GravityKit development priorities and keep us on track. He&rsquo;s the face of our customer support and helps customers get the most out of the product. Rafael hails from <a href="https://wikipedia.org/wiki/Porto_Alegre">Porto Alegre, Brazil</a>.</p>
				</div>

				<div class="col">
					<h3>Vlad K.</h3>
					<h4>Head of Development</h4>
					<p><img alt="Vlad K."  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/team/Vlad.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Vlad is GravityKit&rsquo;s lead developer. Known for his versatility, Vlad handles both front-end and back-end programming, as well as testing and DevOps. He lives in Ottawa, Canada, and frequently travels the world in pursuit of unique experiences that fuel his creativity and broaden his worldview.</p>
				</div>

				<div class="col">
					<h3>Rafael Bennemann <a href="https://x.com/rafaelbe" title="Follow Rafael on X"><span class="dashicons dashicons-twitter"></span></a></h3>
					<h4>Support Specialist</h4>
					<p><img alt="Rafael Bennemann"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/team/Bennemann.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Rafael dedicated most of his adult life to helping people and companies take their ideas to the web, first as a developer and now as a Customer Advocate at GravityKit. He will do his best to help you too, all the while sipping a <a href="https://en.wikipedia.org/wiki/Spritz_Veneziano">Spritz Veneziano</a> in Northern Italy, where he currently lives with his family.</p>
				</div>

				<div class='col'>
					<h3>Casey Burridge</h3>
					<h4 style='font-weight:0; margin-top:0'>Content Creator</h4>
					<p><img alt="Casey Burridge" class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/team/Casey.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94"/>Casey is GravityKit&rsquo;s resident content creator. He&rsquo;s been a WordPress lover ever since launching his first blog more than 6 years ago. Casey has lived and worked in London and Beijing, but feels most at home in Cape Town, South Africa, where he&rsquo;s originally from.</p>
				</div>

				<div class='col'>
					<h3>Doeke Norg <a href="https://x.com/doekenorg" title="Follow Doeke on X"><span class="dashicons dashicons-twitter"></span></a> <a href="https://doeken.org/" title="View Doeke&rsquo;s website"><span class="dashicons dashicons-admin-site"></span></a></h3>
					<h4 style='font-weight:0; margin-top:0'>Senior Developer</h4>
					<p>With almost 20 years of experience in PHP, there are few things Doeke doesn&rsquo;t know about our favourite programming language. He lives with his family in The Netherlands, and spends his time designing elaborate, but maintainable code. He also writes a blog about software design in PHP.</p>
				</div>
			</div>

			<hr class="clear" />

			<div class="feature-section">
				<h2><?php esc_attr_e( 'Contributors', 'gk-gravityview' ); ?></h2>

				<h4>Development</h4>
				<ul class="ul-disc">
					<li>Core &amp; Add-On development by <a href='https://mrcasual.com' class='block'>Vlad K.</a>, <a href='https://malayladu.com' class='block'>Malay Ladu</a>, <a href='https://katz.co' class='block'>Zack Katz</a>, <a href="https://codeseekah.com" class="block">Gennady Kovshenin</a>, <a href='https://tinygod.pt' class='block'>Luis Godinho</a></li>
					<li>Code contributions by <a href="https://github.com/ryanduff">@ryanduff</a>, <a href="https://github.com/dmlinn">@dmlinn</a>, <a href="https://github.com/mgratch">@mgratch</a>, <a href="https://github.com/ViewFromTheBox">@ViewFromTheBox</a>, <a href="https://github.com/stevehenty">@stevehenty</a>, <a href="https://github.com/naomicbush">@naomicbush</a>, <a href='https://github.com/mrcasual'>@mrcasual</a> and <a href="https://github.com/rafaehlers">@rafaehlers</a></li>
					<li>Accessibility contributions by <a href="https://github.com/RianRietveld">@RianRietveld</a></li>
				</ul>

				<h4>Translations</h4>
				<ul class="ul-disc">
					<li>Bengali translation by <a href="https://www.transifex.com/accounts/profile/tareqhi/">@tareqhi</a></li>
					<li>German translation by <a href="https://www.transifex.com/user/profile/hubert123456/">@hubert123456</a>, <a href="https://www.transifex.com/accounts/profile/seschwarz/">@seschwarz</a>, <a href="https://www.transifex.com/accounts/profile/abdmc/">@abdmc</a>, <a href="https://www.transifex.com/accounts/profile/deckerweb/">@deckerweb</a></li>
					<li>Turkish translation by <a href="https://www.transifex.com/accounts/profile/suhakaralar/">@suhakaralar</a></li>
					<li>Dutch translation by <a href="https://www.transifex.com/accounts/profile/leooosterloo/">@leooosterloo</a>, <a href="https://www.transifex.com/accounts/profile/Weergeven/">@Weergeven</a>, and <a href="https://www.transifex.com/accounts/profile/erikvanbeek/">@erikvanbeek</a>, and <a href="https://www.transifex.com/user/profile/SilverXp/">Thom (@SilverXp)</a></li>
					<li>Hungarian translation by <a href="https://www.transifex.com/accounts/profile/dbalage/">@dbalage</a> and <a href="https://www.transifex.com/accounts/profile/Darqebus/">@Darqebus</a></li>
					<li>Italian translation by <a href="https://www.transifex.com/accounts/profile/Lurtz/">@Lurtz</a> and <a href="https://www.transifex.com/accounts/profile/ClaraDiGennaro/">@ClaraDiGennaro</a></li>
					<li>French translation by <a href="https://www.transifex.com/accounts/profile/franckt/">@franckt</a> and <a href="https://www.transifex.com/accounts/profile/Newbdev/">@Newbdev</a></li>
					<li>Portuguese translation by <a href="https://www.transifex.com/accounts/profile/luistinygod/">@luistinygod</a>, <a href="https://www.transifex.com/accounts/profile/marlosvinicius.info/">@marlosvinicius</a>, and <a href="https://www.transifex.com/user/profile/rafaehlers/">@rafaehlers</a></li>
					<li>Romanian translation by <a href="https://www.transifex.com/accounts/profile/ArianServ/">@ArianServ</a></li>
					<li>Finnish translation by <a href="https://www.transifex.com/accounts/profile/harjuja/">@harjuja</a></li>
					<li>Spanish translation by <a href="https://www.transifex.com/accounts/profile/jorgepelaez/">@jorgepelaez</a>, <a href="https://www.transifex.com/accounts/profile/luisdiazvenero/">@luisdiazvenero</a>, <a href="https://www.transifex.com/accounts/profile/josemv/">@josemv</a>, <a href="https://www.transifex.com/accounts/profile/janolima/">@janolima</a> and <a href="https://www.transifex.com/accounts/profile/matrixmercury/">@matrixmercury</a>, <a href="https://www.transifex.com/user/profile/jplobaton/">@jplobaton</a></li>
					<li>Swedish translation by <a href="https://www.transifex.com/accounts/profile/adamrehal/">@adamrehal</a></li>
					<li>Indonesian translation by <a href="https://www.transifex.com/accounts/profile/sariyanta/">@sariyanta</a></li>
					<li>Norwegian translation by <a href="https://www.transifex.com/accounts/profile/aleksanderespegard/">@aleksanderespegard</a></li>
					<li>Danish translation by <a href="https://www.transifex.com/accounts/profile/jaegerbo/">@jaegerbo</a></li>
					<li>Chinese translation by <a href="https://www.transifex.com/user/profile/michaeledi/">@michaeledi</a></li>
					<li>Persian translation by <a href="https://www.transifex.com/user/profile/azadmojtaba/">@azadmojtaba</a>, <a href="https://www.transifex.com/user/profile/amirbe/">@amirbe</a>, <a href="https://www.transifex.com/user/profile/Moein.Rm/">@Moein.Rm</a></li>
					<li>Russian translation by <a href="https://www.transifex.com/user/profile/gkovaleff/">@gkovaleff</a>, <a href="https://www.transifex.com/user/profile/awsswa59/">@awsswa59</a></li>
					<li>Polish translation by <a href="https://www.transifex.com/user/profile/dariusz.zielonka/">@dariusz.zielonka</a></li>
				</ul>

				<h3><?php esc_attr_e( 'Want to contribute?', 'gk-gravityview' ); ?></h3>
				<p><?php printf( esc_attr__( 'If you want to contribute to the code, %1$syou can on Github%2$s. If your contributions are accepted, you will be thanked here.', 'gk-gravityview' ), '<a href="https://github.com/gravityview/GravityView">', '</a>' ); ?></p>
			</div>

			<hr class="clear" />

			<div class="changelog">

				<h3>Thanks to the following open-source software:</h3>

				<ul class="ul-disc">
					<li><a href="https://datatables.net/">DataTables</a> - amazing tool for table data display. Many thanks!</li>
					<li><a href="https://github.com/10up/flexibility">Flexibility</a> - Adds support for CSS flexbox to Internet Explorer 8 &amp; 9</li>
					<li><a href="https://github.com/GaryJones/Gamajo-Template-Loader">Gamajo Template Loader</a> - makes it easy to load template files with user overrides</li>
					<li><a href="https://github.com/carhartl/jquery-cookie">jQuery Cookie plugin</a> - Access and store cookie values with jQuery</li>
					<li><a href="https://www.gravitykit.com/gravityforms">Gravity Forms</a> - If Gravity Forms weren't such a great plugin, GravityView wouldn't exist!</li>
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
		if ( 'plugin-editor.php' === $plugin_page ) {
			return; }

		// Bail if no activation redirect
		if ( ! get_transient( '_gv_activation_redirect' ) ) {
			return;
		}

		if ( ( $_GET['page'] ?? '' ) === GravityKit\GravityView\Foundation\Licenses\Framework::ID ) {
			return;
		}

		// Delete the redirect transient
		delete_transient( '_gv_activation_redirect' );

		$upgrade = get_option( 'gv_version_upgraded_from' );

		// Don't do anything if they've already seen the new version info
		if ( GV_PLUGIN_VERSION === $upgrade ) {
			return;
		}

		// Add "Upgraded From" Option
		update_option( 'gv_version_upgraded_from', GV_PLUGIN_VERSION );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return; }

		// First time install
		if ( ! $upgrade ) {
			wp_safe_redirect( admin_url( 'admin.php?page=gv-getting-started' ) );
			exit;
		}
		// Update
		else {
			wp_safe_redirect( admin_url( 'admin.php?page=gv-changelog' ) );
			exit;
		}
	}
}
new GravityView_Welcome();
