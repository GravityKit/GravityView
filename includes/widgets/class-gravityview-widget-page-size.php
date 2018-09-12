<?php

/**
 * Widget to display page size
 *
 * @extends GravityView_Widget
 */
class GravityView_Widget_Page_Size extends \GV\Widget {

	/**
	 * Does this get displayed on a single entry?
	 * @var boolean
	 */
	protected $show_on_single = false;

	function __construct() {

		$this->widget_description = __( 'Allow users to modify the number of results shown per page.', 'gravityview' );

		$default_values = array(
			'header' => 1,
			'footer' => 1,
		);

		$settings = array();

		parent::__construct( __( 'Page Size', 'gravityview' ) , 'page_size', $default_values, $settings );
	}

	public static function get_page_sizes() {

		$sizes = array(
			array(
				'value' => 10,
				'text'  => 10,
			),
			array(
				'value' => 20,
				'text'  => 20,
			),
			array(
				'value' => 30,
				'text'  => 30,
			),
		);

		return $sizes;
	}

	public function render_frontend( $widget_args, $content = '', $context = '') {

		$search_field = array(
			'label' => 'Page Size',
			'choices' => self::get_page_sizes(),
			'value' => (int) \GV\Utils::_GET( 'page_size' ),
		);

		$default_option = 'Change Page Size';
		?>
		<div class="gv-page-size">
			<?php #if( ! gv_empty( $search_field['label'], false, false ) ) { ?>
			<label for="gv-page_size"><?php echo esc_html( $search_field['label'] ); ?></label>
			<?php #} ?>
			<form method="get" action="<?php esc_url( add_query_arg() ); ?>" onchange="this.submit();">
				<div>
					<select name="page_size" id="gv-page_size">
						<option value="" <?php gv_selected( '', $search_field['value'], true ); ?>><?php echo esc_html( $default_option ); ?></option>
						<?php
						foreach( $search_field['choices'] as $choice ) { ?>
							<option value="<?php echo esc_attr( $choice['value'] ); ?>" <?php gv_selected( esc_attr( $choice['value'] ), esc_attr( $search_field['value'] ), true ); ?>><?php echo esc_html( $choice['text'] ); ?></option>
						<?php } ?>
					</select>
					<input type="submit" value="Submit" style="display: none" />
				</div>
			</form>
		</div>
		<?php
	}

}

new GravityView_Widget_Page_Size;