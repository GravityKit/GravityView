<?php

use GV\Utils;

/**
 * text input type
 */
class GravityView_FieldType_text extends GravityView_FieldType {
	function render_setting( $override_input = null ) {
		if ( ! empty( $this->field['full_width'] ) ) { ?>
			<th scope="row" colspan="2">
				<div>
					<?php $this->render_option(); ?>
				</div>
			</th>
		<?php } else {
			parent::render_setting( $override_input );
		}
	}

	function render_option() {
		?>
		<label for="<?php echo $this->get_field_id(); ?>" class="<?php echo $this->get_label_class(); ?>">
								<?php

								echo $this->get_field_label() . $this->get_tooltip() . $this->get_field_desc();
								?>
		<div>
				<?php $this->render_input(); ?>
			</div>
		</label>
		<?php
	}

	function render_input( $override_input = null ) {

		if ( isset( $override_input ) ) {
			echo $override_input;
			return;
		}

		$class = '';

		$show_mt = $this->show_merge_tags();

		if ( $show_mt && false !== $this->field['merge_tags'] || 'force' === $this->field['merge_tags'] ) {
			$class = 'gv-merge-tag-support mt-position-right mt-hide_all_fields ';
		}
		$class      .= Utils::get( $this->field, 'class', 'widefat' );
		$placeholder = Utils::get( $this->field, 'placeholder' );
		$validation  = Utils::get( $this->field, 'validation' );

		printf(
			'<input name="%s" placeholder="%s" id="%s" type="text" value="%s" class="%s" data-rules="%s">',
			esc_attr( $this->name ),
			esc_attr( $placeholder ),
			$this->get_field_id(),
			esc_attr( $this->value ),
			esc_attr( $class ),
			esc_attr( json_encode( $validation ) )
		);
	}
}



