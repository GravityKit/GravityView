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
if ( ! class_exists( 'Gamajo_Template_Loader' ) ) {
	require gravityview()->plugin->dir( 'future/lib/class-gamajo-template-loader.php' );
}

/**
 * The Field Template class.
 *
 * Attached to a \GV\Field and used by a \GV\Field_Renderer.
 */
abstract class Field_Template extends Template {
	/**
	 * @var string The template directory.
	 */
	protected $plugin_template_directory = 'templatesdfj/';

	/**
	 * @var \GV\Field The field connected to this template.
	 */
	public $field;

	/**
	 * @var \GV\View The view context.
	 */
	public $view;

	/**
	 * @var \GV\Source The source context.
	 */
	public $source;

	/**
	 * @var \GV\Entry The entry context.
	 */
	public $entry;

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
	 * @param \GV\Field $field The field about to be rendered.
	 * @param \GV\View $view The view in this context, if applicable.
	 * @param \GV\Source $source The source (form) in this context, if applicable.
	 * @param \GV\Entry $entry The entry in this context, if applicable.
	 * @param \GV\Request $request The request in this context, if applicable.
	 */
	public function __construct( Field $field, View $view = null, Source $source = null, Entry $entry = null, Request $request = null ) {
		$this->field = $field;
		$this->view = $view;
		$this->source = $source;
		$this->entry = $entry;
		$this->request = $request;

		/** Add granular overrides. */
		add_filter( $this->filter_prefix . '_get_template_part', array( $this, 'add_id_specific_templates' ), 10, 3 );

		// parent::__construct();
	}

	/**
	 * Enable granular template overrides based on current post, view, form, field types, etc.
	 *
	 * @see  Gamajo_Template_Loader::get_template_file_names() Where the filter is
	 * @param array $templates Existing list of templates.
	 * @param string $slug      Name of the template base, example: `table`, `list`, `datatables`, `map`
	 * @param string $name      Name of the template part, example: `body`, `footer`, `head`, `single`
	 *
	 * @return array $templates Modified template array, merged with existing $templates values
	 */
	public function add_id_specific_templates( $templates, $slug, $name ) {

		$specifics = array();

		list( $slug_dir, $slug_name ) = self::split_slug( $slug, $name );

		global $post;

		if ( ! $this->request->is_view() && $post ) {
			if ( $this->field && $this->field->type ) {
				$specifics []= sprintf( '%spost-%d-view-%d-field-%s-%s', $slug_dir, $post->ID, $this->view->ID, $this->field->type, $slug_name );
				$specifics []= sprintf( '%spost-%d-view-%d-field-%s', $slug_dir, $post->ID, $this->view->ID, $this->field->type );
				$specifics []= sprintf( '%spost-%d-field-%s-%s', $slug_dir, $post->ID, $this->field->type, $slug_name );
				$specifics []= sprintf( '%spost-%d-field-%s', $slug_dir, $post->ID, $this->field->type );
			}

			$specifics []= sprintf( '%spost-%d-view-%d-field-%s', $slug_dir, $post->ID, $this->view->ID, $slug_name );
			$specifics []= sprintf( '%spost-%d-view-%d-field', $slug_dir, $post->ID, $this->view->ID );
			$specifics []= sprintf( '%spost-%d-field-%s', $slug_dir, $post->ID, $slug_name );
			$specifics []= sprintf( '%spost-%d-field', $slug_dir, $post->ID );
		}
		
		/** Field-specific */
		if ( $this->field ) {

			if ( $this->field->ID ) {
				$specifics []= sprintf( '%sform-%d-field-%d-%s', $slug_dir, $this->view->form->ID, $this->field->ID, $slug_name );
				$specifics []= sprintf( '%sform-%d-field-%d', $slug_dir, $this->view->form->ID, $this->field->ID );
			}

			if ( $this->field->type ) {
				$specifics []= sprintf( '%sform-%d-field-%s-%s', $slug_dir, $this->view->form->ID, $this->field->type, $slug_name );
				$specifics []= sprintf( '%sform-%d-field-%s', $slug_dir, $this->view->form->ID, $this->field->type );

				$specifics []= sprintf( '%sview-%d-field-%s-%s', $slug_dir, $this->view->ID, $this->field->type, $slug_name );
				$specifics []= sprintf( '%sview-%d-field-%s', $slug_dir, $this->view->ID, $this->field->type );

				$specifics []= sprintf( '%sfield-%s-%s', $slug_dir, $this->field->type, $slug_name );
				$specifics []= sprintf( '%sfield-%s', $slug_dir, $this->field->type );
			}
		}

		/** Generic field templates */
		$specifics []= sprintf( '%sview-%d-field-%s', $slug_dir, $this->view->ID, $slug_name );
		$specifics []= sprintf( '%sform-%d-field-%s', $slug_dir, $this->view->form->ID, $slug_name );

		$specifics []= sprintf( '%sview-%d-field', $slug_dir, $this->view->ID );
		$specifics []= sprintf( '%sform-%d-field', $slug_dir, $this->view->form->ID );

		$specifics []= sprintf( '%sfield-%s', $slug_dir, $slug_name );
		$specifics []= sprintf( '%sfield', $slug_dir );


		return array_merge( $specifics, $templates );
	}

	/**
	 * Output some HTML.
	 *
	 * @return void
	 */
	public function render() {

		/**
		 * Make various pieces of data available to the template
		 *  under the $gravityview scoped variable.
		 *
		 * @filter `gravityview/template/field/data`
		 * @param array $data The default data available to all Field templates.
		 * @param \GV\Field_Template $template The current template.
		 * @since future
		 */
		$this->set_template_data( apply_filters( 'gravityview/template/field/data', array(

			'template' => $this,

			/** Shortcuts */
			'field' => $this->field,
			'entry' => $this->entry,

		), $this ), 'gravityview' );

		/** Load the template. */
		$this->get_template_part( static::$slug );
	}
}

/** Load implementations. */
require gravityview()->plugin->dir( 'future/includes/class-gv-template-field-html.php' );
