<?php
/**
 * @file class-gravityview-field-gravatar.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * @since TODO
 */
class GravityView_Field_Gravatar extends GravityView_Field {

	var $name = 'gravatar';

	var $is_searchable = false;

	var $group = 'gravityview';

	var $contexts = array( 'single', 'multiple', 'export' );

	public function __construct() {
		$this->label = esc_html__( 'Gravatar', 'gravityview' );
		$this->description = esc_html__( 'A Gravatar is an image that represents a person online based on their email. Powered by gravatar.com.', 'gravityview' );

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
	 * @param array $entry_default_fields Array of fields shown by default
	 * @param string|array $form form_ID or form object
	 * @param string $zone Either 'single', 'directory', 'edit', 'header', 'footer'
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
		);

		return $entry_default_fields;
	}

	/**
	 * Get either a Gravatar URL or complete image tag for a specified email address.
	 *
	 * @source https://gravatar.com/site/implement/images/php/
	 *
	 * @param string $email The email address. Required.
	 * @param int $size Size in pixels, 1 - 2048. Default: 80
	 * @param string $default Default imageset to use. Options: '404', 'mp', 'identicon', 'monsterid', 'wavatar', 'retro', 'robohash', 'blank', or a custom URL you provide to your preferred default image. Default: 'mp'
	 * @param string $rating Maximum rating (inclusive) Options: 'g', 'pg', 'r', 'x'. Default: 'g'
	 * @param bool $img True to return a complete IMG tag False for just the URL. Default: true
	 * @param array $atts Optional, additional key/value attributes to include in the IMG tag
	 *
	 * @return string containing either just a URL or a complete image tag
	 */
	static public function get_gravatar( $email, $size = 80, $default = 'mp', $rating = 'g', $img = true, $atts = array() ) {

		$size = (int) $size;

		if ( $size > 2048 ) {
			$size = 2048;
		} elseif ( $size < 1 ) {
			$size = 1;
		}

		$url = 'https://www.gravatar.com/avatar/';
		$url .= md5( strtolower( trim( $email ) ) );
		$url .= "?s={$size}&default={$default}&rating={$rating}";
		if ( $img ) {
			$url = '<img src="' . esc_url( $url ) . '"';

			foreach ( $atts as $key => $val ) {
				$url .= ' ' . esc_attr( $key ) . '="' . esc_attr( $val ) . '"';
			}

			$url .= ' />';
		}

		return $url;
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
	static public function get_email( $field_settings, $entry ) {

		// There was no logged in user.
		switch ( $field_settings['email_field'] ) {
			case 'created_by_email':

				$created_by = \GV\Utils::get( $entry, 'created_by', null );

				if ( empty( $created_by ) ) {
					return '';
				}

				$user = get_user_by( 'id', $created_by );

				$email = $user->user_email;
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

		$field_options['email_field'] = array(
			'type'    => 'select',
			'label'   => __( 'Email to Use', 'gravityview' ),
			'value'   => 'created_by_email',
			'desc'    => __( 'Which email should be used to generate the Gravatar?', 'gravityview' ),
			'choices' => $this->_get_email_field_choices( $form_id ),
		);

		$field_options['default'] = array(
			'type'    => 'select',
			'label'   => __( 'Default Image', 'gravityview' ),
			'desc'    => __( 'Choose the default image to be shown when an email has no Gravatar.', 'gravityview' ) . ' <a href="https://en.gravatar.com/site/implement/images/">' . esc_html( sprintf( __( 'Read more about %s', 'gravityview' ), __( 'Default Image', 'gravityview' ) ) ) . '</a>',
			'value'   => 'mp',
			'choices' => array(
				'mp'        => __( 'Silhouetted Person', 'gravityview' ),
				''          => __( 'Gravatar Icon', 'gravityview' ),
				'identicon' => __( 'Abstract Geometric Patterns', 'gravityview' ),
				'monsterid' => __( 'Monster Faces', 'gravityview' ),
				'retro'     => __( 'Arcade-style Faces', 'gravityview' ),
				'robohash'  => __( 'Robot Faces', 'gravityview' ),
				'blank'     => __( 'Transparent Image', 'gravityview' ),
			),
		);

		$field_options['size'] = array(
			'type'  => 'number',
			'label' => __( 'Size in Pixels', 'gravityview' ),
			'value' => 80,
			'max'   => 2048,
			'min'   => 1,
		);

		$field_options['rating'] = array(
			'type'    => 'radio',
			'label'   => __( 'Maximum Rating', 'gravityview' ),
			'desc'    => __( 'Gravatar allows users to self-rate their images so that they can indicate if an image is appropriate for a certain audience. Specify one of the following ratings to request images up to and including that rating.', 'gravityview' ) . ' <a href="https://en.gravatar.com/site/implement/images/#rating">' . esc_html( sprintf( __( 'Read more about %s', 'gravityview' ), __( 'Ratings', 'gravityview' ) ) ) . '</a>',
			'value'   => 'g',
			'choices' => array(
				'g'  => 'G',
				'pg' => 'PG',
				'r'  => 'R',
				'x'  => 'X',
			),
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
			'created_by_email' => __( 'Entry Creator: Email', 'gravityview' ),
		);

		$form = GFAPI::get_form( $form_id );

		if ( ! $form ) {
			return $field_choices;
		}

		$email_fields = GFAPI::get_fields_by_type( $form, array( 'email' ) );

		foreach ( $email_fields as $email_field ) {
			$email_field_id                   = $email_field['id'];
			$email_field_label                = GVCommon::get_field_label( $form, $email_field_id );
			$email_field_label                = sprintf( __( 'Field: %s', 'gravityview' ), $email_field_label );
			$field_choices[ $email_field_id ] = esc_html( $email_field_label );
		}

		return $field_choices;
	}

}

new GravityView_Field_Gravatar;
