<?php
/**
 * @file       class-gravityview-field-is-read.php
 * @since      2.24
 * @subpackage includes\fields
 * @package    GravityView
 */

use GV\Entry;
use GV\Field;
use GV\GF_Entry;
use GV\Multi_Entry;
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
	 * An array of entry IDs that have been marked as read during the request.
	 *
	 * @since 2.25
	 *
	 * @var array
	 */
	private $marked_read = [];

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
		add_action( 'gravityview/template/before', [ $this, 'maybe_mark_entry_as_read' ] );
		add_action( 'gravityview_default_args', [ $this, 'modify_single_entry_view_settings' ] );
	}

	/**
	 * Adds the "Mark Entry As Read" setting to the View settings.
	 *
	 * @since 2.25
	 *
	 * @param array $settings The settings.
	 *
	 * @return array The modified settings.
	 */
	public function modify_single_entry_view_settings( $settings ) {
		$settings['mark_entry_as_read'] = [
			'label'      => esc_html__( 'Mark Entry As Read', 'gk-gravityview' ),
			'desc'       => esc_html__( 'This will mark the entry as "read" when it is displayed in the Single Entry layout and visited by a user who has the capability to edit Gravity Forms entries.', 'gk-gravityview' ),
			'group'      => 'default',
			'type'       => 'checkbox',
			'full_width' => true,
			'value'      => true,
			'article'    => [
				'id'  => '6660d406804514782072dcfa',
				'url' => 'https://docs.gravitykit.com/article/1008-marking-entries-as-read',
			],
		];

		return $settings;
	}

	/**
	 * Marks the entry as read if the user has `gravityview_edit_entries` capability and the "Mark Entry As Read" View setting is enabled.
	 *
	 * @since 2.25
	 *
	 * @return void
	 */
	public function maybe_mark_entry_as_read() {
		if ( ! GravityView_Roles_Capabilities::has_cap( 'gravityview_edit_entries' ) ) {
			return;
		}

		if ( 'single' !== GravityView_View::getInstance()->getContext() || true !== (bool) GravityView_View::getInstance()->getAtts( 'mark_entry_as_read' ) ) {
			return;
		}

		$entry = gravityview()->request->is_entry();

		$entry = $entry instanceof GF_Entry || $entry instanceof Multi_Entry ? $entry->as_entry() : null;

		if ( ! $entry ) {
			return;
		}

		$entries = ! empty( $entry['_multi'] ) ? $entry['_multi'] : [ $entry ];

		foreach ( $entries as $entry ) {
			if ( ! empty( $entry['is_read'] ) ) {
				continue;
			}

			$entry['is_read'] = '1';

			GFAPI::update_entry( $entry );

			do_action( 'gravityview_clear_entry_cache', $entry['id'] );

			$this->marked_read[ $entry['id'] ] = true;
		}
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
		$value = empty( $value ) && isset( $this->marked_read[ $entry['id'] ?? '' ] ) ? '1' : $value;

		if ( empty( $value ) ) {
			$label = Utils::get( $field, 'is_unread_label', esc_html__( 'Unread', 'gk-gravityview' ) );
		} else {
			$label = Utils::get( $field, 'is_read_label', esc_html__( 'Read', 'gk-gravityview' ) );
		}

		/**
		 * Modify the field's "Read" or "Unread" label.
		 *
		 * @since 2.24
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
