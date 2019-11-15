<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group admin
 */
class GravityView_Render_Settings_Test extends GV_UnitTestCase {

	public function setUp() {
		parent::setUp();

		// Include required files
		$Admin = new GravityView_Admin;
		$Admin->backend_actions();
	}

	/**
	 * @since todo
	 *
	 * @covers GravityView_Render_Settings::render_field_options
	 * @covers GravityView_Render_Settings::get_default_field_options
	 */
	public function test_render_field_options() {

		$form = $this->factory->form->create_and_get();

		$output = GravityView_Render_Settings::render_field_options( $form['id'], 'field', 'default_table', '12', 'Example Field Label', 'directory_table-columns', 'multiselect', 'UNIQID_EXAMPLE', '', 'single', array() );

		#<input type="hidden" class="field-key" name="fields[directory_table-columns][UNIQID_EXAMPLE][id]" value="12">'
		$this->assertContains( 'name="fields[directory_table-columns][UNIQID_EXAMPLE][id]" value="12"', $output );
		$this->assertContains( 'name="fields[directory_table-columns][UNIQID_EXAMPLE][label]" value="Example Field Label"', $output );
		$this->assertContains( 'name="fields[directory_table-columns][UNIQID_EXAMPLE][form_id]" value="' . $form['id'] . '"', $output );
		$this->assertContains( 'name="fields[directory_table-columns][UNIQID_EXAMPLE][only_loggedin]" type="hidden" value="0"', $output );
		$this->assertContains( 'show_label" type="checkbox" value="1"  checked=\'checked\' />', $output );

		add_filter( 'gravityview_template_field_options', $_change_field_options = function ( $default_options = array() ) {

			$new_defaults = $default_options;

			$new_defaults['show_label']['value']    = false;
			$new_defaults['only_loggedin']['value'] = true;


			return $new_defaults;
		} );

		$output = GravityView_Render_Settings::render_field_options( $form['id'], 'field', 'default_table', '12', 'Example Field Label', 'directory_table-columns', 'multiselect', 'UNIQID_EXAMPLE', '', 'single', array() );

		$this->assertContains( 'only_loggedin" type="checkbox" value="1"  checked=\'checked\' />', $output );
		$this->assertContains( 'show_label" type="checkbox" value="1"  />', $output );

		$this->assertTrue( remove_filter( 'gravityview_template_field_options', $_change_field_options ) );
	}

	/**
	 * @since todo
	 *
	 * @covers GravityView_Render_Settings::get_default_field_options
	 */
	public function test_get_default_field_options() {

		$widget_options = GravityView_Render_Settings::get_default_field_options( 'widget', 'template_id', 'field_id', 'context', 'input_type', 'form_id' );
		$this->assertEmpty( $widget_options );

		add_filter( 'gravityview_template_widget_options', $_filter_widget_options = function ( $options ) {
			return array( 'custom_option' => array( 'type' => 'text', 'value' => 'GRATE IDEA' ) );
		} );

		$widget_options = GravityView_Render_Settings::get_default_field_options( 'widget', 'template_id', 'field_id', 'context', 'input_type', 'form_id' );

		$this->assertArrayHasKey( 'custom_option', $widget_options );

		remove_filter( 'gravityview_template_widget_options', $_filter_widget_options );


		$table_single_options    = GravityView_Render_Settings::get_default_field_options( 'field', 'table', 'field_id', 'single', 'input_type', 'form_id' );
		$table_directory_options = GravityView_Render_Settings::get_default_field_options( 'field', 'table', 'field_id', 'directory', 'input_type', 'form_id' );
		$this->assertArrayNotHasKey( 'width', $table_single_options );
		$this->assertArrayHasKey( 'width', $table_directory_options );

		$this->assertArrayNotHasKey( 'input_type_filters_are_cool', $table_directory_options );
		add_filter( 'gravityview_template_input_type_options', $_filter_input_type = function ( $options ) {

			$options['input_type_filters_are_cool'] = true;

			return $options;
		} );

		$input_type_options_filter = GravityView_Render_Settings::get_default_field_options( 'field', 'table', 'field_id', 'directory', 'input_type', 'form_id' );
		$this->assertArrayHasKey( 'input_type_filters_are_cool', $input_type_options_filter );

		remove_filter( 'gravityview_template_input_type_options', $_filter_input_type );
	}

	/**
	 * @covers GravityView_Render_Settings::get_cap_choices
	 *
	 * @since todo
	 */
	public function test_get_cap_choices() {

		$caps = GravityView_Render_Settings::get_cap_choices();

		if ( is_multisite() ) {
			$this->assertArrayHasKey( 'manage_network', $caps );
		} else {
			$this->assertArrayNotHasKey( 'manage_network', $caps );
		}

		add_filter( 'gravityview_field_visibility_caps', '__return_empty_array' );

		$caps = GravityView_Render_Settings::get_cap_choices();

		$this->assertEmpty( $caps );

		remove_all_filters( 'gravityview_field_visibility_caps' );
	}

}