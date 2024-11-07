<?php
/**
 * url input type
 */
class GravityView_FieldType_url extends GravityView_FieldType_text {

	function render_input( $override_input = null ) {

		if ( isset( $override_input ) ) {
			echo $override_input;
			return;
		}

		$class = '';

		$show_mt = $this->show_merge_tags();

		if ( $show_mt && false !== $this->field['merge_tags'] || 'force' === $this->field['merge_tags'] ) {
			$class = 'gv-merge-tag-support mt-position-right mt-hide_all_fields ';
		}
		$class      .= \GV\Utils::get( $this->field, 'class', 'widefat' );
		$placeholder = \GV\Utils::get( $this->field, 'placeholder' );
		?>
		<input name="<?php echo esc_attr( $this->name ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" id="<?php echo $this->get_field_id(); ?>" type="url" value="<?php echo esc_attr( $this->value ); ?>" class="<?php echo esc_attr( $class ); ?>">
		<?php
	}
}



