<?php
/**
 * @file class-gravityview-field-custom.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Add custom options for Code field
 *
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

	var $group = 'featured';

	var $icon = 'dashicons-editor-code';

	public function __construct() {

		$this->label       = esc_html__( 'Custom Content', 'gk-gravityview' );
		$this->description = esc_html__( 'Insert custom text or HTML.', 'gk-gravityview' );

		add_filter( 'gravityview/edit_entry/form_fields', array( $this, 'show_field_in_edit_entry' ), 10, 4 );

		add_filter( 'gravityview/template/table/entry/markup', array( $this, 'filter_table_entry_markup' ), 10, 2 );

		parent::__construct();
	}

	/**
	 * Make the Custom Content field full width in the table template if full width is enabled.
	 *
	 * @param string $markup
	 * @param \GV\Field $field
	 * @return string
	 */
	public function filter_table_entry_markup( $markup, $field ) {
		$field_settings = $field->as_configuration();
		if ( isset( $field_settings['full_width'] ) && 1 === (int) $field_settings['full_width'] ) {
			$markup = '<tr id="{{ field_id }}" class="{{ class }}"><td colspan="2">{{ value }}</td></tr>';
		}

		return $markup;
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		unset( $field_options['search_filter'], $field_options['show_as_link'], $field_options['new_window'] );

		$new_fields = array(
			'content'     => array(
				'type'            => 'textarea',
				'label'           => __( 'Custom Content', 'gk-gravityview' ),
				'desc'            => sprintf( __( 'Enter text or HTML. Also supports shortcodes. You can show or hide data using the %1$s shortcode (%2$slearn more%3$s).', 'gk-gravityview' ), '<code>[gvlogic]</code>', '<a href="https://docs.gravitykit.com/article/252-gvlogic-shortcode" data-beacon-article-sidebar="552355bfe4b0221aadf2572b">', '</a>' ) . ' ' . sprintf( __( 'Click the arrow icon next to the content area to add %1$sMerge Tags%2$s.', 'gk-gravityview' ), '<a href="https://docs.gravitykit.com/article/76-merge-tags" data-beacon-article-inline="54c67bbbe4b051242988551d">', '</a>' ),
				'value'           => '',
				'class'           => 'code',
				'merge_tags'      => 'force',
				'rows'            => 15,
				'show_all_fields' => true, // Show the `{all_fields}` and `{pricing_fields}` merge tags
				'priority'        => 900,
				'group'           => 'field',
			),
			'wpautop'     => array(
				'type'     => 'checkbox',
				'label'    => __( 'Automatically add paragraphs to content', 'gk-gravityview' ),
				'tooltip'  => __( 'Wrap each block of text in an HTML paragraph tag (recommended for text).', 'gk-gravityview' ),
				'value'    => '',
				'priority' => 950,
				'group'    => 'field',
			),
			'oembed'      => array(
				'type'     => 'checkbox',
				'label'    => __( 'Render oEmbeds', 'gk-gravityview' ),
				'desc'     => sprintf( _x( 'Automatically convert oEmbed URLs into embedded content (%1$slearn more%2$s).', 'HTML link pointing to WordPress article on oEmbed', 'gk-gravityview' ), '<a href="https://codex.wordpress.org/Embeds" rel="external noopener noreferrer">', '</a>' ),
				'value'    => '',
				'priority' => 970,
				'group'    => 'field',
			),
			'admin_label' => array(
				'type'     => 'text',
				'class'    => 'widefat',
				'label'    => __( 'Admin Label', 'gk-gravityview' ),
				'desc'     => __( 'A label that is only shown in the GravityView View configuration screen.', 'gk-gravityview' ),
				'value'    => '',
				'priority' => 2000,
				'group'    => 'label',
			),
		);

		if ( 'edit' === $context ) {
			unset( $field_options['custom_label'], $field_options['show_label'], $field_options['allow_edit_cap'], $new_fields['wpautop'], $new_fields['oembed'] );
		}
		
		if ( 'single' === $context && strpos( $template_id, 'table' ) !== false ) {
			$new_fields['full_width'] = array(
				'type'     => 'checkbox',
				'label'    => __( 'Full Width', 'gk-gravityview' ),
				'desc'     => __( 'Display the field in full width (Label will be hidden).', 'gk-gravityview' ),
				'value'    => '',
				'group'    => 'field',
			);
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
	 * @param array      $form GF Form array (`fields` key modified to have only fields configured to show in Edit Entry)
	 * @param int        $view_id View ID
	 *
	 * @return GF_Field[] If Custom Content field exists, returns fields array with the fields inserted. Otherwise, returns unmodified fields array.
	 */
	public function show_field_in_edit_entry( $fields, $edit_fields = null, $form = array(), $view_id = 0 ) {

		// Not configured; show all fields.
		if ( is_null( $edit_fields ) ) {
			return $fields;
		}

		$new_fields = array();
		$i          = 0;

		$entry = gravityview()->request->is_edit_entry( $form['id'] );

		// Loop through the configured Edit Entry fields and add Custom Content fields if there are any
		// TODO: Make this available to other custom GV field types
		foreach ( (array) $edit_fields as $id => $edit_field ) {
			if ( 'custom' === \GV\Utils::get( $edit_field, 'id' ) ) {
				$field_data = array(
					'custom_id'   => $id,
					'label'       => \GV\Utils::get( $edit_field, 'custom_label' ),
					'customLabel' => \GV\Utils::get( $edit_field, 'custom_label' ),
					'content'     => \GV\Utils::get( $edit_field, 'content' ),
				);

				// Replace merge tags in the content
				foreach ( $field_data as $key => $field_datum ) {
					$entry_data         = $entry ? $entry->as_entry() : array();
					$field_data[ $key ] = GravityView_Merge_Tags::replace_variables( $field_datum, $form, $entry_data, false, false );
				}

				$field_data['cssClass'] = \GV\Utils::get( $edit_field, 'custom_class' );

				$new_fields[] = new GF_Field_HTML( $field_data );

			} else {
				if ( isset( $fields[ $i ] ) ) {
					$new_fields[] = $fields[ $i ];
				}
				++$i;
			}
		}

		return $new_fields;
	}
}

new GravityView_Field_Custom();
