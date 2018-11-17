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
 * @since 2.1
 */
class GravityView_Admin_Installer {

	const EDD_API_URL = 'https://gravityview.co/edd-api/products/';

	const EDD_API_KEY = 'e4c7321c4dcf342c9cb078e27bf4ba97';

	const EDD_API_TOKEN = 'e031fd350b03bc223b10f04d8b5dde42';

	const DOWNLOADS_DATA_TRANSIENT = 'gv_downloads_data';

	const DOWNLOADS_DATA_TRANSIENT_EXPIRY = DAY_IN_SECONDS;

	/**
	 * @var string
	 */
	public $minimum_capability = 'install_plugins';

	public function __construct() {

		$this->add_downloads_data_filters();

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 200 );
		add_action( 'gravityview/admin_installer/delete_downloads_data', array( $this, 'delete_downloads_data' ) );
		add_action( 'wp_ajax_gravityview_admin_installer_activate', array( $this, 'activate_download' ) );
		add_action( 'wp_ajax_gravityview_admin_installer_deactivate', array( $this, 'deactivate_download' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_scripts_and_styles' ) );
		add_filter( 'gravityview_noconflict_scripts', array( $this, 'register_noconflict' ) );
		add_filter( 'gravityview_noconflict_styles', array( $this, 'register_noconflict' ) );
		add_filter( 'gravityview/settings/license-key-notice', array( $this, 'maybe_modify_license_notice' ) );
	}

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
	public function add_downloads_data_filters() {

	    $downloads_data = get_site_transient( self::DOWNLOADS_DATA_TRANSIENT );

	    if ( ! $downloads_data ) {
			return;
		}

		add_filter( 'plugins_api', function ( $data, $action, $args ) use ( $downloads_data ) {
			foreach ( $downloads_data as $extension ) {
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

	    $menu_text = _x( 'Extensions', 'Extensions are WordPress plugins that add functionality to GravityView and Gravity Forms', 'gravityview' );

		$menu_text = sprintf( '<span title="%s">%s</span>', esc_attr__( 'Plugins that extend GravityView and Gravity Forms functionality.', 'gravityview' ), $menu_text );

		add_submenu_page(
			'edit.php?post_type=gravityview',
			__( 'GravityView Extensions and Plugins', 'gravityview' ),
			$menu_text,
			$this->minimum_capability,
			'gv-admin-installer',
			array( $this, 'render_screen' )
		);
	}

	/**
     * When on the Installer page, show a different notice than on the Settings page
     *
	 * @param array $notice
	 *
	 * @return string License notice
	 */
	public function maybe_modify_license_notice( $notice = '' ) {

		if ( ! gravityview()->request->is_admin( '', 'downloads' ) ) {
            return $notice;
        }

        return esc_html__( 'Your license %s. Do you want access to these plugins? %sActivate your license%s or %sget a license here%s.', 'gravityview' );
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
	 * Get downloads data from transient or from API; save transient after getting data from API
	 *
	 * @return WP_Error|array If error, returns WP_Error. If not valid JSON, empty array. Otherwise, this structure: {
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
	public function get_downloads_data() {

		$downloads_data = get_site_transient( self::DOWNLOADS_DATA_TRANSIENT );

		if ( $downloads_data ) {
			return $downloads_data;
		}

		if( \GV\Plugin::is_network_activated() ) {
			$home_url = network_home_url();
		} else {
			$home_url = home_url();
		}

		$api_url = add_query_arg(
			array(
				'key'         => self::EDD_API_KEY,
				'token'       => self::EDD_API_TOKEN,
				'url'         => $home_url,
				'license_key' => gravityview()->plugin->settings->get( 'license_key' )
			),
			self::EDD_API_URL
		);

		$response = wp_remote_get( $api_url, array(
			'sslverify' => false,
			'timeout'   => 5,
		) );

		if ( is_wp_error( $response ) ) {
		    gravityview()->log->error( "Extension data response is an error", array( 'data' => $response ) );
			return $response;
		}

		$downloads_data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $downloads_data['products'] ) ) {
			return array();
		}

		$this->set_downloads_data( $downloads_data['products'] );

		return $downloads_data['products'];
	}

	/**
	 * Save downloads data in a time-bound transient
	 *
	 * @param array $data
	 *
	 * @return true if successful, false otherwise
	 */
	public function set_downloads_data( $data ) {
		return set_site_transient( self::DOWNLOADS_DATA_TRANSIENT, $data, self::DOWNLOADS_DATA_TRANSIENT_EXPIRY );
	}

	/**
	 * Delete downloads data transient
	 *
	 * @return bool true if successful, false otherwise
	 */
	public function delete_downloads_data() {
		return delete_site_transient( self::DOWNLOADS_DATA_TRANSIENT );
	}

	/**
	 * Display a grid of available downloads and controls to install/activate/deactivate them
	 *
	 * @since 2.1
	 *
	 * @return void
	 */
	public function render_screen() {

		$downloads_data = $this->get_downloads_data();

		if ( is_wp_error( $downloads_data ) || empty( $downloads_data ) ) {
			?>
            <div class="wrap">
                <h1><?php esc_html_e( 'GravityView Extensions and Plugins', 'gravityview' ); ?></h1>
                <div class="gv-admin-installer-notice notice inline error">
                    <h3><?php esc_html_e( 'Extensions and plugins data cannot be loaded at the moment. Please try again later.', 'gravityview' ); ?></h3>
                    <?php
                    if ( is_wp_error( $downloads_data ) ) {
	                    echo wpautop( '<pre>' . esc_html( $downloads_data->get_error_message() ) . '</pre>' );
                    }
                    ?>
                </div>
            </div>
			<?php

			return;
		}

		?>
        <div class="wrap">


            <h1><?php esc_html_e( 'GravityView Extensions and Plugins', 'gravityview' ); ?></h1>

            <h2><?php esc_html_e( 'The following plugins extend GravityView and Gravity Forms functionality:', 'gravityview' ); ?></h2>

            <div class="wp-header-end"></div>

            <div class="gv-admin-installer-notice notice inline error hidden is-dismissible">
                <p><!-- Contents will be replaced by JavaScript if there is an error --></p>
            </div>

            <div class="gv-admin-installer-container">
				<?php

				$wp_plugins = $this->get_wp_plugins_data();

				foreach ( $downloads_data as $extension ) {

					if ( empty( $extension['info'] ) ) {
						continue;
					}

					if ( 'gravityview' === \GV\Utils::get( $extension, 'info/slug' ) ) {
						continue;
					}

					$this->render_download( $extension, $wp_plugins );
				}
				?>
            </div>
        </div>
		<?php
	}

	/**
	 * Outputs the HTML of a single download
	 *
	 * @param array $download Download data, as returned from EDD API
	 * @param array $wp_plugins
	 *
	 * @return void
	 */
	protected function render_download( $download, $wp_plugins ) {


        $details = $this->get_download_display_details( $download, $wp_plugins );

        $download_info = $details['download_info'];

		?>
        <div class="item <?php echo esc_attr( $details['item_class'] ); ?>">
            <div class="addon-inner">
                <a href="<?php echo esc_url( $download_info['link'] ); ?>" rel="external noreferrer noopener" title="<?php esc_html_e( 'Visit the plugin page', 'gravityview' ); ?>"><img class="thumbnail" src="<?php echo esc_attr( $download_info['thumbnail'] ); ?>" alt="" /></a>
                <h3><?php echo esc_html( \GV\Utils::get( $download_info, 'installer_title', $download_info['title'] ) ); ?></h3>
                <div>
                    <?php if( ! empty( $details['status_label'] ) ) { ?>
                    <div class="status <?php echo esc_attr( $details['status'] ); ?>" title="<?php printf( esc_attr__( 'Plugin status: %s', 'gravityview' ), esc_html( $details['status_label'] ) ); ?>">
                        <span class="dashicons dashicons-admin-plugins"></span> <span class="status-label"><?php echo esc_html( $details['status_label'] ); ?></span>
                    </div>
			        <?php } ?>

                    <a data-status="<?php echo esc_attr( $details['status'] ); ?>" data-plugin-path="<?php echo esc_attr( $details['plugin_path'] ); ?>" href="<?php echo esc_url( $details['href'] ); ?>" class="button <?php echo esc_attr( $details['button_class'] ); ?>" title="<?php echo esc_attr( $details['button_title'] ); ?>">
                        <span class="title"><?php echo esc_html( $details['button_label'] ); ?></span>
                        <?php if( $details['spinner'] ) { ?><span class="spinner"></span><?php } ?>
                    </a>
                </div>

                <div class="addon-excerpt"><?php

                    $excerpt = \GV\Utils::get( $download_info, 'installer_excerpt', $download_info['excerpt'] );

                    // Allow some pure HTML tags, but remove everything else from the excerpt.
                    $tags = array( '<strong>', '</strong>', '<em>', '</em>', '<code>', '</code>' );
                    $replacements = array( '[b]', '[/b]', '[i]', '[/i]', '[code]', '[/code]' );

                    $excerpt = str_replace( $tags, $replacements, $excerpt );
                    $excerpt = esc_html( strip_tags( $excerpt ) );
					$excerpt = str_replace( $replacements, $tags, $excerpt );

					echo wpautop( $excerpt );
                ?></div>
            </div>
        </div>
		<?php
	}

	/**
     * Generates details array for the download to keep the render_download() method a bit tidier
     *
	 * @param array $download Single download, as returned by {@see get_downloads_data}
	 * @param array $wp_plugins All active plugins, as returned by {@see get_plugins()}
	 *
	 * @return array {
     *   @type array $download_info
     *   @type string $plugin_path
     *   @type string $status License status returned by Easy Digital Downloads ("active", "inactive", "expired", "revoked", etc)
     *   @type string $status_label
     *   @type string $button_title Title attribute to show when hovering over the download's button
     *   @type string $button_class CSS class to use for the button
     *   @type string $button_label Text to use for the download's anchor link
     *   @type string $href URL for the download's button
     *   @type bool   $spinner Whether to show the spinner icon
     *   @type string $item_class CSS class for the download container
     *   @type string $required_license The name of the required license for the download ("All Access" or "Core + Extensions")
     *   @type bool   $is_active Is the current GravityView license (as entered in Settings) active?
     * }
	 */
	private function get_download_display_details( $download, $wp_plugins ) {

		$download_info = wp_parse_args( (array) $download['info'], array(
			'thumbnail' => '',
			'title' => '',
			'textdomain' => '',
			'slug' => '',
			'excerpt' => '',
			'link' => '',
            'coming_soon' => false,
			'installer_title' => null, // May not be defined
			'installer_excerpt' => null, // May not be defined
		) );

		$wp_plugin = \GV\Utils::get( $wp_plugins, $download_info['textdomain'], false );

		$has_access = ! empty( $download['files'] );
		$spinner = true;
		$href = $plugin_path = '#';
		$status = $item_class = $button_title = $button_class = '';
		$base_price = $this->get_download_base_price( $download );
		$is_active = in_array( gravityview()->plugin->settings->get( 'license_key_response/license' ), array( 'active', 'valid' ), true );
		$galactic_only = in_array( \GV\Utils::get( $download, 'info/category/0/slug' ), array( 'plugins', 'views' ) );
		$required_license = $galactic_only ? __( 'All Access', 'gravityview' ) : __( 'Core + Extensions', 'gravityview' );

		// The license is not active - no matter what level, this should not work
		if( ! $is_active  && empty( $base_price ) ) {
			$spinner      = false;
			$status_label = '';
			$button_class = 'disabled disabled-license';
			$button_label = sprintf( __( 'Active %s License is Required.', 'gravityview' ), $required_license );
		}

		// No access with the current license level, and the download is available to purchase
		elseif ( ! $has_access && ! empty( $base_price ) ) {
			$spinner      = false;
			$status_label = '';
			$button_label = sprintf( __( 'Purchase Now for %s', 'gravityview' ), '$' . $base_price );
			$button_class = 'button-primary button-large';
			$href         = $download_info['link'];
			$item_class   = 'featured';
		}

		// No access with the current license level, and the download is not sold separately
		elseif ( ! $has_access && $is_active ) {
			$spinner      = false;
			$status_label = '';
			$button_label = sprintf( __( 'Upgrade to %s for Access', 'gravityview' ), $required_license );
			$button_class = 'button-primary button-large';
			$href         = 'https://gravityview.co/pricing/?utm_source=admin-installer&utm_medium=admin&utm_campaign=Admin%20Notice&utm_content=' . $required_license;
		}

        elseif ( ! empty( $download_info['coming_soon'] ) ) {
	        $spinner      = false;
	        $status       = 'notinstalled';
	        $status_label = __( 'Coming Soon', 'gravityview' );
	        $button_label = __( 'Learn More', 'gravityview' );
	        $button_class = 'button-primary button-large';
	        $href         = \GV\Utils::get( $download_info, 'link', 'https://gravityview.co/extensions/' );
        }

		// Access but the plugin is not installed
		elseif ( ! $wp_plugin ) {

			$href = add_query_arg(
				array(
					'action'   => 'install-plugin',
					'plugin'   => $download_info['slug'],
					'_wpnonce' => wp_create_nonce( 'install-plugin_' . $download_info['slug'] ),
				),
				self_admin_url( 'update.php' )
			);

			$status = 'notinstalled';
			$status_label = __( 'Not Installed', 'gravityview' );
			$button_label = __( 'Install', 'gravityview' );

		}

		// Access and the plugin is installed but not active
		elseif ( false === $wp_plugin['activated'] ) {
			$status = 'inactive';
			$status_label = __( 'Inactive', 'gravityview' );
			$button_label = __( 'Activate', 'gravityview' );
			$plugin_path = $wp_plugin['path'];

		}

		// Access and the plugin is installed and active
		else {

			$plugin_path = $wp_plugin['path'];
			$status = 'active';
			$status_label = __( 'Active', 'gravityview' );
			$button_label = __( 'Deactivate', 'gravityview' );

		}

		return compact( 'download_info','plugin_path', 'status', 'status_label', 'button_title', 'button_class', 'button_label', 'href', 'spinner', 'item_class', 'required_license', 'is_active' );
    }

	/**
     * Returns the base price for an extension
     *
	 * @param array $download
	 *
	 * @return float Base price for an extension. If not for sale separately, returns 0
	 */
	private function get_download_base_price( $download ) {

	    $base_price = \GV\Utils::get( $download, 'pricing/amount', 0 );
		$base_price = \GFCommon::to_number( $base_price );

		unset( $download['pricing']['amount'] );

		// Price options array, not single price
		if ( ! $base_price && ! empty( $download['pricing'] ) ) {
			$base_price = array_shift( $download['pricing'] );
		}

		return floatval( $base_price );
    }

	/**
	 * Handle AJAX request to activate extension
	 *
	 * @return void Exits with JSON response
	 */
	public function activate_download() {
		$data = \GV\Utils::_POST( 'data', array() );

		if ( empty( $data['path'] ) ) {
			return;
		}

		$result = activate_plugin( $data['path'] );

		if ( is_wp_error( $result ) || ! is_plugin_active( $data['path'] ) ) {
			wp_send_json_error( array(
                'error' => sprintf( __( 'Plugin activation failed: %s', 'gravityview' ), $result->get_error_message() )
            ) );
		}

		wp_send_json_success();
	}

	/**
	 * Handle AJAX request to deactivate extension
	 *
	 * @return void Send JSON response status and error message
	 */
	public function deactivate_download() {
		$data = \GV\Utils::_POST( 'data', array() );

		if ( empty( $data['path'] ) ) {
			return;
		}

		deactivate_plugins( $data['path'] );

		if( is_plugin_active( $data['path'] ) ) {
            wp_send_json_error( array(
                'error' => sprintf( __( 'Plugin deactivation failed.', 'gravityview' ) )
            ) );
        }

		wp_send_json_success();
	}

	/**
	 * Register and enqueue assets; localize script
	 *
	 * @return void
	 */
	public function maybe_enqueue_scripts_and_styles() {

		if ( ! gravityview()->request->is_admin( '', 'downloads' ) ) {
			return;
		}

		$script_debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_style( 'gravityview-admin-installer', GRAVITYVIEW_URL . 'assets/css/admin-installer.css', array(), \GV\Plugin::$version );

		wp_enqueue_script( 'gravityview-admin-installer', GRAVITYVIEW_URL . 'assets/js/admin-installer' . $script_debug . '.js', array( 'jquery' ), \GV\Plugin::$version, true );

		wp_localize_script( 'gravityview-admin-installer', 'gvAdminInstaller', array(
			'activateErrorLabel'    => __( 'Plugin activation failed.', 'gravityview' ),
			'deactivateErrorLabel'  => __( 'Plugin deactivation failed.', 'gravityview' ),
			'activeStatusLabel'     => __( 'Active', 'gravityview' ),
			'inactiveStatusLabel'   => __( 'Inactive', 'gravityview' ),
			'activateActionLabel'   => __( 'Activate', 'gravityview' ),
			'deactivateActionLabel' => __( 'Deactivate', 'gravityview' )
		) );
	}
}

new GravityView_Admin_Installer;
