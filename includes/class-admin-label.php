<?php

class GravityView_Admin_View_Field extends GravityView_Admin_View_Item {

	private $label_type = 'field';

	protected function additional_info() {

		$field_info = '';

		$field_info_items = array();

		// Fields with IDs, not like Source URL or Entry ID
		if( is_numeric( $this->id ) ) {

			$field_type_title = GFCommon::get_field_type_title( $this->item['input_type'] );

			$field_info_items[] = array(
				'value' => sprintf( __('Type: %s', 'gravityview'), $field_type_title )
			);

			$field_info_items[] = array(
				'value' => sprintf( __('Field ID: %s', 'gravityview'), $this->id ),
			);

		}

		if( !empty( $this->item['desc'] ) ) {
			$field_info_items[] = array(
				'value' => $this->item['desc']
			);
		}

		if( !empty( $this->item['adminLabel'] ) ) {
			$field_info_items[] = array(
				'value' => sprintf( __('Admin Label: %s', 'gravityview' ), $this->item['adminLabel'] ),
				'class'	=> 'gv-sublabel'
			);
		}

		return $field_info_items;
	}

}

class GravityView_Admin_View_Widget extends GravityView_Admin_View_Item {

	private $label_type = 'widget';

	protected function additional_info() {

		$field_info = '';
		$field_info_items = array();

		if( !empty( $this->item['description'] ) ) {

			$field_info_items[] = array(
				'value' => $this->item['description']
			);

		}

		return $field_info_items;
	}

}

/**
 * A field or widget in GravityView view configuration
 */
class GravityView_Admin_View_Item {

	protected $title;
	protected $id;
	protected $subtitle;
	protected $settings_html;
	private $label_type;
	protected $item;

	function __construct( $title = '', $field_id, $item = array(), $settings = array() ) {

		// Backward compat
		if( !empty( $item['type'] ) ) {
			$item['input_type'] = $item['type'];
			unset( $item['type'] );
		}

		// Prevent items from not having index set
		$item = wp_parse_args( $item, array(
			'label_text' => $title,
			'field_id' => NULL,
			'parent_label' => NULL,
			'label_type' => NULL,
			'input_type' => NULL,
			'settings_html' => NULL,
			'adminLabel' => NULL,
			'adminOnly' => NULL,
		));

		$this->title = $title;
		$this->item = $item;
		$this->id = $field_id;
		$this->settings = $settings;
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

		$output = NULL;
		$field_info_items = $this->additional_info();

		/**
		 * Tap in to modify the field information displayed next to an item
		 * @var array
		 */
		$field_info_items = apply_filters( 'gravityview_admin_label_item_info', $field_info_items, $this );

		if( $html ) {

			foreach ( $field_info_items as $item ) {
				$class = isset($item['class']) ? sanitize_html_class( $item['class'] ).' description' : 'description';
				// Add the title in case the value's long, in which case, it'll be truncated by CSS.
				$output .= '<span class="'.$class.'">';
				$output .= esc_html( $item['value'] );
				$output .= '</span>';
			}

		} else {

			$values = wp_list_pluck( $field_info_items, 'value' );

			$output = esc_html( implode(', ', $values) );

		}

		return empty( $output ) ? NULL : $output;

	}

	function getOutput() {
		$settings_title = sprintf(__('Configure %s Settings', 'gravityview'), ucfirst($this->label_type));
		$delete_title = sprintf(__('Remove %s', 'gravityview'), ucfirst($this->label_type));
		$single_link_title = __('This field links to the Single Entry', 'gravityview');

		// $settings_html will just be hidden inputs if empty. Otherwise, it'll have an <ul>. Ugly hack, I know.
		// TODO: Un-hack this
		$hide_settings_link = ( empty( $this->item['settings_html'] ) || strpos( $this->item['settings_html'], '<!-- No Options -->') > 0 ) ? 'hide-if-js' : '';
		$settings_link = sprintf( '<a href="#settings" class="dashicons-admin-generic dashicons %s" title="%s"></a>', $hide_settings_link, esc_attr( $settings_title ) );

		// Should we show the icon that the field is being used as a link to single entry?
		$hide_show_as_link_class = empty( $this->settings['show_as_link'] ) ? 'hide-if-js' : '';
		$show_as_link = '<span class="dashicons dashicons-admin-links '.$hide_show_as_link_class.'" title="'.esc_attr( $single_link_title ).'"></span>';

		// When a field label is empty, use the Field ID
		$label = empty( $this->title ) ? sprintf( _x('Field #%s (No Label)', 'Label in field picker for empty label', 'gravityview'), $this->id ) : $this->title;

		// If there's a custom label, and show label is checked, use that as the field heading
		if( !empty( $this->settings['custom_label'] ) && !empty( $this->settings['show_label'] ) ) {
			$label = $this->settings['custom_label'];
		} else if( !empty( $this->item['customLabel'] ) ) {
			$label = $this->item['customLabel'];
		}

		$output = '<h5 class="selectable gfield field-id-'.esc_attr($this->id).'">';

		$label = esc_attr( $label );

		if( !empty( $this->item['parent'] ) ) {
			$label .= ' <small>('.esc_attr( $this->item['parent']['label'] ) .')</small>';
		}

		// Name of field
		$output .= '<span class="gv-field-label" data-original-title="'.esc_attr( $label ).'" title="'. $this->get_item_info( false ) .'">'. $label . '</span>';


		$output .= '<span class="gv-field-controls">'.$settings_link.$show_as_link.'<a href="#remove" class="dashicons-dismiss dashicons" title="'.esc_attr( $delete_title ) .'"></a></span>';

		// Displays only in the field/widget picker.
		if( $field_info = $this->get_item_info() ) {
			$output .= '<span class="gv-field-info">'.$field_info.'</span>';
		}

		$output .= '</h5>';

		$container_class = !empty( $this->item['parent'] ) ? ' gv-child-field' : '';


		$output = '<div data-fieldid="'.esc_attr($this->id).'" data-inputtype="'.esc_attr( $this->item['input_type'] ).'" class="gv-fields'.$container_class.'">'.$output.$this->item['settings_html'].'</div>';

		return $output;
	}

}
