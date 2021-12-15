<?php

namespace GV;

use GV\Shortcodes\gravityview;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

if ( ! class_exists( '\GFAddOn' ) ) {
	return;
}

/**
 * The Addon Settings class.
 *
 * Uses internal GFAddOn APIs.
 */
class Addon_Settings extends \GFAddOn {

	/**
	 * @var string Title of the plugin to be used on the settings page, form settings and plugins page. Example: 'Gravity Forms MailChimp Add-On'
	 */
	protected $_title = 'GravityView';

	/**
	 * @var string Short version of the plugin title to be used on menus and other places where a less verbose string is useful. Example: 'MailChimp'
	 */
	protected $_short_title = 'GravityView';

	/**
	 * @var string URL-friendly identifier used for form settings, add-on settings, text domain localization...
	 */
	protected $_slug = 'gravityview';

	/**
	 * @var string|array A string or an array of capabilities or roles that can uninstall the plugin
	 */
	protected $_capabilities_uninstall = 'gravityview_uninstall';

	/**
	 * @var string|array A string or an array of capabilities or roles that have access to the settings page
	 */
	protected $_capabilities_app_settings = 'gravityview_view_settings';

	/**
	 * @var string|array A string or an array of capabilities or roles that have access to the settings page
	 */
	protected $_capabilities_app_menu = 'gravityview_view_settings';

	/**
	 * @var string The hook suffix for the app menu
	 */
	public $app_hook_suffix = 'gravityview';

	/**
	 * @var \GV\License_Handler Process license validation
	 */
	private $License_Handler;

	/**
	 * @var bool Whether we have initialized already or not.
	 */
	private static $initialized = false;

	public function __construct() {

		$this->_version                  = Plugin::$version;
		$this->_min_gravityforms_version = Plugin::$min_gf_version;

		/**
		 * Hook everywhere, but only once.
		 */
		if ( ! self::$initialized ) {
			parent::__construct();
			self::$initialized = true;
		}
	}

	/**
	 * Run actions when initializing admin.
	 *
	 * Triggers the license key notice, etc.
	 *
	 * @return void
	 */
	public function init_admin() {

		$this->_load_license_handler();

		add_filter( 'admin_body_class', array( $this, 'body_class' ) );

		add_action( 'admin_head', array( $this, 'license_key_notice' ) );

		add_filter( 'gform_addon_app_settings_menu_gravityview', array( $this, 'modify_app_settings_menu_title' ) );

		add_filter( 'gform_settings_save_button', array( $this, 'modify_gform_settings_save_button' ), 10, 2 );

		/** @since 1.7.6 */
		add_action( 'network_admin_menu', array( $this, 'add_network_menu' ) );

		add_filter( 'gravityview_noconflict_styles', array( $this, 'register_no_conflict' ) );

		parent::init_admin();
	}

	/**
	 * Return tabs for GV Settings page
	 *
	 * @since XXX
	 *
	 * @return array
	 */
	public function get_app_settings_tabs() {

		$setting_tabs = parent::get_app_settings_tabs();

		foreach ( $setting_tabs as &$tab ) {
			if ( 'uninstall' !== $tab['name'] ) {
				continue;
			}

			// Do not display uninstall tab if user is lacking permissions/this is a multisite
			if ( ! ( $this->current_user_can_any( $this->_capabilities_uninstall ) && ( ! function_exists( 'is_multisite' ) || ! is_multisite() || is_super_admin() ) ) ) {
				$tab = null;
				continue;
			}

			// Add trash can icon to resemble the look and feel of the GF Settings page
			$tab['icon'] = 'dashicons-trash';
		}

		return array_filter( $setting_tabs );
	}

	/**
	 * Allow GF styles to load in no-conflict mode
	 *
	 * @param array $items Styles to exclude from no-conflict
	 *
	 * @return array
	 */
	public function register_no_conflict( $items ) {

		$items[] = 'gform_settings';
		$items[] = 'gv-admin-edd-license';

		return $items;
	}

	/**
	 * Adds a CSS class to the <body> of the admin page if running GF 2.5 or newer
	 *
	 * @param $css_class
	 *
	 * @return string
	 */
	public function body_class( $css_class ) {

		if ( ! gravityview()->request->is_admin( '', 'settings' ) ) {
			return $css_class;
		}

		if ( gravityview()->plugin->is_GF_25() ) {
			$css_class .= ' gf-2-5';
		}

		return $css_class;
	}

	/**
	 * Adds an "Uninstall" button next to the GF 2.5 Save Settings button
	 *
	 * @since 2.9.1
	 *
	 * @param string                               $html HTML of the save button.
	 * @param \Gravity_Forms\Gravity_Forms\Settings|null $framework Current instance of the Settings Framework. Or null if < 2.5.
	 */
	public function modify_gform_settings_save_button( $html, $framework = null ) {

		if ( ! gravityview()->request->is_admin( '', 'settings' ) ) {
			return $html;
		}

		if ( ! ( $this->current_user_can_any( $this->_capabilities_uninstall ) && ( ! function_exists( 'is_multisite' ) || ! is_multisite() || is_super_admin() ) ) ) {
			return $html;
		}

		if ( gravityview()->plugin->is_GF_25() ) {
			$html_class = 'button outline secondary alignright button-danger';
		} else {
			$html_class = 'button button-secondary button-large alignright button-danger';
		}

		$href = add_query_arg( array( 'post_type' => 'gravityview', 'page' => 'gravityview_settings', 'view' => 'uninstall' ), admin_url( 'edit.php' ) );

		$uninstall_button = '<a href="' . esc_url( $href ) . '" class="' . gravityview_sanitize_html_class( $html_class ). '">' . esc_html__( 'Uninstall GravityView', 'gravityview' ) . '</a>';

		$html .= $uninstall_button;

		return $html;
	}

	/**
	 * Roll our own "Hero" Save button with an Unsubscribe button attached
	 *
	 * @since 2.9.1
	 *
	 * @param array $field
	 * @param bool $echo
	 *
	 * @return string|null HTML of the button.
	 */
	public function settings_save( $field, $echo = true ) {

		$field['type']  = 'submit';
		$field['name']  = 'gform-settings-save';
		$field['class'] = 'button button-primary primary button-hero';
		$field['value'] = Utils::get( $field, 'value', __( 'Update Settings', 'gravityview' ) );

		$html = $this->as_html( $field, false );

		$html = $this->modify_gform_settings_save_button( $html );

		if ( $echo ) {
			echo $html;
		}

		return $html;
	}

	/**
	 * Change the settings page header title to "GravityView"
	 *
	 * @param $setting_tabs
	 *
	 * @return array
	 */
	public function modify_app_settings_menu_title( $setting_tabs ) {

		$setting_tabs[0]['label'] = __( 'GravityView Settings', 'gravityview' );
		$setting_tabs[0]['icon']  = 'dashicons-admin-settings';

		return $setting_tabs;
	}

	/**
	 * Load license handler in admin-ajax.php
	 *
	 * @return void
	 */
	public function init_ajax() {

		$this->_load_license_handler();
	}

	/**
	 * Make sure the license handler is available
	 *
	 * @return void
	 */
	private function _load_license_handler() {

		if ( ! empty( $this->License_Handler ) ) {
			return;
		}
		$this->License_Handler = License_Handler::get( $this );
	}

	/**
	 * Add global Settings page for Multisite
	 *
	 * @since 1.7.6
	 * @return void
	 */
	public function add_network_menu() {

		if ( ! gravityview()->plugin->is_network_activated() ) {
			return;
		}

		add_menu_page( __( 'Settings', 'gravityview' ), __( 'GravityView', 'gravityview' ), $this->_capabilities_app_settings, "{$this->_slug}_settings", array( $this, 'app_tab_page' ), 'none' );
	}

	/**
	 * Uninstall all traces of GravityView
	 *
	 * Note: method is public because parent method is public
	 *
	 * @return bool
	 */
	public function uninstall() {

		gravityview()->plugin->uninstall();

		/**
		 * Set the path so that Gravity Forms can de-activate GravityView
		 *
		 * @see  GFAddOn::uninstall_addon
		 * @uses deactivate_plugins()
		 */
		$this->_path = GRAVITYVIEW_FILE;

		return true;
	}

	/**
	 * Prevent uninstall tab from being shown by returning false for the uninstall capability check. Otherwise:
	 *
	 * @inheritDoc
	 *
	 * @hack
	 *
	 * @param array|string $caps
	 *
	 * @return bool
	 */
	public function current_user_can_any( $caps ) {

		if ( empty( $caps ) ) {
			$caps = array( 'gravityview_full_access' );
		}

		return \GVCommon::has_cap( $caps );
	}

	public function uninstall_warning_message() {

		$heading = esc_html__( 'If you delete then re-install GravityView, it will be like installing GravityView for the first time.', 'gravityview' );
		$message = esc_html__( 'Delete all Views, GravityView entry approval status, GravityView-generated entry notes (including approval and entry creator changes), and GravityView plugin settings.', 'gravityview' );

		return sprintf( '<h4>%s</h4><p>%s</p>', $heading, $message );
	}

	/**
	 * Get an array of reasons why the plugin might be uninstalled
	 *
	 * @since 1.17.5
	 *
	 * @return array Array of reasons with the label and followup questions for each uninstall reason
	 */
	private function get_uninstall_reasons() {

		$reasons = array(
				'will-continue'  => array(
						'label' => esc_html__( 'I am going to continue using GravityView', 'gravityview' ),
				),
				'no-longer-need' => array(
						'label' => esc_html__( 'I no longer need GravityView', 'gravityview' ),
				),
				'doesnt-work'    => array(
						'label' => esc_html__( 'The plugin doesn\'t work', 'gravityview' ),
				),
				'found-other'    => array(
						'label'    => esc_html__( 'I found a better plugin', 'gravityview' ),
						'followup' => esc_attr__( 'What plugin you are using, and why?', 'gravityview' ),
				),
				'other'          => array(
						'label' => esc_html__( 'Other', 'gravityview' ),
				),
		);

		shuffle( $reasons );

		return $reasons;
	}

	/**
	 * Display a feedback form when the plugin is uninstalled
	 *
	 * @since 1.17.5
	 *
	 * @return string HTML of the uninstallation form
	 */
	public function uninstall_form() {

		ob_start();

		$user = wp_get_current_user();
		?>
		<style>
			#gv-reason-details {
				min-height: 100px;
			}

			.number-scale label {
				border: 1px solid #cccccc;
				padding: .5em .75em;
				margin: .1em;
			}

			#gv-uninstall-thanks p {
				font-size: 1.2em;
			}

			.scale-description ul {
				margin-bottom: 0;
				padding-bottom: 0;
			}

			.scale-description p.description {
				margin-top: 0 !important;
				padding-top: 0 !important;
			}

			.gv-form-field-wrapper {
				margin-top: 30px;
			}
		</style>

		<?php
		if ( gravityview()->plugin->is_GF_25() ) {
			$uninstall_title = esc_html__( 'Uninstall GravityView', 'gravityview' );

			echo <<<HTML
<div class="gform-settings-panel">
    <header class="gform-settings-panel__header">
        <h4 class="gform-settings-panel__title">{$uninstall_title}</h4>
    </header>
    <div class="gform-settings-panel__content" style="padding: 0 1rem 1.25rem">

HTML;
		} else {
			echo '<div class="gv-uninstall-form-wrapper" style="font-size: 110%; padding: 15px 0;">';
		}
		?>
		<script>
			jQuery( function( $ ) {
				$( '#gv-uninstall-feedback' ).on( 'change', function( e ) {

					if ( ! $( e.target ).is( ':input' ) ) {
						return;
					}
					var $textarea = $( '.gv-followup' ).find( 'textarea' );
					var followup_text = $( e.target ).attr( 'data-followup' );
					if ( ! followup_text ) {
						followup_text = $textarea.attr( 'data-default' );
					}

					$textarea.attr( 'placeholder', followup_text );

				} ).on( 'submit', function( e ) {
					e.preventDefault();

					$.post( $( this ).attr( 'action' ), $( this ).serialize() )
							.done( function( data ) {
								if ( 'success' !== data.status ) {
									gv_feedback_append_error_message();
								} else {
									$( '#gv-uninstall-thanks' ).fadeIn();
								}
							} )
							.fail( function( data ) {
								gv_feedback_append_error_message();
							} )
							.always( function() {
								$( e.target ).remove();
							} );

					return false;
				} );

				function gv_feedback_append_error_message() {
					$( '#gv-uninstall-thanks' ).append( '<div class="notice error">' + <?php echo json_encode( esc_html( __( 'There was an error sharing your feedback. Sorry! Please email us at support@gravityview.co', 'gravityview' ) ) ) ?> +'</div>' );
				}
			} );
		</script>

		<form id="gv-uninstall-feedback" method="post" action="https://hooks.zapier.com/hooks/catch/28670/6haevn/">
			<h2><?php esc_html_e( 'Why did you uninstall GravityView?', 'gravityview' ); ?></h2>
			<ul>
				<?php
				$reasons = $this->get_uninstall_reasons();
				foreach ( $reasons as $reason ) {
					printf( '<li><label><input name="reason" type="radio" value="other" data-followup="%s"> %s</label></li>', Utils::get( $reason, 'followup' ), Utils::get( $reason, 'label' ) );
				}
				?>
			</ul>
			<div class="gv-followup widefat">
				<p><strong><label for="gv-reason-details"><?php esc_html_e( 'Comments', 'gravityview' ); ?></label></strong></p>
				<textarea id="gv-reason-details" name="reason_details" data-default="<?php esc_attr_e( 'Please share your thoughts about GravityView', 'gravityview' ) ?>" placeholder="<?php esc_attr_e( 'Please share your thoughts about GravityView', 'gravityview' ); ?>" class="large-text"></textarea>
			</div>
			<div class="scale-description">
				<p><strong><?php esc_html_e( 'How likely are you to recommend GravityView?', 'gravityview' ); ?></strong></p>
				<ul class="inline">
					<?php
					$i = 0;
					while ( $i < 11 ) {
						echo '<li class="inline number-scale"><label><input name="likely_to_refer" id="likely_to_refer_' . $i . '" value="' . $i . '" type="radio"> ' . $i . '</label></li>';
						$i ++;
					}
					?>
				</ul>
				<p class="description"><?php printf( esc_html_x( '%s ("Not at all likely") to %s ("Extremely likely")', 'A scale from 0 (bad) to 10 (good)', 'gravityview' ), '<label for="likely_to_refer_0"><code>0</code></label>', '<label for="likely_to_refer_10"><code>10</code></label>' ); ?></p>
			</div>

			<div class="gv-form-field-wrapper">
				<label><input type="checkbox" class="checkbox" name="follow_up_with_me" value="1" /> <?php esc_html_e( 'Please follow up with me about my feedback', 'gravityview' ); ?></label>
			</div>

			<div class="submit">
				<input type="hidden" name="siteurl" value="<?php echo esc_url( get_bloginfo( 'url' ) ); ?>" />
				<input type="hidden" name="email" value="<?php echo esc_attr( $user->user_email ); ?>" />
				<input type="hidden" name="display_name" value="<?php echo esc_attr( $user->display_name ); ?>" />
				<input type="submit" value="<?php esc_html_e( 'Send Us Your Feedback', 'gravityview' ); ?>" class="button button-primary primary button-hero" />
			</div>
		</form>

		<div id="gv-uninstall-thanks" class="<?php echo ( gravityview()->plugin->is_GF_25() ) ? 'notice-large' : 'notice notice-large notice-updated below-h2'; ?>" style="display:none;">
			<h3 class="notice-title"><?php esc_html_e( 'Thank you for using GravityView!', 'gravityview' ); ?></h3>
			<p><?php echo gravityview_get_floaty(); ?>
				<?php echo make_clickable( esc_html__( 'Your feedback helps us improve GravityView. If you have any questions or comments, email us: support@gravityview.co', 'gravityview' ) ); ?>
			</p>
			<div class="wp-clearfix"></div>
		</div>
		</div>
		<?php
		if ( gravityview()->plugin->is_GF_25() ) {
			echo '</div>';
		}

		$form = ob_get_clean();

		return $form;
	}

	public function app_settings_tab() {

		parent::app_settings_tab();

		if ( $this->maybe_uninstall() ) {
			echo $this->uninstall_form();
		}
	}

	/**
	 * The Settings title
	 *
	 * @return string
	 */
	public function app_settings_title() {

		return null;
	}

	/**
	 * Prevent displaying of any icon
	 *
	 * @return string
	 */
	public function app_settings_icon() {

		return '&nbsp;';
	}

	/**
	 * Retrieve a setting.
	 *
	 * @param string $setting_name The setting key.
	 *
	 * @return mixed The setting or null
	 * @deprecated Use \GV\Addon_Settings::get
	 */
	public function get_app_setting( $setting_name ) {

		return $this->get( $setting_name );
	}

	/**
	 * Retrieve a setting.
	 *
	 * @param string $key     The setting key.
	 * @param string $default A default if not found.
	 *
	 * @return mixed The setting value.
	 */
	public function get( $key, $default = null ) {

		/**
		 * Backward compatibility with Redux
		 */
		if ( $key === 'license' ) {
			return array(
					'license'  => $this->get( 'license_key' ),
					'status'   => $this->get( 'license_key_status' ),
					'response' => $this->get( 'license_key_response' ),
			);
		}

		if ( 'license_key' === $key && defined( 'GRAVITYVIEW_LICENSE_KEY' ) ) {
			return GRAVITYVIEW_LICENSE_KEY;
		}

		return Utils::get( $this->all(), $key, $default );
	}

	/**
	 * Get the setting for GravityView by name
	 *
	 * @param string $key Option key to fetch
	 *
	 * @return mixed
	 * @deprecated Use gravityview()->plugin->settings->get()
	 */
	static public function getSetting( $key ) {

		if ( gravityview()->plugin->settings instanceof Addon_Settings ) {
			return gravityview()->plugin->settings->get( $key );
		}
	}

	/**
	 * Get all settings.
	 *
	 * @return array The settings.
	 * @deprecated Use \GV\Addon_Settings::all() or \GV\Addon_Settings::get()
	 *
	 */
	public function get_app_settings() {

		return $this->all();
	}

	/**
	 * Get all the settings.
	 *
	 * @return array The settings.
	 */
	public function all() {

		$option_name = 'gravityformsaddon_' . $this->_slug . '_app_settings';

		if ( $this->has_site_settings() ) {
			$defaults     = $this->defaults();
			$option_value = get_option( $option_name, array() );
		} else {
			$defaults     = get_blog_option( get_main_site_id(), $option_name );
			$option_value = get_blog_option( get_main_site_id(), $option_name );
		}

		return wp_parse_args( $option_value, $defaults );
	}

	/**
	 * Default settings.
	 *
	 * @return array The defaults.
	 * @deprecated Use \GV\Addon_Settings::defaults()
	 *
	 */
	public function get_default_settings() {

		return $this->defaults();
	}

	/**
	 * Default settings.
	 *
	 * @return array The defaults.
	 */
	private function defaults() {

		$defaults = array(
			// Set the default license in wp-config.php
			'license_key'          => defined( 'GRAVITYVIEW_LICENSE_KEY' ) ? GRAVITYVIEW_LICENSE_KEY : '',
			'license_key_response' => '',
			'license_key_status'   => '',
			'support-email'        => get_bloginfo( 'admin_email' ),
			'no-conflict-mode'     => '1',
			'support_port'         => '1',
			'flexbox_search'       => '1',
			'lightbox'             => 'fancybox',
			'rest_api'             => '0',
			'beta'                 => '0',
			'powered_by'           => '0',
		);

		/**
		 * @filter `gravityview/settings/default` Filter default global settings.
		 * @param  [in,out] array The defaults.
		 */
		return apply_filters( 'gravityview/settings/defaults', $defaults );
	}

	/***
	 * Renders the save button for settings pages
	 *
	 * @param array $field - Field array containing the configuration options of this field
	 * @param bool  $echo  = true - true to echo the output to the screen, false to simply return the contents as a string
	 *
	 * @return string The HTML
	 */
	public function as_html( $field, $echo = true ) {

		$field['type'] = ( isset( $field['type'] ) && in_array( $field['type'], array( 'submit', 'reset', 'button' ) ) ) ? $field['type'] : 'submit';

		$attributes    = $this->get_field_attributes( $field );
		$default_value = Utils::get( $field, 'value', Utils::get( $field, 'default_value' ) );
		$value         = $this->get( $field['name'], $default_value );

		$attributes['class'] = isset( $attributes['class'] ) ? esc_attr( $attributes['class'] ) : 'button-primary primary gfbutton';
		$name                = ( $field['name'] === 'gform-settings-save' ) ? $field['name'] : '_gaddon_setting_' . $field['name'];

		if ( empty( $value ) ) {
			$value = __( 'Update Settings', 'gravityview' );
		}

		$attributes = $this->get_field_attributes( $field );

		$html = '<input
                    type="' . $field['type'] . '"
                    name="' . esc_attr( $name ) . '"
                    value="' . $value . '" ' .
				implode( ' ', $attributes ) .
				' />';

		if ( $echo ) {
			echo $html;
		}

		return $html;
	}

	/**
	 * @deprecated Use \GV\Addon_Settings::as_html
	 */
	public function settings_submit( $field, $echo = true ) {

		gravityview()->log->warning( '\GV\Addon_Settings::settings_submit has been deprecated for \GV\Addon_Settings::as_html' );

		return $this->as_html( $field, $echo );
	}

	/**
	 * Check whether GravityView is being saved
	 *
	 * The generic is_save_postback() is true for all addons
	 *
	 * @since 2.0.8
	 *
	 * @return bool
	 */
	public function is_save_postback() {

		return isset( $_POST['gform-settings-save'] ) && isset( $_POST['_gravityview_save_settings_nonce'] );
	}

	/**
	 * Display a notice if the plugin is inactive.
	 *
	 * @return void
	 */
	public function license_key_notice() {

		if ( 'uninstall' === rgget( 'view' ) ) {
			return; // Do not display license notice on the uninstall page in GF 2.5
		}

		if ( $this->is_save_postback() ) {
			$settings       = $this->get_posted_settings();
			$license_key    = defined( 'GRAVITYVIEW_LICENSE_KEY' ) ? GRAVITYVIEW_LICENSE_KEY : \GV\Utils::get( $settings, 'license_key' );
			$license_status = \GV\Utils::get( $settings, 'license_key_status', 'inactive' );
		} else {
			$license_status = $this->get( 'license_key_status', 'inactive' );
			$license_key    = $this->get( 'license_key' );
		}

		if ( empty( $license_key ) ) {
			$license_id = 'license';
			$license_status = '';
		} else {
			$license_id = $license_key;
		}

		$message = esc_html__( 'Your GravityView license %s. This means you&rsquo;re missing out on updates and support! %sActivate your license%s or %sget a license here%s.', 'gravityview' );

		/** @internal Do not use! Will change without notice (pun slightly intended). */
		$message = apply_filters( 'gravityview/settings/license-key-notice', $message );

		/**
		 * I wanted to remove the period from after the buttons in the string,
		 * but didn't want to mess up the translation strings for the translators.
		 */
		$message             = mb_substr( $message, 0, mb_strlen( $message ) - 1 );
		$title               = __( 'Inactive License', 'gravityview' );
		$status              = '';
		$update_below        = false;
		$primary_button_link = admin_url( 'edit.php?post_type=gravityview&amp;page=gravityview_settings' );

		switch ( $license_status ) {
			/** @since 1.17 */
			case 'expired':
				$title   = __( 'Expired License', 'gravityview' );
				$status  = __( 'has expired', 'gravityview' );
				$message = $this->get_license_handler()->strings( 'expired', $this->get( 'license_key_response' ) );
				break;
			case 'invalid':
				$title  = __( 'Invalid License', 'gravityview' );
				$status = __( 'is invalid', 'gravityview' );
				break;
			case 'deactivated':
				$status       = __( 'is inactive', 'gravityview' );
				$update_below = __( 'Activate your license key below.', 'gravityview' );
				break;
			/** @noinspection PhpMissingBreakStatementInspection */
			case '':
				$license_status = 'site_inactive';
			// break intentionally left blank
			case 'inactive':
			case 'site_inactive':
				$status       = __( 'has not been activated', 'gravityview' );
				$update_below = __( 'Activate your license key below.', 'gravityview' );
				break;
		}
		$url = 'https://gravityview.co/pricing/?utm_source=admin_notice&utm_medium=admin&utm_content=' . $license_status . '&utm_campaign=Admin%20Notice';

		// Show a different notice on settings page for inactive licenses (hide the buttons)
		if ( $update_below && gravityview()->request->is_admin( '', 'settings' ) ) {
			$message = sprintf( $message, $status, '<div class="hidden">', '', '', '</div><a href="#" onclick="jQuery(\'#license_key\').focus(); return false;">' . $update_below . '</a>' );
		} else {
			$message = sprintf( $message, $status, "\n\n" . '<a href="' . esc_url( $primary_button_link ) . '" class="button button-primary primary">', '</a>', '<a href="' . esc_url( $url ) . '" class="button button-secondary">', '</a>' );
		}

		if ( empty( $status ) ) {
			return;
		}

		\GravityView_Admin_Notices::add_notice( array(
				'message' => $message,
				'class'   => 'notice notice-warning gv-license-warning',
				'title'   => $title,
				'cap'     => 'gravityview_edit_settings',
				'dismiss' => sha1( $license_status . '_' . $license_id . '_' . date( 'z' ) ), // Show every day, instead of every 8 weeks (which is the default)
		) );
	}

	/**
	 * Allow public access to the GV\License_Handler class
	 *
	 * @since 1.7.4
	 *
	 * @return \GV\License_Handler
	 */
	public function get_license_handler() {

		return $this->License_Handler;
	}

	/**
	 * Add tooltip script to app settings page. Not enqueued by Gravity Forms for some reason.
	 *
	 * @since 1.21.5
	 *
	 * @see   GFAddOn::scripts()
	 *
	 * @return array Array of scripts
	 */
	public function scripts() {

		$scripts = parent::scripts();

		$scripts[] = array(
				'handle'  => 'gform_tooltip_init',
				'enqueue' => array(
						array(
								'admin_page' => array( 'app_settings' ),
						),
				),
		);

		return $scripts;
	}

	/**
	 * Register styles in the app admin page
	 *
	 * @return array
	 */
	public function styles() {

		$styles = parent::styles();

		$deps = array(
			'gform_admin',
			'gaddon_form_settings_css',
			'gform_font_awesome',
		);

		// This file was removed from 2.5
		if( ! gravityview()->plugin->is_GF_25() ) {
			$deps[] = 'gform_tooltip';
		}

		$styles[] = array(
			'handle'  => 'gravityview_settings',
			'src'     => plugins_url( 'assets/css/admin-settings.css', GRAVITYVIEW_FILE ),
			'version' => Plugin::$version,
			'deps'    => $deps,
			'enqueue' => array(
				array(
					'admin_page' => array(
						'app_settings',
						'plugin_settings',
					),
				),
			),
		);

		return $styles;
	}

	/**
	 * Does the current site have its own settings?
	 *
	 * - If not multisite, returns true.
	 * - If multisite and the plugin is network activated, returns true; we need to register the submenu page for the Network Admin settings to work.
	 * - If multisite and not network admin, return false.
	 *
	 * @since 2.8.2
	 *
	 * @return bool
	 */
	private function has_site_settings() {

		return ( ! is_multisite() ) || is_main_site() || ( ! gravityview()->plugin->is_network_activated() ) || ( is_network_admin() && gravityview()->plugin->is_network_activated() );
	}

	/**
	 * Add Settings link to GravityView menu
	 *
	 * @return void
	 */
	public function create_app_menu() {

		/**
		 * Override whether to show the Settings menu on a per-blog basis.
		 *
		 * @since 1.7.6
		 * @param bool $hide_if_network_activated Default: true
		 */
		$show_submenu = apply_filters( 'gravityview/show-settings-menu', $this->has_site_settings() );

		if ( ! $show_submenu ) {
			return;
		}

		add_submenu_page( 'edit.php?post_type=gravityview', __( 'Settings', 'gravityview' ), __( 'Settings', 'gravityview' ), $this->_capabilities_app_settings, $this->_slug . '_settings', array( $this, 'app_tab_page' ) );
	}

	/**
	 * Gets the required indicator
	 * Gets the markup of the required indicator symbol to highlight fields that are required
	 *
	 * @param $field - The field meta.
	 *
	 * @return string - Returns markup of the required indicator symbol
	 */
	public function get_required_indicator( $field ) {

		return '<span class="required" title="' . esc_attr__( 'Required', 'gravityview' ) . '">*</span>';
	}

	/**
	 * Specify the settings fields to be rendered on the plugin settings page
	 *
	 * @return array
	 */
	public function app_settings_fields() {

		$default_settings = $this->defaults();

		$disabled_attribute = \GVCommon::has_cap( 'gravityview_edit_settings' ) ? false : 'disabled';

		$affiliate_link = 'https://gravityview.co/account/affiliate/?utm_source=in-plugin&utm_medium=setting&utm_content=Register as an affiliate';

		$fields = array(
				array(
						'name'          => 'support-email',
						'type'          => 'text',
						'validate'      => 'email',
						'default_value' => $default_settings['support-email'],
						'label'         => __( 'Support Email', 'gravityview' ),
						'description'   => __( 'In order to provide responses to your support requests, please provide your email address.', 'gravityview' ),
						'class'         => 'code regular-text',
				),
				/**
				 * @since 1.15 Added Support Port support
				 */
				array(
						'name'          => 'support_port',
						'type'          => 'radio',
						'label'         => __( 'Show Support Port?', 'gravityview' ),
						'default_value' => $default_settings['support_port'],
						'horizontal'    => 1,
						'choices'       => array(
								array(
										'label' => _x( 'Show', 'Setting: Show or Hide', 'gravityview' ),
										'value' => '1',
								),
								array(
										'label' => _x( 'Hide', 'Setting: Show or Hide', 'gravityview' ),
										'value' => '0',
								),
						),
						'tooltip' => '<p>' . esc_html__( 'The Support Port provides quick access to how-to articles and tutorials. For administrators, it also makes it easy to contact support.', 'gravityview' ) . '<img src="' . esc_url_raw( plugins_url( 'assets/images/beacon.png', GRAVITYVIEW_FILE ) ) . '" alt="' . esc_attr__( 'The Support Port looks like this.', 'gravityview' ) . '" class="aligncenter" style="display: block; max-width:100%; margin:1em auto;" /></p>',
						'description' => __( 'Show the Support Port on GravityView pages?', 'gravityview' ),
				),
				array(
						'name'          => 'no-conflict-mode',
						'type'          => 'radio',
						'label'         => __( 'No-Conflict Mode', 'gravityview' ),
						'default_value' => $default_settings['no-conflict-mode'],
						'horizontal'    => 1,
						'choices'       => array(
								array(
										'label' => _x( 'On', 'Setting: On or off', 'gravityview' ),
										'value' => '1',
								),
								array(
										'label' => _x( 'Off', 'Setting: On or off', 'gravityview' ),
										'value' => '0',
								),
						),
						'description'   => __( 'Set this to ON to prevent extraneous scripts and styles from being printed on GravityView admin pages, reducing conflicts with other plugins and themes.', 'gravityview' ) . ' ' . __( 'If your Edit View tabs are ugly, enable this setting.', 'gravityview' ),
				),
				/**
				 * @since 2.0 Added REST API
				 */
				gravityview()->plugin->supports( Plugin::FEATURE_REST ) ?
						array(
								'name'          => 'rest_api',
								'type'          => 'radio',
								'label'         => __( 'REST API', 'gravityview' ),
								'default_value' => $default_settings['rest_api'],
								'horizontal'    => 1,
								'choices'       => array(
										array(
												'label' => _x( 'Enable', 'Setting: Enable or Disable', 'gravityview' ),
												'value' => '1',
										),
										array(
												'label' => _x( 'Disable', 'Setting: Enable or Disable', 'gravityview' ),
												'value' => '0',
										),
								),
								'description'   => __( 'Enable View and Entry access via the REST API? Regular per-View restrictions apply (private, password protected, etc.).', 'gravityview' ),
								'tooltip'       => '<p>' . esc_html__( 'If you are unsure, choose the Disable setting.', 'gravityview' ) . '</p>',
						) : array(),
				array(
					'name' => 'powered_by',
					'type' => 'checkbox',
					'label' => __( 'Display "Powered By" Link', 'gravityview' ),
					'default_value' => $default_settings['powered_by'],
					'choices' => array(
						array(
							'label' => esc_html__( 'Display a "Powered by GravityView" link', 'gravityview' ),
							'value' => '1',
							'name'  => 'powered_by',
						),
					),
					'description'   => __( 'When enabled, a "Powered by GravityView" link will be displayed below Views. Help us spread the word!', 'gravityview' ),
				),
				array(
					'name' => 'affiliate_id',
					'type' => 'text',
					'input_type' => 'number',
					'default_value' => null,
					'label' => __( 'Affiliate ID', 'gravityview' ),
					'description' => sprintf( __( 'Earn money when people clicking your links become GravityView customers. <a href="%s" rel="external">Register as an affiliate</a>!', 'gravityview' ), esc_url( $affiliate_link ) ),
					'class' => 'code',
					'placeholder' => '123',
					'data-requires' => 'powered_by',
				),
				array(
						'name'          => 'beta',
						'type'          => 'checkbox',
						'label'         => __( 'Become a Beta Tester', 'gravityview' ),
						'default_value' => $default_settings['beta'],
						'horizontal'    => 1,
						'choices'       => array(
								array(
										'label' => esc_html__( 'Show me beta versions if they are available.', 'gravityview' ),
										'value' => '1',
										'name'  => 'beta',
								),
						),
						'description'   => __( 'You will have early access to the latest GravityView features and improvements. There may be bugs! If you encounter an issue, help make GravityView better by reporting it!', 'gravityview' ),
				),
		);

		$fields = array_filter( $fields, 'count' );

		/**
		 * @filter     `gravityview_settings_fields` Filter the settings fields.
		 * @param array $fields The fields to filter.
		 * @deprecated Use `gravityview/settings/fields`.
		 */
		$fields = apply_filters( 'gravityview_settings_fields', $fields );

		/**
		 * @filter `gravityview/settings/fields` Filter the settings fields.
		 * @param array $fields The fields to filter.
		 */
		$fields = apply_filters( 'gravityview/settings/fields', $fields );

		/**
		 * Redux backward compatibility
		 *
		 * @since 1.7.4
		 */
		foreach ( $fields as &$field ) {
			$field['name']          = isset( $field['name'] ) ? $field['name'] : Utils::get( $field, 'id' );
			$field['label']         = isset( $field['label'] ) ? $field['label'] : Utils::get( $field, 'title' );
			$field['default_value'] = isset( $field['default_value'] ) ? $field['default_value'] : Utils::get( $field, 'default' );
			$field['description']   = isset( $field['description'] ) ? $field['description'] : Utils::get( $field, 'subtitle' );

			if ( $disabled_attribute ) {
				$field['disabled'] = $disabled_attribute;
			}

			if ( empty( $field['disabled'] ) ) {
				unset( $field['disabled'] );
			}
		}

		$license_fields = array(
			array(
					'name' => 'license_key',
					'required' => ! defined( 'GRAVITYVIEW_LICENSE_KEY' ) || ! GRAVITYVIEW_LICENSE_KEY,
					'label' => __( 'License Key', 'gravityview' ),
					'description' => __( 'Enter the license key that was sent to you on purchase. This enables plugin updates &amp; support.', 'gravityview' ),
					'type' => 'edd_license',
					'data-pending-text' => __( 'Verifying license&hellip;', 'gravityview' ),
					'default_value' => $default_settings['license_key'],
					'class' => ( '' == $this->get( 'license_key' ) ) ? 'activate code regular-text edd-license-key' : 'deactivate code regular-text edd-license-key',
			),
			array(
					'name' => 'license_key_response',
					'default_value' => $default_settings['license_key_response'],
					'type' => 'hidden',
			),
			array(
					'name' => 'license_key_status',
					'default_value' => $default_settings['license_key_status'],
					'type' => 'hidden',
			),
		);

		if ( defined( 'GRAVITYVIEW_LICENSE_KEY' ) && GRAVITYVIEW_LICENSE_KEY ) {
			$license_fields[0] = array_merge( $license_fields[0], array(
				'disabled' => true,
				'title'    => __( 'The license key is defined by your site\'s configuration file.', 'gravityview' ),
			) );
		}

		$sections = array();
		$version_info = '<span class="gv-version-info" title="' . sprintf( __( 'You are running GravityView version %s', 'gravityview' ), Plugin::$version ) . '">Version ' . esc_html( Plugin::$version ) . '</span>';

		if ( \gravityview()->plugin->is_GF_25() ) {

			$sections[] = array(
					'title'       => __( 'GravityView License', 'gravityview' ),
					'class'       => 'gform-settings-panel--full gv-settings-panel--license',
					'description' => $version_info,
					'fields'      => $license_fields,
			);

		} else {

			$fields = array_merge( $license_fields, $fields );

			array_unshift( $fields, array(
					'name'  => 'gv_header',
					'value' => $version_info,
					'type'  => 'html',
			) );
		}

		$sections[] = array(
			'title' => ( gravityview()->plugin->is_GF_25() ? __( 'GravityView Settings', 'gravityview' ) : null ),
			'class' => 'gform-settings-panel--full gv-settings-panel--core',
			'fields'      => $fields,
		);

		/**
		 * @filter `gravityview/settings/extension/sections` Modify the GravityView settings page
		 * Extensions can tap in here to insert their own section and settings.
		 * <code>
		 *   $sections[] = array(
		 *      'title' => __( 'GravityView My Extension Settings', 'gravityview' ),
		 *      'fields' => $settings,
		 *   );
		 * </code>
		 * @param array $extension_settings Empty array, ready for extension settings!
		 */
		$extension_sections = apply_filters( 'gravityview/settings/extension/sections', array() );

		// If there are extensions, add a section for them
		if ( ! empty( $extension_sections ) ) {

			if ( $disabled_attribute ) {
				foreach ( $extension_sections as &$section ) {
					foreach ( $section['fields'] as &$field ) {
						$field['disabled'] = $disabled_attribute;
					}
				}
			}

			$sections = array_merge( $sections, $extension_sections );
		}

		return $sections;
	}

	/**
	 * Updates app settings with the provided settings
	 *
	 * Same as the GFAddon, except it returns the value from update_option()
	 *
	 * @param array $settings - App settings to be saved
	 *
	 * @return boolean False if value was not updated and true if value was updated.
	 * @deprecated Use \GV\Addon_Settings::set or \GV\Addon_Settings::update
	 *
	 */
	public function update_app_settings( $settings ) {

		return $this->update( $settings );
	}

	/**
	 * Sets a subset of settings.
	 *
	 * @param array|string An array of settings to update, or string (key) and $value to update one setting.
	 * @param mixed $value A value if $settings is string (key). Default: null.
	 */
	public function set( $settings, $value = null ) {

		if ( is_string( $settings ) ) {
			$settings = array( $settings => $value );
		}
		$settings = wp_parse_args( $settings, $this->all() );

		return update_option( 'gravityformsaddon_' . $this->_slug . '_app_settings', $settings );
	}

	/**
	 * Updates settings.
	 *
	 * @param array $settings The settings array.
	 *
	 * @return boolean False if value was not updated and true if value was updated.
	 */
	public function update( $settings ) {

		return update_option( 'gravityformsaddon_' . $this->_slug . '_app_settings', $settings );
	}

	/**
	 * Register the settings field for the EDD License field type
	 *
	 * @param array $field
	 * @param bool  $echo Whether to echo the
	 *
	 * @return string
	 */
	public function settings_edd_license( $field, $echo = true ) {

		if ( defined( 'GRAVITYVIEW_LICENSE_KEY' ) && GRAVITYVIEW_LICENSE_KEY ) {
			$field['input_type'] = 'password';
		}

		$text = $this->settings_text( $field, false );

		$activation = $this->License_Handler->settings_edd_license_activation( $field, false );

		$return = $text . $activation;

		$return .= $this->get_license_handler()->license_details( \GV\Addon_Settings::get( 'license_key_response' ) );

		if ( $echo ) {
			echo $return;
		}

		return $return;
	}

	/**
	 * Allow pure HTML settings row
	 *
	 * @since 2.0.6
	 *
	 * @param array $field
	 * @param bool  $echo Whether to echo the
	 *
	 * @return string
	 */
	protected function settings_html( $field, $echo = true ) {

		$return = \GV\Utils::get( $field, 'value', '' );

		if ( $echo ) {
			echo $return;
		}

		return $return;
	}

	/**
	 * No <th> needed for pure HTML settings row
	 *
	 * @since 2.0.6
	 *
	 * @param array $field
	 *
	 * @return void
	 */
	public function single_setting_row_html( $field ) {

		?>

		<tr id="gaddon-setting-row-<?php echo esc_attr( $field['name'] ); ?>">
			<td colspan="2">
				<?php $this->single_setting( $field ); ?>
			</td>
		</tr>

		<?php
	}

	/**
	 * Keep GravityView styling for `$field['description']`, even though Gravity Forms added support for it
	 *
	 * Converts `$field['description']` to `$field['gv_description']`
	 * Converts `$field['subtitle']` to `$field['description']`
	 *
	 * @since 1.21.5.2
	 *
	 * @see   http://share.gravityview.co/P28uGp/2OIRKxog for image that shows subtitle vs description
	 *
	 * @see   \GV\Addon_Settings::single_setting_label Converts `gv_description` back to `description`
	 * @param array $field
	 *
	 * @return void
	 */
	public function single_setting_row( $field ) {

		$field['gv_description'] = Utils::get( $field, 'description' );
		$field['description']    = Utils::get( $field, 'subtitle' );
		parent::single_setting_row( $field );
	}

	/**
	 * The same as the parent, except added support for field descriptions
	 *
	 * @inheritDoc
	 * @param $field array
	 */
	public function single_setting_label( $field ) {

		parent::single_setting_label( $field );
		if ( $description = Utils::get( $field, 'gv_description' ) ) {
			echo '<span class="description">' . $description . '</span>';
		}
	}

	/**
	 * Check for the `gravityview_edit_settings` capability before saving plugin settings.
	 * Gravity Forms says you're able to edit if you're able to view settings. GravityView allows two different permissions.
	 *
	 * @since 1.15
	 * @return void
	 */
	public function maybe_save_app_settings() {

		if ( $this->is_save_postback() ) {
			if ( ! \GVCommon::has_cap( 'gravityview_edit_settings' ) ) {
				$_POST = array(); // If you don't reset the $_POST array, it *looks* like the settings were changed, but they weren't
				\GFCommon::add_error_message( __( 'You don\'t have the ability to edit plugin settings.', 'gravityview' ) );

				return;
			}
		}
		parent::maybe_save_app_settings();
	}

	/**
	 * When the settings are saved, make sure the license key matches the previously activated key
	 *
	 * @return array settings from parent::get_posted_settings(), with `license_key_response` and `license_key_status` potentially unset
	 */
	public function get_posted_settings() {

		$posted_settings = parent::get_posted_settings();

		$local_key = Utils::get( $posted_settings, 'license_key' );

		if ( ! $local_key && defined( 'GRAVITYVIEW_LICENSE_KEY' ) ) {
			$local_key = GRAVITYVIEW_LICENSE_KEY;
		}

		$response_key = Utils::get( $posted_settings, 'license_key_response/license_key' );

		static $added_message = false;

		// If the posted key doesn't match the activated/deactivated key (set using the Activate License button, AJAX response),
		// then we assume it's changed. If it's changed, unset the status and the previous response.
		if ( ! $added_message && ( $local_key !== $response_key ) ) {

			unset( $posted_settings['license_key_response'] );
			unset( $posted_settings['license_key_status'] );

			\GFCommon::add_error_message( __( 'The license key you entered has been saved, but not activated. Please activate the license.', 'gravityview' ) );

			$added_message = true;
		}

		return $posted_settings;
	}
}
