<?php
/**
 * Checkboxes input type - show a group of checkboxes
 * Supports a "requires", which is the name of another checkbox in the same setting. If set, the checkbox will be shown only if "requires" checkbox is checked as well.
 * @since 1.17
 */
class GravityView_FieldType_checkboxes extends GravityView_FieldType {

	function render_option() {
		?>
		<fieldset class="<?php echo $this->get_label_class(); ?>">
			<legend><span class="gv-label"><?php echo $this->get_field_label(); ?></span></legend>
		<?php

			echo $this->get_tooltip() . $this->get_field_desc();

			$this->render_input();

		?>
		</fieldset>
		<?php
	}

	function render_input( $override_input = null ) {
		if( isset( $override_input ) ) {
			echo $override_input;
			return;
		}

		?>
		<ul class="gv-setting-list">
		<?php
		foreach( $this->field['options'] as $value => $label ) { ?>
			<li <?php if( isset( $label['requires'] ) ) { printf( 'class="gv-sub-setting" data-requires="%s"', $label['requires'] ); } ?>>
				<label>
				<input name="<?php printf( '%s[%s]', esc_attr( $this->name ), esc_attr( $value ) ); ?>" type="hidden"
				       value="0"/>
				<input name="<?php printf( '%s[%s]', esc_attr( $this->name ), esc_attr( $value ) ); ?>"
				       id="<?php echo $this->get_field_id(); ?>" type="checkbox"
				       value="1" <?php checked( ! empty( $this->value[ $value ] ) ); ?> />
				<?php echo esc_html( $label['label'] ); ?>
				</label>
				<?php if( ! empty( $label['desc'] ) ) {
					printf( '<span class="howto">%s</span>', $label['desc'] );
				}
				?>
			</li>
			<?php
		}
		?>
		</ul>
		<?php
	}

}
