<?php

/**
 * Widget to sum up the values of a certain field for the visible
 *
 * @since 1.5.4
 *
 * @extends GravityView_Widget
 */
class GravityView_Widget_Sum_Column extends GravityView_Widget {

	/**
	 * Does this get displayed on a single entry?
	 * @var boolean
	 */
	protected $show_on_single = false;

	function __construct() {

		$this->widget_description = __('Display the sum of a certain column for the visible entries', 'gravityview' );

		$default_values = array(
			'header' => 1,
			'footer' => 1,
		);

		$settings = array(
			'label' => array(
				'type' => 'text',
				'label' => __( 'Label', 'gravityview' ),
				'desc' => '',
				'value' => __( 'Sum', 'gravityview' ),
				'merge_tags' => false,
				'show_all_fields' => false,
			),
			'sum_field' => array(
				'type' => 'hidden',
				'label' => __( 'Field to sum up', 'gravityview' ),
				'value' => '',
                'class' => 'gv-sum-field-value'
			),
            'format' => array(
                'label' 	=> __( 'Number Format', 'gravityview' ),
                'type' => 'select',
                'value' => 'decimal_dot',
                'options' => array(
                    'decimal_dot' => '9,999.99',
                    'decimal_comma' => '9.999,99',
                    'currency' => __( 'Currency', 'gravityview' ),
                ),
            ),
		);

		parent::__construct( __( 'Sum', 'gravityview' ) , 'column_sum', $default_values, $settings );

        // ajax - get the searchable fields
        add_action( 'wp_ajax_gv_number_fields', array( 'GravityView_Widget_Sum_Column', 'get_number_fields' ) );
	}

	public function render_frontend( $widget_args ) {
        $view = GravityView_View::getInstance();

        $sum_field_id = (string)$widget_args['sum_field'];
        $form = $view->getForm();

        $sum_field = GVCommon::get_field( $form, $sum_field_id );

        if( empty( $sum_field ) ) {
            return;
        }

		if( !$this->pre_render_frontend() ) {
			return;
		}

        $sum = 0;

        foreach( $view->getEntries() as $entry ) {
            $sum += RGFormsModel::get_lead_field_value( $entry, $sum_field );
        }

		// Add custom class
		$class = !empty( $widget_args['custom_class'] ) ? $widget_args['custom_class'] : '';
		$class = gravityview_sanitize_html_class( $class );

        ?>
        <div class="gv-widget-sum-columns <?php echo $class; ?>">
            <p><?php echo esc_html( $widget_args['label'] ); ?>&nbsp;<?php echo esc_html( $sum ); ?></p>
        </div>
        <?php
	}


    /**
     * Ajax
     * Returns the form fields ( only the number ones )
     *
     * @access public
     * @return void
     */
    static function get_number_fields() {

        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajaxviews' ) ) {
            exit(0);
        }
        $form = '';

        // Fetch the form for the current View
        if( !empty( $_POST['view_id'] ) ) {

            $form = gravityview_get_form_id( $_POST['view_id'] );

        } elseif( !empty( $_POST['formid'] ) ) {

            $form = (int) $_POST['formid'];

        } elseif( !empty( $_POST['template_id'] ) && class_exists('GravityView_Ajax') ) {

            $form = GravityView_Ajax::pre_get_form_fields( $_POST['template_id'] );
            $form = $form['id'];
        }

        $current = !empty( $_POST['current'] ) ?  $_POST['current'] : '';

        ?>

        <label for="" class="gv-label-select">
            <span class="gv-label"><?php esc_html_e( 'Field to sum up', 'gravityview' ); ?></span>
            <select class="gv-sum-number-fields">
                <?php echo self::render_number_fields( $form, $current ); ?>
            </select>
        </label>

        <?php
        die();
    }

    /**
     * Generates html for the available Search Fields dropdown
     * @param  string $form_id
     * @param  string $current (for future use)
     * @return string
     */
    static function render_number_fields( $form_id = null, $current = '' ) {

        if( is_null( $form_id ) ) {
            return '';
        }

        // Get fields with sub-inputs and no parent
        $fields = gravityview_get_form_fields( $form_id, true, true );

        // start building output

        $output = '';

        if( !empty( $fields ) ) {

            $numeric_field_types = apply_filters( 'gravityview/widget/sum/numeric_fields', array( 'number') );

            foreach( $fields as $id => $field ) {

                if( !in_array( $field['type'], $numeric_field_types ) ) { continue; }

                $output .= '<option value="'. $id .'" '. selected( $id, $current, false ).'">'. esc_html( $field['label'] ) .'</option>';
            }

        }

        return $output;

    }

}

new GravityView_Widget_Sum_Column;