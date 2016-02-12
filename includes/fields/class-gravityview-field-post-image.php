<?php
/**
 * @file class-gravityview-field-post-image.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Add custom options for Post Image fields
 */
class GravityView_Field_Post_Image extends GravityView_Field {

	var $name = 'post_image';

	var $_gf_field_class_name = 'GF_Field_Post_Image';

	var $group = 'post';

	public function __construct() {
		$this->label = esc_html__( 'Post Image', 'gravityview' );
		parent::__construct();
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		unset ( $field_options['search_filter'] );

		if( 'edit' === $context ) {
			return $field_options;
		}

		$this->add_field_support('link_to_post', $field_options );

		// @since 1.5.4
		$this->add_field_support('dynamic_data', $field_options );

		return $field_options;
	}

	public function get_field_input( $form, $value = '', $entry = null, GF_Field_Post_Image $field ) {

		$form_id         = $form['id'];
		$id       = (int) $field->id;
		$field_id = 'input_' . $form_id . "_$id";

		$class        = esc_attr( $field->size );

		//hiding meta fields for admin
		$hidden_style      = "style='display:none;'";
		$title_style       = ! $field->displayTitle  ? $hidden_style : '';
		$caption_style     = ! $field->displayCaption  ? $hidden_style : '';
		$description_style = ! $field->displayDescription  ? $hidden_style : '';
		$file_label_style  =  ! ( $field->displayTitle || $field->displayCaption || $field->displayDescription ) ? $hidden_style : '';

		$hidden_class = $preview = '';

		// get image file name if exists
		$ary = ! empty( $value ) ? explode( '|:|', $value ) : array();
		$url = count( $ary ) > 0 ? $ary[0] : '';
		$path = parse_url( $url, PHP_URL_PATH );
		$path_frags = explode( '/', $path );
		$img_name = end( $path_frags );

		$title       = count( $ary ) > 1 ? $ary[1] : '';
		$caption     = count( $ary ) > 2 ? $ary[2] : '';
		$description = count( $ary ) > 3 ? $ary[3] : '';

		if ( !empty( $img_name ) ) {
			$hidden_class     = ' gform_hidden';
			$file_label_style = $hidden_style;
			$preview          = "<span class='ginput_preview'><strong>" . esc_html( $img_name ) . "</strong> | <a href='javascript:;' onclick='gformDeleteUploadedFile( {$form_id}, {$id});'>" . __( 'delete', 'gravityview' ) . '</a></span>';
		}

		//in admin, render all meta fields to allow for immediate feedback, but hide the ones not selected
		$file_label = ( $field->displayTitle || $field->displayCaption || $field->displayDescription ) ? "<label for='$field_id' class='ginput_post_image_file' $file_label_style>" . gf_apply_filters( array( 'gform_postimage_file', $form_id ), __( 'File', 'gravityview' ), $form_id ) . '</label>' : '';

		$tabindex = $field->get_tabindex();

		$upload = sprintf( "<span class='ginput_full'>{$preview}<input name='input_%d' id='%s' type='file' value='' class='%s' $tabindex />$file_label</span>", $id, $field_id,  esc_attr( $class . $hidden_class ) );

		$tabindex = $field->get_tabindex();

		$title_field = $field->displayTitle ? sprintf( "<span class='ginput_full ginput_post_image_title' $title_style><input type='text' name='input_%d.1' id='%s_1' value='%s' $tabindex /><label for='%s_1'>" . gf_apply_filters( array( 'gform_postimage_title', $form_id ), __( 'Title', 'gravityforms' ), $form_id ) . '</label></span>', $id, $field_id, $title,  $field_id ) : '';

		$tabindex = $field->get_tabindex();

		$caption_field = $field->displayCaption ? sprintf( "<span class='ginput_full ginput_post_image_caption' $caption_style><input type='text' name='input_%d.4' id='%s_4' value='%s' $tabindex /><label for='%s_4'>" . gf_apply_filters( array( 'gform_postimage_caption', $form_id ), __( 'Caption', 'gravityforms' ), $form_id ) . '</label></span>', $id, $field_id, $caption,  $field_id ) : '';

		$tabindex = $field->get_tabindex();

		$description_field = $field->displayDescription  ? sprintf( "<span class='ginput_full ginput_post_image_description' $description_style><input type='text' name='input_%d.7' id='%s_7' value='%s' $tabindex /><label for='%s_7'>" . gf_apply_filters( array( 'gform_postimage_description', $form_id ), __( 'Description', 'gravityforms' ), $form_id ) . '</label></span>', $id, $field_id, $description, $field_id ) : '';

		return "<div class='ginput_complex ginput_container ginput_container_post_image'>" . $upload . $title_field . $caption_field . $description_field . '</div>';
	}

}

new GravityView_Field_Post_Image;
