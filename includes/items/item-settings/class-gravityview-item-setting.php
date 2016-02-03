<?php
/**
 * @file class-gravityview-item-setting.php
 * @package GravityView
 * @subpackage includes\items\item-settings
 */

/**
 * Item Setting is the setting to provide special attributes to a field or a widget
 * Modify Item Setting by extending this class
 */
abstract class GravityView_Item_Setting {

    /**
     * The name of the GravityView Item Setting (should be unique)
     * Example: `show_label`, `custom_label`, `custom_class`..
     * @var string
     */
    public $name;

    /**
     * @var string The label of the item setting (e.g. "Show Label")
     */
    public $label;

    /**
     * @var string The description of the item setting (to be shown near the setting input)
     */
    public $description;

    /**
     * @type string The item setting input type (e.g. checkbox, select, text, ..)
     */
    public $type;

    /**
     * Default value for the item setting
     * @var mixed
     */
    public $value;

    /**
     * HTML class
     * @var string
     */
    public $class;

    /**
     * Options in case the setting has multiple options (e.g. for type dropdown)
     * @var string
     */
    public $options;

    /**
     * If setting supports merge tags
     * @var boolean
     */
    public $merge_tags = false;

    /**
     * Show the `{all_fields}` and `{pricing_fields}` merge tags
     * @var boolean
     */
    public $show_all_fields = false;

    /**
     * The Gravity Forms tooltip slug for this item.
     * In the react admin this could be deprecated and if field has a description, we use it to generate a tooltip
     * @var string
     */
    public $tooltip;

    /**
     * List of visibility conditions. If empty, setting is visible everywhere.
     * @var array
     */
    public $visibility;



    /**
     * GravityView_Item_Setting constructor.
     */
    public function __construct() {
        GravityView_Item_Settings::register( $this );
    }

    /**
     * Add a visibility condition:
     *   - selector: context ( `directory, `single, ..), item_type ( `field`, `widget..), template ( `table`, `list`, `datatable`..), item_id ( `3`, `5.2`, `entry_link`, `created_by`.. ), field_type ( `textarea`, `list`, `select` ..) or setting_name
     *   - operator: is / isnot / like / in
     *   - value: string, integer, array
     *
     * @param $condition array The condition details
     */
    public function add_visibility_condition( $selector = '', $operator = '', $value = '' ) {

        if( ! self::is_valid_operator() || empty( $selector ) ) {
            return;
        }

        $this->visibility[] = array(
            'selector' => $selector,
            'operator' => $operator,
            'value'    => $value
        );

    }

    /**
     * Check if a certain string is a valid operator to set a visibility condition
     * @param string $operator
     * @return bool
     */
    public static function is_valid_operator( $operator = '' ) {
        return in_array( strtolower( $operator ), array( 'is', 'isnot', 'like', 'in' ) );
    }


}
