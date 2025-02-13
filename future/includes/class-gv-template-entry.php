<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * Load up the Gamajo Template Loader.
 *
 * @see https://github.com/GaryJones/Gamajo-Template-Loader
 */
if ( ! class_exists( '\GV\Gamajo_Template_Loader' ) ) {
	require gravityview()->plugin->dir( 'future/lib/class-gamajo-template-loader.php' );
}

/**
 * The Entry Template class .
 *
 * Renders a \GV\Entry using a \GV\Entry_Renderer.
 */
abstract class Entry_Template extends Template {
	/**
	 * Prefix for filter names.
	 *
	 * @var string
	 */
	protected $filter_prefix = 'gravityview/template/entries';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
	 * @var string
	 */
	protected $theme_template_directory = 'gravityview/entries/';

	/**
	 * Directory name where the default templates for this plugin are found.
	 *
	 * @var string
	 */
	protected $plugin_template_directory = 'templates/entries/';

	/**
	 * @var \GV\Entry The entry that need to be rendered.
	 */
	public $entry;

	/**
	 * @var \GV\View The view context.
	 */
	public $view;

	/**
	 * @var \GV\Request The request context.
	 */
	public $request;

	/**
	 * @var string The template slug to be loaded (like "table", "list")
	 */
	public static $slug;

	/**
	 * Initializer.
	 *
	 * @param \GV\View             $view The View connected to this template.
	 * @param \GV\Entry_Collection $entries A collection of entries for this view.
	 * @param \GV\Request          $request The request context.
	 */
	public function __construct( Entry $entry, View $view, Request $request ) {
		$this->entry   = $entry;
		$this->view    = $view;
		$this->request = $request;

		/** Add granular overrides. */
		add_filter( $this->filter_prefix . '_get_template_part', array( $this, 'add_id_specific_templates' ), 10, 3 );

		parent::__construct();
	}

	public function __destruct() {
		remove_filter( $this->filter_prefix . '_get_template_part', array( $this, 'add_id_specific_templates' ) );
	}

	/**
	 * Enable granular template overrides based on current post, view, form, etc.
	 *
	 * The loading order is:
	 *
	 * - post-[ID of post or page where view is embedded]-view-[View ID]-entry-[Entry ID]-table-footer.php
	 * - post-[ID of post or page where view is embedded]-entry-[Entry ID]-table-footer.php
	 * - post-[ID of post or page where view is embedded]-view-[View ID]-table-footer.php
	 * - post-[ID of post or page where view is embedded]-table-footer.php
	 * - view-[View ID]-entry-[Entry ID]-table-footer.php
	 * - form-[Form ID]-entry-[Entry ID]-table-footer.php
	 * - view-[View ID]-table-footer.php
	 * - form-[Form ID]-table-footer.php
	 * - entry-[Entry ID]-table-footer.php
	 * - table-footer.php
	 *
	 * @see  Gamajo_Template_Loader::get_template_file_names() Where the filter is
	 * @param array  $templates Existing list of templates.
	 * @param string $slug      Name of the template base, example: `table`, `list`, `datatables`, `map`
	 * @param string $name      Name of the template part, example: `body`, `footer`, `head`, `single`
	 *
	 * @return array $templates Modified template array, merged with existing $templates values
	 */
	public function add_id_specific_templates( $templates, $slug, $name ) {

		$specifics = array();

		list( $slug_dir, $slug_name ) = self::split_slug( $slug, $name );

		global $post;

		if ( ! $this->request->is_view( false ) && $post ) {
			$specifics [] = sprintf( '%spost-%d-view-%d-entry-%d-%s.php', $slug_dir, $post->ID, $this->view->ID, $this->entry->ID, $slug_name );
			$specifics [] = sprintf( '%spost-%d-entry-%d-%s.php', $slug_dir, $post->ID, $this->entry->ID, $slug_name );
			$specifics [] = sprintf( '%spost-%d-view-%d-%s.php', $slug_dir, $post->ID, $this->view->ID, $slug_name );
			$specifics [] = sprintf( '%spost-%d-%s.php', $slug_dir, $post->ID, $slug_name );
		}

		$specifics [] = sprintf( '%sview-%d-entry-%d-%s.php', $slug_dir, $this->view->ID, $this->entry->ID, $slug_name );
		$specifics [] = sprintf( '%sform-%d-entry-%d-%s.php', $slug_dir, $this->view->form->ID, $this->entry->ID, $slug_name );
		$specifics [] = sprintf( '%sview-%d-%s.php', $slug_dir, $this->view->ID, $slug_name );
		$specifics [] = sprintf( '%sform-%d-%s.php', $slug_dir, $this->view->form->ID, $slug_name );

		$specifics [] = sprintf( '%sentry-%d-%s.php', $slug_dir, $this->entry->ID, $slug_name );

		return array_merge( $specifics, $templates );
	}

	/**
	 * Output some HTML.
	 *
	 * @return void
	 */
	public function render() {
		$context = Template_Context::from_template( $this );

		/**
		 * Make various pieces of data available to the template
		 *  under the $gravityview scoped variable.
		 *
		 * @filter `gravityview/template/entry/context`
		 * @param \GV\Template_Context $context The context for this template.
		 * @param \GV\Entry_Template $template The current template.
		 * @since 2.0
		 */
		$this->push_template_data( apply_filters( 'gravityview/template/entry/context', $context, $this ), 'gravityview' );

		/** Load the template. */
		$this->get_template_part( static::$slug );
		$this->pop_template_data( 'gravityview' );
	}

	/**
	 * Fetch the back link label for an entry context.
	 *
	 * @param boolean $do_replace Whether to perform merge tag replacements, etc.
	 *
	 * @see `gravityview/template/links/back/label` filter
	 *
	 * @return string The link label
	 */
	public function get_back_label( $do_replace = true ) {
		if ( ! $this->view ) {
			return '';
		}

		$back_link_label = $this->view->settings->get( 'back_link_label', null );

		if ( $do_replace ) {
			$back_link_label = \GravityView_API::replace_variables( $back_link_label, Utils::get( $this->view, 'form/form' ), $this->entry->as_entry() );
			return do_shortcode( $back_link_label );
		}
		return $back_link_label;
	}
}

/** Load implementations. */
require gravityview()->plugin->dir( 'future/includes/class-gv-template-entry-table.php' );
require gravityview()->plugin->dir( 'future/includes/class-gv-template-entry-list.php' );
require gravityview()->plugin->dir( 'future/includes/class-gv-template-entry-layout-builder.php' );
