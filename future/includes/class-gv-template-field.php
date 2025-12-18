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
 * The Field Template class.
 *
 * Attached to a \GV\Field and used by a \GV\Field_Renderer.
 */
abstract class Field_Template extends Template {
	/**
	 * Prefix for filter names.
	 *
	 * @var string
	 */
	protected $filter_prefix = 'gravityview/template/fields';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
	 * @var string
	 */
	protected $theme_template_directory = 'gravityview/fields/';

	/**
	 * Directory name where the default templates for this plugin are found.
	 *
	 * @var string
	 */
	protected $plugin_template_directory = 'templates/fields/';

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
	 * THe callback that registers the template files.
	 *
	 * @var callable
	 */
	private $_add_id_specific_templates_callback;

	/**
	 * Initializer.
	 *
	 * @param \GV\Field   $field The field about to be rendered.
	 * @param \GV\View    $view The view in this context, if applicable.
	 * @param \GV\Source  $source The source (form) in this context, if applicable.
	 * @param \GV\Entry   $entry The entry in this context, if applicable.
	 * @param \GV\Request $request The request in this context, if applicable.
	 */
	public function __construct( Field $field, View $view = null, Source $source = null, Entry $entry = null, Request $request = null ) {
		$this->field   = $field;
		$this->view    = $view;
		$this->source  = $source;
		$this->entry   = $entry;
		$this->request = $request;

		/** Add granular overrides. */
		add_filter( $this->filter_prefix . '_get_template_part', $this->_add_id_specific_templates_callback = self::add_id_specific_templates( $this ), 10, 3 );

		parent::__construct();
	}

	public function __destruct() {
		remove_filter( $this->filter_prefix . '_get_template_part', $this->_add_id_specific_templates_callback );
	}

	/**
	 * Enable granular template overrides based on current post, view, form, field types, etc.
	 *
	 * Why? See https://github.com/gravityview/GravityView/issues/1024
	 *
	 * @param \GV\Field_Template $template The template instance.
	 * @return callable The callback bound to `get_template_part`. See `\GV\Field_Template::__construct`
	 */
	public static function add_id_specific_templates( $template ) {

		$inputType  = null;
		$field_type = null;
		$field_id   = null;
		$view_id    = null;
		$form_id    = null;
		$is_view    = $template->request && $template->request->is_view( false );

		if ( $template->field ) {
			$inputType  = $template->field->inputType;
			$field_type = $template->field->type;
			$field_id   = $template->field->ID;
		}

		if ( $template->view ) {
			$view_id = $template->view->ID;
			$form_id = $template->view->form ? $template->view->form->ID : null;
		}

		$class = get_class( $template );

		/**
		 * Enable granular template overrides based on current post, view, form, field types, etc.
		 *
		 * The hierarchy is as follows:
		 *
		 * - post-[ID of post of page where view is embedded]-view-[View ID]-field-[Field type]-html.php
		 * - post-[ID of post of page where view is embedded]-view-[View ID]-field-[Field inputType]-html.php
		 * - post-[ID of post of page where view is embedded]-view-[View ID]-field-html.php
		 * - post-[ID of post of page where view is embedded]-field-[Field type]-html.php
		 * - post-[ID of post of page where view is embedded]-field-[Field inputType]-html.php
		 * - post-[ID of post of page where view is embedded]-field-html.php
		 * - post-[ID of post of page where view is embedded]-view-[View ID]-field-[Field type].php
		 * - post-[ID of post of page where view is embedded]-view-[View ID]-field-[Field inputType].php
		 * - post-[ID of post of page where view is embedded]-view-[View ID]-field.php
		 * - post-[ID of post of page where view is embedded]-field-[Field type].php
		 * - post-[ID of post of page where view is embedded]-field-[Field inputType].php
		 * - post-[ID of post of page where view is embedded]-field.php
		 * - form-[Form ID]-field-[Field ID]-html.php
		 * - form-[Form ID]-field-[Field ID].php
		 * - form-[Form ID]-field-[Field type]-html.php
		 * - form-[Form ID]-field-[Field inputType]-html.php
		 * - form-[Form ID]-field-[Field type].php
		 * - form-[Form ID]-field-[Field inputType].php
		 * - view-[View ID]-field-[Field type]-html.php
		 * - view-[View ID]-field-[Field inputType]-html.php
		 * - view-[View ID]-field-[Field type].php
		 * - view-[View ID]-field-[Field inputType].php
		 * - field-[Field type]-html.php
		 * - field-[Field inputType]-html.php
		 * - field-[Field type].php
		 * - field-[Field inputType].php
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
		return function ( $templates, $slug, $name ) use ( $class, $inputType, $field_type, $view_id, $is_view, $form_id, $field_id ) {
			$specifics = array();

			list( $slug_dir, $slug_name ) = $class::split_slug( $slug, $name );

			global $post;

			if ( $is_view && $post ) {
				if ( $field_type ) {
					$specifics []               = sprintf( '%spost-%d-view-%d-field-%s-%s.php', $slug_dir, $post->ID, $view_id, $field_type, $slug_name );
					$inputType && $specifics [] = sprintf( '%spost-%d-view-%d-field-%s-%s.php', $slug_dir, $post->ID, $view_id, $inputType, $slug_name );
					$specifics []               = sprintf( '%spost-%d-view-%d-field-%s.php', $slug_dir, $post->ID, $view_id, $field_type );
					$inputType && $specifics [] = sprintf( '%spost-%d-view-%d-field-%s.php', $slug_dir, $post->ID, $view_id, $inputType );
					$specifics []               = sprintf( '%spost-%d-field-%s-%s.php', $slug_dir, $post->ID, $field_type, $slug_name );
					$inputType && $specifics [] = sprintf( '%spost-%d-field-%s-%s.php', $slug_dir, $post->ID, $inputType, $slug_name );
					$specifics []               = sprintf( '%spost-%d-field-%s.php', $slug_dir, $post->ID, $field_type );
					$inputType && $specifics [] = sprintf( '%spost-%d-field-%s.php', $slug_dir, $post->ID, $inputType );
				}

				$specifics [] = sprintf( '%spost-%d-view-%d-field-%s.php', $slug_dir, $post->ID, $view_id, $slug_name );
				$specifics [] = sprintf( '%spost-%d-view-%d-field.php', $slug_dir, $post->ID, $view_id );
				$specifics [] = sprintf( '%spost-%d-field-%s.php', $slug_dir, $post->ID, $slug_name );
				$specifics [] = sprintf( '%spost-%d-field.php', $slug_dir, $post->ID );
			}

			/** Field-specific */
			if ( $field_id && $form_id ) {

				if ( $field_id ) {
					$specifics [] = sprintf( '%sform-%d-field-%d-%s.php', $slug_dir, $form_id, $field_id, $slug_name );
					$specifics [] = sprintf( '%sform-%d-field-%d.php', $slug_dir, $form_id, $field_id );

					if ( $view_id ) {
						$specifics [] = sprintf( '%sview-%d-field-%d.php', $slug_dir, $view_id, $field_id );
					}
				}

				if ( $field_type ) {
					$specifics []               = sprintf( '%sform-%d-field-%s-%s.php', $slug_dir, $form_id, $field_type, $slug_name );
					$inputType && $specifics [] = sprintf( '%sform-%d-field-%s-%s.php', $slug_dir, $form_id, $inputType, $slug_name );
					$specifics []               = sprintf( '%sform-%d-field-%s.php', $slug_dir, $form_id, $field_type );
					$inputType && $specifics [] = sprintf( '%sform-%d-field-%s.php', $slug_dir, $form_id, $inputType );

					$specifics []               = sprintf( '%sview-%d-field-%s-%s.php', $slug_dir, $view_id, $field_type, $slug_name );
					$inputType && $specifics [] = sprintf( '%sview-%d-field-%s-%s.php', $slug_dir, $view_id, $inputType, $slug_name );
					$specifics []               = sprintf( '%sview-%d-field-%s.php', $slug_dir, $view_id, $field_type );
					$inputType && $specifics [] = sprintf( '%sview-%d-field-%s.php', $slug_dir, $view_id, $inputType );

					$specifics []               = sprintf( '%sfield-%s-%s.php', $slug_dir, $field_type, $slug_name );
					$inputType && $specifics [] = sprintf( '%sfield-%s-%s.php', $slug_dir, $inputType, $slug_name );
					$specifics []               = sprintf( '%sfield-%s.php', $slug_dir, $field_type );
					$inputType && $specifics [] = sprintf( '%sfield-%s.php', $slug_dir, $inputType );
				}
			}

			if ( $form_id ) {
				/** Generic field templates */
				$specifics [] = sprintf( '%sview-%d-field-%s.php', $slug_dir, $view_id, $slug_name );
				$specifics [] = sprintf( '%sform-%d-field-%s.php', $slug_dir, $form_id, $slug_name );

				$specifics [] = sprintf( '%sview-%d-field.php', $slug_dir, $view_id );
				$specifics [] = sprintf( '%sform-%d-field.php', $slug_dir, $form_id );
			}

			/**
			 * Legacy.
			 * Ignore some types that conflict.
			 */
			if ( ! in_array( $field_type, array( 'notes' ) ) ) {
				$specifics [] = sprintf( '%s.php', $field_type );
				$specifics [] = sprintf( 'fields/%s.php', $field_type );
			}

			$specifics [] = sprintf( '%sfield-%s.php', $slug_dir, $slug_name );
			$specifics [] = sprintf( '%sfield.php', $slug_dir );

			return array_merge( $specifics, $templates );
		};
	}

	/**
	 * Output some HTML.
	 *
	 * @todo Move to \GV\Field_HTML_Template, but call filters here?
	 *
	 * @return void
	 */
	public function render() {
		if ( ! $entry = $this->entry->from_field( $this->field ) ) {
			gravityview()->log->error( 'Entry is invalid for field. Returning empty.' );
			return;
		}

		/** Retrieve the value. */
		$display_value = $value = $this->field->get_value( $this->view, $this->source, $entry );

		$source         = $this->source;
		$source_backend = $source ? $source::$backend : null;

		\GV\Mocks\Legacy_Context::load(
			array(
				'field' => $this->field,
			)
		);

		/** Alter the display value according to Gravity Forms. */
		if ( \GV\Source::BACKEND_GRAVITYFORMS === $source_backend && ! $this->field instanceof Internal_Field ) {

			/** Prevent any PHP warnings that may be generated. */
			ob_start();

			// The base GF_Field::get_value_entry_detail() method accepts a string value and needs to be overridden to handle arrays.
			// If it's an array, we need to convert it to a string to prevent a PHP notice generated by GF_Field::get_value_entry_detail().
			// This occurs when rendering an entry value for a custom field from an inactive add-on that would otherwise handle the array.
			if ( is_array( $value ) && 'GF_Field' === get_class( $this->field->field ) ) {
				$value = implode( ', ', array_filter( $value ) );
			}

			$display_value = \GFCommon::get_lead_field_display( $this->field->field, $value, $entry['currency'], false, 'html' );

			if ( $errors = ob_get_clean() ) {
				gravityview()->log->error( 'Errors when calling GFCommon::get_lead_field_display()', array( 'data' => $errors ) );
			}

			// `gform_entry_field_value` expects a GF_Field, but $this->field->field can be NULL
			if ( ! $this->field->field instanceof GF_Field ) {
				$gf_field = \GF_Fields::create( $this->field->field );
			}

			/** Call the Gravity Forms field value filter. */
			$display_value = apply_filters( 'gform_entry_field_value', $display_value, $gf_field, $entry->as_entry(), $this->source->form );

			unset( $gf_field );

			/** Replace merge tags for admin-only fields. */
			if ( ! empty( $this->field->field->adminOnly ) ) {
				$display_value = \GravityView_API::replace_variables( $display_value, $this->form->form, $entry->as_entry(), false, false );
			}
		}

		$context = Template_Context::from_template( $this, compact( 'display_value', 'value' ) );

		/**
		 * Make various pieces of data available to the template
		 *  under the $gravityview scoped variable.
		 *
		 * @filter `gravityview/template/field/context`
		 * @param \GV\Template_Context $context The context for this template.
		 * @since 2.0
		 */
		$this->push_template_data( apply_filters( 'gravityview/template/field/context', $context ), 'gravityview' );

		/** Bake the template. */
		ob_start();
		$this->located_template = $this->get_template_part( static::$slug );
		$output                 = ob_get_clean();

		if ( empty( $output ) ) {
			/**
			 * What to display when a field is empty.
			 *
			 * @deprecated Use the `gravityview/field/value/empty` filter instead
			 * @param string $value (empty string)
			 */
			$output = apply_filters( 'gravityview_empty_value', $output );

			/**
			 * What to display when this field is empty.
			 *
			 * @param string               $value   The value to display (Default: empty string)
			 * @param \GV\Template_Context $context The template context this is being called from.
			 */
			$output = apply_filters( 'gravityview/field/value/empty', $output, Template_Context::from_template( $this ) );

			$context = Template_Context::from_template( $this, compact( 'display_value', 'value' ) );
		}

		gravityview()->log->info(
			'Field template for field #{field_id} loaded: {located_template}',
			array(
				'field_id'         => $this->field->ID,
				'located_template' => $this->located_template,
			)
		);

		$this->pop_template_data( 'gravityview' );

		/** A compatibility array that's required by some of the deprecated filters. */
		$field_compat = array(
			'form'           => \GV\Source::BACKEND_GRAVITYFORMS == $source_backend ? $this->source->form : ( $this->view->form ? $this->view->form->form : null ),
			'field_id'       => $this->field->ID,
			'field'          => $this->field->field,
			'field_settings' => $this->field->as_configuration(),
			'value'          => $value,
			'display_value'  => $display_value,
			'format'         => 'html',
			'entry'          => $entry->as_entry(),
			'field_type'     => $this->field->type,
			'field_path'     => $this->located_template,
		);

		/**
		 * Wrap output in a link, if enabled in the field settings
		 *
		 * @todo Cleanup
		 *
		 * @param string $output HTML value output
		 * @param \GV\Template_Context $context
		 *
		 * @return mixed|string|void
		 */
		$pre_link_compat_callback = function ( $output, $context ) use ( $field_compat ) {
			$field = $context->field;

			/**
			 * Modify the field value output for a field type before Show As Link setting is applied. Example: `gravityview_field_entry_value_number_pre_link`.
			 *
			 * @since 1.16
			 * @param string $output HTML value output
			 * @param array  $entry The GF entry array
			 * @param array  $field_settings Settings for the particular GV field
			 * @param array  $field Field array, as fetched from GravityView_View::getCurrentField()
			 *
			 * @deprecated Use the `gravityview/field/{$field_type}/output` or `gravityview/field/output` filters instead.
			 */
			$output = apply_filters( "gravityview_field_entry_value_{$field->type}_pre_link", $output, $context->entry->as_entry(), $field->as_configuration(), $field_compat );

			$output = apply_filters( 'gravityview_field_entry_value_pre_link', $output, $context->entry->as_entry(), $field->as_configuration(), $field_compat );

			/**
			 * Link to the single entry by wrapping the output in an anchor tag
			 *
			 * Fields can override this by modifying the field data variable inside the field. See /templates/fields/post_image.php for an example.
			 */
			if ( ! empty( $field->show_as_link ) && ! \gv_empty( $output, false, false ) ) {
				$link_atts = empty( $field->new_window ) ? array() : array( 'target' => '_blank' );

				$permalink = $context->entry->get_permalink( $context->view, $context->request );
				$output    = \gravityview_get_link( $permalink, $output, $link_atts );

				/**
				 * Modify the link HTML.
    			 *
				 * @param string $link HTML output of the link
				 * @param string $href URL of the link
				 * @param array  $entry The GF entry array
				 * @param array $field_settings Settings for the particular GV field
				 * @deprecated Use `gravityview/template/field/entry_link`
				 */
				$output = apply_filters( 'gravityview_field_entry_link', $output, $permalink, $context->entry->as_entry(), $field->as_configuration() );

				/**
				 * Modify the link HTML.
    			 *
				 * @since 2.0
				 * @param string $link HTML output of the link
				 * @param string $href URL of the link
				 * @param \GV\Template_Context $context The context
				 */
				$output = apply_filters( 'gravityview/template/field/entry_link', $output, $permalink, $context );
			}

			return $output;
		};

		// TODO Cleanup
		$post_link_compat_callback = function ( $output, $context ) use ( $field_compat ) {
			$field = $context->field;

			/**
			 * Modify the field value output for a field type. Example: `gravityview_field_entry_value_number`.
			 *
			 * @since 1.6
			 * @param string $output HTML value output
			 * @param array  $entry The GF entry array
			 * @param  array $field_settings Settings for the particular GV field
			 * @param array $field Current field being displayed
			 *
			 * @deprecated Use the `gravityview/field/{$field_type}/output` or `gravityview/field/output` filters instead.
			 */
			$output = apply_filters( "gravityview_field_entry_value_{$field->type}", $output, $context->entry->as_entry(), $field->as_configuration(), $field_compat );

			/**
			 * Modify the field value output for all field types.
			 *
			 * @param string $output HTML value output
			 * @param array  $entry The GF entry array
			 * @param  array $field_settings Settings for the particular GV field
			 * @param array $field_data  {@since 1.6}
			 *
			 * @deprecated Use the `gravityview/field/{$field_type}/output` or `gravityview/field/output` filters instead.
			 */
			$output = apply_filters( 'gravityview_field_entry_value', $output, $context->entry->as_entry(), $field->as_configuration(), $field_compat );

			/**
			 * Modify the field output for a field type.
			 *
			 * @since 2.0
			 *
			 * @param string               $output  The current output.
			 * @param \GV\Template_Context $context The template context this is being called from.
			 */
			return apply_filters( "gravityview/template/field/{$field->type}/output", $output, $context );
		};

		/**
		 * Okay, what's this whole pre/post_link compat deal, huh?
		 *
		 * Well, the `gravityview_field_entry_value_{$field_type}_pre_link` filter
		 *  is expected to be applied before the value is turned into an entry link.
		 *
		 * And then `gravityview_field_entry_value_{$field_type}` and `gravityview_field_entry_value`
		 *  are called afterwards.
		 *
		 * So we're going to use filter priorities to make sure this happens inline with
		 *  our new filters, in the correct sequence. Pre-link called with priority 5 and
		 *  post-link called with priority 9. Then everything else.
		 *
		 * If a new code wants to alter the value before it is hyperlinked (hyperlinkified?),
		 *  it should hook into a priority between -inf. and 8. Afterwards: 10 to +inf.
		 */
		add_filter( 'gravityview/template/field/output', $pre_link_compat_callback, 5, 2 );
		add_filter( 'gravityview/template/field/output', $post_link_compat_callback, 9, 2 );

		/**
		 * Modify the field output for a field.
		 *
		 * @since 2.0
		 *
		 * @param string               $output  The current output.
		 * @param \GV\Template_Context $context The template context this is being called from.
		 */
		echo apply_filters( 'gravityview/template/field/output', $output, $context );

		remove_filter( 'gravityview/template/field/output', $pre_link_compat_callback, 5 );
		remove_filter( 'gravityview/template/field/output', $post_link_compat_callback, 9 );
	}
}

/** Load implementations. */
require gravityview()->plugin->dir( 'future/includes/class-gv-template-field-html.php' );
require gravityview()->plugin->dir( 'future/includes/class-gv-template-field-csv.php' );
