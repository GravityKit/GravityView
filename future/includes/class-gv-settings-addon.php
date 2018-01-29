<?php
namespace GV;

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
	protected  $_short_title = 'GravityView';

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
		$this->_version = Plugin::$version;
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
	 * Triggers the license key notice, et.c
	 *
	 * @return void
	 */
	function init_admin() {
		$this->_load_license_handler();
		$this->license_key_notice();

		add_filter( 'gform_addon_app_settings_menu_gravityview', array( $this, 'modify_app_settings_menu_title' ) );

		/** @since 1.7.6 */
		add_action( 'network_admin_menu', array( $this, 'add_network_menu' ) );

		parent::init_admin();
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
		$this->License_Handler = License_Handler::get();
	}

	/**
	 * Add global Settings page for Multisite
	 * @since 1.7.6
	 * @return void
	 */
	public function add_network_menu() {
		if ( gravityview()->plugin->is_network_activated() ) {
			add_menu_page( __( 'Settings', 'gravityview' ), __( 'GravityView', 'gravityview' ), $this->_capabilities_app_settings, "{$this->_slug}_settings", array( $this, 'app_tab_page' ), 'none' );
		}
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
         * @see GFAddOn::uninstall_addon
         * @uses deactivate_plugins()
         */
		$this->_path = GRAVITYVIEW_FILE;

		return true;
	}

	/**
	 * Prevent uninstall tab from being shown by returning false for the uninstall capability check. Otherwise:
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
			'will-continue' => array(
                'label' => esc_html__( 'I am going to continue using GravityView', 'gravityview' ),
            ),
			'no-longer-need' => array(
                'label' => esc_html__( 'I no longer need GravityView', 'gravityview' ),
            ),
			'doesnt-work' => array(
                'label' => esc_html__( 'The plugin doesn\'t work', 'gravityview' ),
            ),
			'found-other' => array(
                'label' => esc_html__( 'I found a better plugin', 'gravityview' ),
                'followup' => esc_attr__( 'What plugin you are using, and why?', 'gravityview' ),
            ),
			'other' => array(
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
            margin-top: 0!important;
            padding-top: 0!important;
        }
        .gv-form-field-wrapper {
            margin-top: 30px;
        }
    </style>

    <div class="gv-uninstall-form-wrapper" style="font-size: 110%; padding: 15px 0;">
        <script>
            jQuery( function( $ ) {
                $( '#gv-uninstall-feedback' ).on( 'change', function( e ) {

                    if ( ! $( e.target ).is( ':input' ) ) {
                        return;
                    }
                    var $textarea = $( '.gv-followup' ).find( 'textarea' );
                    var followup_text = $( e.target ).attr( 'data-followup' );
                    if( ! followup_text ) {
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
                        })
                        .fail( function( data ) {
                            gv_feedback_append_error_message();
                        } )
                        .always( function() {
                            $( e.target ).remove();
                        } );

                    return false;
                });

                function gv_feedback_append_error_message() {
                    $( '#gv-uninstall-thanks' ).append( '<div class="notice error">' + <?php echo json_encode( esc_html( __( 'There was an error sharing your feedback. Sorry! Please email us at support@gravityview.co', 'gravityview' ) ) ) ?> + '</div>' );
                }
            });
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
                <textarea id="gv-reason-details" name="reason_details" data-default="<?php esc_attr_e('Please share your thoughts about GravityView', 'gravityview') ?>" placeholder="<?php esc_attr_e('Please share your thoughts about GravityView', 'gravityview'); ?>" class="large-text"></textarea>
            </div>
            <div class="scale-description">
                <p><strong><?php esc_html_e( 'How likely are you to recommend GravityView?', 'gravityview' ); ?></strong></p>
                <ul class="inline">
					<?php
					$i = 0;
					while( $i < 11 ) {
						echo '<li class="inline number-scale"><label><input name="likely_to_refer" id="likely_to_refer_'.$i.'" value="'.$i.'" type="radio"> '.$i.'</label></li>';
						$i++;
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
                <input type="submit" value="<?php esc_html_e( 'Send Us Your Feedback', 'gravityview' ); ?>" class="button button-primary button-hero" />
            </div>
        </form>

        <div id="gv-uninstall-thanks" class="notice notice-large notice-updated below-h2" style="display:none;">
            <h3 class="notice-title"><?php esc_html_e( 'Thank you for using GravityView!', 'gravityview' ); ?></h3>
            <p><?php echo gravityview_get_floaty(); ?>
				<?php echo make_clickable( esc_html__( 'Your feedback helps us improve GravityView. If you have any questions or comments, email us: support@gravityview.co', 'gravityview' ) ); ?>
            </p>
            <div class="wp-clearfix"></div>
        </div>
    </div>
		<?php
		$form = ob_get_clean();

		return $form;
	}

	public function app_settings_uninstall_tab() {
		if ( $this->maybe_uninstall() ) {
			return parent::app_settings_uninstall_tab();
		}

		if ( ! ( $this->current_user_can_any( $this->_capabilities_uninstall ) && ( ! function_exists( 'is_multisite' ) || ! is_multisite() || is_super_admin() ) ) ) {
			return;
		}

		?>
		<script>
			jQuery( document ).on( 'click', 'a[rel="gv-uninstall-wrapper"]', function( e ) {
				e.preventDefault();
				jQuery( '#gv-uninstall-wrapper' ).slideToggle();
			} );
		</script>

		<a rel="gv-uninstall-wrapper" href="#gv-uninstall-wrapper" class="button button-large alignright button-danger">Uninstall GravityView</a>

		<div id="gv-uninstall-wrapper">
			<form action="" method="post">
				<?php wp_nonce_field( 'uninstall', 'gf_addon_uninstall' ) ?>
				<div class="delete-alert alert_red">

					<h3>
						<i class="fa fa-exclamation-triangle gf_invalid"></i> <?php esc_html_e( 'Delete all GravityView content and settings', 'gravityview' ); ?>
					</h3>

					<div class="gf_delete_notice">
						<?php echo $this->uninstall_warning_message() ?>
					</div>

					<?php
					echo '<input type="submit" name="uninstall" value="' . sprintf( esc_attr__( 'Uninstall %s', 'gravityview' ), $this->get_short_title() ) . '" class="button button-hero" onclick="return confirm( ' . json_encode( $this->uninstall_confirm_message() ) . ' );" onkeypress="return confirm( ' . json_encode( $this->uninstall_confirm_message() ) . ' );"/>';
					?>

				</div>
			</form>
		</div>
	<?php
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

	public function get_app_setting( $setting_name ) {
		/**
		 * Backward compatibility with Redux
		 */
		if ( $setting_name === 'license' ) {
			return array(
				'license' => parent::get_app_setting( 'license_key' ),
				'status' => parent::get_app_setting( 'license_key_status' ),
				'response' => parent::get_app_setting( 'license_key_response' ),
			);
		}

		return parent::get_app_setting( $setting_name );
	}

	public function get_app_settings() {
		$defaults = array(
			// Set the default license in wp-config.php
			'license_key' => defined( 'GRAVITYVIEW_LICENSE_KEY' ) ? GRAVITYVIEW_LICENSE_KEY : '',
			'license_key_response' => '',
			'license_key_status' => '',
			'support-email' => get_bloginfo( 'admin_email' ),
			'no-conflict-mode' => '1',
			'support_port' => '1',
			'flexbox_search' => '1',
			'beta' => '0',
		);

	    return wp_parse_args( get_option( 'gravityformsaddon_' . $this->_slug . '_app_settings', array() ), $defaults );
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
		$field['type']  = ( isset( $field['type'] ) && in_array( $field['type'], array( 'submit','reset','button' ) ) ) ? $field['type'] : 'submit';

		$attributes    = $this->get_field_attributes( $field );
		$default_value = Utils::get( $field, 'value', Utils::get( $field, 'default_value' ) );
		$value         = $this->get( $field['name'], $default_value );


		$attributes['class'] = isset( $attributes['class'] ) ? esc_attr( $attributes['class'] ) : 'button-primary gfbutton';
		$name    = ( $field['name'] === 'gform-settings-save' ) ? $field['name'] : '_gaddon_setting_' . $field['name'];

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
}
