<?php
/**
 * @file class-gravityview-field-address.php
 * @package GravityView
 * @subpackage includes\fields
 */

use GV\Utils;

/**
 * Add custom options for address fields
 */
class GravityView_Field_Address extends GravityView_Field {

	/** @inheritDoc  */
	var $name = 'address';

	/** @inheritDoc  */
	var $group = 'advanced';

	/** @inheritDoc  */
	var $is_numeric = false;

	/** @inheritDoc  */
	var $is_searchable = true;

	/** @inheritDoc  */
	var $search_operators = array( 'is', 'isnot', 'contains' );

	/**
	 * @since 2.8.1
	 * @var string
	 */
	var $icon = 'dashicons-location-alt';

	/** @inheritDoc  */
	var $_gf_field_class_name = 'GF_Field_Address';

	public function __construct() {
		$this->label = esc_html__( 'Address', 'gk-gravityview' );

		$this->add_hooks();

		parent::__construct();
	}

	/**
	 * Add filters for this field type
	 *
	 * @since 1.19.2
	 *
	 * @return void
	 */
	private function add_hooks() {
		add_filter( 'gravityview/extension/search/input_type', array( $this, 'search_bar_input_type' ), 10, 3 );
		add_filter( 'gravityview/search/input_types', array( $this, 'input_types' ) );
		add_filter( 'gravityview_widget_search_filters', array( $this, 'search_field_filter' ), 10, 3 );
	}

	/**
	 * Dynamically add choices to the address field dropdowns, if any
	 *
	 * @since 1.19.2
	 * @since 2.42.2 Removed `$widget` and `$widget_args` parameters.
	 *
	 * @param array $search_fields Array of search filters with `key`, `label`, `value`, `type` keys
	 *
	 * @return array If the search field GF Field type is `address`, and there are choices to add, adds them and changes the input type. Otherwise, sets the input to text.
	 */
	public function search_field_filter( $search_fields ) {
		foreach ( $search_fields as &$search_field ) {

			if ( 'address' !== Utils::get( $search_field, 'gf_field_type' ) ) {
				continue;
			}

			$field_id = intval( floor( $search_field['key'] ) );
			$input_id = gravityview_get_input_id_from_id( $search_field['key'] );
			$form     = GravityView_View::getInstance()->getForm();

			/** @type GF_Field_Address $address_field */
			$address_field = GFFormsModel::get_field( $form, $field_id );

			$choices = array();

			$method_name = 'get_choices_' . self::get_input_type_from_input_id( $input_id );
			if ( method_exists( $this, $method_name ) ) {
				/**
				 * @uses GravityView_Field_Address::get_choices_country()
				 * @uses GravityView_Field_Address::get_choices_state()
				 */
				$choices = $this->{$method_name}( $address_field, $form );
			}

			if ( ! empty( $choices ) ) {
				$search_field['choices'] = $choices;
				$search_field['type']    = Utils::get( $search_field, 'input' );
			} else {
				$search_field['type']  = 'text';
				$search_field['input'] = 'input_text';
			}
		}

		return $search_fields;
	}

	/**
	 * Get array of countries to use for the search choices
	 *
	 * @since 1.19.2
	 *
	 * @see GF_Field_Address::get_countries()
	 *
	 * @param GF_Field_Address $address_field
	 *
	 * @return array Array of countries with `value` and `text` keys as the name of the country
	 */
	private function get_choices_country( $address_field ) {

		$countries = $address_field->get_countries();

		$country_choices = array();

		foreach ( $countries as $key => $country ) {
			$country_choices[] = array(
				'value' => $country,
				'text'  => $country,
			);
		}

		return $country_choices;
	}

	/**
	 * Get array of states to use for the search choices
	 *
	 * @since 1.19.2
	 *
	 * @uses GF_Field_Address::get_us_states()
	 * @uses GF_Field_Address::get_us_state_code()
	 * @uses GF_Field_Address::get_canadian_provinces()
	 *
	 * @param GF_Field_Address $address_field
	 * @param array            $form
	 *
	 * @return array Array of countries with `value` and `text` keys as the name of the country
	 */
	private function get_choices_state( $address_field, $form ) {

		$address_type = empty( $address_field->addressType ) ? $address_field->get_default_address_type( $form['id'] ) : $address_field->addressType;

		$state_choices = array();

		switch ( $address_type ) {
			case 'us':
				$states = GFCommon::get_us_states();
				break;
			case 'canadian':
				$states = GFCommon::get_canadian_provinces();
				break;
			default:
				$address_types = $address_field->get_address_types( $form['id'] );
				$states        = empty( $address_types[ $address_type ]['states'] ) ? array() : $address_types[ $address_type ]['states'];
				break;
		}

		foreach ( $states as $key => $state ) {
			if ( is_array( $state ) ) {
				$state_subchoices = array();

				foreach ( $state as $key => $substate ) {
					$state_subchoices[] = array(
						'value' => is_numeric( $key ) ? $substate : $key,
						'text'  => $substate,
					);
				}

				$state_choices[] = array(
					'text'  => $key,
					'value' => $state_subchoices,
				);

			} else {
				$state_choices[] = array(
					'value' => is_numeric( $key ) ? $state : $key,
					'text'  => $state,
				);
			}
		}

		return $state_choices;
	}

	/**
	 * Add the input types available for each custom search field type
	 *
	 * @since 1.19.2
	 *
	 * @param array $input_types Array of input types as the keys (`select`, `radio`, `multiselect`, `input_text`) with a string or array of supported inputs as values
	 *
	 * @return array $input_types array, but
	 */
	public function input_types( $input_types ) {

		// Use the same inputs as the "text" input type allows
		$text_inputs = Utils::get( $input_types, 'text' );

		$input_types['street']  = $text_inputs;
		$input_types['street2'] = $text_inputs;
		$input_types['city']    = $text_inputs;

		$input_types['state']   = array( 'select', 'radio', 'link' ) + $text_inputs;
		$input_types['zip']     = array( 'input_text' );
		$input_types['country'] = array( 'select', 'radio', 'link' ) + $text_inputs;

		return $input_types;
	}

	/**
	 * Converts the custom input type (address) into the selected type
	 *
	 * @since 1.19.2
	 *
	 * @param string           $input_type Assign an input type according to the form field type. Defaults: `boolean`, `multi`, `select`, `date`, `text`
	 * @param string           $field_type Gravity Forms field type (also the `name` parameter of GravityView_Field classes)
	 * @param string|int|float $field_id ID of the field being processed
	 *
	 * @return string If the field ID matches an address field input, return those options {@see GravityView_Field_Address::input_types() }. Otherwise, original value is used.
	 */
	public function search_bar_input_type( $input_type, $field_type, $field_id ) {

		// Is this search field for an input (eg: 4.2) or the whole address field (eg: 4)?
		$input_id = gravityview_get_input_id_from_id( $field_id );

		if ( 'address' !== $field_type && $input_id ) {
			return $input_type;
		}

		// If the input ID matches an expected address input, set to that. Otherwise, keep existing input type.
		if ( $address_field_name = self::get_input_type_from_input_id( $input_id ) ) {
			$input_type = $address_field_name;
		}

		return $input_type;
	}

	/**
	 * Get a name for the input based on the input ID
	 *
	 * @since 1.19.2
	 *
	 * @param int $input_id ID of the specific input for the address field
	 *
	 * @return false|string If the input ID matches a known address field input, returns a name for that input ("city", or "country"). Otherwise, returns false.
	 */
	private static function get_input_type_from_input_id( $input_id ) {

		$input_type = false;

		switch ( $input_id ) {
			case 1:
				$input_type = 'street';
				break;
			case 2:
				$input_type = 'street2';
				break;
			case 3:
				$input_type = 'city';
				break;
			case 4:
				$input_type = 'state';
				break;
			case 5:
				$input_type = 'zip';
				break;
			case 6:
				$input_type = 'country';
				break;
		}

		return $input_type;
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		// If this is NOT the full address field, return default options.
		if ( floor( $field_id ) !== floatval( $field_id ) ) {
			return $field_options;
		}

		if ( 'edit' === $context ) {
			return $field_options;
		}

		$add_options = array();

		$add_options['show_map_link'] = array(
			'type'       => 'checkbox',
			'label'      => __( 'Show Map Link:', 'gk-gravityview' ),
			'desc'       => __( 'Display a "Map It" link below the address', 'gk-gravityview' ),
			'value'      => true,
			'merge_tags' => false,
			'group'      => 'display',
			'priority'   => 98,
		);

		$add_options['show_map_link_new_window'] = array(
			'type'       => 'checkbox',
			'label'      => __( 'Open map link in a new tab or window?', 'gk-gravityview' ),
			'value'      => false,
			'merge_tags' => false,
			'group'      => 'display',
			'requires'   => 'show_map_link',
			'priority'   => 99,
		);

		return $add_options + $field_options;
	}
}

new GravityView_Field_Address();
