<?php

trait GravityView_Functionality_Placeholder {

	protected $icon;

	/**
	 * Add hooks for when Maps is disabled.
	 */
	protected function add_placeholder_hooks() {
		add_action( 'add_meta_boxes', [ $this, 'register_metabox_placeholder' ] );
	}

	/**
	 * Register the Maps placeholder metabox.
	 *
	 * @since TODO
	 */
	abstract function register_metabox_placeholder();

	abstract function get_placeholder_icon();

	abstract function get_placeholder_title();

	abstract function get_placeholder_description();

	/**
	 * Returns the link to buy the plugin.
	 *
	 * @since TODO
	 *
	 * @return string URL to buy the plugin
	 */
	abstract function get_buy_now_link();

	/**
	 * Render placeholder HTML.
	 *
	 * @access public
	 * @param WP_Post $post
	 * @return void
	 */
	function render_metabox_placeholder( $post ) {
		?>
		<div class='gk-gravityview-placeholder-container'>
			<div class='gk-gravityview-placeholder-content'>
				<div class="gk-gravityview-placeholder-icon"><?php echo $this->get_placeholder_icon(); ?></div>

				<div class='gk-placeholder-summary'>
					<h3 class='gk-h3'><?php echo esc_html( $this->get_placeholder_title() ); ?></h3>
					<div class="howto">
						<p><?php echo esc_html( $this->get_placeholder_description() ); ?></p>
					</div>
				</div>
			</div>
			<button class="gk-placeholder-button button button-primary button-hero"><?php

				esc_html_e( 'Activate Now', 'gk-gravityview' );

				// TODO: Enable logic to check if the plugin is installed/activated.
				# esc_html_e( 'Buy Now', 'gk-gravityview' );
				#esc_html_e( 'Install & Activate', 'gk-gravityview' );

				?></button>
			<p><a class="gk-placeholder-learn-more" href="<?php echo esc_url( $this->get_buy_now_link() ); ?>" rel="external noopener noreferrer" target="_blank"><?php
					echo esc_html( sprintf( __( 'Learn more about %s…', 'gk-gravityview' ), $this->get_placeholder_title() ) );
					?><span class="screen-reader-text"> <?php esc_html_e( 'This link opens in a new window.', 'gk-gravityview' );?></span></a></p>
		</div>
		<?php
	}
}
