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
	 * Prefix for filter names.
	 * @var string
	 */
	protected $filter_prefix = 'gravityview/future/template/fields';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 * @var string
	 */
	protected $theme_template_directory = 'gravityview/future/fields/';

	/**
	 * Directory name where the default templates for this plugin are found.
	 * @var string
	 */
	protected $plugin_template_directory = 'future/templates/fields/';

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

		parent::__construct();
	}

	/**
	 * Enable granular template overrides based on current post, view, form, field types, etc.
	 *
	 * The hierarchy is as follows:
	 *
	 * - post-[ID of post of page where view is embedded]-view-[View ID]-field-[Field type]-html.php
	 * - post-[ID of post of page where view is embedded]-view-[View ID]-field-html.php
	 * - post-[ID of post of page where view is embedded]-field-[Field type]-html.php
	 * - post-[ID of post of page where view is embedded]-field-html.php
	 * - post-[ID of post of page where view is embedded]-view-[View ID]-field-[Field type].php
	 * - post-[ID of post of page where view is embedded]-view-[View ID]-field.php
	 * - post-[ID of post of page where view is embedded]-field-[Field type].php
	 * - post-[ID of post of page where view is embedded]-field.php
	 * - form-[Form ID]-field-[Field ID]-html.php
	 * - form-[Form ID]-field-[Field ID].php
	 * - form-[Form ID]-field-[Field type]-html.php
	 * - form-[Form ID]-field-[Field type].php
	 * - view-[View ID]-field-[Field type]-html.php
	 * - view-[View ID]-field-[Field type].php
	 * - field-[Field type]-html.php
	 * - field-[Field type].php
	 * - field-html.php
	 * - field.php
	 *
	 * @see  Gamajo_Template_Loader::get_template_file_names() Where the filter is
	 * @param array $templates Existing list of templates.
	 * @param string $slug      Name of the template base, example: `html`, `json`, `xml`
	 * @param string $name      Name of the template part.
	 *
	 * @return array $templates Modified template array, merged with existing $templates values
	 */
	public function add_id_specific_templates( $templates, $slug, $name ) {

		$specifics = array();

		list( $slug_dir, $slug_name ) = self::split_slug( $slug, $name );

		global $post;

		if ( ! $this->request->is_view() && $post ) {
			if ( $this->field && $this->field->type ) {
				$specifics []= sprintf( '%spost-%d-view-%d-field-%s-%s.php', $slug_dir, $post->ID, $this->view->ID, $this->field->type, $slug_name );
				$specifics []= sprintf( '%spost-%d-view-%d-field-%s.php', $slug_dir, $post->ID, $this->view->ID, $this->field->type );
				$specifics []= sprintf( '%spost-%d-field-%s-%s.php', $slug_dir, $post->ID, $this->field->type, $slug_name );
				$specifics []= sprintf( '%spost-%d-field-%s.php', $slug_dir, $post->ID, $this->field->type );
			}

			$specifics []= sprintf( '%spost-%d-view-%d-field-%s.php', $slug_dir, $post->ID, $this->view->ID, $slug_name );
			$specifics []= sprintf( '%spost-%d-view-%d-field.php', $slug_dir, $post->ID, $this->view->ID );
			$specifics []= sprintf( '%spost-%d-field-%s.php', $slug_dir, $post->ID, $slug_name );
			$specifics []= sprintf( '%spost-%d-field.php', $slug_dir, $post->ID );
		}
		
		/** Field-specific */
		if ( $this->field ) {

			if ( $this->field->ID ) {
				$specifics []= sprintf( '%sform-%d-field-%d-%s.php', $slug_dir, $this->view->form->ID, $this->field->ID, $slug_name );
				$specifics []= sprintf( '%sform-%d-field-%d.php', $slug_dir, $this->view->form->ID, $this->field->ID );
			}

			if ( $this->field->type ) {
				$specifics []= sprintf( '%sform-%d-field-%s-%s.php', $slug_dir, $this->view->form->ID, $this->field->type, $slug_name );
				$specifics []= sprintf( '%sform-%d-field-%s.php', $slug_dir, $this->view->form->ID, $this->field->type );

				$specifics []= sprintf( '%sview-%d-field-%s-%s.php', $slug_dir, $this->view->ID, $this->field->type, $slug_name );
				$specifics []= sprintf( '%sview-%d-field-%s.php', $slug_dir, $this->view->ID, $this->field->type );

				$specifics []= sprintf( '%sfield-%s-%s.php', $slug_dir, $this->field->type, $slug_name );
				$specifics []= sprintf( '%sfield-%s.php', $slug_dir, $this->field->type );
			}
		}

		/** Generic field templates */
		$specifics []= sprintf( '%sview-%d-field-%s.php', $slug_dir, $this->view->ID, $slug_name );
		$specifics []= sprintf( '%sform-%d-field-%s.php', $slug_dir, $this->view->form->ID, $slug_name );

		$specifics []= sprintf( '%sview-%d-field.php', $slug_dir, $this->view->ID );
		$specifics []= sprintf( '%sform-%d-field.php', $slug_dir, $this->view->form->ID );

		$specifics []= sprintf( '%sfield-%s.php', $slug_dir, $slug_name );
		$specifics []= sprintf( '%sfield.php', $slug_dir );


		return array_merge( $specifics, $templates );
	}

	/**
	 * Output some HTML.
	 *
	 * @return void
	 */
	public function render() {

		$value = $this->field->get_value( $this->view, $this->source, $this->entry );
		$display_value = $value;

		/**
		 * Make various pieces of data available to the template
		 *  under the $gravityview scoped variable.
		 *
		 * @filter `gravityview/template/field/data`
		 * @param array $data The default data available to all Field templates.
		 * @param \GV\Field_Template $template The current template.
		 * @since future
		 */
		$this->push_template_data( apply_filters( 'gravityview/template/field/data', array(

			'template' => $this,

			'value' => $value,
			'display_value' => $display_value,

			/** Shortcuts */
			'field' => $this->field,
			'view' => $this->view,
			'source' => $this->source,
			'entry' => $this->entry,
			'request' => $this->request,

		), $this ), 'gravityview' );

		/** Load the template. */
		$this->get_template_part( static::$slug );
		$this->pop_template_data( 'gravityview' );
	}
}

/** Load implementations. */
require gravityview()->plugin->dir( 'future/includes/class-gv-template-field-html.php' );
