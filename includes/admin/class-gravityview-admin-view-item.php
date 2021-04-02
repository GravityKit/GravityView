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

	/**
	 * @var string For ID, if available
	 */
	protected $form_id;

	function __construct( $title = '', $item_id = '', $item = array(), $settings = array(), $form_id = null) {

		// Backward compat
		if ( ! empty( $item['type'] ) ) {
			$item['input_type'] = $item['type'];
			unset( $item['type'] );
		}

		if ( $admin_label = \GV\Utils::get( $settings, 'admin_label' ) ) {
			$title = $admin_label;
		}

		// Prevent items from not having index set
		$item = wp_parse_args( $item, array(
			'label_text'    => $title,
			'field_id'      => null,
			'parent_label'  => null,
			'label_type'    => null,
			'input_type'    => null,
			'settings_html' => null,
			'adminLabel'    => null,
			'adminOnly'     => null,
			'subtitle'      => null,
			'placeholder'   => null,
			'icon'          => null,
		) );

		$this->title      = $title;
		$this->item       = $item;
		$this->id         = $item_id;
		$this->form_id    = $form_id;
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

				if( \GV\Utils::get( $item, 'hide_in_picker', false ) ) {
					continue;
				}

				$class = isset( $item['class'] ) ? sanitize_html_class( $item['class'] ) . ' description' : 'description';
				// Add the title in case the value's long, in which case, it'll be truncated by CSS.
				$output .= '<span class="' . $class . '">';
				$output .= esc_html( $item['value'] );
				$output .= '</span>';
			}

		} else {

			$values = wp_list_pluck( $field_info_items, 'value' );

			$output = esc_html( implode( "\n", $values ) );

		}

		return empty( $output ) ? NULL : $output;
	}

	/**
	 * Generate HTML for field or a widget modal
	 *
	 * @return string
	 */
	function getOutput() {

		$settings_title    = sprintf( __( 'Configure %s Settings', 'gravityview' ), esc_html( rgar( $this->item, 'label', ucfirst( $this->label_type ) ) ) );
		$delete_title      = sprintf( __( 'Remove %s', 'gravityview' ), ucfirst( $this->label_type ) );
		$single_link_title = __( 'This field links to the Single Entry', 'gravityview' );
		$visibility_title = __( 'This field has modified visibility', 'gravityview' );

		// $settings_html will just be hidden inputs if empty. Otherwise, it'll have an <ul>. Ugly hack, I know.
		// TODO: Un-hack this
		$hide_settings_link_class = ( empty( $this->item['settings_html'] ) || strpos( $this->item['settings_html'], '<!-- No Options -->' ) > 0 ) ? 'hide-if-js' : '';
		$settings_link      = sprintf( '<button class="gv-field-settings %2$s" title="%1$s" aria-label="%1$s"><span class="dashicons-admin-generic dashicons"></span></button>', esc_attr( $settings_title ), $hide_settings_link_class );

		// When a field label is empty, use the Field ID
		$label = empty( $this->title ) ? sprintf( _x( 'Field #%s (No Label)', 'Label in field picker for empty label', 'gravityview' ), $this->id ) : $this->title;

		// If there's a custom label, and show label is checked, use that as the field heading
		if ( ! empty( $this->settings['custom_label'] ) && ! empty( $this->settings['show_label'] ) ) {
			$label = $this->settings['custom_label'];
		} else if ( ! empty( $this->item['customLabel'] ) ) {
			$label = $this->item['customLabel'];
		}
		$label = esc_attr( $label );

		$field_icon = '';

		$form = ! empty( $this->form_id ) ? GVCommon::get_form( $this->form_id ) : false;
		$nonexistent_form_field = $form && $this->id && preg_match('/^\d+\.\d+$|^\d+$/', $this->id) && ! gravityview_get_field( $form, $this->id );

		if ( $this->item['icon'] && ! \GV\Utils::get( $this->item, 'parent' ) ) {

			$has_gf_icon = ( false !== strpos( $this->item['icon'], 'gform-icon' ) );
			$has_dashicon = ( false !== strpos( $this->item['icon'], 'dashicons' ) );

			if ( 0 === strpos( $this->item['icon'], 'data:' ) ) {
				// Inline icon SVG
				$field_icon = '<i class="dashicons background-icon" style="background-image: url(\'' . esc_attr( $this->item['icon'] ) . '\');"></i>';
			} elseif( $has_gf_icon && gravityview()->plugin->is_GF_25() ) {
				// Gravity Forms icon font
				$field_icon = '<i class="gform-icon ' . esc_attr( $this->item['icon'] ) . '"></i>';
			} elseif( $has_dashicon ) {
				// Dashicon; prefix with "dashicons"
				$field_icon = '<i class="dashicons ' . esc_attr( $this->item['icon'] ) . '"></i>';
			} else {
				// Not dashicon icon
				$field_icon = '<i class="' . esc_attr( $this->item['icon'] ) . '"></i>';
			}

			$field_icon = $field_icon . ' ';
		} elseif( \GV\Utils::get( $this->item, 'parent' ) ) {
			$field_icon = '<i class="gv-icon gv-icon-level-down"></i>' . ' ';
		}

		$output = '<button class="gv-add-field screen-reader-text">' . sprintf( esc_html__( 'Add "%s"', 'gravityview' ), $label ) . '</button>';
		$title = esc_attr( sprintf( __( 'Field: %s', 'gravityview' ), $label ) );
		if ( ! $nonexistent_form_field ) {
			$title .= "\n" . $this->get_item_info( false );
		} else {
			$output        = '';
			$settings_link = '';
			$label = '<span class="dashicons-warning dashicons"></span> ' . esc_html( sprintf( __( 'The field connected to "%s" was deleted from the form. The associated entry data no longer exists.', 'gravityview' ), $label ) );
		}

		$output .= '<h5 class="selectable gfield field-id-' . esc_attr( $this->id ) . '">';

		$output .= '<span class="gv-field-controls">' . $settings_link . $this->get_indicator_icons() . '<button class="gv-remove-field" aria-label="' . esc_attr( $delete_title ) . '" title="' . esc_attr( $delete_title ) . '"><span class="dashicons-dismiss dashicons"></span></button></span>';

		$output .= '<span class="gv-field-label" data-original-title="' . esc_attr( $label ) . '" title="' . $title . '">' . $field_icon . '<span class="gv-field-label-text-container">' . $label . '</span></span>';

		// Displays only in the field/widget picker
		if ( ! $nonexistent_form_field && $field_info = $this->get_item_info() ) {
			$output .= '<span class="gv-field-info">' . $field_info . '</span>';
		}

		$output .= '</h5>';

		$container_class = ! empty( $this->item['parent'] ) ? ' gv-child-field' : '';

		$container_class .= $nonexistent_form_field ? ' gv-nonexistent-form-field' : '';

		$container_class .= empty( $this->settings['show_as_link'] ) ? '' : ' has-single-entry-link';

		$container_class .= empty( $this->settings['only_loggedin'] ) ? '' : ' has-custom-visibility';

		$data_form_id   = $form ? ' data-formid="' . esc_attr( $this->form_id ) . '"' : '';

		$data_parent_label = ! empty( $this->item['parent'] ) ? ' data-parent-label="' . esc_attr( $this->item['parent']['label'] ) . '"' : '';

		$output = '<div data-fieldid="' . esc_attr( $this->id ) . '" ' . $data_form_id . $data_parent_label . ' data-inputtype="' . esc_attr( $this->item['input_type'] ) . '" class="gv-fields' . $container_class . '">' . $output . $this->item['settings_html'] . '</div>';

		return $output;
	}

	/**
	 * Returns array of item icons used to represent field settings state
	 *
	 * Has `gravityview/admin/indicator_icons` filter for other components to modify displayed icons.
	 *
	 * @since 2.9.5
	 *
	 * @return string HTML output of icons
	 */
	private function get_indicator_icons() {

		$icons = array(
			'show_as_link' => array(
				'visible' => ( ! empty( $this->settings['show_as_link'] ) ),
				'title' => __( 'This field links to the Single Entry', 'gravityview' ),
				'css_class' => 'dashicons dashicons-media-default icon-link-to-single-entry',
			),
			'only_loggedin' => array(
				'visible' => ( \GV\Utils::get( $this->settings, 'only_loggedin' ) || isset( $this->settings['allow_edit_cap'] ) && 'read' !== $this->settings['allow_edit_cap'] ),
				'title' => __( 'This field has modified visibility', 'gravityview' ),
				'css_class' => 'dashicons dashicons-lock icon-custom-visibility',
			),
		);

		$output = '';

		/**
		 * @filter `gravityview/admin/indicator_icons` Modify the icon output to add additional indicator icons
		 * @internal This is currently internally used. Consider not relying on it until further notice :-)
		 * @param array $icons Array of icons to be shown, with `visible`, `title`, `css_class` keys.
		 * @param array $item_settings Settings for the current item (widget or field)
		 */
		$icons = (array) apply_filters( 'gravityview/admin/indicator_icons', $icons, $this->settings );

		foreach ( $icons as $icon ) {

			if ( empty( $icon['css_class'] ) || empty( $icon['title'] ) ) {
				continue;
			}

			$css_class = trim( $icon['css_class'] );

			if ( empty( $icon['visible'] ) ) {
				$css_class .= ' hide-if-js';
			}

			$output .= '<span class="' . gravityview_sanitize_html_class( $css_class ) . '" title="' . esc_attr( $icon['title'] ) . '"></span>';
		}

		return $output;
	}

}
