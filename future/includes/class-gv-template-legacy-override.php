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
 * Loads legacy override templates from the theme.
 *
 * Makes sure they work by setting up context, etc.
 */
class Legacy_Override_Template extends \GV\Gamajo_Template_Loader {
	/**
	 * Prefix for filter names.
	 *
	 * @var string
	 */
	protected $filter_prefix = 'gravityview';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
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
	 * Catch deprecated template loads.
	 *
	 * @param \GV\View    $view The View.
	 * @param \GV\Entry   $entry The Entry.
	 * @param \GV\Field   $field The Field.
	 * @param \GV\Request $request The request.
	 *
	 * @return void
	 */
	public function __construct( \GV\View $view, \GV\Entry $entry = null, \GV\Field $field = null, \GV\Request $request = null ) {
		add_filter( $this->filter_prefix . '_get_template_part', array( $this, 'add_id_specific_templates' ), 10, 3 );

		$this->view  = $view;
		$this->entry = $entry;

		$this->plugin_directory          = gravityview()->plugin->dir();
		$this->plugin_template_directory = 'templates/deprecated/';
	}

	public function __destruct() {
		remove_filter( $this->filter_prefix . '_get_template_part', array( $this, 'add_id_specific_templates' ) );
	}

	/**
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
	 * @param array  $templates Existing list of templates.
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
		add_action(
			'gravityview/template/after',
			$view_id_output = function ( $context ) {
				printf( '<input type="hidden" class="gravityview-view-id" value="%d">', $context->view->ID );
			}
		);

		ob_start();

		$request                     = new Mock_Request();
		$request->returns['is_view'] = $this->view;

		/**
		 * You got one shot. One opportunity. To render all the widgets you have ever wanted.
		 *
		 * Since we're overriding the singleton we need to remove the widget actions since they can only
		 *  be called once in a request (did_action/do_action mutex).
		 *
		 * Oh, and Mom's spaghetti.
		 */
		global $wp_filter;
		foreach ( array( 'gravityview_before', 'gravityview_after' ) as $hook ) {
			foreach ( $wp_filter[ $hook ]->callbacks[10] as $function_key => $callback ) {
				if ( strpos( $function_key, 'render_widget_hooks' ) ) {
					unset( $wp_filter[ $hook ]->callbacks[10][ $function_key ] );
				}
			}
		}

		/**
		 * Single entry view.
		 */
		if ( $this->entry ) {

			$request->returns['is_entry'] = $this->entry;

			global $post;

			$entries = new Entry_Collection();
			$entries->add( $this->entry );

			\GV\Mocks\Legacy_Context::push(
				array(
					'view'        => $this->view,
					'entry'       => $this->entry,
					'entries'     => $entries,
					'request'     => $request,
					'fields'      => $this->view->fields->by_visible( $this->view ),
					'in_the_loop' => true,
				)
			);

			\GravityView_View::getInstance()->setTemplatePartSlug( $slug );
			\GravityView_View::getInstance()->setTemplatePartName( 'single' );

			\GravityView_View::getInstance()->_include( $this->get_template_part( $slug, 'single' ) );

			Mocks\Legacy_Context::pop();

			/**
			 * Directory view.
			 */
		} else {
			$entries = $this->view->get_entries( $request );

			/**
			 * Remove multiple sorting before calling legacy filters.
			 * This allows us to fake it till we make it.
			 */
			$parameters = $this->view->settings->as_atts();
			if ( ! empty( $parameters['sort_field'] ) && is_array( $parameters['sort_field'] ) ) {
				$has_multisort            = true;
				$parameters['sort_field'] = reset( $parameters['sort_field'] );
				if ( ! empty( $parameters['sort_direction'] ) && is_array( $parameters['sort_direction'] ) ) {
					$parameters['sort_direction'] = reset( $parameters['sort_direction'] );
				}
			}

			$parameters = \GravityView_frontend::get_view_entries_parameters( $parameters, $this->view->form->ID );

			global $post;

			add_action( 'gravityview_before', array( \GravityView_View::getInstance(), 'render_widget_hooks' ) );
			add_action( 'gravityview_after', array( \GravityView_View::getInstance(), 'render_widget_hooks' ) );

			foreach ( array( 'header', 'body', 'footer' ) as $part ) {
				\GV\Mocks\Legacy_Context::push(
					array_merge(
						array(
							'view'        => $this->view,
							'entries'     => $entries,
							'request'     => $request,
							'fields'      => $this->view->fields->by_visible( $this->view ),
							'in_the_loop' => true,
						),
						empty( $parameters ) ? array() : array(
							'paging'  => $parameters['paging'],
							'sorting' => $parameters['sorting'],
						),
						$post ? array(
							'post' => $post,
						) : array()
					)
				);

				\GravityView_View::getInstance()->setTemplatePartSlug( $slug );

				\GravityView_View::getInstance()->setTemplatePartName( $part );

				\GravityView_View::getInstance()->_include( $this->get_template_part( $slug, $part ) );

				Mocks\Legacy_Context::pop();
			}
		}

		remove_action( 'gravityview/template/after', $view_id_output );

		return ob_get_clean();
	}
}
