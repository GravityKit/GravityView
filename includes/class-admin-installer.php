<?php

// Exit if accessed directly

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GravityView_Admin_Installer Class
 *
 * A general class for About page.
 *
 * @since 2.0.XX
 */
class GravityView_Admin_Installer {
	const EDD_API_URL = 'https://gravityview.co/edd-api/products';
	const EDD_API_KEY = 'e4c7321c4dcf342c9cb078e27bf4ba97';
	const EDD_API_TOKEN = 'e031fd350b03bc223b10f04d8b5dde42';
	const EXTENSIONS_DATA_EXPIRY = 86400;
	const EXTENSIONS_DATA_TRANSIENT = 'gv_extensions_data';

	public $minimum_capability = 'install_plugins';
	private $_extensions_data_updated = false;

	public function __construct() {
		$this->add_extensions_data_filters();
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 200 );
		add_filter( 'gravityview/admin_installer/delete_extensions_data', array( $this, 'delete_extensions_data' ) );
		add_action( 'wp_ajax_gravityview_admin_installer_activate', array( $this, 'activate_extension' ) );
		add_action( 'wp_ajax_gravityview_admin_installer_deactivate', array( $this, 'deactivate_extension' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_ui_assets' ) );
	}

	public function add_extensions_data_filters() {
		$extensions_data = get_transient( 'gv_extensions_data' );
		if ( ! $extensions_data ) {
			return;
		}

		add_filter( 'plugins_api', function ( $data, $action, $args ) use ( $extensions_data ) {
			foreach ( $extensions_data as $extension ) {
				if ( empty( $args->slug ) || $args->slug !== $extension['info']['slug'] ) {
					continue;
				}

				return (object) array(
					'slug'          => $extension['info']['slug'],
					'name'          => $extension['info']['title'],
					'version'       => $extension['licensing']['version'],
					'download_link' => $extension['files'][0]['file'],
				);
			}

			return $data;
		}, 10, 3 );
	}

	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=gravityview',
			__( 'Extensions & Plugins', 'gravityview' ),
			__( 'Extensions & Plugins', 'gravityview' ),
			$this->minimum_capability,
			'gv-admin-installer',
			array( $this, 'display_extensions_and_plugins_screen' )
		);
	}

	public function get_extensions_data() {
		$extensions_data = get_transient( self::EXTENSIONS_DATA_TRANSIENT );
		if ( $extensions_data ) {
			return $extensions_data;
		}

		$home_url = parse_url( home_url() );
		$api_url  = add_query_arg(
			array(
				'key'         => self::EDD_API_KEY,
				'token'       => self::EDD_API_TOKEN,
				'url'         => $home_url['host'],
				'license_key' => gravityview()->plugin->settings->get( 'license_key' )
			),
			self::EDD_API_URL
		);

		$response = wp_remote_get( $api_url, array(
			'sslverify' => false
		) );

		$extensions_data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $extensions_data['products'] ) ) {
			return false;
		}

		$this->_extensions_data_updated = true;

		return $extensions_data['products'];
	}

	public function set_extensions_data( $data = false ) {
		if ( ! $data ) {
			$data = $this->get_extensions_data();
		}

		return set_transient( self::EXTENSIONS_DATA_TRANSIENT, $data, self::EXTENSIONS_DATA_EXPIRY );
	}

	public function delete_extensions_data() {
		return delete_transient( self::EXTENSIONS_DATA_TRANSIENT );
	}

	public function display_extensions_and_plugins_screen() {
		$extensions_data = $this->get_extensions_data();
		if ( $this->_extensions_data_updated ) {
			$this->set_extensions_data( $extensions_data );
		}

		if ( ! $extensions_data ) {
			?>
            <div class="wrap">
                <h2>
					<?php _e( 'GravityView Extensions and Plugins', 'gravityview' ); ?>
                </h2>
                <p>
					<?php _e( 'Extensions and plugins data cannot be loaded at the moment. Please try again later.', 'gravityview' ); ?>
                </p>
            </div>
			<?php

			return;
		}

		$wp_plugins = array();
		foreach ( get_plugins() as $path => $plugin ) {
			if ( empty( $plugin['TextDomain'] ) ) {
				continue;
			}

			$wp_plugins[ $plugin['TextDomain'] ] = array(
				'path'      => $path,
				'version'   => $plugin['Version'],
				'activated' => is_plugin_active( $path )
			);
		}

		?>
        <div class="wrap">
            <h2>
				<?php _e( 'GravityView Extensions and Plugins', 'gravityview' ); ?>
            </h2>

            <p>
				<?php _e( 'The following are available add-ons to extend GravityView functionality:', 'gravityview' ); ?>
            </p>

            <div class="gv-admin-installer-notice notice inline error hidden">
                <p></p>
            </div>

            <div class="gv-admin-installer-container">

				<?php
				foreach ( $extensions_data as $extension ) {
					$extension_info = $extension['info'];
					$install_url    = add_query_arg(
						array(
							'action'   => 'install-plugin',
							'plugin'   => $extension_info['slug'],
							'_wpnonce' => wp_create_nonce( 'install-plugin_' . $extension_info['slug'] ),
						),
						self_admin_url( 'update.php' )
					);

					if ( $extension_info['slug'] === 'gravityview' ) {
						continue;
					}

					$wp_plugin = ( ! empty( $wp_plugins[ $extension_info['textdomain'] ] ) ) ? $wp_plugins[ $extension_info['textdomain'] ] : false;

					?>
                    <div class="item">
                        <div class="addon-inner">
                            <img class="thumbnail" src="<?php echo $extension_info['thumbnail']; ?>" alt="extension image"/>
                            <h3>
								<?php echo $extension_info['title']; ?>
                            </h3>
                            <div>
								<?php

								if ( ! $wp_plugin ) {

									?>
                                    <div class="status notinstalled">
										<?php _e( 'Not Installed', 'gravityview' ); ?>
                                    </div>
                                    <a data-status="notinstalled" href="<?php echo $install_url; ?>" class="button">
                                        <span class="title"><?php _e( 'Install', 'gravityview' ); ?></span>
                                        <span class="spinner"></span>
                                    </a>
									<?php

								} else if ( $wp_plugin['activated'] === false ) {

									?>
                                    <div class="status inactive">
										<?php _e( 'Inactive', 'gravityview' ); ?>
                                    </div>
                                    <a data-status="inactive" data-plugin-path="<?php echo $wp_plugin['path']; ?>" href=" #" class="button">
                                        <span class="title"><?php _e( 'Activate', 'gravityview' ); ?></span>
                                        <span class="spinner"></span>
                                    </a>

									<?php

								} else {

									?>
                                    <div class="status active">
										<?php _e( 'Active', 'gravityview' ); ?>
                                    </div>
                                    <a data-status="active" data-plugin-path="<?php echo $wp_plugin['path']; ?>" href="#" class="button">
                                        <span class="title"><?php _e( 'Deactivate', 'gravityview' ); ?></span>
                                        <span class="spinner"></span>
                                    </a>

									<?php

								}

								?>
                            </div>

                            <div>
								<?php echo $extension_info['excerpt']; ?>
                            </div>

                        </div>
                    </div>
					<?php
				}
				?>
            </div>
        </div>
		<?php
	}

	public function activate_extension() {
		$data = \GV\Utils::_POST( 'data', array() );

		if ( empty( $data['path'] ) ) {
			return;
		}

		$result = activate_plugin( $data['path'] );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'error' => sprintf( __( 'Extension activation failed: %s', 'gravityview' ), $result->get_error_message() )
				)
			);
		}

		wp_send_json_success();
	}

	public function deactivate_extension() {
		$data = \GV\Utils::_POST( 'data', array() );

		if ( empty( $data['path'] ) ) {
			return;
		}

		$result = deactivate_plugins( $data['path'] );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'error' => sprintf( __( 'Extension deactivation failed: %s', 'gravityview' ), $result->get_error_message() )
				)
			);
		}

		wp_send_json_success();
	}

	public function register_ui_assets() {
		$script_debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_style( 'gravityview-admin-installer', GRAVITYVIEW_URL . 'assets/css/admin-installer.css', array(), \GV\Plugin::$version );
		wp_enqueue_style( 'gravityview-admin-installer' );

		wp_register_script( 'gravityview-admin-installer', GRAVITYVIEW_URL . 'assets/js/admin-installer' . $script_debug . '.js', array( 'jquery' ), \GV\Plugin::$version, true );
		wp_enqueue_script( 'gravityview-admin-installer' );

		wp_localize_script( 'gravityview-admin-installer', 'gvAdminInstaller', array(
			'activateErrorLabel'    => __( 'Extension activation failed.', 'gravityview' ),
			'deactivateErrorLabel'  => __( 'Extension deactivation failed.', 'gravityview' ),
			'activeStatusLabel'     => __( 'Active', 'gravityview' ),
			'inactiveStatusLabel'   => __( 'Inactive', 'gravityview' ),
			'activateActionLabel'   => __( 'Activate', 'gravityview' ),
			'deactivateActionLabel' => __( 'Deactivate', 'gravityview' )
		) );
	}
}

new GravityView_Admin_Installer;
