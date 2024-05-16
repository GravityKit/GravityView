<?php
/**
 * GravityView HTML elements that are commonly used
 *
 * Thanks to EDD
 *
 * @see https://github.com/easydigitaldownloads/easy-digital-downloads/blob/master/includes/class-edd-html-elements.php
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2016, Katz Web Services, Inc.
 * @since     1.22.1
 */

class GravityView_HTML_Elements {


	/**
	 * Renders an HTML Dropdown of all the Products (Downloads)
	 *
	 * @since 1.22.1
	 * @param array $args Arguments for the dropdown
	 * @return string $output Dropdown of forms
	 */
	public function form_dropdown( $args = array() ) {

		$defaults = array(
			'active'           => true,
			'trash'            => false,
			'options'          => array(),
			'exclude'          => array(),
			'name'             => 'gravityview_form_id',
			'id'               => 'gravityview_form_id',
			'class'            => '',
			'multiple'         => false,
			'selected'         => 0,
			'show_option_none' => sprintf( '&mdash; %s &mdash;', esc_html__( 'list of forms', 'gk-gravityview' ) ),
			'data'             => array( 'search-type' => 'form' ),
		);

		$args = wp_parse_args( $args, $defaults );

		$forms = gravityview_get_forms( (bool) $args['active'], (bool) $args['trash'] );

		if ( array() === $args['options'] ) {
			foreach ( $forms as $form ) {

				if ( in_array( $form['id'], $args['exclude'] ) ) {
					continue;
				}

				$args['options'][ $form['id'] ] = esc_html( $form['title'] );
			}
		}

		$output = $this->select( $args );

		return $output;
	}

	/**
	 * Renders an HTML Dropdown of all the fields in a form
	 *
	 * @param array $args Arguments for the dropdown
	 * @return string $output Product dropdown
	 */
	public function field_dropdown( $args = array() ) {

		$defaults = array(
			'form_id'          => 0,
			'options'          => array(),
			'name'             => 'gravityview_form_fields',
			'id'               => 'gravityview_form_fields',
			'class'            => '',
			'multiple'         => false,
			'selected'         => 0,
			'show_option_none' => __( 'Select a field', 'gk-gravityview' ),
			'data'             => array( 'search-type' => 'form' ),
		);

		$args = wp_parse_args( $args, $defaults );

		if ( empty( $args['form_id'] ) ) {
			return '';
		}

		$fields = GVCommon::get_sortable_fields_array( $args['form_id'] );

		if ( array() === $args['options'] ) {
			foreach ( $fields as $field_id => $field ) {
				$args['options'][ $field_id ] = esc_html( $field['label'] );
			}
		}

		$output = $this->select( $args );

		return $output;
	}

	/**
	 * Renders an HTML Dropdown
	 *
	 * @since 1.22.1
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function select( $args = array() ) {
		$defaults = array(
			'options'          => array(),
			'name'             => null,
			'class'            => '',
			'id'               => '',
			'selected'         => 0,
			'placeholder'      => null,
			'multiple'         => false,
			'disabled'         => false,
			'show_option_all'  => _x( 'All', 'all dropdown items', 'gk-gravityview' ),
			'show_option_none' => _x( 'None', 'no dropdown items', 'gk-gravityview' ),
			'data'             => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		$data_elements = '';
		foreach ( $args['data'] as $key => $value ) {
			$data_elements .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
		}

		if ( $args['multiple'] ) {
			$multiple = ' MULTIPLE';
		} else {
			$multiple = '';
		}

		if ( $args['placeholder'] ) {
			$placeholder = $args['placeholder'];
		} else {
			$placeholder = '';
		}

		$disabled = $args['disabled'] ? ' disabled="disabled"' : '';
		$class    = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $args['class'] ) ) );
		$output   = '<select name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( str_replace( '-', '_', $args['id'] ) ) . '" class="gravityview-select ' . $class . '"' . $multiple . $disabled . ' data-placeholder="' . $placeholder . '"' . $data_elements . '>';

		if ( ! empty( $args['options'] ) ) {

			if ( $args['show_option_none'] ) {
				if ( $args['multiple'] ) {
					$selected = selected( true, in_array( -1, $args['selected'] ), false );
				} else {
					$selected = selected( $args['selected'], -1, false );
				}
				$output .= '<option value="-1"' . $selected . '>' . esc_html( $args['show_option_none'] ) . '</option>';
			}

			foreach ( $args['options'] as $key => $option ) {

				if ( $args['multiple'] && is_array( $args['selected'] ) ) {
					$selected = selected( true, in_array( $key, $args['selected'], true ), false );
				} else {
					$selected = selected( $args['selected'], $key, false );
				}

				$output .= '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $option ) . '</option>';
			}
		}

		$output .= '</select>';

		return $output;
	}
}
