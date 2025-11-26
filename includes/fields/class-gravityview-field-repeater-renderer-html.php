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

		$field_id     = $config['id'] ?? 0;
		$ancestor_ids = $fields[ $field_id ] ?? [ 0 ];
		$root_id      = $ancestor_ids[0];

		$data = $entry[ $root_id ] ?? [];

		// Todo: Include a setting to limit the number of nested results.
		$flattened = array_values( array_filter(
			$this->flatten( $data ),
			static fn( $key ) => substr( $key, -strlen( '_' . $field_id ) ) === '_' . $field_id,
			ARRAY_FILTER_USE_KEY
		) );

		$old_entry = $this->entry;

		foreach ( $flattened as $i => $value ) {
			$data              = $entry->as_entry();
			$data[ $field_id ] = $value;
			// Temporarily overwrite entry for rendering.
			$this->entry = GF_Entry::from_entry( $data );

			if ( $i > 0 ) {
				// Todo: This is terrible, and needs to look better.
				echo '<hr style="margin: 1em 0;"/>';
			}

			// Render the original way, PER value.
			parent::render();
		}

		// Reset entry.
		$this->entry = $old_entry;
	}

	/**
	 * Utility to flatten array values recursively so they can be saved with the appropriate index.
	 *
	 * @since $ver
	 *
	 * @param array|mixed $array  The array to flatten.
	 * @param string      $prefix The prefix to prepend.
	 *
	 * @return array The flattened array.
	 */
	private function flatten( $array, string $prefix = '' ): array {
		$result = [];
		if ( ! is_array( $array ) ) {
			return [];
		}

		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$result += $this->flatten( $value, $prefix . $key . '_' );
			} else {
				$result[ $prefix . $key ] = $value;
			}
		}

		return $result;
	}

}
