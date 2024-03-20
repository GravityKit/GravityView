<?php
/**
 * @file class-gravityview-field-fileupload.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Pipe_Recorder extends GravityView_Field {

	var $name = 'pipe_recorder';

	var $_gf_field_class_name = 'GF_Field_Pipe_Recorder';

	var $is_searchable = false;

	var $group = 'advanced';

	public function __construct() {
		$this->label = esc_html__( 'Pipe Recorder', 'gk-gravityview' );
		parent::__construct();
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		unset( $field_options['search_filter'] );

		if ( 'edit' === $context ) {
			return $field_options;
		}

		$add_options['embed'] = array(
			'type'       => 'checkbox',
			'label'      => __( 'Display as embedded', 'gk-gravityview' ),
			'desc'       => __( 'Display the video in a player, rather than a direct link to the video.', 'gk-gravityview' ),
			'value'      => true,
			'merge_tags' => false,
		);

		return $add_options + $field_options;
	}
}

new GravityView_Field_Pipe_Recorder();
