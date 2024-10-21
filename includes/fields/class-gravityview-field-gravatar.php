<?php
/**
 * @file class-gravityview-field-gravatar.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * @since 2.8
 */
class GravityView_Field_Gravatar extends GravityView_Field {

	var $name = 'gravatar';

	var $is_searchable = false;

	var $group = 'gravityview';

	var $contexts = array( 'single', 'multiple', 'export' );

	var $icon = 'dashicons-id';

	public function __construct() {
		$this->label       = esc_html__( 'Gravatar', 'gk-gravityview' );
		$this->description = esc_html__( 'A Gravatar is an image that represents a person online based on their email. Powered by gravatar.com.', 'gk-gravityview' );

		$this->add_hooks();

		parent::__construct();
	}

	/**
	 * Add filters for this field
	 */
	public function add_hooks() {
		add_filter( 'gravityview_entry_default_fields', array( $this, 'add_default_field' ), 10, 3 );
	}

	/**
	 * Add this field to the default fields in the GV field picker
	 *
	 * @param array        $entry_default_fields Array of fields shown by default
	 * @param string|array $form form_ID or form object
	 * @param string       $zone Either 'single', 'directory', 'edit', 'header', 'footer'
	 *
	 * @return array
	 */
	function add_default_field( $entry_default_fields = array(), $form = array(), $zone = '' ) {

		if ( 'edit' === $zone ) {
			return $entry_default_fields;
		}

		$entry_default_fields[ $this->name ] = array(
			'label' => $this->label,
			'desc'  => $this->description,
			'type'  => $this->name,
			'icon'  => 'dashicons-id',
		);

		return $entry_default_fields;
	}

	/**
	 * Get the email address to use, based on field settings
	 *
	 * @internal May change in the future! Don't rely on this.
	 *
	 * @param array $field_settings
	 * @param array $entry Gravity Forms entry
	 *
	 * @return string Email address from field or from entry creator
	 */
	public static function get_email( $field_settings, $entry ) {

		// There was no logged in user.
		switch ( $field_settings['email_field'] ) {
			case 'created_by_email':
				$created_by = \GV\Utils::get( $entry, 'created_by', null );

				if ( empty( $created_by ) ) {
					return '';
				}

				$user = get_user_by( 'id', $created_by );

				$email = $user ? $user->user_email : '';
				break;
			default:
				$field_id = \GV\Utils::get( $field_settings, 'email_field' );
				$email    = rgar( $entry, $field_id );
				break;
		}

		return $email;
	}

	/**
	 * @inheritDoc
	 */
	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		if ( 'edit' === $context ) {
			return $field_options;
		}

		unset( $field_options['new_window'] );

		$field_options['email_field'] = array(
			'type'    => 'select',
			'label'   => __( 'Email to Use', 'gk-gravityview' ),
			'value'   => 'created_by_email',
			'desc'    => __( 'Which email should be used to generate the Gravatar?', 'gk-gravityview' ),
			'choices' => $this->_get_email_field_choices( $form_id ),
			'group'   => 'display',
		);

		$field_options['default'] = array(
			'type'    => 'select',
			'label'   => __( 'Default Image', 'gk-gravityview' ),
			'desc'    => __( 'Choose the default image to be shown when an email has no Gravatar.', 'gk-gravityview' ) . ' <a href="https://en.gravatar.com/site/implement/images/">' . esc_html( sprintf( __( 'Read more about %s', 'gk-gravityview' ), __( 'Default Image', 'gk-gravityview' ) ) ) . '</a>',
			'value'   => get_option( 'avatar_default', 'mystery' ),
			'choices' => array(
				'mystery'          => __( 'Silhouetted Person', 'gk-gravityview' ),
				'gravatar_default' => __( 'Gravatar Icon', 'gk-gravityview' ),
				'identicon'        => __( 'Abstract Geometric Patterns', 'gk-gravityview' ),
				'monsterid'        => __( 'Monster Faces', 'gk-gravityview' ),
				'retro'            => __( 'Arcade-style Faces', 'gk-gravityview' ),
				'robohash'         => __( 'Robot Faces', 'gk-gravityview' ),
				'blank'            => __( 'Transparent Image', 'gk-gravityview' ),
			),
			'group'   => 'display',
		);

		$field_options['size'] = array(
			'type'       => 'number',
			'label'      => __( 'Size in Pixels', 'gk-gravityview' ),
			'value'      => 80,
			'max'        => 2048,
			'min'        => 1,
			'merge_tags' => false,
			'group'      => 'display',
		);

		return $field_options;
	}

	/**
	 * Get email fields for the form, as well as default choices
	 *
	 * @param int $form_id ID of the form to fetch fields for
	 *
	 * @return array Array keys are field IDs and value is field label
	 */
	private function _get_email_field_choices( $form_id = 0 ) {

		$field_choices = array(
			'created_by_email' => __( 'Entry Creator: Email', 'gk-gravityview' ),
		);

		$form = GVCommon::get_form( $form_id );

		if ( ! $form ) {
			return $field_choices;
		}

		$email_fields = GFAPI::get_fields_by_type( $form, array( 'email' ) );

		foreach ( $email_fields as $email_field ) {
			$email_field_id                   = $email_field['id'];
			$email_field_label                = GVCommon::get_field_label( $form, $email_field_id );
			$email_field_label                = sprintf( __( 'Field: %s', 'gk-gravityview' ), $email_field_label );
			$field_choices[ $email_field_id ] = esc_html( $email_field_label );
		}

		return $field_choices;
	}
}

new GravityView_Field_Gravatar();
