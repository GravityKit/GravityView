<?php

class GravityView_Admin_View_Field extends GravityView_Admin_View_Item {

	private $label_type = 'field';

	protected function additional_info() {

		$field_info = '';

		$field_info_items = array();

		/*if( !empty( $this->item['parent'] ) ) {
			$field_info_items[] = array(
				'value' => sprintf( __('Parent Field: %s', 'gravity-view' ), $this->item['parent']['label'] ),
				'class'	=> 'gv-sublabel'
			);
		}*/

		if( is_numeric( $this->id ) ) {

			$field_type_title = GFCommon::get_field_type_title( $this->item['input_type'] );

			$field_info_items[] = array(
				'value' => sprintf( __('Type: %s', 'gravity-view'), $field_type_title )
			);

			$field_info_items[] = array(
				'value' => sprintf( __('Field ID: %s', 'gravity-view'), $this->id ),
			);

		}

		/*if( !empty( $this->item['adminLabel'] ) ) {
			$field_info_items[] = array(
				'value' => sprintf( __('Admin Label: %s', 'gravity-view' ), $this->item['adminLabel'] ),
				'class'	=> 'gv-sublabel'
			);
		}*/

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

	protected function get_item_info() {

		$output = '';
		$field_info_items = $this->additional_info();

		/**
		 * Tap in to modify the field information displayed next to an item
		 * @var array
		 */
		$field_info_items = apply_filters( 'gravityview_admin_label_item_info', $field_info_items, $this );

		foreach ( $field_info_items as $item ) {
			$class = isset($item['class']) ? sanitize_html_class( $item['class'] ).' description' : 'description';
			// Add the title in case the value's long, in which case, it'll be truncated by CSS.
			$output .= '<span class="'.$class.'">';
			$output .= esc_html( $item['value'] );
			$output .= '</span>';
		}

		return $output;

	}

	function getOutput() {
		$settings_title = sprintf(__('Configure %s Settings', 'gravity-view'), ucfirst($this->label_type));
		$delete_title = sprintf(__('Remove %s', 'gravity-view'), ucfirst($this->label_type));
		$single_link_title = __('This field links to the Single Entry', 'gravity-view');

		// $settings_html will just be hidden inputs if empty. Otherwise, it'll have an <ul>. Ugly hack, I know.
		// TODO: Un-hack this
		$hide_settings_link = ( empty( $this->item['settings_html'] ) || strpos( $this->item['settings_html'], '<!-- No Options -->') > 0 ) ? 'hide-if-js' : '';
		$settings_link = sprintf( '<a href="#settings" class="dashicons-admin-generic dashicons %s" title="%s"></a>', $hide_settings_link, esc_attr( $settings_title ) );

		// Should we show the icon that the field is being used as a link to single entry?
		$hide_show_as_link_class = empty( $this->settings['show_as_link'] ) ? 'hide-if-js' : '';
		$show_as_link = '<span class="dashicons dashicons-admin-links '.$hide_show_as_link_class.'" title="'.esc_attr( $single_link_title ).'"></span>';

		$field_info = $this->get_item_info();

		// When a field label is empty, use the Field ID
		$label = empty( $this->title ) ? sprintf( _x('Field #%s (No Label)', 'Label in field picker for empty label', 'gravity-view'), $this->id ) : $this->title;

		// If there's a custom label, and show label is checked, use that as the field heading
		if( !empty( $this->settings['custom_label'] ) && !empty( $this->settings['show_label'] ) ) {
			$label = $this->settings['custom_label'];
		}

		$output = '<h5 class="selectable gfield field-id-'.esc_attr($this->id).'">';

		// Name of field
		$output .= '<span class="gv-field-label" data-original-title="'.esc_attr( $this->title ).'">'.esc_attr( $label ).'</span>';

		if( !empty( $this->item['parent'] ) ) {
			$output .= ' <small>('.$this->item['parent']['label'].')</small>';
		}


		$output .= '<span class="gv-field-controls">'.$settings_link.$show_as_link.'<a href="#remove" class="dashicons-dismiss dashicons" title="'.esc_attr( $delete_title ) .'"></a></span>';

		if( !empty( $field_info ) ) {
			$output .= '<span class="gv-field-info">'.$field_info.'</span>';
		}

		$output .= '</h5>';

		$container_class = !empty( $this->item['parent'] ) ? ' gv-child-field' : '';


		$output = '<div data-fieldid="'.esc_attr($this->id).'" data-inputtype="'.esc_attr( $this->item['input_type'] ).'" class="gv-fields'.$container_class.'">'.$output.$this->item['settings_html'].'</div>';

		return $output;
	}

}
