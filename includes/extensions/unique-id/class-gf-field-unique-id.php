<?php
/**
 * The GravityView Delete Entry Extension
 *
 * Delete entries in GravityView.
 *
 * @since     1.5.1
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityView <hello@gravityview.co>
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class GF_Field_GV_Unique_ID extends GF_Field_Hidden {

	public $type = 'unique_id';

	public $_supports_state_validation = false;

	protected static $instance;

	/**
	 * GF_Field_GV_Unique_ID constructor.
	 */
	public function __construct( $data = array() ) {

		parent::__construct( $data );

		if ( ! empty( $data['init'] ) ) {
			$this->add_hooks();
		}
	}

	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new self( array( 'init' => true ) );
		}

		return self::$instance;
	}

	/**
	 * Returns the field title.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_html__( 'Unique ID', 'gravityview' );
	}

	/**
	 * Sets the default field label for the field.
	 *
	 * @return string
	 */
	public function get_form_editor_inline_script_on_page_render() {
		return sprintf( "function SetDefaultValues_%s(field) {field.label = '%s';}", $this->type, esc_html__( 'Unique ID', 'gravityview' ) ) . PHP_EOL;
	}

	public function get_form_editor_field_icon() {
		return 'gform-icon--circle-check-alt';
	}

	protected function is_input_valid( $input_id ) {
		return true;
	}

	/**
	 * @param $form
	 * @param $value
	 * @param $entry
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		return sprintf( "<div class='ginput_container gform_hidden ginput_container_text'><input style='%s' value='%s' disabled='disabled'></div>", 'border:1px dashed #ccc;background-color:transparent;text-transform:lowercase;width: 100%;text-align:center;font-size: 0.9375rem;padding: 0.5rem;line-height: 2;border-radius: 4px;', 'we’re going to be in the back room today;' );

		return parent::get_field_input( $form, $value, $entry );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to grant access to a website using TrustedLogin.', 'trustedlogin-gf' );
	}

	/**
	 * This field supports conditional logic.
	 *
	 * @since 2.6
	 *
	 * @return bool
	 */
	public function is_conditional_logic_supported() {
		return true;
	}

	/**
	 * Decides if the field markup should not be reloaded after AJAX save.
	 *
	 * @since 2.6
	 *
	 * @return boolean
	 */
	public function disable_ajax_reload() {
		return true;
	}

	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'rules_setting',
			'placeholder_setting',
			'default_value_setting',
			'visibility_setting',
			'duplicate_setting',
			'description_setting',
			'css_class_setting',
			'autocomplete_setting',
			'unique_id_type_setting',
		);
	}

	public function add_hooks() {

		$hook_priority = 5;

		// Run at 5 priority so the unique ID is created before feeds are processed at default 10 priority.
		add_filter( 'gform_entry_post_save', array( $this, 'maybe_set_field_value' ), $hook_priority, 2 );
		add_action( 'gform_post_add_entry', array( $this, 'maybe_set_field_value' ), $hook_priority, 2 );
//		 add_action( 'gform_paypal_fulfillment', array( $this, 'delayed_populate_field_value' ), $hook_priority );

		add_action( 'gform_field_css_class', array( $this, 'maybe_add_gform_field_css_class' ), 10, 2 );

		add_action( 'gform_field_standard_settings', array( $this, 'render_field_settings' ), 10, 2 );
	}

	public function render_field_settings( $position = 25, $form_id = 0 ) {

		if ( 25 !== $position ) {
			return;
		}

		?>
<li class='field_setting unique_id_type_setting'>

	<div>
		<label for="unique_id_type" class='section_label'>
			<?php esc_html_e( 'Unique ID Type', 'gp-unique-id' ); ?>
		</label>
		<select name="unique_id_type"
		        id="unique_id_type"
		        onchange="SetFieldProperty( 'unique_id_type', this.value );">
			<?php foreach ( $this->get_unique_id_types() as $value => $type ) : ?>
				<?php printf( '<option value="%s">%s</option>', $value, $type['label'] ); ?>
			<?php endforeach; ?>
		</select>
	</div>

</li><?php
	}

	public function get_unique_id_types() {

		$print_vars = array(
			'<code>',
			'</code>',
		);

		$uid_types = array(
			'alphanumeric' => array(
				'label'       => __( 'Alphanumeric', 'gp-unique-id' ),
				'description' => sprintf( __( 'Contains letters and numbers (i.e. %1$sa12z9%2$s).', 'gp-unique-id' ), $print_vars[0], $print_vars[1] ),
			),
			'numeric'      => array(
				'label'       => __( 'Numeric', 'gp-unique-id' ),
				'description' => sprintf( __( 'Contains only numbers (i.e. %1$s152315902%2$s).', 'gp-unique-id' ), $print_vars[0], $print_vars[1] ),
			),
			'sequential'   => array(
				'label'       => __( 'Sequential', 'gp-unique-id' ),
				'description' => sprintf( __( 'Contains only numbers and is sequential with previously generated IDs per field (i.e. %1$s1%2$s, %1$s2%2$s, %1$s3%2$s).', 'gp-unique-id' ), $print_vars[0], $print_vars[1] ),
			),
		);

		return $uid_types;
	}

	/**
	 * @param string $css_class
	 * @param GF_Field $field
	 *
	 * @return string
	 */
	public function maybe_add_gform_field_css_class( $css_class, $field ) {

		if ( $this->type !== $field->get_input_type() ) {
			return $css_class;
		}

		if ( ! $this->is_form_editor() ) {
			return $css_class;
		}

		$css_class .= ' gform_hidden';

		return $css_class;
	}

	public function maybe_set_field_value( $entry = array(), $form = array() ) {

		// This is not a completed entry yet!
		if ( rgar( $entry, 'partial_entry_id' ) ) {
			return $entry;
		}

		$unique_id_fields = GFCommon::get_fields_by_type( $form, $this->type );

		if ( empty( $unique_id_fields ) ) {
			return $entry;
		}

		/** @var GF_Field_GV_Unique_ID $unique_id_field */
		foreach ( $unique_id_fields as $unique_id_field ) {

			#$default_value = rgar( $entry, $unique_id_field->id, rgpost( "input_{$field->id}" ) );

			$value = $this->get_unique_value( $unique_id_field );
			$value = $this->save_value( $entry, $unique_id_field, $value );

			if ( $value ) {
				$entry[ $unique_id_field->id ] = $value;
			}
		}

		return $entry;
	}

	/**
	 * @param array $entry
	 * @param GF_Field_GV_Unique_ID $field
	 * @param $value
	 *
	 * @return false
	 */
	private function save_value( $entry, $field, $value ) {

		if ( ! is_array( $entry ) ) {
			return false;
		}

		$result = GFAPI::update_entry_field( $entry['id'], $field->id, $value );

		return $result ? $value : false;
	}

	private function get_unique_value( $field ) {

		$length = 8;

		return self::get_random_hash( $length );

		return self::get_random_number( $length );
	}

	static public function get_random_number( $default_length = 8 ) {

		$length = (int) apply_filters( 'gravityview/fields/unique-id/number_length', $default_length );

		// If the filter is corrupted
		if ( ! $length ) {
			$length = $default_length;
		}

		// Make sure not greater than the PHP_INT_MAX, which is 19 digits long.
		$length = min( $length, 19 );

		$range_bottom = pow( 10, $length - 1 ); // 100000
		$range_top    = pow( 10, $length ) - 1; // 999999

		$number = mt_rand( 0, min( $range_top, PHP_INT_MAX ) );

		return str_pad( $number, $length, '0', STR_PAD_LEFT );
	}

	static public function get_random_hash() {

		$byte_length = 8; // Character length will be double this

		$hash = false;

		if ( function_exists( 'random_bytes' ) ) {
			try {
				$bytes = random_bytes( $byte_length );
				$hash  = bin2hex( $bytes );
			} catch ( \TypeError $e ) {
				GFCommon::log_error( $e->getMessage() );
			} catch ( \Error $e ) {
				GFCommon::log_error( $e->getMessage() );
			} catch ( \Exception $e ) {
				GFCommon::log_error( $e->getMessage() );
			}
		}

		if ( $hash ) {
			return $hash;
		}

		if ( ! function_exists( 'openssl_random_pseudo_bytes' ) ) {
			return new WP_Error( 'generate_hash_failed', 'Could not generate a secure hash with random_bytes or openssl.' );
		}

		return openssl_random_pseudo_bytes( $byte_length, $crypto_strong );
	}

	public function update_entry_field( $entry, $field, $value ) {

		if ( ! $value ) {
			gp_unique_id()->log( sprintf( 'Generating a unique ID for field %d', $field->id ) );
			$value = gp_unique_id()->get_unique( $entry['form_id'], $field, 5, array(), $entry );
		}

		gp_unique_id()->log( sprintf( 'Saving unique ID for field %d: %s', $field->id, $value ) );

		$result = GFAPI::update_entry_field( $entry['id'], $field->id, $value );

		return $result ? $value : false;
	}

	public function should_be_validated() {
		return false;
	}

}

GF_Fields::register( GF_Field_GV_Unique_ID::get_instance() );
