<?php

/**
 * @inheritDoc
 *
 * @since TODO
 */
class GravityView_Plugin_Hooks_GravityView_DataTables extends GravityView_Plugin_and_Theme_Hooks {

	use GravityView_Functionality_Placeholder;

	/**
	 * @inheritDoc
	 */
	public $constant_name = 'GV_DT_VERSION';

	/**
	 * @inheritDoc
	 *
	 * @since TODO
	 *
	 * @return string
	 */
	public function get_placeholder_title() {
		return __( 'DataTables Layout', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 *
	 * @since TODO
	 *
	 * @return string
	 */
	public function get_placeholder_description() {
		return __( 'Display Gravity Forms data in a live-updating table with extended sorting, filtering and exporting capabilities.', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 *
	 * @since TODO
	 *
	 * @return string
	 */
	protected function get_buy_now_link() {
		return 'https://www.gravitykit.com/products/datatables/';
	}

	/**
	 * @inheritDoc
	 *
	 * @since TODO
	 *
	 * @return string
	 */
	protected function get_plugin_basename() {
		return defined( 'GV_DT_FILE' ) ? plugin_basename( GV_DT_FILE ) : 'gravityview-datatables/gravityview-datatables.php';
	}

	/**
	 * @inheritDoc
	 *
	 * @since TODO
	 *
	 * @return string
	 */
	public function get_placeholder_icon() {
		$icon = <<<ICON
	<svg width='80' height='80' viewBox='0 0 80 80' fill='none' xmlns='http://www.w3.org/2000/svg'>
		<rect x='1.5' y='1.5' width='77' height='77' rx='6.5' fill='white'/>
		<rect x='1.5' y='1.5' width='77' height='77' rx='6.5' stroke='#FF1B67' stroke-width='3'/>
		<path
			d='M60.5 42.5V56.5C60.5 58.7091 58.7091 60.5 56.5 60.5H24.5C22.2909 60.5 20.5 58.7091 20.5 56.5V24.5C20.5 22.2909 22.2909 20.5 24.5 20.5H46'
			stroke='#FF1B67' stroke-width='3' stroke-miterlimit='10' stroke-linecap='round'
			stroke-linejoin='round'/>
		<path d='M26.5 42.5H37.5' stroke='#FF1B67' stroke-width='3' stroke-miterlimit='10'
			  stroke-linecap='round' stroke-linejoin='round'/>
		<path d='M43.5 42.5H54.5' stroke='#FF1B67' stroke-width='3' stroke-miterlimit='10'
			  stroke-linecap='round' stroke-linejoin='round'/>
		<path d='M26.5 54.5H37.5' stroke='#FF1B67' stroke-width='3' stroke-miterlimit='10'
			  stroke-linecap='round' stroke-linejoin='round'/>
		<path d='M43.5 54.5H54.5' stroke='#FF1B67' stroke-width='3' stroke-miterlimit='10'
			  stroke-linecap='round' stroke-linejoin='round'/>
		<path d='M26.5 48.5H37.5' stroke='#FF1B67' stroke-width='3' stroke-miterlimit='10'
			  stroke-linecap='round' stroke-linejoin='round'/>
		<path d='M43.5 48.5H54.5' stroke='#FF1B67' stroke-width='3' stroke-miterlimit='10'
			  stroke-linecap='round' stroke-linejoin='round'/>
		<path d='M28.5 28.5H45.5' stroke='#FF1B67' stroke-width='3' stroke-miterlimit='10'
			  stroke-linecap='round' stroke-linejoin='round'/>
		<path d='M21 35.5H47' stroke='#FF1B67' stroke-width='3' stroke-miterlimit='10'
			  stroke-linecap='round' stroke-linejoin='round'/>
		<circle cx='60' cy='27' r='8.5' stroke='#FF1B67' stroke-width='3'/>
		<path d='M52.5 27H57L59 25L62 29L64 27H67' stroke='#FF1B67' stroke-width='3' stroke-linecap='round'
			  stroke-linejoin='round'/>
	</svg>
ICON;

		return $icon;
	}

	/**
	 * @inheritDoc
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	public function register_metabox_placeholder() {

		$m = [
			'id' => 'datatables_settings',
			'title' => __( 'DataTables', 'gv-datatables' ),
			'callback' => [ $this, 'render_metabox_placeholder' ],
			'callback_args' => [],
			'screen' => 'gravityview',
			'file' => '',
			'icon-class' => 'gv-icon-datatables-icon',
			'context' => 'side',
			'priority' => 'default',
		];

		$metabox = new GravityView_Metabox_Tab( $m['id'], $m['title'], $m['file'], $m['icon-class'], $m['callback'], $m['callback_args'] );

		GravityView_Metabox_Tabs::add( $metabox );
	}
}

new GravityView_Plugin_Hooks_GravityView_DataTables();
