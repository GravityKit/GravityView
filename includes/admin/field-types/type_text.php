<?php
/**
 * text input type
 */
class GravityView_FieldType_text extends GravityView_FieldType {

	function render_option() {
		?>
		<label for="<?php echo $this->get_field_id(); ?>" class="<?php echo $this->get_label_class(); ?>"><?php

			echo '<span class="gv-label">'.$this->get_field_label().'</span>';
			echo $this->get_tooltip() . $this->get_field_desc();
		?><div>
				<?php $this->render_input(); ?>
			</div>
		</label>
		<?php
	}

	function render_input( $override_input = null ) {

		if( isset( $override_input ) ) {
			echo $override_input;
			return;
		}

		$class = '';

		$show_mt = $this->show_merge_tags();

        if( $show_mt && $this->field['merge_tags'] !== false || $this->field['merge_tags'] === 'force' ) {
            $class = 'merge-tag-support mt-position-right mt-hide_all_fields ';
        }
        $class .= rgar( $this->field, 'class', 'widefat' );
        $placeholder = rgar( $this->field, 'placeholder' );
		?>
		<input name="<?php echo esc_attr( $this->name ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" id="<?php echo $this->get_field_id(); ?>" type="text" value="<?php echo esc_attr( $this->value ); ?>" class="<?php echo esc_attr( $class ); ?>">
		<?php
	}

}



