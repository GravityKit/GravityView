<?php
/**
 * number input type
 */

if( !class_exists('GravityView_FieldType_text') ) {
	include_once( GRAVITYVIEW_DIR . 'includes/admin/field-types/type_text.php' );
}

class GravityView_FieldType_number extends GravityView_FieldType_text {

	function render_input( $override_input = null ) {
		if ( isset( $override_input ) ) {
			echo $override_input;

			return;
		}

		$class = '';

		$show_mt = $this->show_merge_tags();

		if ( $show_mt && $this->field['merge_tags'] !== false || $this->field['merge_tags'] === 'force' ) {
			$class = 'merge-tag-support mt-position-right mt-hide_all_fields ';
		}

		$class .= \GV\Utils::get( $this->field, 'class', 'widefat' );

		$max  = \GV\Utils::get( $this->field, 'max', null );
		$min  = \GV\Utils::get( $this->field, 'min', null );
		$step = \GV\Utils::get( $this->field, 'step', null );

		$atts = '';
		$atts .= $max ? ' max="' . (int) $max . '"' : '';
		$atts .= $min ? ' min="' . (int) $min . '"' : '';
		$atts .= $step ? ' step="' . (int) $step . '"' : '';
		?>
		<input name="<?php echo esc_attr( $this->name ); ?>" id="<?php echo $this->get_field_id(); ?>" type="number"
		       value="<?php echo esc_attr( $this->value ); ?>"
		       class="<?php echo esc_attr( $class ); ?>"<?php echo $atts; ?>>
		<?php
	}

}
