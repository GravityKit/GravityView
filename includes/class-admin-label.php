<?php

class GravityView_Admin_View_Field extends GravityView_Admin_View_Item {

	private $label_type = 'field';

}

class GravityView_Admin_View_Widget extends GravityView_Admin_View_Item {

	private $label_type = 'widget';

}

/**
 * A field or widget in GravityView view configuration
 */
class GravityView_Admin_View_Item {

	private $title;
	private $id;
	private $subtitle;
	private $settings_html;
	private $label_type;
	private $item;

	function __construct( $title = '', $field_id, $item = array(), $settings = array() ) {

		// Prevent items from not having index set
		$item = wp_parse_args( $item, array(
			'label_text' => $title,
			'field_id' => NULL,
			'label_type' => NULL,
			'input_type' => NULL,
			'settings_html' => NULL
		));


		$this->title = $title;
		$this->item = $item;
		$this->id = $field_id;
		$this->settings = $settings;

		if( !empty( $item['label_type'] ) ) {
			$this->label_type = $item['label_type'];
		}
	}

	public function __toString() {

		return $this->getOutput();
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

		$output = '<h5 class="field-id-'.esc_attr($this->id).'">' . esc_attr( $this->title );

		$output .= '<span class="gv-field-controls">'.$settings_link.$show_as_link.'<a href="#remove" class="dashicons-dismiss dashicons" title="'.esc_attr( $delete_title ) .'"></a></span>';

		$output .= '</h5>';

		$output = '<div data-fieldid="'.esc_attr($this->id).'" data-inputtype="'.esc_attr( $this->item['input_type'] ).'" class="gv-fields">'.$output.$this->item['settings_html'].'</div>';

		return $output;
	}

}
