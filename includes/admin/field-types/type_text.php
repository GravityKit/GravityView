<?php
/**
 * text input type
 */
class GravityView_FieldType_text extends GravityView_FieldType {

	function render_option() {
		?>
		<label for="<?php echo $this->get_field_id(); ?>" class="<?php echo $this->get_label_class(); ?>">
			<?php echo $this->get_field_label() . $this->get_tooltip() . $this->get_field_desc(); ?>
			<div>
				<?php self::render_input(); ?>
			</div>
		</label>
		<?php
	}

	function render_input( $override_input ) {

		if( isset( $override_input ) ) {
			echo $override_input;
			return;
		}

		$class = '';

		$show_mt = $this->show_merge_tags();

        if( $show_mt && $this->field['merge_tags'] !== false || $this->field['merge_tags'] === 'force' ) {
            $class = 'merge-tag-support mt-position-right mt-hide_all_fields ';
        }
        $class .= !empty( $this->field['class'] ) ? $this->field['class'] : 'widefat';

		?>
		<input name="<?php echo esc_attr( $this->name ); ?>" id="<?php echo $this->get_field_id(); ?>" type="text" value="<?php echo esc_attr( $this->value ); ?>" class="<?php echo esc_attr( $class ); ?>">
		<?php
	}

}



