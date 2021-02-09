<?php
/**
 * @file class-gravityview-field-duration.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Duration extends GravityView_Field {

	var $name = 'duration';

	var $_gf_field_class_name = 'GF_Field_Duration';

	var $is_searchable = true;

	var $search_operators = array( 'contains', 'is', 'isnot', 'starts_with', 'ends_with' );

	var $group = 'gravityview';

	var $icon = 'dashicons-hourglass';

	public function __construct() {
		$this->label = esc_html__( 'Duration', 'gravityview' );

		add_filter( 'gform_input_masks', array( $this, 'add_gf_input_mask' ) );
		add_filter( 'gform_add_field_buttons', array( $this, 'add_field_buttons' ), 20 );
		add_action( 'gform_editor_js_set_default_values', array( $this, 'set_defaults' ) );

		parent::__construct();
	}

	/**
	 * Adds duration dropdowns from available masks
	 *
	 * @param array $masks
	 *
	 * @return array
	 */
	public function add_gf_input_mask( $masks = array() ) {

		$masks[ esc_html_x( 'Duration (MM:SS)', 'Duration with hours and minutes', 'gravityview' ) ] = '99:99';
		$masks[ esc_html_x( 'Duration (HH:MM:SS)', 'Duration with hours, minutes, and seconds', 'gravityview' ) ] = '99:99:99';

		return $masks;
	}

	/**
	 * Inject new add field buttons in the gravity form editor page
	 *
	 * @param mixed $field_groups
	 *
	 * @return array Array of fields
	 */
	function add_field_buttons( $field_groups ) {

		foreach ( $field_groups as &$field_group ) {
			if ( 'gravityview_fields' !== \GV\Utils::get( $field_group, 'name' ) ) {
				continue;
			}

			$field_group['fields'][] = array(
				'class'     => 'button',
				'value'     => $this->label,
				'onclick'   => "StartAddField('gravityview_duration');",
				'data-type' => 'gravityview_duration',
				'data-icon' => $this->icon,
			);
		}

		return $field_groups;
	}

	/**
	 * At edit form page, set the field Approve defaults
	 *
	 * @todo Convert to a partial include file
	 * @return void
	 */
	function set_defaults() {
		?>
		case 'gravityview_duration':
		field.label = "<?php echo esc_js( $this->label ); ?>";

		field.type = 'duration';
		field.inputMask = true;
		field.inputMaskIsCustom = false;
		field.inputMaskValue = '99:99';
		field.cssClass = 'gv-math-time';

		break;
		<?php
	}
}

new GravityView_Field_Duration;

class GF_Field_Duration extends GF_Field_Text {
	public $type = 'duration';

	public function get_form_editor_field_title() {
		return esc_attr__( 'Duration', 'gravityview' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to submit the time it takes to perform a task.', 'gravityforms' );
	}


	/**
	 * Returns the field's form editor icon.
	 *
	 * This could be an icon url or a gform-icon class.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'dashicons-hourglass';
	}

}

GF_Fields::register( new GF_Field_Duration() );
