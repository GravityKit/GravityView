<?php
/**
 * @file class-gravityview-field-total.php
 *
 * @since 1.20
 */
class GravityView_Field_Total extends GravityView_Field
{
    public $name = 'total';

    public $is_searchable = true;

    public $is_numeric = true;

    public $search_operators = ['is', 'isnot', 'greater_than', 'less_than', 'contains'];

    public $group = 'product';

    public $icon = 'dashicons-cart';

    /** @see GF_Field_Total */
    public $_gf_field_class_name = 'GF_Field_Total';

    public function __construct()
    {
        $this->label = esc_html__('Total', 'gravityview');

        add_action('gravityview/edit_entry/after_update', [$this, 'edit_entry_recalculate_totals'], 10, 3);

        add_filter('gravityview_blocklist_field_types', [$this, 'add_to_blocklist'], 10, 2);

        parent::__construct();
    }

    /**
     * Prevent the Total fields from being displayed in the Edit Entry configuration screen -- for now.
     *
     * Gravity Forms forms need to know all the pricing information available to calculate a Total.
     *
     * If you have an Edit Entry field with just two fields (Quantity and Total), the Total will not be able to calculate
     * without the Product field, and possibly the Option, Shipping, and Coupon fields.
     *
     * The only options currently available are: show the whole form, or don't show the Total
     *
     * @since 1.20
     *
     * @todo Support Total fields in Edit Entry configuration
     *
     * @param array       $blocklist Array of field types not able to be added to Edit Entry
     * @param string|null $context   Context
     *
     * @return array Blocklist, with "total" added. If not edit context, original field blocklist. Otherwise, blocklist including total.
     */
    public function add_to_blocklist($blocklist = [], $context = null)
    {
        if (empty($context) || $context !== 'edit') {
            return $blocklist;
        }

        $blocklist[] = 'total';

        return $blocklist;
    }

    /**
     * If entry has totals fields, recalculate them.
     *
     * @since 1.20
     *
     * @param array                         $form              Gravity Forms form array
     * @param int                           $entry_id          Gravity Forms Entry ID
     * @param GravityView_Edit_Entry_Render $Edit_Entry_Render
     *
     * @return void
     */
    public function edit_entry_recalculate_totals($form = [], $entry_id = 0, $Edit_Entry_Render = null)
    {
        $original_form = GFAPI::get_form($form['id']);

        $total_fields = GFCommon::get_fields_by_type($original_form, 'total');

        //saving total field as the last field of the form.
        if (!empty($total_fields)) {
            $entry = GFAPI::get_entry($entry_id);

            /** @var GF_Field_Total $total_field */
            foreach ($total_fields as $total_field) {
                $entry["{$total_field->id}"] = GFCommon::get_order_total($original_form, $entry);
            }

            $return_entry = GFAPI::update_entry($entry);

            if (is_wp_error($return_entry)) {
                gravityview()->log->error('Updating the entry total fields failed', ['data' => $return_entry]);
            } else {
                gravityview()->log->debug('Updating the entry total fields succeeded');
            }
        }
    }
}

new GravityView_Field_Total();
