<?php
/**
 * GravityView Widget Pagination
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */



/**
 * Widget to display pagination info
 * 
 * @extends GravityView_Widget
 */
class GravityView_Widget_Pagination extends GravityView_Widget {
	
	function __construct() {
		$settings = array();
		parent::__construct( __( 'Show Pagination Info', 'gravity-view' ) , 'page_info', $settings );
		
	}
	
	
	public function render_frontend() {
	
		global $gravity_view;
		
		$offset = $gravity_view->paging['offset'];
		$page_size = $gravity_view->paging['page_size'];
		$total = $gravity_view->total_entries;
		
		
		// displaying info
		if( $total == 0 ) {
			$first = $last = 0;
		} else {
			$first = empty( $offset ) ? 1 : $offset + 1;
			$last = $offset + $page_size > $total ? $total : $offset + $page_size;
		}
		
		echo '<span class="">'. sprintf(__( 'Displaying %1$s - %2$s of %3$s', 'gravity-view' ), $first , $last , $total ) . '</span>';
	
	}

} // GravityView_Widget_Pagination






/**
 * Widget to display page links
 * 
 * @extends GravityView_Widget
 */
class GravityView_Widget_Page_Links extends GravityView_Widget {
	
	function __construct() {
		$settings = array( 'show_all' => array( 'type' => 'checkbox', 'label' => __( 'Show each page number', 'gravity-view' ) ) );
		parent::__construct( __( 'Show Page Links', 'gravity-view' ) , 'page_links', $settings );
		
	}
	
	
	public function render_frontend() {
	
		global $gravity_view;
		
		$page_size = $gravity_view->paging['page_size'];
		$total = $gravity_view->total_entries;
		
		$adv_settings = $this->get_advanced_settings();
		
		$show_all = !empty( $adv_settings['show_all'] ) ? true : false;

		
		// displaying info
		$curr_page = empty( $_GET['pagenum'] ) ? 1 : intval( $_GET['pagenum'] );
		
		$page_links = array(
			'base' => add_query_arg('pagenum','%#%'),
			'format' => '&pagenum=%#%',
			'add_args' => array(), //
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'total' => ceil( $total / $page_size ),
			'current' => $curr_page,
			'show_all' => $show_all, // to be available at backoffice
		);

		$page_links = paginate_links( $page_links );
		
		echo $page_links;
	
	}

} // GravityView_Widget_Page_Links






/**
 * Widget to display search bar (free search, field and date filters)
 * 
 * @extends GravityView_Widget
 */
class GravityView_Widget_Search_Bar extends GravityView_Widget {
	
	private $search_filters = array();
	
	function __construct() {
		$settings = array( 
			'search_free' => array( 'type' => 'checkbox', 'label' => __( 'Show search input', 'gravity-view' ), 'default' => true ),
			'search_date' => array( 'type' => 'checkbox', 'label' => __( 'Show date filters', 'gravity-view' ), 'default' => false ),
			
		);
		parent::__construct( __( 'Show Search Bar', 'gravity-view' ) , 'search_bar', $settings );
		
		add_filter( 'gravityview_fe_search_criteria', array( $this, 'filter_entries' ) );
		
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts_and_styles' ) );
	}
	
	
	function filter_entries( $search_criteria ) {

		// add free search
		if( !empty( $_GET['gv_search'] ) ) {
			$search_criteria['field_filters'][] = array( 'value' => $_GET['gv_search'] );
		}
		
		// add specific fields search
		$search_filters = $this->get_search_filters();
		if( !empty( $search_filters ) && is_array( $search_filters ) ) {
			foreach( $search_filters as $k => $filter ) {
				if( !empty( $filter['value'] ) ) {
					$search_criteria['field_filters'][] = $filter;
				}
			}
		}
		
		//start date & end date
		$curr_start = empty( $_GET['gv_start'] ) ? '' : $_GET['gv_start'];
		$curr_end = empty( $_GET['gv_end'] ) ? '' : $_GET['gv_end'];
		if( !empty( $curr_start ) && !empty( $curr_end ) ) {
			$search_criteria['start_date'] = $curr_start;
			$search_criteria['end_date'] = $curr_end;
		}
		
		return $search_criteria;
	}
	
	
	public function render_frontend() {
	
		global $gravity_view;
		
		$form_id = $gravity_view->form_id;
		
		// get configured search filters (fields)
		$search_filters = $this->get_search_filters();
		
		
		$adv_settings = $this->get_advanced_settings();
		$search_free = !empty( $adv_settings['search_free'] ) ? true : false;
		$search_date = !empty( $adv_settings['search_date'] ) ? true : false;
		

		// Search box and filters
		$curr_search = empty( $_GET['gv_search'] ) ? '' : $_GET['gv_search'];
		$curr_start = empty( $_GET['gv_start'] ) ? '' : $_GET['gv_start'];
		$curr_end = empty( $_GET['gv_end'] ) ? '' : $_GET['gv_end'];
		
		?>
		<form id="lead_form" method="get" action="">
		
			<?php // search filters (fields)
			if( !empty( $search_filters ) ) {
				$form = gravityview_get_form( $form_id );
				foreach( $search_filters as $filter ) {
					$field = gravityview_get_field( $form, $filter['key'] );
					if( in_array( $field['type'] , array( 'select', 'checkbox', 'radio', 'post_category' ) ) ) {
						echo self::render_search_dropdown( $field['label'], 'filter_'.$field['id'], $field['choices'], $filter['value'] ); //Label, name attr, choices
					} else {
						echo self::render_search_input( $field['label'], 'filter_'.$field['id'], $filter['value'] ); //label, attr name
					}
				}
			}
		
			?>
		
		
			<p class="search-box">
			
				<?php if( $search_free ): ?>
					<label for="gv_search"><?php esc_html_e( 'Search Entries:', 'gravity-view' ); ?></label>
					<input type="text" name="gv_search" id="gv_search" value="<?php echo $curr_search; ?>" />
				<?php endif; ?>
				
				<?php if( $search_date ): ?>
					<label for="gv_start_date"><?php esc_html_e('Filter by date:', 'gravity-view' ); ?></label>
					<input name="gv_start" id="gv_start_date" type="text" class="gv-datepicker" placeholder="<?php esc_attr_e('Start date', 'gravity-view' ); ?>" value="<?php echo $curr_start; ?>">
					<input name="gv_end" id="gv_end_date" type="text" class="gv-datepicker" placeholder="<?php esc_attr_e('End date', 'gravity-view' ); ?>" value="<?php echo $curr_end; ?>">
					<?php // enqueue datepicker stuff only if needed!
					wp_enqueue_script( 'jquery-ui-datepicker' );
					wp_enqueue_style( 'jquery-ui-datepicker', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/smoothness/jquery-ui.css' );
					wp_enqueue_script( 'gravityview_search_bar' ); 
					?>
				<?php endif; ?>
				<input type="submit" class="button" id="gv_search_button" value="<?php esc_attr_e( 'Search', 'gravity-view' ); ?>" />
			</p>
		</form>
	<?php
	
	}
	
	
	/**
	 * Register script to include the js datepicker in the frontend
	 * 
	 * @access public
	 * @return void
	 */
	function add_scripts_and_styles() {
		wp_register_script( 'gravityview_search_bar',  GRAVITYVIEW_URL  . 'includes/js/fe-search-bar.js', array( 'jquery', 'jquery-ui-datepicker' ), '1.0.0', true );
	}
	
	
	/**
	 * render_search_dropdown function.
	 * 
	 * @access private
	 * @static
	 * @param string $label (default: '')
	 * @param string $name (default: '')
	 * @param mixed $choices
	 * @param string $current_value (default: '')
	 * @return void
	 */
	static private function render_search_dropdown( $label = '', $name = '', $choices, $current_value = '' ) {

		if( empty( $choices ) || !is_array( $choices ) || empty( $name ) ) {
			return '';
		}

		$output = '<div class="search-box">';
		$output .= '<label for=search-box-'.$name.'>' . $label . '</label>';
		$output .= '<select name="'.$name.'" id="search-box-'. $name.'">';
		$output .= '<option value="" '. selected( '', $current_value, false ) .'>---</option>';
		foreach( $choices as $choice ) {
			$output .= '<option value="'. $choice['value'] .'" '. selected( $choice['value'], $current_value, false ) .'>'. $choice['text'] .'</option>';
		}
		$output .= '</select>';
		$output .= '</div>';

		return $output;

	}


	
	/**
	 * render_search_input function.
	 * 
	 * @access private
	 * @static
	 * @param string $label (default: '')
	 * @param string $name (default: '')
	 * @param string $current_value (default: '')
	 * @return void
	 */
	static private function render_search_input( $label = '', $name = '', $current_value = '' ) {

		if( empty( $name ) ) {
			return '';
		}

		$output = '<div class="search-box">';
		$output .= '<label for=search-box-'. $name .'>' . $label . '</label>';
		$output .= '<input type="text" name="'. $name .'" id="search-box-'. $name .'" value="'. $current_value .'">';
		$output .= '</div>';

		return $output;

	}
	
	private function get_search_filters() {
		if( !empty( $this->search_filters ) ) {
			return $this->search_filters;
		}
	
		global $gravity_view;
		
		// get configured search filters (fields)
		$search_filters = array();
		if( is_array( $gravity_view->fields ) ) {
			foreach( $gravity_view->fields as $t => $fields ) {
				foreach( $fields as $field ) {
					if( !empty( $field['search_filter'] ) ) {
						$value = isset( $_GET['filter_'. $field['id'] ] ) ? $_GET['filter_'. $field['id'] ] : '';
						$search_filters[] = array( 'key' => $field['id'], 'value' => $value );
					}
				}
			}
		}
		$this->search_filters = $search_filters;
		
		return $search_filters;
	}
	
	
	

} // GravityView_Widget_Page_Links




class GravityView_Widget {
	
	// Widget admin label
	protected $widget_label;
	
	// Widget admin id
	protected $widget_id;
	
	// Widget admin advanced settings
	protected $settings;
	
	// hold widget View options
	private $widget_options;
	
	function __construct( $widget_label , $widget_id , $settings ) {
		
		$this->widget_label = $widget_label;
		$this->widget_id = $widget_id;
		$this->settings = $settings;
		
		// render html settings in the View admin screen
		add_action( 'gravityview_admin_view_widgets', array( $this, 'render_admin_settings' ), 10, 1 );
		
		// frontend logic
		add_action( 'gravityview_before', array( $this, 'render_frontend_hooks' ) );
		add_action( 'gravityview_after', array( $this, 'render_frontend_hooks' ) );
		
	}
	
	
	
	function render_admin_settings( $widgets ) {
		
		$header = empty( $widgets['header'][ $this->widget_id ] ) ? 0 : 1;
		$footer = empty( $widgets['footer'][ $this->widget_id ] ) ? 0 : 1;
		
		?>
		<tr valign="top">
			<td><label for="gravityview_widget_header_<?php echo esc_attr( $this->widget_id ); ?>"><?php echo esc_html( $this->widget_label ); ?></label></td>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><span><?php esc_html_e( 'Enable this widget to appear in View header', 'gravity-view'); ?></span></legend>
					<label for="gravityview_widget_header_<?php echo esc_attr( $this->widget_id ); ?>">
						<input name="widgets[header][<?php echo esc_attr( $this->widget_id ); ?>]" type="checkbox" id="gravityview_widget_header_<?php echo esc_attr( $this->widget_id ); ?>" value="1" <?php checked( $header , 1, true ); ?>>
					</label>
				</fieldset>
			</td>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><span><?php esc_html_e( 'Enable this widget to appear in View footer', 'gravity-view'); ?></span></legend>
					<label for="gravityview_widget_footer_<?php echo esc_attr( $this->widget_id ); ?>">
						<input name="widgets[footer][<?php echo esc_attr( $this->widget_id ); ?>]" type="checkbox" id="gravityview_widget_footer_<?php echo esc_attr( $this->widget_id ); ?>" value="1" <?php checked( $footer , 1, true ); ?>>
					</label>
				</fieldset>
			</td>
			<td>
				<?php if( !empty( $this->settings ) ): ?>
					<a class="button-secondary" href="#widget-settings" title="<?php esc_attr_e( 'Advanced Settings', 'gravity-view' ); ?>"><span class=""><?php esc_html_e( 'config', 'gravity-view'); ?></span></a>
					<div class="gv-dialog-options" title="<?php printf( __( '%1$s options', 'gravity-view' ), $this->widget_label ); ?>">
						<?php $this->render_advanced_settings( $widgets ); ?>
					</div>
				<?php endif; ?>
			</td>
			
		</tr>
		
		<?php
	}
	
	
	function render_advanced_settings( $widgets ) {
	
		if( !is_array( $this->settings ) ) {
			return '';
		}
		
		echo '<ul>';
		
		foreach( $this->settings as $key => $details ) {
			
			//$default = isset( $details['default'] ) ? $details['default'] : '';
			$default = '';
			$curr_value = isset( $widgets[ $this->widget_id ][ $key ] ) ? $widgets[ $this->widget_id ][ $key ] : $default;
			$label = isset( $details['label'] ) ? $details['label'] : '';
			$type = isset( $details['type'] ) ? $details['type'] : 'input_text';
			
			switch( $type ) {
				case 'checkbox':
					echo GravityView_Admin_Views::render_checkbox_option( 'widgets['. $this->widget_id .']['. $key .']' , $label, $curr_value );
					break;
				
				case 'input_text':
				default:
					echo GravityView_Admin_Views::render_input_text_option( 'widgets['. $this->widget_id .']['. $key .']' , $label, $curr_value );
					break;
			
			}
			
			
		}
		
		echo '</ul>';
		
	}
	
	
	
	/** Frontend logic */
	
	function render_frontend_hooks( $view_id ) {

		if( empty( $view_id ) ) {
			return;
		}
		// get View widget configuration
		$widgets = $this->get_widget_options( $view_id );
		
		
		switch( current_filter() ) {
			case 'gravityview_before':
				if( !empty( $widgets['header'][ $this->widget_id ] ) ) {
					$this->render_frontend();
				}
				break;
			case 'gravityview_after':
				if( !empty( $widgets['footer'][ $this->widget_id ] ) ) {
					$this->render_frontend();
				}
				break;
			
		}

	}
	
	
	function render_frontend() {
		// to be defined by child class
	}
	
	
	
	
	
	
	
	// helper
	function get_widget_options( $id ) {
		
		if( empty( $id ) ) {
			return '';
		}
		
		if( empty( $this->widget_options ) ) {
			$this->widget_options = get_post_meta( $id, '_gravityview_directory_widgets', true );
		}
		
		return $this->widget_options;
	}
	
	function get_advanced_settings() {
		return isset( $this->widget_options[ $this->widget_id ] ) ? $this->widget_options[ $this->widget_id ] : '';
	}
	
	
	
} // GravityView_Widget
