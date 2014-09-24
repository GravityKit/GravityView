<?php
/**
 * select
 */
class GravityView_FieldType_select extends GravityView_FieldType {

	function render_option() {
		?>
		<label for="<?php echo $this->get_field_id(); ?>" class="<?php echo $this->get_label_class(); ?>">
			<?php echo $this->get_field_label() . $this->get_tooltip() . $this->get_field_desc(); ?>&nbsp;
			<?php $this->render_input(); ?>
		</label>
		<?php
	}

	function render_input( $override_input = null ) {
		if( isset( $override_input ) ) {
			echo $override_input;
			return;
		}
		?>
		<select name="<?php echo esc_attr( $this->name ); ?>" id="<?php echo $this->get_field_id(); ?>">
			<?php foreach( $this->field['options'] as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $this->value, true ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}
}

