<?php

use GV\Field_HTML_Template;
use GV\GF_Entry;

/**
 * Field renderer for {@see GF_Field_Repeater}.
 *
 * @since $ver$
 */
final class GravityView_Repeater_Field_HTML_Template extends Field_HTML_Template {
	/**
	 * {@inheritDoc}
	 *
	 * Renders the field as often as is required for the current entry.
	 *
	 * @since $ver$
	 */
	public function render(): void {
		$config = $this->field->as_configuration();
		$fields = GravityView_Field_Repeater::get_repeater_field_ids( $config['form_id'] ?? 0 );
		if ( ! array_key_exists( $config['id'] ?? 0, $fields ) ) {
			parent::render();

			return;
		}

		$entry = $this->entry->from_field( $this->field );
		if ( ! $entry ) {
			gravityview()->log->error( 'Entry is invalid for field. Returning empty.' );

			return;
		}

		// The value has added to the entry by the template.
		$field_id = $config['id'] ?? 0;
		if ( isset( $entry[ $field_id ] ) ) {
			parent::render();

			return;
		}

		// Rendering this at the first level, so it's "not" nested.
		if ( 1 === ( $this->field->field->nestingLevel ?? 0 ) ) {
			$this->field->field->nestingLevel = 0;
		}

		$old_entry = $this->entry;

		foreach ( $this->field->get_results( $this->view, $this->source, $entry, $this->request ) as $i => $value ) {
			$data = $entry->as_entry();
			if ( is_array( $value ) && isset( $value[ $field_id ] ) && false !== strpos( $field_id, '.' ) ) {
				// This field might be an input of a complex field.
				$value = $value[ $field_id ];
			}
			$data[ $field_id ] = $value;
			// Temporarily overwrite entry for rendering.
			$this->entry = GF_Entry::from_entry( $data );

			if ( $i > 0 ) {
				echo '<hr class="gv-repeater-hr" />';
			}

			// Render the original way, PER value.
			parent::render();
		}

		// Reset entry.
		$this->entry = $old_entry;
	}
}
