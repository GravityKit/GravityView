<?php
/**
 * checkbox input type
 */
class GravityView_FieldType_checkbox extends GravityView_FieldType {

	function render_option() {
		?>
		<label for="<?php echo $this->get_field_id(); ?>" class="<?php echo $this->get_label_class(); ?>">
			<?php $this->render_input(); ?>
			&nbsp;<?php echo $this->get_field_label() . $this->get_tooltip() . $this->get_field_desc(); ?>
		</label>
		<?php
	}

	function render_setting( $override_input = null ) {

		if ( $this->get_field_left_label() ) {
			?>

			<th scope="row">
				<label for="<?php echo $this->get_field_id(); ?>">
					<?php echo $this->get_field_left_label() . $this->get_tooltip(); ?>
				</label>
			</th>
			<td>
				<label>
				<?php $this->render_input( $override_input ); ?>
				&nbsp;<?php echo $this->get_field_label() . $this->get_tooltip() . $this->get_field_desc(); ?>
				</label>
			</td>

		<?php } else { ?>

			<td colspan="2">
				<label for="<?php echo $this->get_field_id(); ?>">
					<?php $this->render_input( $override_input ); ?>
					&nbsp;<?php echo $this->get_field_label() . $this->get_tooltip() . $this->get_field_desc(); ?>
				</label>
			</td>

			<?php
		}
	}

	function render_input( $override_input = null ) {
		if ( isset( $override_input ) ) {
			echo $override_input;
			return;
		}

		?>
		<input name="<?php echo esc_attr( $this->name ); ?>" type="hidden" value="0" />
			<input name="<?php echo esc_attr( $this->name ); ?>" id="<?php echo $this->get_field_id(); ?>" type="checkbox" value="1" <?php checked( $this->value, '1', true ); ?> />
		<?php
	}
}
