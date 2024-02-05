<?php

/**
 * @inheritDoc
 * @since TODO
 */
class GravityView_Plugin_Hooks_GravityView_Advanced_Filtering extends GravityView_Plugin_and_Theme_Hooks {

	public $class_name = 'GravityView_Plugin_and_Theme_Hooks'; // Always true!

	public function __construct() {

		if ( defined( 'GRAVITYKIT_ADVANCED_FILTERING_VERSION' ) ) {
			return;
		}

		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	public function add_hooks() {
		add_action( 'gravityview_metabox_sort_filter_after', [ $this, 'render_placehodler' ] );
	}

	/**
	 * Render placeholder HTML.
	 *
	 * @access public
	 * @param WP_Post $post
	 * @return void
	 */
	function render_placehodler( $post ) {
?>
		<tr id="gk-placeholder-advanced-filtering">
			<td colspan="2">
				<style>
					#gk-placeholder-advanced-filtering .gk-placeholder-container {
						margin: 8px 10px;
						width: calc(100% - 20px);
						box-sizing: border-box;
						padding: 15px;
						background: white;
						border-radius: 7px;
						border: 1px #DDDDE5 solid;
						justify-content: flex-start;
						align-items: center;
						display: inline-flex
					}
					#gk-placeholder-advanced-filtering .gk-placeholder-content {
						flex: 1 1 0;
						min-height: 48px;
						border-radius: 4px;
						justify-content: flex-start;
						align-items: center;
						gap: 16px;
						display: flex;
					}

					@media (max-width: 1199px) {
						#gk-placeholder-advanced-filtering .gk-placeholder-container {
							margin: 0;
							width: 100%;
						}
						#gk-placeholder-advanced-filtering .gk-placeholder-content {
							gap: 0;
						}
						#gk-placeholder-advanced-filtering svg {
							display: none;
						}
					}

					#gk-placeholder-advanced-filtering .gk-placeholder-summary {
						flex: 1 1 0; flex-direction: column; justify-content: flex-end; align-items: flex-start; display: inline-flex;
					}

					#gk-placeholder-advanced-filtering .gk-placeholder-summary .gk-h3 {
						align-self: stretch;
						display: block;
						position: relative;
						font-weight: 500;
						line-height: 1.3;
						vertical-align: middle;
						color: #23282D;
						font-size: 15px;
						margin: 0;
						padding: 0;
					}


					#gk-placeholder-advanced-filtering .gk-placeholder-summary .howto p {
						margin: 0!important;
					}

					#gk-placeholder-advanced-filtering .gk-placeholder-button {
						padding: 0 16px;
					}
				</style>
				<div class="gk-placeholder-container">
					<div class='gk-placeholder-content'>
						<svg width='48' height='48' viewBox='0 0 48 48' fill='none' xmlns='http://www.w3.org/2000/svg'>
							<rect width='48' height='48' rx='8' fill='#FF1B67'/>
							<path d='M12.3 21.9L15.6 12.3H16.8L20.1 21.9' stroke='white' stroke-width='2'
							      stroke-miterlimit='10'
							      stroke-linecap='round' stroke-linejoin='round'/>
							<path d='M13.125 19.5H19.275' stroke='white' stroke-width='2' stroke-miterlimit='10'
							      stroke-linecap='round' stroke-linejoin='round'/>
							<path d='M24.3 30.3L30.3 36.3L36.3 30.3' stroke='white' stroke-width='2'
							      stroke-miterlimit='10'
							      stroke-linecap='round' stroke-linejoin='round'/>
							<path d='M30.3 36.3V11.7' stroke='white' stroke-width='2' stroke-linecap='round'
							      stroke-linejoin='round'/>
							<path d='M12.9 26.7H19.5V27.3L12.9 35.7V36.3H19.5' stroke='white' stroke-width='2'
							      stroke-miterlimit='10' stroke-linecap='round' stroke-linejoin='round'/>
						</svg>

						<div class="gk-placeholder-summary">
							<h3 class="gk-h3"><?php esc_html_e( 'Advanced Filtering', 'gk-gravityview' ); ?></h3>
							<div class="howto">
								<p><?php esc_html_e( 'Control what entries are displayed in a View using advanced conditional logic.', 'gk-gravityview' ); ?></p>
							</div>
						</div>
					</div>
					<button class="gk-placeholder-button button button-primary button-hero"><?php

						esc_html_e( 'Activate Now', 'gk-gravityview' );

						// TODO: Enable logic to check if the plugin is installed/activated.
						# esc_html_e( 'Buy Now', 'gk-gravityview' );
						#esc_html_e( 'Install & Activate', 'gk-gravityview' );

					?></button>
				</div>
			</td>
		</tr>
<?php
	}
}

new GravityView_Plugin_Hooks_GravityView_Advanced_Filtering();
