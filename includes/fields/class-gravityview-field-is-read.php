<?php
/**
 * @file       class-gravityview-field-is-read.php
 * @since      2.24
 * @subpackage includes\fields
 * @package    GravityView
 */

use GV\Entry;
use GV\Field;
use GV\Source;
use GV\Template_Context;
use GV\Utils;
use GV\View;

/**
 * Field to display whether the entry has been read.
 *
 * @since 2.24
 */
class GravityView_Field_Is_Read extends GravityView_Field {
	var $name = 'is_read';

	var $is_searchable = true;

	var $entry_meta_key = 'is_read';

	var $search_operators = [ 'is', 'isnot' ];

	var $group = 'meta';

	var $contexts = [ 'single', 'multiple', 'export' ];

	var $icon = 'dashicons-book-alt';

	var $entry_meta_is_default_column = true;

	var $is_sortable = true;

	/**
	 * Class constructor.
	 *
	 * @since 2.24
	 */
	public function __construct() {
		$this->label                = esc_html__( 'Read Status', 'gk-gravityview' );
		$this->default_search_label = __( 'Is Read', 'gk-gravityview' );
		$this->description          = esc_html__( 'Display whether the entry has been read.', 'gk-gravityview' );

		$this->add_hooks();

		parent::__construct();
	}

	/**
	 * Prevents overriding Gravity Forms entry meta, even though it's a meta field.
	 *
	 * @since 2.24
	 *
	 * @param array $entry_meta Existing entry meta.
	 *
	 * @return array
	 */
	public function add_entry_meta( $entry_meta ) {
		return $entry_meta;
	}

	/**
	 * Adds field hooks.
	 *
	 * @since 2.24
	 */
	private function add_hooks() {
		/** @see Field::get_value_filters */
		add_filter( 'gravityview/field/is_read/value', [ $this, 'get_value' ], 10, 5 );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 2.24
	 */
	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {
		$field_options['is_read_label'] = [
			'type'  => 'text',
			'label' => __( 'Read Label', 'gk-gravityview' ),
			'desc'  => __( 'If the entry has been read, display this value', 'gk-gravityview' ),
			'value' => __( 'Read', 'gk-gravityview' ),
		];

		$field_options['is_unread_label'] = [
			'type'  => 'text',
			'label' => __( 'Unread Label', 'gk-gravityview' ),
			'desc'  => __( 'If the entry has not been read, display this value', 'gk-gravityview' ),
			'value' => __( 'Unread', 'gk-gravityview' ),
		];

		return $field_options;
	}

	/**
	 * Displays the value based on the field settings.
	 *
	 * @since 2.0
	 *
	 * @param string      $value  The value.
	 * @param Field       $field  The field we're doing this for.
	 * @param View        $view   The view for this context if applicable.
	 * @param Source|null $source The source (form) for this context if applicable.
	 * @param Entry       $entry  The entry for this context if applicable.
	 *
	 * @return string Value of the field
	 */
	public function get_value( $value, $field, $view, $source, $entry ) {
		if ( empty( $value ) ) {
			$label = Utils::get( $field, 'is_unread_label', esc_html__( 'Unread', 'gk-gravityview' ) );
		} else {
			$label = Utils::get( $field, 'is_read_label', esc_html__( 'Read', 'gk-gravityview' ) );
		}

		/**
		 * Modify the field's "Read" or "Unread" label.
		 *
		 * @filter `gk/gravityview/field/is-read/label`
		 *
		 * @since  2.24
		 *
		 * @param string $label The label.
		 * @param string $value The field value.
		 * @param Field  $field The field.
		 * @param View   $view  The View.
		 * @param Entry  $entry The entry for this context if applicable.
		 */
		return apply_filters( 'gk/gravityview/field/is-read/label', $label, $value, $field, $view, $entry );
	}
}

new GravityView_Field_Is_Read();
