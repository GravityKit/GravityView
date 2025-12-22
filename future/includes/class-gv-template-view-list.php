<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The View List Template class .
 *
 * Renders a \GV\View and a \GV\Entry_Collection via a \GV\View_Renderer.
 */
class View_List_Template extends View_Template {
	/**
	 * @var string The template slug to be loaded (like "table", "list")
	 */
	public static $slug = 'list';

	/**
	 * Output the field in the list view.
	 *
	 * @param \GV\Field $field The field to output.
	 * @param \GV\Entry $entry The entry.
	 * @param array     $extras Extra stuff, like wpautop, etc.
	 *
	 * @return string
	 */
	public function the_field( \GV\Field $field, \GV\Entry $entry, $extras = null ) {
		$form = $this->view->form;

		if ( isset( $this->view->unions[ $entry['form_id'] ] ) ) {
			if ( isset( $this->view->unions[ $entry['form_id'] ][ $field->ID ] ) ) {
				$field = $this->view->unions[ $entry['form_id'] ][ $field->ID ];
			} elseif ( ! $field instanceof Internal_Field ) {
				$field = Internal_Field::from_configuration( array( 'id' => 'custom' ) );
			}
		}

		if ( $entry->is_multi() ) {
			if ( ! $single_entry = $entry->from_field( $field ) ) {
				return;
			}
			$form = GF_Form::by_id( $field->form_id );
		}

		/**
		 * Push legacy entry context.
		 */
		\GV\Mocks\Legacy_Context::load(
			array(
				'entry' => $entry,
				'form'  => $form,
			)
		);

		$context = Template_Context::from_template( $this, compact( 'field', 'entry' ) );

		$renderer = new Field_Renderer();
		$source   = is_numeric( $field->ID ) ? $form : new Internal_Source();

		$value = $renderer->render( $field, $this->view, $source, $entry, $this->request );

		/**
		 * @deprecated Here for back-compatibility.
		 */
		$label = apply_filters( 'gravityview_render_after_label', $field->get_label( $this->view, $form, $entry ), $field->as_configuration() );
		$label = apply_filters( 'gravityview/template/field_label', $label, $field->as_configuration(), $form->form ? $form->form : null, null );

		/**
		 * Override the field label.
		 *
		 * @since 2.0
		 * @param string $label The label to override.
		 * @param \GV\Template_Context $context The context.
		 */
		$label = apply_filters( 'gravityview/template/field/label', $label, $context );

		/**
		 * Whether to hide the zone if the value is empty.
		 *
		 * @since 1.7.6
		 *
		 * @param bool                 $hide_empty Should the row be hidden if the value is empty? Default: don't hide.
		 * @param \GV\Template_Context $context    The template context.
		 */
		$hide_empty = apply_filters( 'gravityview/render/hide-empty-zone', Utils::get( $extras, 'hide_empty', $this->view->settings->get( 'hide_empty', false ) ), $context );

		if ( is_numeric( $field->ID ) ) {
			$extras['field'] = $field->as_configuration();
		}

		$extras['entry']      = $entry->as_entry();
		$extras['hide_empty'] = $hide_empty;
		$extras['label']      = $label;
		$extras['value']      = $value;

		return \gravityview_field_output( $extras, $context );
	}

	/**
	 * Return an array of variables ready to be extracted.
	 *
	 * @param string|array $zones The field zones to grab.
	 *
	 * @return array An array ready to be extract()ed in the form of
	 *  $zone => \GV\Field_Collection
	 *  has_$zone => int
	 */
	public function extract_zone_vars( $zones ) {
		if ( ! is_array( $zones ) ) {
			$zones = array( $zones );
		}

		$vars = array();
		foreach ( $zones as $zone ) {
			$zone_var                = str_replace( '-', '_', $zone );
			$vars[ $zone_var ]       = $this->view->fields->by_position( 'directory_list-' . $zone )->by_visible( $this->view );
			$vars[ "has_$zone_var" ] = $vars[ $zone_var ]->count();
		}

		return $vars;
	}

	/**
	 * `gravityview_entry_class` and `gravityview/template/list/entry/class` filters.
	 *
	 * Modify of the class of a row.
	 *
	 * @param string               $class   The class.
	 * @param \GV\Entry            $entry   The entry.
	 * @param \GV\Template_Context $context The context.
	 *
	 * @return string The classes.
	 */
	public static function entry_class( $class, $entry, $context ) {
		/**
		 * Modify the class applied to the entry row.
		 *
		 * @param string $class Existing class.
		 * @param array $entry Current entry being displayed
		 * @param \GravityView_View $this Current GravityView_View object
		 * @deprecated Use `gravityview/template/list/entry/class`
		 * @return string The modified class.
		 */
		$class = apply_filters( 'gravityview_entry_class', $class, $entry->as_entry(), \GravityView_View::getInstance() );

		/**
		 * Modify the class applied to the entry row.
		 *
		 * @since 2.0.6.1
		 *
		 * @param string               $class   The existing class.
		 * @param \GV\Template_Context $context The context.
		 *
		 * @return string The modified class.
		 */
		return apply_filters( 'gravityview/template/list/entry/class', $class, Template_Context::from_template( $context->template, compact( 'entry' ) ) );
	}

	/**
	 * `gravityview_list_body_before` and `gravityview/template/list/body/before` actions.
	 *
	 * Output inside the `tbody` of the list.
	 *
	 * @param $context \GV\Template_Context The 2.0 context.
	 *
	 * @return void
	 */
	public static function body_before( $context ) {
		/**
		 * Fires inside the body of the list, before any entries are rendered.
		 *
		 * @since 2.0
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( 'gravityview/template/list/body/before', $context );

		/**
		* Inside the `tbody`, before any rows are rendered. Can be used to insert additional rows.
		 *
		* @deprecated Use `gravityview/template/list/body/before`
		* @since 1.0.7
		* @param \GravityView_View $gravityview_view Current GravityView_View object.
		*/
		do_action( 'gravityview_list_body_before', \GravityView_View::getInstance() /** ugh! */ );
	}

	/**
	 * `gravityview_list_body_after` and `gravityview/template/list/body/after` actions.
	 *
	 * Output inside the `tbody` of the list.
	 *
	 * @param $context \GV\Template_Context The 2.0 context.
	 *
	 * @return void
	 */
	public static function body_after( $context ) {
		/**
		 * Fires inside the body of the list, after all entries are rendered.
		 *
		 * @since 2.0
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( 'gravityview/template/list/body/after', $context );

		/**
		* Inside the `tbody`, after any rows are rendered. Can be used to insert additional rows.
		 *
		* @deprecated Use `gravityview/template/list/body/after`
		* @since 1.0.7
		* @param \GravityView_View $gravityview_view Current GravityView_View object.
		*/
		do_action( 'gravityview_list_body_after', \GravityView_View::getInstance() /** ugh! */ );
	}

	/**
	 * `gravityview_list_entry_before` and `gravityview/template/list/entry/before` actions.
	 * `gravityview_list_entry_title_before` and `gravityview/template/list/entry/title/before` actions.
	 * `gravityview_list_entry_content_before` and `gravityview/template/list/entry/content/before` actions.
	 * `gravityview_list_entry_footer_before` and `gravityview/template/list/entry/footer/before` actions.
	 *
	 * Output inside the `entry` of the list.
	 *
	 * @param \GV\Entry            $entry The entry.
	 * @param \GV\Template_Context $context The 2.0 context.
	 * @param string               $zone The list zone (footer, image, title, etc.).
	 *
	 * @return void
	 */
	public static function entry_before( $entry, $context, $zone = '' ) {
		$zone = str_replace( '//', '/', "/$zone/" );

		/**
		 * Fires inside the entry of the list, before the zone is rendered.
		 *
		 * @since 2.0
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( sprintf( 'gravityview/template/list/entry%sbefore', $zone ), Template_Context::from_template( $context->template, compact( 'entry' ) ) );

		$zone = str_replace( '/', '_', $zone );

		/**
		 * Inside the `entry`, before any rows are rendered. Can be used to insert additional rows.
		 *
		 * @deprecated Use `gravityview/template/list/entry/$zone/before`
		 * @since 1.0.7
		 * @param array             $entry            The entry being displayed.
		 * @param \GravityView_View $gravityview_view Current GravityView_View object.
		 */
		do_action( sprintf( 'gravityview_list_entry%sbefore', $zone ), $entry->as_entry(), \GravityView_View::getInstance() /** ugh! */ );
	}

	/**
	 * `gravityview_list_entry_after` and `gravityview/template/list/entry/after` actions.
	 * `gravityview_list_entry_title_after` and `gravityview/template/list/entry/title/after` actions.
	 * `gravityview_list_entry_content_after` and `gravityview/template/list/entry/content/after` actions.
	 * `gravityview_list_entry_footer_after` and `gravityview/template/list/entry/footer/after` actions.
	 *
	 * Output inside the `entry` of the list.
	 *
	 * @param \GV\Entry            $entry The entry.
	 * @param \GV\Template_Context $context The 2.0 context.
	 * @param string               $zone The list zone (footer, image, title, etc.).
	 *
	 * @return void
	 */
	public static function entry_after( $entry, $context, $zone = '' ) {
		$zone = str_replace( '//', '/', "/$zone/" );

		/**
		 * Fires inside the entry of the list, after the zone is rendered.
		 *
		 * @since 2.0
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( sprintf( 'gravityview/template/list/entry%safter', $zone ), Template_Context::from_template( $context->template, compact( 'entry' ) ) );

		$zone = str_replace( '/', '_', $zone );

		/**
		 * Inside the `entry`, after any rows are rendered. Can be used to insert additional rows.
		 *
		 * @deprecated Use `gravityview/template/list/entry/$zone/after`
		 * @since 1.0.7
		 * @param array             $entry            The entry being displayed.
		 * @param \GravityView_View $gravityview_view Current GravityView_View object.
		 */
		do_action( sprintf( 'gravityview_list_entry%safter', $zone ), $entry->as_entry(), \GravityView_View::getInstance() /** ugh! */ );
	}
}
