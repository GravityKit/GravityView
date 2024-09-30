<?php
/**
 * The default survey field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

/** @var \GV\Field $field */
$field         = $gravityview->field;
$display_value = $gravityview->display_value;
$input_id      = gravityview_get_input_id_from_id( $field->ID );

// Used in filters below.
$return_true = function () {
	return true;
};

// Backward compatibility for the `score` field setting checkbox before migrating to `choice_display` radio
$default_display = $field->score ? 'score' : 'default';

$choice_display = \GV\Utils::get( $field, 'choice_display', $default_display );

switch ( $gravityview->field->field->inputType ) {
	case 'text':
	case 'textarea':
	case 'radio':
	case 'rank':
	case 'select':
	default:
		echo $display_value;
		return;  // Return early
	case 'checkbox':
		// Display the <ul>
		if ( ! $input_id ) {
			echo $display_value;
			return;
		}

		if ( 'tick' === $choice_display || 'default' === $choice_display ) {
			/**
			 * Filter is defined in /templates/fields/field-checkbox-html.php
			 */
			echo apply_filters( 'gravityview_field_tick', '<span class="dashicons dashicons-yes"></span>', $gravityview->entry, $field->as_configuration(), $gravityview );

			return; // Return early
		}

		echo RGFormsModel::get_choice_text( $field->field, $gravityview->value, $field->ID );

		return; // Return early
	case 'likert':
		if ( class_exists( 'GFSurvey' ) && is_callable( array( 'GFSurvey', 'get_instance' ) ) ) {

			if ( version_compare( GFSurvey::get_instance()->get_version(), '3.8', '>=' ) ) {
				wp_register_style( 'gsurvey_css', GFSurvey::get_instance()->get_base_url() . '/assets/css/dist/admin.css' );
			} else {
				wp_register_style( 'gsurvey_css', GFSurvey::get_instance()->get_base_url() . '/css/gsurvey.css' );
			}

			wp_print_styles( 'gsurvey_css' );
		}

		// Gravity Forms-generated Likert table output
		if ( 'default' === $choice_display || empty( $choice_display ) ) {

			// Default is the likert table; show it and return early.
			if ( $field->field->gsurveyLikertEnableMultipleRows && ! $input_id ) {

				add_filter( 'gform_is_entry_detail', $return_true );

				echo '<div class="gform-settings__content gform-settings-panel__content">';
				echo $field->field->get_field_input( \GVCommon::get_form( $field->form_id ), $gravityview->value );
				echo '</div>';

				remove_filter( 'gform_is_entry_detail', $return_true );
				return;  // Return early
			}
		}

		// Force the non-multirow fields into the same formatting (row:column)
		$raw_value = is_array( $gravityview->value ) ? $gravityview->value : array( $field->ID => ':' . $gravityview->value );

		add_filter( 'gform_is_entry_detail', $return_true );

		$output_values = array();
		foreach ( $raw_value as $row => $row_values ) {
			list( $_likert_row, $row_value ) = array_pad( explode( ':', $row_values ), 2, '' );

			// If we're displaying a single row, don't include other row values
			if ( $input_id && $row !== $field->ID ) {
				continue;
			}

			switch ( $choice_display ) {
				case 'score':
					$output_values[] = GravityView_Field_Survey::get_choice_score( $field->field, $row_value, $row );
					break;
				case 'text':
					$output_values[] = RGFormsModel::get_choice_text( $field->field, $row_value, $row );
					break;
				case 'default':
				default:
					// When displaying a single input, render as if multiple rows were disabled
					/** @var GF_Field_Likert $single_input_field */
					$single_input_field                                  = clone $field->field;
					$single_input_field->id                              = $field->ID;
					$single_input_field->gsurveyLikertEnableMultipleRows = false;
					$output_values[]                                     = $single_input_field->get_field_input( array( 'id' => $field->form_id ), $row_value );
					break;
			}
		}

		remove_filter( 'gform_is_entry_detail', $return_true );

		/**
		 * The value used to separate multiple values in the Survey field output.
		 *
		 * @since 2.10.4
		 *
		 * @param string The glue. Default: "; " (semicolon with a trailing space)
		 * @param \GV\Template_Context The context.
		 */
		$glue = apply_filters( 'gravityview/template/field/survey/glue', '; ', $gravityview );

		echo '<div class="gform-settings__content gform-settings-panel__content">';
		echo implode( $glue, $output_values );
		echo '</div>';

		return; // Return early

	case 'rating':
		$choice_text = RGFormsModel::get_choice_text( $field->field, $gravityview->value, $input_id );

		if ( ! in_array( $choice_display, array( 'stars', 'dashicons', 'emoji' ), true ) ) {
			echo $choice_text;
			return;
		}

		$choices = $field->field->choices;

		// If the choices are reversed, reverse them back.
		if ( ! empty( $choices ) && $choices[0]['text'] === 'Excellent' ) {
			$choices = array_reverse( $choices );
		}

		$choice_values = wp_list_pluck( $choices, 'value', $gravityview->value );
		$starred_index = array_search( $gravityview->value, $choice_values );
		$star_a11y_label = $starred_index !== false
			? sprintf( __( '%s (%d out of %d stars)', 'gk-gravityview'), $choice_text, $starred_index + 1, count( $choice_values ) )
			: '';

		/**
		 * @action `gravityview/field/survey/rating-styles`
		 * @usedby {@see GravityView_Field_Survey::output_frontend_css} to Enqueue styles for the Survey field.
		 * @since 2.16
		 * @param \GV\GF_Field $field The current field.
		 * @param \GV\Template_Context $gravityview The context.
		 */
		do_action( 'gravityview/template/field/survey/rating/before', $field, $gravityview );

		echo '<span class="gv-field-survey-screen-reader-text">' . esc_html( $star_a11y_label ) . '</span>';
		foreach ( $choices as $current_index => $choice_value ) {

			// Have we already shown the last filled-in star?
			$empty = ( $starred_index === false || $current_index > $starred_index );
			$css_class = 'gv-field-survey-star-' . ( $empty ? 'empty' : 'filled' );

			printf( '<span class="%s" title="%s"></span>', esc_attr( $css_class ), esc_attr( $choice_value['text'] ) );
		}


		return;
}
