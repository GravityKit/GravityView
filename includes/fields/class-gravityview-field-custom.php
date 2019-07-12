<?php
/**
 * @file class-gravityview-field-custom.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Add custom options for Code field
 * @since 1.2
 */
class GravityView_Field_Custom extends GravityView_Field {

	var $name = 'custom';

	var $contexts = array( 'single', 'multiple', 'edit' );

	/**
	 * @var bool
	 * @since 1.15.3
	 */
	var $is_sortable = false;

	/**
	 * @var bool
	 * @since 1.15.3
	 */
	var $is_searchable = false;

	var $group = 'gravityview';

	public function __construct() {

		$this->label = esc_html__( 'Custom Content', 'gravityview' );

		add_filter( 'gravityview/edit_entry/form_fields', array( $this, 'show_field_in_edit_entry' ), 10, 4 );

		parent::__construct();
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		unset ( $field_options['search_filter'], $field_options['show_as_link'] );

		$new_fields = array(
			'content' => array(
				'type' => 'textarea',
				'label' => __( 'Custom Content', 'gravityview' ),
				'desc' => sprintf( __( 'Enter text or HTML. Also supports shortcodes. You can show or hide data using the %s shortcode (%slearn more%s).', 'gravityview' ), '<code>[gvlogic]</code>', '<a href="https://docs.gravityview.co/article/252-gvlogic-shortcode">', '</a>' ),
				'value' => '',
				'class'	=> 'code',
				'merge_tags' => 'force',
				'rows' => 15,
				'show_all_fields' => true, // Show the `{all_fields}` and `{pricing_fields}` merge tags
			),
			'wpautop' => array(
				'type' => 'checkbox',
				'label' => __( 'Automatically add paragraphs to content', 'gravityview' ),
				'tooltip' => __( 'Wrap each block of text in an HTML paragraph tag (recommended for text).', 'gravityview' ),
				'value' => '',
			),
			'oembed' => array(
				'type' => 'checkbox',
				'label' => __( 'Render oEmbeds', 'gravityview' ),
				'desc' => sprintf( _x( 'Automatically convert oEmbed URLs into embedded content (%slearn more%s).', 'HTML link pointing to WordPress article on oEmbed', 'gravityview' ), '<a href="https://codex.wordpress.org/Embeds" rel="external noopener noreferrer">', '</a>' ),
				'value' => '',
			),
		);

		if ( 'edit' === $context ) {
			unset( $field_options['custom_label'], $field_options['show_label'], $field_options['allow_edit_cap'], $new_fields['wpautop'], $new_fields['oembed'] );
		}

		return $new_fields + $field_options;
	}

	/**
	 * Adds the GravityView Custom Content field to the Edit Entry form
	 *
	 * It does this by pretending to be a HTML field so that Gravity Forms displays it
	 *
	 * @since 1.19.2
	 *
	 * @param GF_Field[] $fields Gravity Forms form fields
	 * @param array|null $edit_fields Fields for the Edit Entry tab configured in the View Configuration
	 * @param array $form GF Form array (`fields` key modified to have only fields configured to show in Edit Entry)
	 * @param int $view_id View ID
	 *
	 * @return GF_Field[] If Custom Content field exists, returns fields array with the fields inserted. Otherwise, returns unmodified fields array.
	 */
	public function show_field_in_edit_entry( $fields, $edit_fields = null, $form = array(), $view_id = 0 ) {

		// Not configured; show all fields.
		if ( is_null( $edit_fields ) ) {
			return $fields;
		}

		$new_fields = array();
		$i = 0;

		$entry = gravityview()->request->is_edit_entry();

		// Loop through the configured Edit Entry fields and add Custom Content fields if there are any
		// TODO: Make this available to other custom GV field types
		foreach ( (array) $edit_fields as $edit_field ) {

			if( 'custom' === \GV\Utils::get( $edit_field, 'id') ) {

				$field_data = array(
					'label' => \GV\Utils::get( $edit_field, 'custom_label' ),
					'customLabel' => \GV\Utils::get( $edit_field, 'custom_label' ),
				    'content' => \GV\Utils::get( $edit_field, 'content' ),
				);

				// Replace merge tags in the content
				foreach ( $field_data as $key => $field_datum ) {
					$field_data[ $key ] = GravityView_Merge_Tags::replace_variables( $field_datum, $form, $entry->as_entry(), false, false );
				}

				$field_data['cssClass'] = \GV\Utils::get( $edit_field, 'custom_class' );

				$new_fields[] = new GF_Field_HTML( $field_data );

			} else {
				if( isset( $fields[ $i ] ) ) {
					$new_fields[] =  $fields[ $i ];
				}
				$i++;
			}

		}

		return $new_fields;
	}

}

new GravityView_Field_Custom;
