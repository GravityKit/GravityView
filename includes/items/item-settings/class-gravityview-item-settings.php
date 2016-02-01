<?php
/**
 * @file class-gravityview-item-settings.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */


final class GravityView_Item_Settings {

    /* @var GravityView_Item_Settings[] */
    protected static $_settings = array();

    /**
     * @param GravityView_Item_Setting $setting Item Setting to register
     *
     * @throws Exception If requirements aren't met
     *
     * @return void
     */
    public static function register( $setting ) {
        if ( ! is_subclass_of( $setting, 'GravityView_Item_Setting' ) ) {
            throw new Exception( 'Must be a subclass of GravityView_Item_Setting' );
        }
        if ( empty( $setting->name ) ) {
            throw new Exception( 'The name must be set' );
        }
        if ( isset( self::$_settings[ $setting->name ] ) && ! defined( 'DOING_GRAVITYVIEW_TESTS' ) ) {
            throw new Exception( 'Field type already registered: ' . $setting->name );
        }
        self::$_settings[ $setting->name ] = $setting;
    }

    /**
     *
     *
     * @param $setting_name
     * @return GravityView_Item_Setting
     */
    public static function load( $setting_name  ) {

        $setting_name = strtolower( $setting_name );

        if( self::exists( $setting_name ) ) {
            return self::get_instance( $setting_name );
        }

        $filename  = GRAVITYVIEW_DIR . 'includes/items/item-settings/class-gravityview-item-setting-' . str_replace( '_', '-', $setting_name ) . '.php';
        $classname = 'GravityView_Item_Setting_' . str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $setting_name ) ) );

        require_once $filename;

        return new $classname;
    }

    /**
     * Does the setting exist (has it been registered)?
     *
     * @param string $setting_name
     *
     * @return bool True: yes, it exists; False: nope
     */
    public static function exists( $setting_name ) {
        return isset( self::$_settings["{$setting_name}"] );
    }

    /**
     * @param string $setting_name
     *
     * @return GravityView_Item_Setting
     */
    public static function get_instance( $setting_name ) {
        return isset( self::$_settings[ $setting_name ] ) ? self::$_settings[ $setting_name ] : false;
    }

    /**
     * Alias for get_instance()
     *
     * @param $setting_name
     *
     * @return GravityView_Item_Setting
     */
    public static function get( $setting_name ) {
        return self::get_instance( $setting_name );
    }



    /**
     * Get all Settings
     *
     *
     *
     *
     * @return GravityView_Item_Settings[]
     */
    public static function get_all( $item_type = '', $context = '', $item_id = '', $template_id = '' ) {

        /*if( '' !==  ) {
            $return_fields = self::$_settings;
            foreach ( $return_fields as $key => $field ) {
                if( $group !== $field->group ) {
                    unset( $return_fields[ $key ] );
                }
            }
            return $return_fields;
        } else {
            return self::$_settings;
        }*/
    }

    /**
     *
     * @param string $setting_name
     * @param string $selector
     * @param string $operator
     * @param string $value
     */
    public static function set_visibility_condition( $setting_name = '', $selector = '', $operator = '', $value = '' ) {

        $setting = null;

        if( !self::exists( $setting_name ) ) {
            /**
             * @var GravityView_Item_Setting
             */
            $setting = self::load( $setting_name );
        }

        if ( ! is_subclass_of( $setting, 'GravityView_Item_Setting' ) ) {
            throw new Exception( 'Setting ['. $setting_name .'] not valid or cannot be registered.' );
        }

        $setting->add_visibility_condition( $selector, $operator, $value );

    }

}