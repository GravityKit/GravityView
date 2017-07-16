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
if ( ! class_exists( '\Gamajo_Template_Loader' ) ) {
	require gravityview()->plugin->dir( 'future/lib/class-gamajo-template-loader.php' );
}

/**
 * Loads legacy override templates from the theme.
 *
 * Makes sure they work by setting up context, etc.
 */
class Legacy_Override_Template extends \Gamajo_Template_Loader {
	/**
	 * Prefix for filter names.
	 * @var string
	 */
	protected $filter_prefix = 'gravityview';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 * @var string
	 */
	protected $theme_template_directory = 'gravityview';

	/**
	 * @var \GV\View The view we're working with.
	 */
	private $view;

	/**
	 * @var \GV\Entry The entry we're working with.
	 */
	private $entry;

	/**
	 * Catch deprecated theme loads.
	 *
	 * @param \GV\View $view The View.
	 * @param \GV\Entry $entry The Entry.
	 * @param \GV\Field $field The Field.
	 * @param \GV\Request $request The request.
	 *
	 * @return void
	 */
	public function __construct( \GV\View $view, \GV\Entry $entry = null, \GV\Field $field = null, \GV\Request $request = null ) {
		add_filter( $this->filter_prefix . '_get_template_part', array( $this, 'add_id_specific_templates' ), 10, 3 );

		$this->view = $view;
		$this->entry = $entry;

		$this->plugin_directory = gravityview()->plugin->dir();
		$this->plugin_template_directory = 'templates/deprecated/';
	}

	/**
	 * In order to improve lookup times, we store located templates in a local array.
	 *
	 * This improves performance by up to 1/2 second on a 250 entry View with 7 columns showing
	 *
	 * @inheritdoc
	 * @see Gamajo_Template_Loader::locate_template()
	 * @return null|string NULL: Template not found; String: path to template
	 */
	public function locate_template( $template_names, $load = false, $require_once = false ) {
		return parent::locate_template( $template_names, false, false );
	}

	/**
	 * Enable overrides of GravityView templates on a granular basis
	 *
	 * The loading order is:
	 *
	 * - view-[View ID]-table-footer.php
	 * - form-[Form ID]-table-footer.php
	 * - page-[ID of post or page where view is embedded]-table-footer.php
	 * - table-footer.php
	 *
	 * @see  Gamajo_Template_Loader::get_template_file_names() Where the filter is
	 * @param array $templates Existing list of templates.
	 * @param string $slug      Name of the template base, example: `table`, `list`, `datatables`, `map`
	 * @param string $name      Name of the template part, example: `body`, `footer`, `head`, `single`
	 *
	 * @return array $templates Modified template array, merged with existing $templates values
	 */
	public function add_id_specific_templates( $templates, $slug, $name ) {

		$additional = array();

		// form-19-table-body.php
		$additional[] = sprintf( 'form-%d-%s-%s.php', $this->view->form ? $this->view->form->ID : 0, $slug, $name );

		// view-3-table-body.php
		$additional[] = sprintf( 'view-%d-%s-%s.php', $this->view->ID, $slug, $name );

		global $post;
		if ( $post ) {
			// page-19-table-body.php
			$additional[] = sprintf( 'page-%d-%s-%s.php', $post->ID, $slug, $name );
		}

		// Combine with existing table-body.php and table.php
		$templates = array_merge( $additional, $templates );

		return $templates;
	}

	/**
	 * Setup legacy rendering.
	 *
	 * @param string $slug The slug.
	 *
	 * @return string The output.
	 */
	public function render( $slug ) {
		ob_start();

		if ( \GVCommon::has_cap( array( 'edit_gravityviews', 'edit_gravityview' ), $this->view->ID ) ) {
			echo \GVCommon::generate_notice( 'We have detected some legacy template overrides in your theme\'s gravityview/ directory. We urge you to port them over to their 2.0 versions as soon as possible.' );
		}

		$request = new Mock_Request();
		$request->returns['is_view'] = $this->view;
		
		/**
		 * Single entry view.
		 */
		if ( $this->entry ) {

			$request->returns['is_entry'] = $this->entry;

			global $post;

			$entries = new Entry_Collection();
			$entries->add( $this->entry );

			\GV\Mocks\Legacy_Context::push( array(
				'view' => $this->view,
				'entry' => $this->entry,
				'entries' => $entries,
				'request' => $request,
				'fields' => $this->view->fields->by_visible(),
				'in_the_loop' => true,
			) );

			\GravityView_View::getInstance()->setTemplatePartSlug( $slug );
			\GravityView_View::getInstance()->setTemplatePartName( 'single' );
			\GravityView_View::getInstance()->_include( $this->get_template_part( $slug, 'single' ) );

			Mocks\Legacy_Context::pop();

		/**
		 * Directory view.
		 */
		} else {
			$entries = $this->view->get_entries( $request );

			$parameters = \GravityView_frontend::get_view_entries_parameters( $this->view->settings->as_atts(), $this->view->form->ID );

			global $post;

			\GV\Mocks\Legacy_Context::push( array_merge( array(
				'view' => $this->view,
				'entries' => $entries,
				'request' => $request,
				'fields' => $this->view->fields->by_visible(),
				'in_the_loop' => true,
			), empty( $parameters ) ? array() : array(
				'paging' => $parameters['paging'],
				'sorting' => $parameters['sorting'],
			), $post ? array() : array(
				'post' => $post,
			) ) );

			foreach ( array( 'header', 'body', 'footer' ) as $part ) {
				\GravityView_View::getInstance()->setTemplatePartSlug( $slug );
				\GravityView_View::getInstance()->setTemplatePartName( $part );
				\GravityView_View::getInstance()->_include( $this->get_template_part( $slug, $part ) );
			}

			Mocks\Legacy_Context::pop();

		}

		printf( '<input type="hidden" class="gravityview-view-id" value="%d">', $this->view->ID );
		return ob_get_clean();
	}
}
