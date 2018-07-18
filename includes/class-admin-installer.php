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
			'sslverify' => false,
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
				'version'   => $plugin['Version'],
				'activated' => is_plugin_active( $path )
			);
		}

		?>
        <div class="wrap">
            <style>
                .gv-admin-installer-container .addon-inner .active {
                    background-color: #CBECA0;
                    border: 1px solid #97B48A;
                    padding: 6px;
                    display: block;
                    font-weight: bold;
                    color: #2D5312;
                    margin-bottom: 12px;
                    background-repeat: repeat-x;
                    background-position: 0 0;
                    -webkit-border-radius: 3px;
                    -moz-border-radius: 3px;
                    border-radius: 3px
                }

                .gv-admin-installer-container .addon-inner .notinstalled {
                    background-color: #EEE;
                    border: 1px solid #DADADA;
                    padding: 6px;
                    display: block;
                    font-weight: bold;
                    color: #424242;
                    margin-bottom: 12px;
                    background-repeat: repeat-x;
                    background-position: 0 0;
                    -webkit-border-radius: 3px;
                    -moz-border-radius: 3px;
                    border-radius: 3px
                }

                .gv-admin-installer-container .addon-inner .inactive {
                    background-color: #FFFBCC;
                    padding: 6px;
                    font-weight: bold;
                    border: 1px solid #E6DB55;
                    color: #424242;
                    margin-bottom: 12px;
                    background-repeat: repeat-x;
                    background-position: 0 0;
                    -webkit-border-radius: 3px;
                    -moz-border-radius: 3px;
                    border-radius: 3px
                }

                .gv-admin-installer-container .addon-inner {
                    margin: 1.5em;
                }

                .gv-admin-installer-container .addon-inner ul {
                    margin-left: 1.25em;
                    list-style: initial;
                }

                .gv-admin-installer-container .addon-inner div {
                    margin-top: 1em
                }

                .gv-admin-installer-container .addon-inner .thumbnail {
                    width: 100%;
                    max-width: 300px;

                    /*-webkit-clip-path: polygon(50% 0%, 80% 10%, 100% 35%, 100% 70%, 80% 90%, 50% 100%, 20% 90%, 0% 70%, 0% 35%, 20% 10%);
                    clip-path: polygon(50% 0%, 80% 10%, 100% 35%, 100% 70%, 80% 90%, 50% 100%, 20% 90%, 0% 70%, 0% 35%, 20% 10%);*/
                }

                .gv-admin-installer-container {
                    min-height: 400px;
                    display: flex;
                    display: -webkit-flex;
                    flex-wrap: wrap;
                    flex-direction: row;
                    align-items: flex-start;
                    align-content: space-between;
                }

                .gv-admin-installer-container:after {
                    display: block;
                    flex: 999 999 auto;
                }

                .gv-admin-installer-container > .item {
                    flex: 0 0 auto;
                    margin: 0 20px 20px 0;
                    border: 1px solid #ccc;
                    -moz-box-shadow: 0 0 5px rgba(0, 0, 0, 0.25);
                    -webkit-box-shadow: 0 0 5px rgba(0, 0, 0, 0.25);
                    box-shadow: 0 0 5px rgba(0, 0, 0, 0.25);
                    width: 33%;
                    min-width: 250px;
                    max-width: 340px;
                    min-height: 440px;
                }
            </style>

            <h2>
				<?php _e( 'GravityView Extensions and Plugins', 'gravityview' ); ?>
            </h2>

            <p>
				<?php _e( 'The following are available add-ons to extend GravityView functionality:', 'gravityview' ); ?>
            </p>

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

					?>
                    <div class="item">
                        <div class="addon-inner">
                            <img class="thumbnail" src="<?php echo $extension_info['thumbnail']; ?>"
                                 alt="extension image"/>
                            <h3>
								<?php echo $extension_info['title']; ?>
                            </h3>
                            <div>
								<?php
								if ( empty( $wp_plugins[ $extension_info['textdomain'] ] ) ) {
									?>
                                    <div class="notinstalled">
										<?php _e( 'Not Installed', 'gravityview' ); ?>
                                    </div>
                                    <a href="<?php echo $install_url; ?>" class=" button">
										<?php _e( 'Install', 'gravityview' ); ?>
                                    </a>
									<?php
								} else if ( $wp_plugins[ $extension_info['textdomain'] ]['activated'] === false ) {
									?>
                                    <div class="inactive">
										<?php _e( 'Inactive', 'gravityview' ); ?>
                                    </div>
                                    <a href="#" class=" button">
										<?php _e( 'Activate', 'gravityview' ); ?>
                                    </a>
									<?php
								} else {
									?>
                                    <div class="active">
										<?php _e( 'Active', 'gravityview' ); ?>
                                    </div>
                                    <a href="#" class=" button">
										<?php _e( 'Deactivate', 'gravityview' ); ?>
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
}

new GravityView_Admin_Installer;
