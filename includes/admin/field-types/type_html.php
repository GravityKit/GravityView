<?php
/**
 * HTML input type - pass pure HTML into settings in the `desc` key
 * @since 1.17
 */
class GravityView_FieldType_html extends GravityView_FieldType {

	/**
	 * Display HTML, wrapped in container class
	 */
	function render_option() {
		?>
		<div class="<?php echo $this->get_label_class(); ?>">
		<?php echo $this->get_field_desc(); ?>
		</div>
		<?php
	}

	/**
	 * @since 1.17
	 * @return string
	 */
	function get_field_desc() {
		return !empty( $this->field['desc'] ) ? $this->field['desc'] : '';
	}

}
