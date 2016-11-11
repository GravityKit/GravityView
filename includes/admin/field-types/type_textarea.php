<?php
/**
 * textarea input type
 */
class GravityView_FieldType_textarea extends GravityView_FieldType {

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

		$class = 'widefat ';

		$show_mt = $this->show_merge_tags();

        if( $show_mt && $this->field['merge_tags'] !== false || $this->field['merge_tags'] === 'force' ) {
            $class .= ' merge-tag-support mt-position-right ';

            if( empty( $this->field['show_all_fields'] ) ) {
            	$class .= ' mt-hide_all_fields ';
            }
        }
		$class .= rgar( $this->field, 'class' );
		$placeholder = rgar( $this->field, 'placeholder' );
		?>
		<textarea name="<?php echo esc_attr( $this->name ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" id="<?php echo $this->get_field_id(); ?>" class="<?php echo gravityview_sanitize_html_class( $class ); ?>" rows="5"><?php echo esc_textarea(  $this->value ); ?></textarea>
       	<?php
	}

}
