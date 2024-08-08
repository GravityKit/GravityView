<?php

/**
 * @inheritDoc
 *
 * @since 2.26
 */
class GravityView_Plugin_Hooks_GravityView_DataTables extends GravityView_Plugin_and_Theme_Hooks {
	/**
	 * @inheritDoc
	 */
	public $constant_name = 'GV_DT_VERSION';

	/**
	 * Returns the placeholder value object.
	 *
	 * @since 2.26
	 *
	 * @return GravityView_Object_Placeholder The placeholder.
	 */
	private function get_placeholder(): GravityView_Object_Placeholder {
		return
			GravityView_Object_Placeholder::card(
				__( 'DataTables Layout', 'gk-gravityview' ),
				__( 'Display Gravity Forms data in a live-updating table with extended sorting, filtering and exporting capabilities.', 'gk-gravityview' ),
				$this->get_placeholder_icon(),
				'gv-datatables',
				'https://www.gravitykit.com/products/datatables/'
			);
	}

	/**
	 * Returns the icon for the DataTables layout.
	 *
	 * @since 2.26
	 *
	 * @return string The SVG icon.
	 */
	public function get_placeholder_icon(): string {
		return <<<ICON
	<svg viewBox='0 0 80 80' fill='none' xmlns='http://www.w3.org/2000/svg'>
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
	}

	/**
	 * @inheritDoc
	 *
	 * @since 2.26
	 */
	protected function add_inactive_hooks(): void {
		add_action( 'add_meta_boxes', [ $this, 'register_metabox_placeholder' ] );
	}

	/**
	 * Register the DataTables placeholder metabox.
	 *
	 * @since 2.26
	 */
	public function register_metabox_placeholder(): void {
		$disabled = apply_filters( 'gk/gravityview/feature/upgrade/disabled', false );
		if ( $disabled ) {
			return;
		}

		$metabox = new GravityView_Metabox_Tab(
			'datatables_settings',
			__( 'DataTables', 'gv-datatables', 'gk-gravityview' ),
			'',
			'gv-icon-datatables-icon',
			function () {
				$this->get_placeholder()->render();
			}
		);

		$metabox->extra_nav_class = 'gravityview-upgrade';

		GravityView_Metabox_Tabs::add( $metabox );
	}
}

new GravityView_Plugin_Hooks_GravityView_DataTables();
