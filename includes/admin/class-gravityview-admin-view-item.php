<?php
/**
 * @file class-gravityview-admin-view-item.php
 * @since 1.17.3
 */

/**
 * A field or widget in GravityView view configuration
 */
abstract class GravityView_Admin_View_Item {

	/**
	 * @var string Name of the item in the field or widget picker
	 */
	protected $title;

	/**
	 * @var string The field ID or the widget slug ( `2.3` or `custom_content`)
	 */
	protected $id;

	/**
	 * @var string Description of the item
	 */
	protected $subtitle;

	/**
	 * @var string The type of item ("field" or "widget")
	 */
	protected $label_type;

	/**
	 * @var array Associative array of item details
	 */
	protected $item;

	/**
	 * @var array Existing settings for the item
	 */
	protected $settings;

	function __construct( $title = '', $item_id, $item = array(), $settings = array() ) {

		// Backward compat
		if ( ! empty( $item['type'] ) ) {
			$item['input_type'] = $item['type'];
			unset( $item['type'] );
		}

		// Prevent items from not having index set
		$item = wp_parse_args( $item, array(
			'label_text'    => $title,
			'field_id'      => NULL,
			'parent_label'  => NULL,
			'label_type'    => NULL,
			'input_type'    => NULL,
			'settings_html' => NULL,
			'adminLabel'    => NULL,
			'adminOnly'     => NULL,
			'subtitle'      => NULL,
			'placeholder'   => NULL,
		) );

		$this->title      = $title;
		$this->item       = $item;
		$this->id         = $item_id;
		$this->settings   = $settings;
		$this->label_type = $item['label_type'];
	}

	/**
	 * When echoing this class, print the HTML output
	 * @return string HTML output of the class
	 */
	public function __toString() {

		return $this->getOutput();
	}

	/**
	 * Overridden by child classes
	 * @return array Array of content with arrays for each item. Those arrays have `value`, `label` and (optional) `class` keys
	 */
	protected function additional_info() {
		return array();
	}

	/**
	 * Generate the output for a field based on the additional_info() output
	 *
	 * @see GravityView_Admin_View_Item::additional_info()
	 * @param  boolean $html Display HTML output? If yes, output is wrapped in spans. If no, plaintext.
	 * @return string|null        If empty, return null. Otherwise, return output HTML/text.
	 */
	protected function get_item_info( $html = true ) {

		$output           = NULL;
		$field_info_items = $this->additional_info();

		/**
		 * @filter `gravityview_admin_label_item_info` Tap in to modify the field information displayed next to an item
		 *
		 * @param array $field_info_items Additional information to display in a field
		 * @param GravityView_Admin_View_Field $this Field shown in the admin
		 */
		$field_info_items = apply_filters( 'gravityview_admin_label_item_info', $field_info_items, $this );

		if ( $html ) {

			foreach ( $field_info_items as $item ) {
				$class = isset( $item['class'] ) ? sanitize_html_class( $item['class'] ) . ' description' : 'description';
				// Add the title in case the value's long, in which case, it'll be truncated by CSS.
				$output .= '<span class="' . $class . '">';
				$output .= esc_html( $item['value'] );
				$output .= '</span>';
			}

		} else {

			$values = wp_list_pluck( $field_info_items, 'value' );

			$output = esc_html( implode( ', ', $values ) );

		}

		return empty( $output ) ? NULL : $output;
	}

	/**
	 * Generate HTML for field or a widget modal
	 *
	 * @return string
	 */
	function getOutput() {

		$settings_title    = sprintf( __( 'Configure %s Settings', 'gravityview' ), ucfirst( $this->label_type ) );
		$delete_title      = sprintf( __( 'Remove %s', 'gravityview' ), ucfirst( $this->label_type ) );
		$single_link_title = __( 'This field links to the Single Entry', 'gravityview' );

		// $settings_html will just be hidden inputs if empty. Otherwise, it'll have an <ul>. Ugly hack, I know.
		// TODO: Un-hack this
		$hide_settings_link = ( empty( $this->item['settings_html'] ) || strpos( $this->item['settings_html'], '<!-- No Options -->' ) > 0 ) ? 'hide-if-js' : '';
		$settings_link      = sprintf( '<a href="#settings" class="dashicons-admin-generic dashicons %s" title="%s"></a>', $hide_settings_link, esc_attr( $settings_title ) );

		// Should we show the icon that the field is being used as a link to single entry?
		$hide_show_as_link_class = empty( $this->settings['show_as_link'] ) ? 'hide-if-js' : '';
		$show_as_link            = '<span class="dashicons dashicons-admin-links ' . $hide_show_as_link_class . '" title="' . esc_attr( $single_link_title ) . '"></span>';

		// When a field label is empty, use the Field ID
		$label = empty( $this->title ) ? sprintf( _x( 'Field #%s (No Label)', 'Label in field picker for empty label', 'gravityview' ), $this->id ) : $this->title;

		// If there's a custom label, and show label is checked, use that as the field heading
		if ( ! empty( $this->settings['custom_label'] ) && ! empty( $this->settings['show_label'] ) ) {
			$label = $this->settings['custom_label'];
		} else if ( ! empty( $this->item['customLabel'] ) ) {
			$label = $this->item['customLabel'];
		}

		$output = '<h5 class="selectable gfield field-id-' . esc_attr( $this->id ) . '">';

		$label = esc_attr( $label );

		if ( ! empty( $this->item['parent'] ) ) {
			$label .= ' <small>(' . esc_attr( $this->item['parent']['label'] ) . ')</small>';
		}

		// Name of field / widget
		$output .= '<span class="gv-field-label" data-original-title="' . esc_attr( $label ) . '" title="' . $this->get_item_info( false ) . '">' . $label . '</span>';


		$output .= '<span class="gv-field-controls">' . $settings_link . $show_as_link . '<a href="#remove" class="dashicons-dismiss dashicons" title="' . esc_attr( $delete_title ) . '"></a></span>';

		// Displays only in the field/widget picker.
		if ( $field_info = $this->get_item_info() ) {
			$output .= '<span class="gv-field-info">' . $field_info . '</span>';
		}

		$output .= '</h5>';

		$container_class = ! empty( $this->item['parent'] ) ? ' gv-child-field' : '';

		$output = '<div data-fieldid="' . esc_attr( $this->id ) . '" data-inputtype="' . esc_attr( $this->item['input_type'] ) . '" class="gv-fields' . $container_class . '">' . $output . $this->item['settings_html'] . '</div>';

		return $output;
	}

}
