<?php


if (!class_exists('GravityView_Settings')) {

    class GravityView_Settings {

        public $args        = array();
        public $sections    = array();
        public $theme;
        public $ReduxFramework;

        public function __construct() {

        	require_once( GRAVITYVIEW_DIR . 'vendor/redux-framework/redux-framework/redux-framework.php');

        	if (!class_exists('ReduxFramework')) {
                return;
            }

            add_action('plugins_loaded', array($this, 'initSettings'), 10);

            // Add the EDD extension to Redux
            add_action( "redux/extensions/gravityview_settings", array($this, 'register_edd_extension') );

            add_action('gravityview_remove_conflicts_after', array($this, '_enqueue'));
        }

        /**
         * Add the EDD License settings field.
         * @param  ReduxFramework $ReduxFramework ReduxFramework object
         * @return void
         */
        public function register_edd_extension($ReduxFramework) {

        	require_once( GRAVITYVIEW_DIR . 'includes/lib/edd/extension_edd.php');

        	new ReduxFramework_extension_edd($ReduxFramework);

        }

        /**
         * Add the Redux scripts back in on settings page.
         * @return [type] [description]
         */
        public function _enqueue() {
        	get_redux_instance('gravityview_settings')->_enqueue();
        }

        public function initSettings() {

            // Set the default arguments
            $this->setArguments();

            // Create the sections and fields
            $this->setSections();

            if (!isset($this->args['opt_name'])) { // No errors please
                return;
            }

            // Then populate properly.
            $this->ReduxFramework = new ReduxFramework($this->sections, $this->args);
        }

        public function setSections() {

            ob_start();

            $fields = array(
            	array(
            	    'id'        => 'license',
            	    'type'      => 'edd_license',
            	    'remote_api_url' => 'https://katz.co',
            	    'author'	=> 'Katz Web Services, Inc.',
            	    'item_name'	=> 'GravityView',
            	    'version'	=> GravityView_Plugin::version,
            	    'mode'		=> 'plugin',
            	    'path'		=> GRAVITYVIEW_DIR,
            	    'title'     => __('Beta Invite Key', 'gravity-view'),
            	    #'subtitle'  => __('With the "section" field you can create indent option sections.', 'gravity-view'),
            	),
            	array(
            	    'id'        => 'beta-email',
            	    'type'      => 'text',
            	    'title'     => __('Beta Invite Email', 'gravity-view'),
            	    'validate'	=> 'email',
            	    'msg'		=> 'Please enter a valid email address',
            	),
            	array(
            	    'id'        => 'no-conflict-mode',
            	    'type'      => 'switch',
            	    'title'     => __('No-Conflict Mode', 'gravity-view'),
            	    'subtitle'  => __('Set this to ON to prevent extraneous scripts and styles from being printed on GravityView admin pages, reducing conflicts with other plugins and themes.', 'gravity-view'),
            	)
            );

			if($beta_email = $this->getSetting('beta-email')) {

				$tester_hash = sha1(sprintf("1p8W2mQTK31zmpUfUA8H%s", $beta_email));

            	$fields[] = array(
            		'id'		=> 'share-beta',
            		'type'		=> 'raw',
            		'title'		=> __('Share with your friends'),
            		'subtitle'	=> __('Invite your friends to participate in the GravityView Beta'),
            		'content'	=> "<iframe id='prefinery_iframe_inline' allowTransparency='true' width='100%' height='300' scrolling='no' frameborder='0' src='https://kws.prefinery.com/betas/4444/friend_invitations/new?display=inline&tester_hash={$tester_hash}'></iframe>",
            	);
            }

            // ACTUAL DECLARATION OF SECTIONS
            $this->sections[] = array(
                'title'     => __('Home Settings', 'gravity-view'),
                'desc'      => __('Redux Framework was created with the developer in mind. It allows for any theme developer to have an advanced theme panel with most of the features a developer would need. For more information check out the Github repo at: <a href="https://github.com/ReduxFramework/Redux-Framework">https://github.com/ReduxFramework/Redux-Framework</a>', 'gravity-view'),
                'icon'      => 'el-icon-home',
                // 'submenu' => false, // Setting submenu to false on a given section will hide it from the WordPress sidebar menu!
                'fields'    => $fields,
            );

        }

        /**
         * Get a setting.
         *
         * For now, there's no easy way to instantiate the ReduxFramework class from this point in the code,
         * so we're just duplicating it. Puff.
         *
         * @see  ReduxFramework::get()
         * @param  string $key     Option key to fetch
         * @param  mixed $default Default if value at key is not set.
         * @return mixed          The setting
         */
        public function getSetting($key, $default = NULL) {
        	if(!empty($this->ReduxFramework)) {
        		return $this->ReduxFramework->get($key, $default);
        	} else {

        		$settings = get_option( 'gravityview_settings' );

        		return isset($settings[$key]) ? $settings[$key] : $default;
        	}
        }

        public function setArguments() {

            $theme = wp_get_theme(); // For use with some settings. Not necessary.

            $this->args = array(
                // TYPICAL -> Change these values as you need/desire
                'opt_name'          => 'gravityview_settings',            // This is where your data is stored in the database and also becomes your global variable name.
                'display_name'      => 'GravityView',     // Name that appears at the top of your panel
                'display_version'   => GravityView_Plugin::version,  // Version that appears at the top of your panel
                'menu_type'         => 'submenu',                  //Specify if the admin menu should appear or not. Options: menu or submenu (Under appearance only)
                'allow_sub_menu'    => true,                    // Show the sections below the admin menu item or not
                'menu_title'        => __('Sample Options', 'gravity-view'),
                'page_title'        => __('Sample Options', 'gravity-view'),

                // You will need to generate a Google API key to use this feature.
                // Please visit: https://developers.google.com/fonts/docs/developer_api#Auth
                'google_api_key' => '', // Must be defined to add google fonts to the typography module

                'async_typography'  => false,                    // Use a asynchronous font on the front end or font string
                'admin_bar'         => false,                    // Show the panel pages on the admin bar
                'global_variable'   => '',                      // Set a different name for your global variable other than the opt_name
                'dev_mode'          => false,                    // Show the time the page took to load, etc
                'customizer'        => false,                    // Enable basic customizer support

                // OPTIONAL -> Give you extra features
                'page_priority'     => null,                    // Order where the menu appears in the admin area. If there is any conflict, something will not show. Warning.
                'page_parent'       => 'edit.php?post_type=gravityview',
                'page_permissions'  => 'manage_options',        // Permissions needed to access the options panel.
                'menu_icon'         => '',                      // Specify a custom URL to an icon
                'last_tab'          => '',                      // Force your panel to always open to a specific tab (by id)
                'page_icon'         => 'icon-themes',           // Icon displayed in the admin panel next to your menu_title
                'page_slug'         => 'settings',              // Page slug used to denote the panel
                'save_defaults'     => true,                    // On load save the defaults to DB before user clicks save or not
                'default_show'      => false,                   // If true, shows the default value next to each field that is not the default value.
                'default_mark'      => '',                      // What to print by the field's title if the value shown is default. Suggested: *
                'show_import_export' => true,                   // Shows the Import/Export panel when not used as a field.

                // HINTS
                'hints' => array(
                    'icon'          => 'icon-question-sign',
                    'icon_position' => 'right',
                    'icon_color'    => 'lightgray',
                    'icon_size'     => 'normal',
                    'tip_style'     => array(
                        'color'         => 'light',
                        'shadow'        => true,
                        'rounded'       => false,
                        'style'         => '',
                    ),
                    'tip_position'  => array(
                        'my' => 'top left',
                        'at' => 'bottom right',
                    ),
                    'tip_effect'    => array(
                        'show'          => array(
                            'effect'        => 'slide',
                            'duration'      => '500',
                            'event'         => 'mouseover',
                        ),
                        'hide'      => array(
                            'effect'    => 'slide',
                            'duration'  => '500',
                            'event'     => 'click mouseleave',
                        ),
                    ),
                )
            );


            // SOCIAL ICONS -> Setup custom links in the footer for quick links in your panel footer icons.
            $this->args['share_icons'][] = array(
                'url'   => 'https://github.com/ReduxFramework/ReduxFramework',
                'title' => 'Visit us on GitHub',
                'icon'  => 'el-icon-github'
                //'img'   => '', // You can use icon OR img. IMG needs to be a full URL.
            );
            $this->args['share_icons'][] = array(
                'url'   => 'https://www.facebook.com/pages/Redux-Framework/243141545850368',
                'title' => 'Like us on Facebook',
                'icon'  => 'el-icon-facebook'
            );
            $this->args['share_icons'][] = array(
                'url'   => 'http://twitter.com/reduxframework',
                'title' => 'Follow us on Twitter',
                'icon'  => 'el-icon-twitter'
            );
            $this->args['share_icons'][] = array(
                'url'   => 'http://www.linkedin.com/company/redux-framework',
                'title' => 'Find us on LinkedIn',
                'icon'  => 'el-icon-linkedin'
            );

            // Panel Intro text -> before the form
            if (!isset($this->args['global_variable']) || $this->args['global_variable'] !== false) {
                if (!empty($this->args['global_variable'])) {
                    $v = $this->args['global_variable'];
                } else {
                    $v = str_replace('-', '_', $this->args['opt_name']);
                }
                $this->args['intro_text'] = sprintf(__('<p>Did you know that Redux sets a global variable for you? To access any of your saved options from within your code you can use your global variable: <strong>$%1$s</strong></p>', 'gravity-view'), $v);
            } else {
                $this->args['intro_text'] = __('<p>This text is displayed above the options panel. It isn\'t required, but more info is always better! The intro_text field accepts all HTML.</p>', 'gravity-view');
            }

            // Add content after the form.
            $this->args['footer_text'] = __('<p>This text is displayed below the options panel. It isn\'t required, but more info is always better! The footer_text field accepts all HTML.</p>', 'gravity-view');
        }

    }

    new GravityView_Settings;
}