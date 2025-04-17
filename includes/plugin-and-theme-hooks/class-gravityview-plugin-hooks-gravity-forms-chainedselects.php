<?php
/**
 * Add Gravity Forms Chained Selects compatibility.
 *
 * @file      class-gravityview-plugin-hooks-gravity-forms-chainedselects.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      https://www.gravitykit.com
 * @copyright Copyright 2022, Katz Web Services, Inc.
 */

class GravityView_Plugin_Hooks_Gravity_Forms_Chained_Selects extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @var string $constant_name The name of the constant that, if defined, means the plugin is active.
	 */
	protected $constant_name = 'GF_CHAINEDSELECTS_VERSION';

	const INPUT_TYPE = 'chainedselect';

	/**
	 * @since 1.20
	 */
	protected function add_hooks() {
		parent::add_hooks();

		add_filter( 'gravityview/extension/search/input_type', array( $this, 'set_input_type' ), 10, 3 );

		add_filter( 'gravityview/search/input_types', array( $this, 'add_input_type' ) );

		add_filter( 'gravityview/search/searchable_fields', array( $this, 'modify_searchable_fields' ), 10, 2 );

		add_filter( 'gravityview/search/searchable_fields/allowlist', array( $this, 'modify_searchable_fields_allowlist' ), 10, 3 );

		add_filter( 'gravityview/search/input_labels', array( $this, 'add_input_label' ) );

		add_action( 'gravityview_search_widget_field_before', array( $this, 'print_scripts' ), 10, 2 );

		add_action( 'gravityview_search_widget_field_before', array( $this, 'print_styles' ), 10, 2 );
	}

	/**
	 * Allow all inputs of a Chained Select field to be searched, even though only the parent is added to the widget.
	 *
	 * @param array    $searchable_fields Array of GravityView-formatted fields or only the field ID? Example: [ '1.2', 'created_by' ]
	 * @param \GV\View $view Object of View being searched.
	 * @param bool     $with_full_field Does $searchable_fields contain the full field array or just field ID? Default: false (just field ID)
	 *
	 * @return array If chainedselect search type,
	 */
	function modify_searchable_fields_allowlist( $searchable_fields, $view, $with_full_field ) {

		/**
		 * The first time through, it's just field IDs. We want the full details that include input type.
		 *
		 * @see GravityView_Widget_Search::filter_entries()
		 */
		if ( ! $with_full_field ) {
			return $searchable_fields;
		}

		foreach ( $searchable_fields as $searchable_field ) {

			if ( self::INPUT_TYPE !== \GV\Utils::get( $searchable_field, 'input' ) ) {
				continue;
			}

			$field = GFAPI::get_field( $searchable_field['form_id'], $searchable_field['field'] );

			if( ! $field ) {
				continue;
			}

			foreach ( $field->get_entry_inputs() as $input ) {
				$searchable_fields[] = array(
					'field' => $input['id'],
				);
			}
		}

		return $searchable_fields;
	}

	/**
	 * Outputs inline style for vertical display
	 *
	 * @param GravityView_Widget_Search                                             $this GravityView Widget instance
	 * @param array{key:string,label:string,value:string,type:string,choices:array} $search_field
	 *
	 * @return void
	 */
	public function print_styles( $search_widget, $search_field ) {

		if ( self::INPUT_TYPE !== \GV\Utils::get( $search_field, 'type' ) ) {
			return;
		}

		static $did_print_styles;

		/**
		 * Prevent Chained Select Search Bar input fields from outputting styles.
		 *
		 * @since 2.14.4
		 * @param bool $should_print_styles True: Output styles; False: don't.
		 * @param GravityView_Widget_Search $this GravityView Widget instance.
		 * @param array{key:string,label:string,value:string,type:string,choices:array} $search_field
		 */
		$should_print_styles = apply_filters( 'gravityview/search/chained_selects/print_styles', true, $search_widget, $search_field );

		if ( ! $should_print_styles || $did_print_styles ) {
			return;
		}

		?>
		<style>
			.gfield_chainedselect span {
				display: inline-block;
				padding: 0 4px 0 0;
			}

			.gfield_chainedselect.vertical span {
				display: block;
				padding: 0 0 4px;
			}

			.gfield_chainedselect.vertical select {
				min-width: 100px;
				max-width: 100%;
			}
		</style>
		<?php

		$did_print_styles = true;
	}

	/**
	 * Enqueues and prints the required scripts for
	 *
	 * @param GravityView_Widget_Search $this GravityView Widget instance
	 * @param array                     $search_field
	 *
	 * @return void
	 */
	public function print_scripts( $search_widget, $search_field ) {

		if ( self::INPUT_TYPE !== \GV\Utils::get( $search_field, 'type' ) ) {
			return;
		}

		if ( ! function_exists( 'gf_chained_selects' ) ) {
			gravityview()->log->error( 'The Gravity Forms Chained Select Add-On is not active.' );

			return;
		}

		if ( ! class_exists( 'GFFormDisplay' ) ) {
			return;
		}

		// Adds the gform hooks required by Chained Selects. See gforms_hooks.js.
		if ( empty( GFFormDisplay::$hooks_js_printed ) ) {
			echo GFCommon::get_hooks_javascript_code();
		}

		if ( ! wp_script_is( 'gform_chained_selects' ) ) {
			wp_enqueue_script(
				'gform_chained_selects',
				gf_chained_selects()->get_base_url() . '/js/frontend.js',
				array(
					'jquery',
					'gform_gravityforms',
				),
				gf_chained_selects()->get_version()
			);
		}

		// Print the required JS var that includes the ajaxURL.
		gf_chained_selects()->localize_scripts();

		wp_print_scripts( 'gform_chained_selects' );
	}

	/**
	 * @param GF_Field $gf_field
	 *
	 * @return array
	 */
	public static function get_field_values( $gf_field ) {

		$field_values = array();

		foreach ( $gf_field->get_entry_inputs() as $input ) {

			// Inputs are converted from . to _
			$input_url_arg = 'input_' . str_replace( '.', '_', $input['id'] );

			$field_values[ $input['id'] ] = \GV\Utils::_REQUEST( $input_url_arg );
		}

		return $field_values;
	}

	function add_input_label( $input_labels = array() ) {

		$input_labels[ self::INPUT_TYPE ] = esc_html__( 'Chained Select', 'gk-gravityview' );

		return $input_labels;
	}

	/**
	 * Don't show inputs of the Chained Select field, only the parent.
	 *
	 * @see gravityview_get_form_fields() Used to fetch the fields
	 * @see GravityView_Widget_Search::get_search_input_types See this method to modify the type of input types allowed for a field
	 * @param array $fields Array of searchable fields, as fetched by gravityview_get_form_fields()
	 * @param  int   $form_id
	 *
	 * @return array
	 */
	function modify_searchable_fields( $fields, $form_id ) {

		foreach ( $fields as $key => $field ) {
			if ( 'chainedselect' === $field['type'] && ! empty( $field['parent'] ) ) {
				unset( $fields[ $key ] );
			}
		}

		return $fields;
	}

	/**
	 * Turns Chained Select inputs into the same choices as select/radio inputs.
	 *
	 * @param array $input_types Associative array: key is field `name`, value is array of GravityView input types (note: use `input_text` for `text`).
	 *
	 * @return array
	 */
	public function add_input_type( $input_type ) {

		$input_type[ self::INPUT_TYPE ] = array( self::INPUT_TYPE, 'input_text' );

		return $input_type;
	}

	/**
	 * Turns Chained Select inputs into the same choices as select/radio inputs.
	 *
	 * @param array $input_types Associative array: key is field `name`, value is array of GravityView input types (note: use `input_text` for `text`).
	 *
	 * @return string
	 */
	public function set_input_type( $input_type, $field_type, $field_id ) {

		if ( ! in_array( $field_type, array( 'chainedselect' ) ) ) {
			return $input_type;
		}

		return self::INPUT_TYPE;
	}
}

new GravityView_Plugin_Hooks_Gravity_Forms_Chained_Selects();
