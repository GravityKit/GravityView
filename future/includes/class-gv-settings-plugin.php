<?php

namespace GV;

use GravityKitFoundation;
use GravityKit\GravityView\Foundation\Settings\Framework as SettingsFramework;
use GravityKit\GravityView\Foundation\Core as FoundationCore;
use GravityKit\GravityView\Foundation\Helpers\Arr;

/**
 * Ex-GF Addon Settings class that's been stripped of GF functionality.
 * Serves as a wrapper for Foundation Settings while also providing old getter/setter methods for backward compatibility.
 */
class Plugin_Settings {
	const SETTINGS_PLUGIN_ID = 'gravityview';

	/**
	 * Foundation Settings framework instance.
	 *
	 * @since 2.16
	 *
	 * @var SettingsFramework|GravityKitFoundation::settings
	 */
	private $_foundation_settings;

	/**
	 * Cached settings.
	 *
	 * @since 2.16.2
	 *
	 * @var array
	 */
	private $_plugin_settings = [];

	public function __construct() {
		// GravityKitFoundation may not yet be available when this class is instantiated, so let's temporarily use the Settings framework from Foundation that's included with GravityView and then possibly replace it with the latest version.
		$this->_foundation_settings = SettingsFramework::get_instance();

		add_action( 'gk/foundation/initialized', function () {
			$this->_foundation_settings = GravityKitFoundation::settings();
		} );

		add_filter( 'gk/foundation/settings/data/plugins', [ $this, 'add_settings' ] );
	}

	/**
	 * Returns a setting.
	 *
	 * @param string $setting_name The setting key.
	 *
	 * @return mixed The setting or null
	 * @deprecated Use \GV\Plugin_Settings::get()
	 */
	public function get_app_setting( $setting_name ) {
		return $this->get( $setting_name );
	}

	/**
	 * Returns setting by its name.
	 *
	 * @param string $key     The setting key.
	 * @param string $default (optional) A default if not found.
	 *
	 * @return mixed The setting value.
	 */
	public function get( $key, $default = null ) {
		return Arr::get( $this->all(), $key, $default );
	}

	/**
	 * Returns setting by its name.
	 *
	 * @param string $key Option key to fetch
	 *
	 * @return mixed
	 * @deprecated Use gravityview()->plugin->settings->get()
	 */
	static public function getSetting( $key ) {
		if ( gravityview()->plugin->settings instanceof Plugin_Settings ) {
			return gravityview()->plugin->settings->get( $key );
		}
	}

	/**
	 * Returns all settings.
	 *
	 * @return array The settings.
	 * @deprecated Use \GV\Plugin_Settings::all() or \GV\Plugin_Settings::get()
	 */
	public function get_app_settings() {
		return $this->all();
	}

	/**
	 * Returns all settings.
	 *
	 * @return array The settings.
	 */
	public function all() {
		if ( ! empty( $this->_plugin_settings ) ) {
			return $this->_plugin_settings;
		}

		$this->_plugin_settings = $this->_foundation_settings->get_plugin_settings( self::SETTINGS_PLUGIN_ID );

		// Migrate legacy settings
		if ( empty( $this->_plugin_settings ) ) {
			$this->_plugin_settings = $this->migrate_legacy_settings();
		}

		$this->_plugin_settings = wp_parse_args( $this->_plugin_settings, $this->defaults() );

		return $this->_plugin_settings;
	}

	/**
	 * Migrates GravityView <2.16 settings to the new settings framework.
	 * Some of those settings are now part of the GravityKit general settings, and some are part of the GravityView plugin settings.
	 *
	 * @return array The settings.
	 */
	public function migrate_legacy_settings() {
		$option_name = 'gravityformsaddon_' . self::SETTINGS_PLUGIN_ID . '_app_settings';

		// Legacy check (@see https://github.com/gravityview/GravityView/blob/3719151f3752ebca56b5ec70bd4064effcb7094a/future/includes/class-gv-settings-addon.php#L939)
		$site_has_settings = ( ! is_multisite() ) || is_main_site() || ( ! gravityview()->plugin->is_network_activated() ) || ( is_network_admin() && gravityview()->plugin->is_network_activated() );

		if ( $site_has_settings ) {
			$legacy_settings = get_option( $option_name, array() );
		} else {
			$legacy_settings = get_blog_option( get_main_site_id(), $option_name );
		}

		if ( empty( $legacy_settings ) ) {
			return [];
		}

		// Migrate legacy GravityView settings that are still part of GravityView.
		$plugin_settings = [
			'rest_api' => (int) Arr::get( $legacy_settings, 'rest_api' ),
		];

		$this->_foundation_settings->save_plugin_settings( self::SETTINGS_PLUGIN_ID, $plugin_settings );

		// Migrate legacy GravityView settings that are now part of the GravityKit general settings.
		$gk_settings_id = class_exists( 'GravityKitFoundation' ) ? GravityKitFoundation::ID : FoundationCore::ID;

		// If there are no settings configured, this would typically return an array of default settings.
		// However, because GV\Plugin_Settings is used before Foundation initializes and configures default settings, this will return an empty array indicating that migration should proceed.
		$gk_settings = $this->_foundation_settings->get_plugin_settings( $gk_settings_id );

		if ( empty( $gk_settings ) ) {
			$gk_settings = [
				'support_email'    => Arr::get( $legacy_settings, 'support-email' ),
				'support_port'     => Arr::get( $legacy_settings, 'support_port' ),
				'powered_by'       => (int) Arr::get( $legacy_settings, 'powered_by' ),
				'affiliate_id'     => Arr::get( $legacy_settings, 'affiliate_id' ),
				'beta'             => (int) Arr::get( $legacy_settings, 'beta' ),
				'support_email'    => Arr::get( $legacy_settings, 'support-email' ),
				'support_port'     => (int) Arr::get( $legacy_settings, 'support_port' ),
				'no_conflict_mode' => (int) Arr::get( $legacy_settings, 'no-conflict-mode' ),
			];

			$this->_foundation_settings->save_plugin_settings( $gk_settings_id, $gk_settings );
		}

		return $plugin_settings;
	}

	/**
	 * Returns a GravityKit general setting.
	 *
	 * @param string     $setting Setting value.
	 * @param mixed|null $default (optional) A default value if setting is not set. Defaults to null.
	 *
	 * @return mixed
	 */
	public function get_gravitykit_setting( $setting, $default = null ) {
		$gk_settings_id = class_exists( 'GravityKitFoundation' ) ? GravityKitFoundation::ID : FoundationCore::ID;

		return $this->_foundation_settings->get_plugin_setting( $gk_settings_id, $setting, $default );
	}

	/**
	 * Returns default settings.
	 *
	 * @return array The defaults.
	 */
	public function defaults() {
		$defaults = [
			'rest_api' => 0,
			'public_entry_moderation' => 0,
		];

		/**
		 * @filter `gravityview/settings/default` Filter default global settings.
		 *
		 * @param  [in,out] array The defaults.
		 */
		return apply_filters( 'gravityview/settings/defaults', $defaults );
	}

	/**
	 * Adds GravityView settings to Foundation.
	 *
	 * @since 2.16
	 *
	 * @param array $plugins_data Plugins data
	 *
	 * @return array $plugins_data
	 */
	public function add_settings( $plugins_data ) {
		/**
		 * Override whether to show GravityView settings.
		 *
		 * @since 1.7.6
		 *
		 * @param bool $show_settings Default: true
		 */
		$show_settings = apply_filters( 'gravityview/show-settings-menu', true );

		if ( ! $show_settings ) {
			return $plugins_data;
		}

		$default_settings = $this->defaults();

		$settings = [
			'id'       => self::SETTINGS_PLUGIN_ID,
			'title'    => 'GravityView',
			'defaults' => $default_settings,
			'icon'     => 'data:image/svg+xml,%3Csvg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg"%3E%3Crect width="80" height="80" rx="8" fill="%23FF1B67"/%3E%3Cg clip-path="url(%23clip0_16_27)"%3E%3Cpath fill-rule="evenodd" clip-rule="evenodd" d="M58.4105 54.6666H52.8824V56.4999C52.8824 59.5375 50.3902 61.9999 47.3544 61.9999H32.6134C29.5375 61.9999 27.0853 59.5375 27.0853 56.4999V29H21.5572C20.5144 29 19.7148 29.8207 19.7148 30.8333V49.1666C19.7148 50.1792 20.5144 51 21.5572 51H22.4788C22.9629 51 23.3997 51.4104 23.3997 51.9167V53.75C23.3997 54.2562 22.9629 54.6666 22.4788 54.6666H21.5572C18.4786 54.6666 16.0292 52.2042 16.0292 49.1666V30.8333C16.0292 27.7957 18.4786 25.3333 21.5572 25.3333H27.0853V23.5C27.0853 20.4624 29.5375 18 32.6134 18H47.3544C50.3902 18 52.8824 20.4624 52.8824 23.5V51H58.4105C59.4132 51 60.2529 50.1792 60.2529 49.1666V30.8333C60.2529 29.8207 59.4132 29 58.4105 29H57.4889C56.9647 29 56.5673 28.5896 56.5673 28.0833V26.2499C56.5673 25.7437 56.9647 25.3333 57.4889 25.3333H58.4105C61.449 25.3333 63.9378 27.7957 63.9378 30.8333V49.1666C63.9378 52.2042 61.449 54.6666 58.4105 54.6666ZM49.1968 23.5C49.1968 22.4874 48.3544 21.6667 47.3544 21.6667H32.6134C31.5733 21.6667 30.7709 22.4874 30.7709 23.5V56.4999C30.7709 57.5125 31.5733 58.3333 32.6134 58.3333H47.3544C48.3544 58.3333 49.1968 57.5125 49.1968 56.4999V23.5Z" fill="white"/%3E%3C/g%3E%3Cdefs%3E%3CclipPath id="clip0_16_27"%3E%3Crect width="48" height="44" fill="white" transform="translate(16 18)"/%3E%3C/clipPath%3E%3C/defs%3E%3C/svg%3E%0A',
			'sections' => [
				[
					'title'    => esc_html__( 'General', 'gk-gravityview' ),
					'settings' => [
						[
							'id'          => 'rest_api',
							'type'        => 'checkbox',
							'title'       => esc_html__( 'REST API', 'gk-gravityview' ),
							'description' => esc_html__( 'Enable View and Entry access via the REST API? Regular per-View restrictions apply (private, password protected, etc.).', 'gk-gravityview' ) . ' ' . esc_html__( 'If you are unsure, disable this setting.', 'gk-gravityview' ),
							'value'       => $this->get( 'rest_api', $default_settings['rest_api'] ),
						],
					],
				],
				[
					'title'    => esc_html__( 'Permissions', 'gk-gravityview' ),
					'settings' => [
						[
							'id'          => 'public_entry_moderation',
							'type'        => 'checkbox',
							'title'       => esc_html__( 'Enable Public Entry Moderation', 'gk-gravityview' ),
							'description'   => strtr(
								// translators: Do not translate the words inside the {} curly brackets; they are replaced.
								__( 'If enabled, adding {public} to {link}entry moderation merge tags{/link} will allow logged-out users to approve or reject entries. If disabled, all entry moderation actions require the user to be logged-in and have the ability to edit the entry.', 'gk-gravityview' ),
								array(
									'{public}' => '<code style="font-size: .9em">:public</code>',
									'{link}' => '<a href="https://docs.gravitykit.com/article/904-entry-moderation-merge-tags" target="_blank" rel="noopener noreferrer">',
									'{/link}' => '<span class="screen-reader-text"> ' . esc_html__( '(This link opens in a new window.)', 'gk-gravityview' ) . '</span></a>',
								)
							),
							'value'       => $this->get( 'public_entry_moderation', $default_settings['public_entry_moderation'] ),
						],
					],
				],
			],
		];

		return array_merge( (array) $plugins_data, [ self::SETTINGS_PLUGIN_ID => $settings ] );
	}

	/**
	 * Updates app settings with the provided settings.
	 *
	 * Same as the GFAddon, except it returns the value from update_option()
	 *
	 * @param array $settings - App settings to be saved
	 *
	 * @return boolean False if value was not updated and true if value was updated.
	 * @deprecated Use \GV\Settings::set() or \GV\Settings::update()
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
			$settings = [ $settings => $value ];
		}

		$settings = wp_parse_args( $settings, $this->all() );

		return $this->_foundation_settings->save_plugin_settings( self::SETTINGS_PLUGIN_ID, $settings );
	}

	/**
	 * Updates settings.
	 *
	 * @param array $settings The settings array.
	 *
	 * @return boolean False if value was not updated and true if value was updated.
	 */
	public function update( $settings ) {
		$result = $this->_foundation_settings->save_plugin_settings( self::SETTINGS_PLUGIN_ID, $settings );

		if ( ! $result ) {
			return false;
		}

		$this->_plugin_settings = $this->_foundation_settings->get_plugin_settings( self::SETTINGS_PLUGIN_ID );

		return true;
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
				$_POST = []; // If you don't reset the $_POST array, it *looks* like the settings were changed, but they weren't
				\GFCommon::add_error_message( __( 'You don\'t have the ability to edit plugin settings.', 'gk-gravityview' ) );

				return;
			}
		}
	}

	/*
	 * @TODO Reimplement elsewhere.
	 */
	private function _uninstall_warning_message() {
		$heading = esc_html__( 'If you delete then re-install GravityView, it will be like installing GravityView for the first time.', 'gk-gravityview' );
		$message = esc_html__( 'Delete all Views, GravityView entry approval status, GravityView-generated entry notes (including approval and entry creator changes), and GravityView plugin settings.', 'gk-gravityview' );

		return sprintf( '<h4>%s</h4><p>%s</p>', $heading, $message );
	}

	/**
	 * @TODO Reimplement elsewhere.
	 */
	private function _get_uninstall_reasons() {
		$reasons = [
			'will-continue'  => [
				'label' => esc_html__( 'I am going to continue using GravityView', 'gk-gravityview' ),
			],
			'no-longer-need' => [
				'label' => esc_html__( 'I no longer need GravityView', 'gk-gravityview' ),
			],
			'doesnt-work'    => [
				'label' => esc_html__( 'The plugin doesn\'t work', 'gk-gravityview' ),
			],
			'found-other'    => [
				'label'    => esc_html__( 'I found a better plugin', 'gk-gravityview' ),
				'followup' => esc_attr__( 'What plugin you are using, and why?', 'gk-gravityview' ),
			],
			'other'          => [
				'label' => esc_html__( 'Other', 'gk-gravityview' ),
			],
		];

		shuffle( $reasons );

		return $reasons;
	}

	/**
	 * @TODO Reimplement elsewhere.
	 */
	private function _uninstall_form() {
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
			$uninstall_title = esc_html__( 'Uninstall GravityView', 'gk-gravityview' );

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
			jQuery( function ( $ ) {
				$( '#gv-uninstall-feedback' ).on( 'change', function ( e ) {

					if ( !$( e.target ).is( ':input' ) ) {
						return;
					}
					var $textarea = $( '.gv-followup' ).find( 'textarea' );
					var followup_text = $( e.target ).attr( 'data-followup' );
					if ( !followup_text ) {
						followup_text = $textarea.attr( 'data-default' );
					}

					$textarea.attr( 'placeholder', followup_text );

				} ).on( 'submit', function ( e ) {
					e.preventDefault();

					$.post( $( this ).attr( 'action' ), $( this ).serialize() )
						.done( function ( data ) {
							if ( 'success' !== data.status ) {
								gv_feedback_append_error_message();
							} else {
								$( '#gv-uninstall-thanks' ).fadeIn();
							}
						} )
						.fail( function ( data ) {
							gv_feedback_append_error_message();
						} )
						.always( function () {
							$( e.target ).remove();
						} );

					return false;
				} );

				function gv_feedback_append_error_message() {
					$( '#gv-uninstall-thanks' ).append( '<div class="notice error">' + <?php echo json_encode( esc_html( __( 'There was an error sharing your feedback. Sorry! Please email us at support@gravityview.co', 'gk-gravityview' ) ) ) ?> +'</div>' );
				}
			} );
		</script>

		<form id="gv-uninstall-feedback" method="post" action="https://hooks.zapier.com/hooks/catch/28670/6haevn/">
			<h2><?php esc_html_e( 'Why did you uninstall GravityView?', 'gk-gravityview' ); ?></h2>
			<ul>
				<?php
				$reasons = $this->get_uninstall_reasons();
				foreach ( $reasons as $reason ) {
					printf( '<li><label><input name="reason" type="radio" value="other" data-followup="%s"> %s</label></li>', Arr::get( $reason, 'followup' ), Arr::get( $reason, 'label' ) );
				}
				?>
			</ul>
			<div class="gv-followup widefat">
				<p><strong><label for="gv-reason-details"><?php esc_html_e( 'Comments', 'gk-gravityview' ); ?></label></strong></p>
				<textarea id="gv-reason-details" name="reason_details" data-default="<?php esc_attr_e( 'Please share your thoughts about GravityView', 'gk-gravityview' ) ?>" placeholder="<?php esc_attr_e( 'Please share your thoughts about GravityView', 'gk-gravityview' ); ?>" class="large-text"></textarea>
			</div>
			<div class="scale-description">
				<p><strong><?php esc_html_e( 'How likely are you to recommend GravityView?', 'gk-gravityview' ); ?></strong></p>
				<ul class="inline">
					<?php
					$i = 0;
					while ( $i < 11 ) {
						echo '<li class="inline number-scale"><label><input name="likely_to_refer" id="likely_to_refer_' . $i . '" value="' . $i . '" type="radio"> ' . $i . '</label></li>';
						$i++;
					}
					?>
				</ul>
				<p class="description"><?php printf( esc_html_x( '%s ("Not at all likely") to %s ("Extremely likely")', 'A scale from 0 (bad) to 10 (good)', 'gk-gravityview' ), '<label for="likely_to_refer_0"><code>0</code></label>', '<label for="likely_to_refer_10"><code>10</code></label>' ); ?></p>
			</div>

			<div class="gv-form-field-wrapper">
				<label><input type="checkbox" class="checkbox" name="follow_up_with_me" value="1" /> <?php esc_html_e( 'Please follow up with me about my feedback', 'gk-gravityview' ); ?></label>
			</div>

			<div class="submit">
				<input type="hidden" name="siteurl" value="<?php echo esc_url( get_bloginfo( 'url' ) ); ?>" />
				<input type="hidden" name="email" value="<?php echo esc_attr( $user->user_email ); ?>" />
				<input type="hidden" name="display_name" value="<?php echo esc_attr( $user->display_name ); ?>" />
				<input type="submit" value="<?php esc_html_e( 'Send Us Your Feedback', 'gk-gravityview' ); ?>" class="button button-primary primary button-hero" />
			</div>
		</form>

		<div id="gv-uninstall-thanks" class="<?php echo ( gravityview()->plugin->is_GF_25() ) ? 'notice-large' : 'notice notice-large notice-updated below-h2'; ?>" style="display:none;">
			<h3 class="notice-title"><?php esc_html_e( 'Thank you for using GravityView!', 'gk-gravityview' ); ?></h3>
			<p><?php echo gravityview_get_floaty(); ?>
				<?php echo make_clickable( esc_html__( 'Your feedback helps us improve GravityView. If you have any questions or comments, email us: support@gravityview.co', 'gk-gravityview' ) ); ?>
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
}
