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

	abstract function get_plugin_basename();


	/**
	 * Render placeholder HTML.
	 *
	 * @access public
	 * @return void
	 */
	function render_metabox_placeholder() {

		$plugin_basename = $this->get_plugin_basename();

		switch( GravityView_Compatibility::get_plugin_status( $plugin_basename ) ) {
			case 'inactive':
				$caps = 'activate_plugins';
				$button_text = __( 'Activate Now', 'gk-gravityview' );
				$button_href = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $plugin_basename ), 'activate-plugin_' . $plugin_basename );
				break;
			case false:

				if( true ) { // TODO: Add check to see if license includes the plugin.
					$caps = 'install_plugins';
					$button_text = __( 'Install & Activate', 'gk-gravityview' );
					$button_href = '#'; // TODO Add link to install & activate the plugin. Should we just link to Foundation?
				} else {
					$caps = 'read';
					$button_text = __( 'Buy Now', 'gk-gravityview' );
					$button_href = $this->get_buy_now_link();
				}
				break;
		}

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
			<?php

			// Only show the button if the user has ability to take action with the plugin.
			if ( current_user_can( $caps ) ) {
				echo sprintf( '<a href="%1$s" class="gk-placeholder-button button button-primary button-hero">%2$s</a>', esc_url( $button_href ), esc_html( $button_text ) );
				$learn_more_class = ''; // Learn more link is just a link if the user has the ability to install/activate.
			} else {
				$learn_more_class = 'button button-primary button-hero';
			}
			?>
			<p><a class="gk-placeholder-learn-more <?php echo gravityview_sanitize_html_class( $learn_more_class ); ?>" href="<?php echo esc_url( $this->get_buy_now_link() ); ?>" rel="external noopener noreferrer" target="_blank"><?php
					echo esc_html( sprintf( __( 'Learn more about %s…', 'gk-gravityview' ), $this->get_placeholder_title() ) );
					?><span class="screen-reader-text"> <?php esc_html_e( 'This link opens in a new window.', 'gk-gravityview' );?></span></a></p>
		</div>
		<?php
	}
}
