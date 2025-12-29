<?php
namespace GV;

/**
 * Trait responsible for rendering a single field for an entry.
 *
 * @since 2.33
 */
trait Field_Renderer_Trait {
	/**
	 * Output the field in the diy view.
	 *
	 * @param Field $field  The field to output.
	 * @param Entry $entry  The entry.
	 * @param array $extras Extra stuff, like wpautop, etc.
	 *
	 * @return string The field HTML.
	 */
	public function the_field( Field $field, Entry $entry, array $extras = [] ): string {
		$form = $this->view->form;

		$context = Template_Context::from_template( $this, compact( 'field', 'entry' ) );

		$renderer = new Field_Renderer();
		$source   = is_numeric( $field->ID ) ? $this->view->form : new Internal_Source();

		$value = $renderer->render( $field, $this->view, $source, $entry, $this->request );
		$label = apply_filters(
			'gravityview/template/field_label',
			$field->get_label( $this->view, $form ),
			$field->as_configuration(),
			$form->form ?: null,
			null,
		);

		/**
		 * Override the field label.
		 *
		 * @since 2.0
		 *
		 * @param string               $label   The label to override.
		 * @param \GV\Template_Context $context The context.
		 */
		$label = apply_filters( 'gravityview/template/field/label', $label, $context );

		/**
		 * Filters whether the field should be hidden if the value is empty.
		 *
		 * @since 2.33
		 *
		 * @param bool                 $hide_empty Should the row be hidden if the value is empty? Default: don't hide.
		 * @param \GV\Template_Context $context    The context.
		 */
		$hide_empty = apply_filters(
			'gravityview/render/hide-empty-zone',
			Utils::get( $extras, 'hide_empty', $this->view->settings->get( 'hide_empty', false ) ),
			$context,
		);

		$markup = '<div id="{{ field_id }}" class="{{ class }}">
			{{ label }}
			<div class="gv-grid-value">{{ value }}</div>
		</div>';

		$extras = array_merge( $extras, compact( 'hide_empty', 'value', 'label', 'markup' ) );

		return \gravityview_field_output( $extras, $context );
	}
}
