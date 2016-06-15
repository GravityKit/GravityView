<?php
/**
 * select
 */
class GravityView_FieldType_radio extends GravityView_FieldType {

	function render_option() { ?>
		<div class="gv-label">
		<?php
			echo $this->get_field_label();
		?>
		</div>
		<?php
			echo $this->get_tooltip();
			echo $this->get_field_desc() . ' ';

			$this->render_input();

	}

	function render_input( $override_input = null ) {
		if( isset( $override_input ) ) {
			echo $override_input;
			return;
		}

		foreach( $this->field['options'] as $value => $label ) : ?>
		<label class="<?php echo $this->get_label_class(); ?>">
			<input name="<?php echo esc_attr( $this->name ); ?>" id="<?php echo $this->get_field_id(); ?>-<?php echo esc_attr( $value ); ?>" type="radio" value="<?php echo esc_attr( $value ); ?>" <?php checked( $value, $this->value, true ); ?> />&nbsp;<?php echo esc_html( $label ); ?>
		</label>
<?php
		endforeach;
	}
}

