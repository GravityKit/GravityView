<?php

/**
 * HTML input type - pass pure HTML into settings in the `desc` key
 *
 * @since 1.17
 */
class GravityView_FieldType_html extends GravityView_FieldType {

	/**
	 * Display HTML, wrapped in container class
	 */
	public function render_option() {
		?>
		<div class="<?php echo $this->get_label_class(); ?>" id="<?php echo $this->get_field_id(); ?>">
			<?php echo $this->get_field_desc(); ?>
		</div>
		<?php
	}

	/**
	 * @since 1.17
	 * @return string
	 */
	public function get_field_desc() {
		$html = $this->field['desc'] ?? '';
		if ( is_callable( $html ) ) {
			return (string) $html(
				[
					'name'  => $this->name,
					'value' => $this->value,
				]
			);
		}

		return (string) $html;
	}
}
