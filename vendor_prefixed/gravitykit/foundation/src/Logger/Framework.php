<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\Logger;

use GravityKit\GravityView\Foundation\Core as FoundationCore;
use GravityKit\GravityView\Foundation\Helpers\Core as CoreHelpers;
use GravityKit\GravityView\Foundation\Helpers\Arr;
use GravityKit\GravityView\Monolog\Handler\ChromePHPHandler;
use GravityKit\GravityView\Monolog\Handler\StreamHandler;
use GravityKit\GravityView\Monolog\Logger as MonologLogger;
use GravityKit\GravityView\Foundation\Settings\Framework as SettingsFramework;
use GravityKit\GravityView\Foundation\Encryption\Encryption;
use Exception;
use GravityKit\GravityView\Psr\Log\LoggerInterface;
use GravityKit\GravityView\Psr\Log\LoggerTrait;

/**
 * Logging framework for GravityKit.
 */
class Framework implements LoggerInterface {
    use LoggerTrait;

	const DEFAULT_LOGGER_ID = 'gravitykit';

	const DEFAULT_LOGGER_TITLE = 'GravityKit';

	/**
	 * @since 1.0.0
	 *
	 * @var array Instances of the logger class instantiated by various plugins.
	 */
	private static $_instances = [];

	/**
	 * Settings framework instance.
	 *
	 * @since 1.0.0
	 *
	 * @var SettingsFramework Settings framework instance.
	 */
	private $_settings;

	/**
	 * Monolog class instance.
	 *
	 * @since 1.0.0
	 *
	 * @var MonologLogger
	 */
	private $_logger;

	/**
	 * @since 1.0.0
	 *
	 * @var string Unique logger ID.
	 */
	private $_logger_id;

	/**
	 * @since 1.0.0
	 *
	 * @var string Logger title.
	 */
	private $_logger_title;

	/**
	 * @since 1.0.0
	 *
	 * @var string Location where logs are stored relative to WP_CONTENT_DIR.
	 */
	private $_log_path = 'logs';

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $logger_id    Unique name that's prefixed to each log entry.
	 * @param string $logger_title Logger title (used in the admin UI).
	 *
	 * @return void
	 */
	private function __construct( $logger_id, $logger_title ) {
		global $initialized;

		if ( ! $initialized ) {
			add_filter( 'gk/foundation/settings/' . FoundationCore::ID . '/save/before', [ $this, 'save_settings' ] );
			add_filter( 'gk/foundation/settings', [ $this, 'get_settings' ] );
		}

		$this->_settings = SettingsFramework::get_instance();

		$this->_logger_id    = $logger_id;
		$this->_logger_title = $logger_title;

		/**
		 * @filter `gk/foundation/logger/log-path` Changes path where logs are stored.
		 *
		 * @since  1.0.0
		 *
		 * @param string $log_path Location where logs are stored relative to WP_CONTENT_DIR. Default: WP_CONTENT_DIR . '/logs'.
		 */
		$this->_log_path = apply_filters( 'gk/foundation/logger/log-path', $this->_log_path );

		$logger_handler = $this->get_logger_handler();

		if ( $logger_handler ) {
			$this->_logger = new MonologLogger( $logger_id );

			$this->_logger->pushHandler( $logger_handler );
		}

		$initialized = true;
	}

	/**
	 * Returns class instance.
	 *
	 * @since 1.0.0
	 *
	 * @param string $logger_id    (optional) Unique logger identifier that's prefixed to each log entry or used with some handlers.. Default: gravitykit.
	 * @param string $logger_title (optional) Logger title (used in the admin UI). Default: GravityKit.
	 *
	 * @return Framework
	 */
	public static function get_instance( $logger_id = '', $logger_title = '' ) {
		$logger_id    = $logger_id ?: self::DEFAULT_LOGGER_ID;
		$logger_title = $logger_title ?: self::DEFAULT_LOGGER_TITLE;

		if ( empty( self::$_instances[ $logger_id ] ) ) {
			self::$_instances[ $logger_id ] = new self( $logger_id, $logger_title );
		}

		return self::$_instances[ $logger_id ];
	}

	/**
	 * Returns handler that will process log messages.
	 *
	 * @since 1.0.0
	 *
	 * @return void|ChromePHPHandler|GravityFormsHandler|StreamHandler|QueryMonitorHandler
	 */
	public function get_logger_handler() {
		$settings = $this->_settings->get_plugin_settings( FoundationCore::ID );

		if ( empty( $settings['logger'] ) ) {
			if ( class_exists( 'GFLogging' ) && get_option( 'gform_enable_logging' ) ) {
				return new GravityFormsHandler( $this->_logger_id, $this->_logger_title );
			}

			return;
		}

		switch ( $settings['logger_type'] ) {
			case 'file':
				try {
					return new StreamHandler( $this->get_log_file() );
				} catch ( Exception $e ) {
					error_log( 'Could not initialize file logging for GravityKit:' . $e->getMessage() );

					return;
				}
			case 'query_monitor':
				return new QueryMonitorHandler();
			case 'chrome_logger':
				return new ChromePHPHandler();
		}
	}

	/**
	 * Returns UI settings for the logger.
	 *
	 * @since 1.0.0
	 * @since 1.0.3 Added $gk_settings parameter.
	 *
	 * @param array $gk_settings GravityKit general settings object.
	 *
	 * @return array[]
	 */
	public function get_settings( $gk_settings ) {
		$saved_gk_settings_values = $this->_settings->get_plugin_settings( FoundationCore::ID );

		// If multisite and not the main site, get default settings from the main site.
		// This allows site admins to configure the default settings for all subsites.
		// If no settings are found on the main site, default settings (set below) will be used.
		if ( ! is_main_site() && empty( $saved_gk_settings_values ) ) {
			$saved_gk_settings_values = $this->_settings->get_plugin_settings( FoundationCore::ID, get_main_site_id() );
		}

		$default_logger_settings = [
			'logger'      => 0,
			'logger_type' => 'file',
		];

		$log_file    = $this->get_log_file();
		$logger      = Arr::get( $saved_gk_settings_values, 'logger', $default_logger_settings['logger'] );
		$logger_type = Arr::get( $saved_gk_settings_values, 'logger_type', $default_logger_settings['logger_type'] );

		add_filter( 'gk/foundation/inline-styles', function ( $styles ) {
			$css      = <<<CSS
.bg-yellow-50 {
    --tw-bg-opacity: 1;
    background-color: rgba(255, 251, 235, var(--tw-bg-opacity))
}

.bg-blue-50 {
    --tw-bg-opacity: 1;
    background-color: rgba(239, 246, 255, var(--tw-bg-opacity))
}

.text-yellow-400 {
    --tw-text-opacity: 1;
    color: rgba(251, 191, 36, var(--tw-text-opacity))
}

.text-yellow-700 {
    --tw-text-opacity: 1;
    color: rgba(180, 83, 9, var(--tw-text-opacity))
}

.text-blue-400 {
    --tw-text-opacity: 1;
    color: rgba(96, 165, 250, var(--tw-text-opacity))
}

.text-blue-700 {
    --tw-text-opacity: 1;
    color: rgba(29, 78, 216, var(--tw-text-opacity))
}

.hover\:text-yellow-600:hover {
    --tw-text-opacity: 1;
    color: rgba(217, 119, 6, var(--tw-text-opacity))
}

.hover\:text-blue-600:hover {
    --tw-text-opacity: 1;
    color: rgba(37, 99, 235, var(--tw-text-opacity))
}
CSS;
			$styles[] = [
				'style' => $css,
			];

			return $styles;
		} );

		$query_monitor_notice = strtr(
			esc_html_x( 'You must install [link]Query Monitor[/link] WordPress plugin to use this option.', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
			[
				'[link]'  => '<a href="https://wordpress.org/plugins/query-monitor/" class="font-medium underline text-yellow-700 hover:text-yellow-600">',
				'[/link]' => '</a>',
			]
		);

		$chrome_logger_tip = strtr(
			esc_html_x( 'You must install [link]Chrome Logger[/link] browser extension to use this option.', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
			[
				'[link]'  => '<a href="https://craig.is/writing/chrome-logger" class="font-medium underline text-yellow-700 hover:text-yellow-600">',
				'[/link]' => '</a>',
			]
		);

		$gravity_forms_logger_tip = strtr(
			esc_html_x( 'Logging is currently handled by [link]Gravity Forms[/link].', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
			[
				'[link]'  => '<a href="' . admin_url( 'admin.php?page=gf_settings&subview=gravityformslogging' ) . '" class="font-medium underline text-yellow-700 hover:text-yellow-600">',
				'[/link]' => '</a>',
			]
		);

		$info_icon = <<<HTML
<svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
	<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
</svg>
HTML;

		$checkmark_icon = <<<HTML
<svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
	<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
</svg>
HTML;

		$notice_template = <<<HTML
<div class="bg-%color%-50 p-4">
	<div class="flex">
		<div class="flex-shrink-0">
			%icon%
		</div>
	    <div class="ml-3">
			<p class="text-sm text-%color%-700">
			%notice%
			</p>
		</div>
	</div>
</div>
HTML;

		$logger_settings = [];

		$_update_gk_settings = function () use ( &$logger_settings, &$gk_settings, $default_logger_settings ) {
			// Add logging settings under the Technical section in GravityKit settings.
			Arr::set( $gk_settings, 'gk_foundation.sections.2.settings', array_merge(
				Arr::get( $gk_settings, 'gk_foundation.sections.2.settings' ),
				$logger_settings
			) );

			// Update defaults.
			Arr::set( $gk_settings, 'gk_foundation.defaults', array_merge(
				Arr::get( $gk_settings, 'gk_foundation.defaults' ),
				$default_logger_settings
			) );
		};

		if ( ! $logger && class_exists( 'GFLogging' ) && get_option( 'gform_enable_logging' ) ) {
			$logger_settings[] = [
				'id'       => 'gravity_forms_logger_tip',
				'html'     => strtr( $notice_template, [
					'%color%'  => 'yellow',
					'%icon%'   => $info_icon,
					'%notice%' => $gravity_forms_logger_tip
				] ),
				'requires' => [
					'id'       => 'logger',
					'operator' => '!=',
					'value'    => '1',
				],
			];
		}

		$logger_settings = array_merge( $logger_settings, [
			[
				'id'    => 'logger',
				'type'  => 'checkbox',
				'title' => esc_html__( 'Enable Logging', 'gk-gravityview' ),
				'value' => $logger,
			],
			[
				'id'          => 'logger_type',
				'type'        => 'select',
				'title'       => esc_html__( 'Log Type', 'gk-gravityview' ),
				'description' => esc_html__( 'Where to store log output', 'gk-gravityview' ),
				'value'       => $logger_type,
				'choices'     => [
					[
						'title' => esc_html__( 'File', 'gk-gravityview' ),
						'value' => 'file',
					],
					[
						'title' => esc_html__( 'Query Monitor', 'gk-gravityview' ),
						'value' => 'query_monitor',
					],
					[
						'title' => esc_html__( 'Chrome Logger', 'gk-gravityview' ),
						'value' => 'chrome_logger',
					],
				],
				'requires'    => [
					'id'       => 'logger',
					'operator' => '=',
					'value'    => '1',
				],
			],
			[
				'id'              => 'chrome_logger_tip',
				'html'            => strtr( $notice_template, [
					'%color%'  => 'yellow',
					'%icon%'   => $info_icon,
					'%notice%' => $chrome_logger_tip
				] ),
				'requires'        => [
					'id'       => 'logger_type',
					'operator' => '=',
					'value'    => 'chrome_logger',
				],
				'excludeFromSave' => true,
			],
		] );

		if ( ! class_exists( 'QueryMonitor' ) ) {
			$logger_settings[] = [
				'id'              => 'query_monitor_notice',
				'html'            => strtr( $notice_template, [
					'%color%'  => 'yellow',
					'%icon%'   => $info_icon,
					'%notice%' => $query_monitor_notice
				] ),
				'requires'        => [
					'id'       => 'logger_type',
					'operator' => '=',
					'value'    => 'query_monitor',
				],
				'excludeFromSave' => true,
			];
		}

		if ( ! $this->_logger || 'file' !== $logger_type || ! file_exists( $log_file ) ) {
			$_update_gk_settings();

			return $gk_settings;
		}

		$download_link = sprintf( '%s/%s/%s',
			content_url(),
			$this->_log_path,
			basename( $log_file )
		);

		$download_notice = strtr(
			esc_html_x( 'Download [link]log file[/link] ([size] / [date_modified]).', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
			[
				'[link]'          => '<a href="' . $download_link . '" class="font-medium underline text-blue-700 hover:text-blue-600">',
				'[/link]'         => '</a>',
				'[size]'          => size_format( filesize( $log_file ), 2 ),
				'[date_modified]' => date_i18n( 'Y-m-d @ H:i:s', filemtime( $log_file ) ),
			]
		);

		$logger_settings[] = [
			'id'       => 'log_file',
			'html'     => strtr( $notice_template, [
				'%color%'  => 'blue',
				'%icon%'   => $checkmark_icon,
				'%notice%' => $download_notice
			] ),
			'requires' => [
				'id'       => 'logger_type',
				'operator' => '=',
				'value'    => 'file',
			],
		];

		$_update_gk_settings();

		return $gk_settings;
	}

	/**
	 * Deletes log files when UI savings are saved and the logger type is no longer "file".
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function save_settings( $new_settings ) {
		$current_settings = $this->_settings->get_plugin_settings( FoundationCore::ID );

		if ( ! $this->_logger || empty( $current_settings['logger'] ) || 'file' !== $current_settings['logger_type'] ) {
			return $new_settings;
		}

		if ( ! empty( $new_settings['logger'] ) && 'file' === $new_settings['logger_type'] ) {
			return $new_settings;
		}

		$this->_logger->getHandlers()[0]->close();

		wp_delete_file( $this->get_log_file() );

		return $new_settings;
	}

	/**
	 * Returns log file name with path.
	 *
	 * @return string
	 */
	public function get_log_file() {
		$hash = substr( Encryption::get_instance()->hash( FoundationCore::ID ), 0, 10 );

		return sprintf( '%s/%s/gravitykit-%s.log', WP_CONTENT_DIR, $this->_log_path, $hash );
	}

	/**
	 * Magic method to access Monolog's logger class methods.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name      Package/class name.
	 * @param array  $arguments Optional and not used.
	 *
	 * @return mixed|void
	 */
	public function __call( $name, array $arguments = [] ) {
		if ( ! $this->_logger instanceof MonologLogger ) {
			return;
		}

		/**
		 * @filter `gk/foundation/logger/allow-heartbeat-requests` Allows logging of WP heartbeat requests.
		 *
		 * @since  1.0.0
		 *
		 * @param bool $log_heartbeat Default: false.
		 */
		$log_heartbeat = apply_filters( 'gk/foundation/logger/allow-heartbeat-requests', false );

		if ( isset( $_REQUEST['action'] ) && 'heartbeat' == $_REQUEST['action'] && ! $log_heartbeat ) {
			return;
		}

		if ( CoreHelpers::is_callable_class_method( [ $this->_logger, $name ] ) ) {
			return call_user_func_array( [ $this->_logger, $name ], $arguments );
		}
	}

    /**
     * @inheritDoc
     * @since $ver$
     */
    public function log($level, $message, array $context = array())
    {
        $this->__call($level, [$message, $context]);
    }
}
