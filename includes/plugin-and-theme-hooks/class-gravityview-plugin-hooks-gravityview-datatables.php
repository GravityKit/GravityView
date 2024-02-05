<?php

/**
 * @inheritDoc
 * @since TODO
 */
class GravityView_Plugin_Hooks_GravityView_DataTables extends GravityView_Plugin_and_Theme_Hooks {

	public $class_name = 'GravityView_Plugin_and_Theme_Hooks'; // Always true!

	public function __construct() {

		if ( defined( 'GV_DT_VERSION' ) ) {
			return;
		}

		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	public function add_hooks() {
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
	}

	/**
	 * Add DataTables Extension settings
	 */
	function register_metabox() {

		$m = array(
			'id' => 'datatables_settings',
			'title' => __( 'DataTables', 'gv-datatables' ),
			'callback' => array( $this, 'render_metabox_placeholder' ),
			'callback_args' => array(),
			'screen' => 'gravityview',
			'file' => '',
			'icon-class' => 'gv-icon-datatables-icon',
			'context' => 'side',
			'priority' => 'default',
		);

		$metabox = new GravityView_Metabox_Tab( $m['id'], $m['title'], $m['file'], $m['icon-class'], $m['callback'], $m['callback_args'] );

		GravityView_Metabox_Tabs::add( $metabox );
	}

	/**
	 * Render placeholder HTML.
	 *
	 * @access public
	 * @param WP_Post $post
	 * @return void
	 */
	function render_metabox_placeholder( $post ) {
		?>
		<style>
			#gk-placeholder-datatables .gk-placeholder-container {
				margin: 15px auto;
				box-sizing: border-box;
				padding: 30px 15px;
				background: white;
				border-radius: 7px;
				max-width: 80%;
				border: 1px #DDDDE5 solid;
			}

			#gk-placeholder-datatables .gk-placeholder-content {
				min-height: 48px;
				border-radius: 4px;
				text-align: center;
			}
			#gk-placeholder-datatables svg {
				margin-bottom: 30px;
			}
			#gk-placeholder-datatables .button {
				display: block;
				padding: 0 16px;
				text-align: center;
				margin: 1.5em auto 1em;
			}

			#gk-placeholder-datatables .gk-placeholder-summary {
				max-width: 800px;
				margin: 0 auto;
			}

			#gk-placeholder-datatables .gk-placeholder-summary .gk-h3 {
				display: block;
				position: relative;
				font-weight: 500;
				line-height: 24px;
				vertical-align: middle;
				color: #23282D;
				font-size: 18px;
				margin: 0;
				padding: 0;
			}

			#gk-placeholder-datatables .gk-placeholder-summary .howto p {
				font-size: 1.3em;
				line-height: 1.7;
			}
			#gk-placeholder-datatables .gk-placeholder-learn-more {
				display: block;
				text-align: center;
				margin: 1.5em auto 0;
				font-size: 1.1em;
			}
		</style>
		<div id='gk-placeholder-datatables'>
			<div class='gk-placeholder-container'>
				<div class='gk-placeholder-content'>
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

					<div class='gk-placeholder-summary'>
						<h3 class='gk-h3'><?php esc_html_e( 'DataTables Layout', 'gk-gravityview' ); ?></h3>
						<div class="howto">
							<p><?php esc_html_e( 'Display Gravity Forms data in a live-updating table with extended sorting, filtering and exporting capabilities.', 'gk-gravityview' ); ?></p>
						</div>
					</div>
				</div>
				<button class="gk-placeholder-button button button-primary button-hero"><?php

					esc_html_e( 'Activate Now', 'gk-gravityview' );

					// TODO: Enable logic to check if the plugin is installed/activated.
					# esc_html_e( 'Buy Now', 'gk-gravityview' );
					#esc_html_e( 'Install & Activate', 'gk-gravityview' );

					?></button>
				<p><a class="gk-placeholder-learn-more" href="https://www.gravitykit.com/products/datatables/" rel="external" target="_blank"><?php
						echo esc_html( sprintf( __( 'Learn more about %s…', 'gk-gravityview' ), __( 'DataTables Layout', 'gk-gravityview' ) ) );
						?><span class="screen-reader-text"> <?php esc_html_e( 'This link opens in a new window.', 'gk-gravityview' );?></span></a></p>
			</div>
		</div>
		<?php
	}
}

new GravityView_Plugin_Hooks_GravityView_DataTables();
