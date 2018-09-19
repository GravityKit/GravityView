<?php
namespace GV\Widgets;
/**
 * Widget to display page size
 *
 * @extends GV\Widget
 */
class Page_Size extends \GV\Widget {

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

		if ( ! $this->is_registered() ) {
			// add_filter( '
		}

		parent::__construct( __( 'Page Size', 'gravityview' ) , 'page_size', $default_values, $settings );
	}

	/** 
	 * Get an array of page sizes.
	 *
	 * @param \GV\Context $context The context.
	 *
	 * @return array The page sizes in an array with value and text keys.
	 */
	public static function get_page_sizes( $context ) {
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

		/**
		 * @filter `gravityview/widgets/page_size/page_sizes` Filter the available page sizes as needed.
		 * @param[in,out] array $page_sizes The sizes.
		 * @param \GV\Context The context.
		 */
		$sizes = apply_filters( 'gravityview/widgets/page_size/page_sizes', $sizes, $context );

		return $sizes;
	}

	public function render_frontend( $widget_args, $content = '', $context = '') {

		$search_field = array(
			'label' => __( 'Page Size', 'gravityview' ),
			'choices' => self::get_page_sizes( $context ),
			'value' => (int) \GV\Utils::_GET( 'page_size' ),
		);

		$default_option = __( 'Change Page Size', 'gravityview' );
		?>
		<div class="gv-page-size">
			<label for="gv-page_size"><?php echo esc_html( $search_field['label'] ); ?></label>
			<form method="get" action="" onchange="this.submit();">
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