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

	const EXTENSIONS_DATA_TRANSIENT = 'gv_extensions_data';

	const EXTENSIONS_DATA_TRANSIENT_EXPIRY = DAY_IN_SECONDS;

	/**
	 * @var string
	 */
	public $minimum_capability = 'install_plugins';

	public function __construct() {

		$this->add_extensions_data_filters();

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 200 );
		add_action( 'gravityview/admin_installer/delete_extensions_data', array( $this, 'delete_extensions_data' ) );
		add_action( 'wp_ajax_gravityview_admin_installer_activate', array( $this, 'activate_extension' ) );
		add_action( 'wp_ajax_gravityview_admin_installer_deactivate', array( $this, 'deactivate_extension' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_scripts_and_styles' ) );
		add_filter( 'gravityview_noconflict_scripts', array( $this, 'register_noconflict' ) );
		add_filter( 'gravityview_noconflict_styles', array( $this, 'register_noconflict' ) );

	/**
	 * Let us operate when GF no-conflict is enabled
	 *
	 * @param array $items Scripts or styles to exclude from no-conflict
	 *
	 * @return array
	 */
	public function register_noconflict( $items ) {

		$items[] = 'gravityview-admin-installer';

		return $items;
	}

	/**
	 * Modify plugins data with custom GV extension info
	 *
	 * @return void
	 */
	public function add_extensions_data_filters() {
		$extensions_data = get_transient( self::EXTENSIONS_DATA_TRANSIENT );
		if ( ! $extensions_data ) {
			return;
		}

		add_filter( 'plugins_api', function ( $data, $action, $args ) use ( $extensions_data ) {
			foreach ( $extensions_data as $extension ) {
				if ( empty( $extension['info'] ) || empty( $args->slug ) || $args->slug !== $extension['info']['slug'] ) {
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

	/**
	 * Add new admin menu
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=gravityview',
			__( 'Extensions & Plugins', 'gravityview' ),
			__( 'Extensions & Plugins', 'gravityview' ),
			$this->minimum_capability,
			'gv-admin-installer',
			array( $this, 'render_screen' )
		);
	}

	/**
	 * Get an array of plugins with textdomains as keys
	 *
	 * @return array {
	 * @type string $path Path to the plugin
	 * @type string $version What version is the plugin
	 * @type bool $activated Is the plugin activated
	 * }
	 */
	protected function get_wp_plugins_data() {

		$wp_plugins = array();

		$all_plugins = get_plugins();

		foreach ( $all_plugins as $path => $plugin ) {

			if ( empty( $plugin['TextDomain'] ) ) {
				continue;
			}

			$wp_plugins[ $plugin['TextDomain'] ] = array(
				'path'      => $path,
				'version'   => $plugin['Version'],
				'activated' => is_plugin_active( $path )
			);
		}

		return $wp_plugins;
	}

	/**
	 * Get extensions data from transient or from API; save transient after getting data from API
	 *
	 * @return array {
     *   @type array  $info {
     *       @type string $id int 17
     *       @type string $slug Extension slug
     *       @type string $title Extension title
     *       @type string $create_date in '2018-07-19 20:03:10' format
     *       @type string $modified_date
     *       @type string $status
     *       @type string $link URL to public plugin page
     *       @type string $content
     *       @type string $excerpt
     *       @type string $thumbnail URL to thumbnail
     *       @type array  $category Taxonomy details for the plugin's category {
     *         @type int $term_id => int 30
     *         @type string $name => string 'Plugins' (length=7)
     *         @type string $slug => string 'plugins' (length=7)
     *         @type int $term_group => int 0
     *         @type int $term_taxonomy_id => int 30
     *         @type string $taxonomy => string 'download_category' (length=17)
     *         @type string $description => string '' (length=0)
     *         @type int $parent => int 0
     *         @type int $count => int 4
     *         @type string $filter => string 'raw' (length=3)
     *       }
     *       @type array $tags {see $category above}
     *       @type string $textdomain string 'gravityview' (length=11)
     *   }
     *   @type array $pricing array of `price_name_slugs` => '00.00' values, if price options exist
     *   @type array $licensing {
     *       @type bool   $enabled Is licensing enabled for the extension
     *       @type string $version Version number
     *       @type string $exp_unit Expiration unit ('years')
     *       @type string $exp_length Expiration length ('1')
     *   }
     *   @type array $files Array of files. Empty if user has no access to the file. {
     *       @type string $file string URL of the file download
     *   }
     * }
	 */
	public function get_extensions_data() {

		$extensions_data = get_transient( self::EXTENSIONS_DATA_TRANSIENT );

		if ( $extensions_data ) {
			return $extensions_data;
		}

		$home_url = parse_url( home_url() );

		$api_url = add_query_arg(
			array(
				'key'         => self::EDD_API_KEY,
				'token'       => self::EDD_API_TOKEN,
				'url'         => \GV\Utils::get( $home_url, 'host', home_url() ),
				'license_key' => gravityview()->plugin->settings->get( 'license_key' )
			),
			self::EDD_API_URL
		);

		$response = wp_remote_get( $api_url, array(
			'sslverify' => false,
			'timeout'   => 10,
		) );

		$extensions_data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $extensions_data['products'] ) ) {
			return array();
		}

		$this->set_extensions_data( $extensions_data['products'] );

		return $extensions_data['products'];
	}

	/**
	 * Save extensions data in a time-bound transient
	 *
	 * @param array $data
	 *
	 * @return true if successful, false otherwise
	 */
	public function set_extensions_data( $data ) {
		return set_transient( self::EXTENSIONS_DATA_TRANSIENT, $data, self::EXTENSIONS_DATA_TRANSIENT_EXPIRY );
	}

	/**
	 * Delete extensions data transient
	 *
	 * @return bool true if successful, false otherwise
	 */
	public function delete_extensions_data() {
		return delete_transient( self::EXTENSIONS_DATA_TRANSIENT );
	}

	/**
	 * Display a grid of available extensions and controls to install/activate/deactivate them
	 *
	 * @since 2.1
	 *
	 * @return void
	 */
	public function render_screen() {

		$extensions_data = $this->get_extensions_data();

		if ( empty( $extensions_data ) ) {
			?>
            <div class="wrap">
                <h2>
					<?php esc_html_e( 'GravityView Extensions and Plugins', 'gravityview' ); ?>
                </h2>
                <div class="gv-admin-installer-notice notice inline error">
                    <h3>
						<?php esc_html_e( 'Extensions and plugins data cannot be loaded at the moment. Please try again later.', 'gravityview' ); ?>
                    </h3>
                </div>
            </div>
			<?php

			return;
		}

		?>
        <div class="wrap">
            <h2>
				<?php esc_html_e( 'GravityView Extensions and Plugins', 'gravityview' ); ?>
            </h2>

            <p>
				<?php esc_html_e( 'The following are available add-ons to extend GravityView functionality:', 'gravityview' ); ?>
            </p>

            <div class="gv-admin-installer-notice notice inline error hidden is-dismissible">
                <p><!-- Contents will be replaced by JavaScript if there is an error --></p>
            </div>

            <div class="gv-admin-installer-container">
				<?php

				$wp_plugins = $this->get_wp_plugins_data();

				foreach ( $extensions_data as $extension ) {

					if ( empty( $extension['info'] ) ) {
						continue;
					}

					if ( 'gravityview' === \GV\Utils::get( $extension, 'info/slug' ) ) {
						continue;
					}

					$this->render_extension( $extension, $wp_plugins );
				}
				?>
            </div>
        </div>
		<?php
	}

	/**
	 * Outputs the HTML of a single extension
	 *
	 * @param array $extension Extension data, as returned from EDD API
	 * @param array $wp_plugins
	 *
	 * @return void
	 */
	protected function render_extension( $extension, $wp_plugins ) {

		$extension_info = wp_parse_args( (array) $extension['info'], array(
		    'thumbnail' => '',
            'title' => '',
            'textdomain' => '',
            'slug' => '',
            'excerpt' => '',
        ) );

		?>
        <div class="item">
            <div class="addon-inner">
                <img class="thumbnail" src="<?php echo esc_attr( $extension_info['thumbnail'] ); ?>" alt=""/>
                <h3>
					<?php echo esc_html( $extension_info['title'] ); ?>
                </h3>
                <div><?php

	                $wp_plugin = \GV\Utils::get( $wp_plugins, $extension_info['textdomain'], false );

                    $href = $plugin_path = '#';

					if ( ! $wp_plugin ) {

						$href = add_query_arg(
							array(
								'action'   => 'install-plugin',
								'plugin'   => $extension_info['slug'],
								'_wpnonce' => wp_create_nonce( 'install-plugin_' . $extension_info['slug'] ),
							),
							self_admin_url( 'update.php' )
						);

						$status = 'notinstalled';
	                    $status_label = __( 'Not Installed', 'gravityview' );
						$button_label = __( 'Install', 'gravityview' );

					} else if ( false === $wp_plugin['activated'] ) {

						$status = 'inactive';
						$status_label = __( 'Inactive', 'gravityview' );
						$button_label = __( 'Activate', 'gravityview' );
						$plugin_path = $wp_plugin['path'];

					} else {

					    $plugin_path = $wp_plugin['path'];
						$status = 'active';
						$status_label = __( 'Active', 'gravityview' );
						$button_label = __( 'Deactivate', 'gravityview' );

					}

					?>

                    <div class="status <?php echo esc_attr( $status ); ?>">
		                <?php echo esc_html( $status_label ); ?>
                    </div>
                    <a data-status="<?php echo esc_attr( $status ); ?>" data-plugin-path="<?php echo esc_attr( $plugin_path ); ?>" href="<?php echo esc_url( $href ); ?>" class="button">
                        <span class="title"><?php echo esc_html( $button_label ); ?></span>
                        <span class="spinner"></span>
                    </a>
                </div>

                <div class="addon-excerpt">
					<?php echo wpautop( esc_html( $extension_info['excerpt'] ) ); ?>
                </div>

            </div>
        </div>
		<?php
	}

	/**
	 * Handle AJAX request to activate extension
	 *
	 * @return void Exits with JSON response
	 */
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

	/**
	 * Handle AJAX request to deactivate extension
	 *
	 * @return void Send JSON response status and error message
	 */
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

	/**
	 * Register and enqueue assets; localize script
	 *
	 * @return void
	 */
	public function maybe_enqueue_scripts_and_styles() {

		if ( ! gravityview()->request->is_admin( '', 'extensions' ) ) {
			return;
		}

		$script_debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_style( 'gravityview-admin-installer', GRAVITYVIEW_URL . 'assets/css/admin-installer.css', array(), \GV\Plugin::$version );

		wp_enqueue_script( 'gravityview-admin-installer', GRAVITYVIEW_URL . 'assets/js/admin-installer' . $script_debug . '.js', array( 'jquery' ), \GV\Plugin::$version, true );

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
