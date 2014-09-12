<?php
/**
 * textarea input type
 */
class GravityView_FieldType_textarea extends GravityView_FieldType {

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

	function render_input() {
		$class = '';

		$show_mt = $this->show_merge_tags();

        if( $show_mt && $this->field['merge_tags'] !== false || $this->field['merge_tags'] === 'force' ) {
            $class = 'merge-tag-support mt-position-right mt-hide_all_fields ';
        }
        $class .= !empty( $this->field['class'] ) ? 'widefat ' . $this->field['class'] : 'widefat';

		?>
		<textarea name="<?php echo esc_attr( $this->name ); ?>" id="<?php echo $this->get_field_id(); ?>" class="<?php echo esc_attr( $class ); ?>">
			<?php echo esc_textarea(  $this->value ); ?>
		</textarea>
       	<?php
	}

}