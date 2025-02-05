<?php
/**
 * textarea input type
 */
class GravityView_FieldType_textarea extends GravityView_FieldType {

	function render_option() {

		?>
		<label for="<?php echo $this->get_field_id(); ?>" class="<?php echo $this->get_label_class(); ?>">
								<?php

								echo '<span class="gv-label">' . $this->get_field_label() . '</span>';
								echo $this->get_tooltip();
								?>
		<div>
				<?php $this->render_input(); ?>
			</div>
		</label>
		<?php
	}

	function render_input( $override_input = null ) {
		if ( isset( $override_input ) ) {
			echo $override_input;
			return;
		}

		$class = 'widefat mt-wp_editor ';

		$field_support_merge_tags = false !== rgar( $this->field, 'merge_tags', false ) || 'force' === rgar( $this->field, 'merge_tags', false );

		if ( rgar( $this->field, 'codemirror', false ) || $field_support_merge_tags ) {
			$class .= 'codemirror ';
		}

		$show_mt = $this->show_merge_tags();

		if ( $show_mt || $field_support_merge_tags ) {
			$class .= ' gv-merge-tag-support mt-position-right ';

			if ( empty( $this->field['show_all_fields'] ) ) {
				$class .= ' mt-hide_all_fields ';
			}
		}

		$class      .= rgar( $this->field, 'class' );
		$placeholder = rgar( $this->field, 'placeholder' );

		/**
		 * @since 1.22.5
		 */
		$default_rows = apply_filters( 'gravityview/admin/field-types/textarea/rows', 5 );

		$rows = rgar( $this->field, 'rows', $default_rows );

		echo $this->get_field_desc();
		?>
		<textarea name="<?php echo esc_attr( $this->name ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>"
					id="<?php echo $this->get_field_id(); ?>"
					class="<?php echo gravityview_sanitize_html_class( $class ); ?>"
					rows="<?php echo absint( $rows ); ?>"
					data-codemirror="<?php echo esc_attr( $this->get_codemirror_config() ); ?>"
		><?php echo $this->value ? esc_textarea( $this->value ) : $this->value; ?></textarea>
		<?php
	}

	/**
	 * Returns a JSON-encoded value of the field's `codemirror` setting.
	 *
	 * @since 2.19
	 *
	 * @return string JSON value of `codemirror` setting.
	 */
	function get_codemirror_config() {
		return json_encode( rgar( $this->field, 'codemirror', '' ) );
	}
}
