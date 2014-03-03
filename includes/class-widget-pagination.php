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



class GravityView_Widget_Pagination extends GravityView_Widget {
	
	function __construct() {
		
		parent::__construct( 'Pagination info' , 'page_info' );
		
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
		
		
		
		
		
		// pagination links
		$curr_page = empty( $_GET['pagenum'] ) ? 1 : intval( $_GET['pagenum'] );
		
		$page_links = array(
			'base' => add_query_arg('pagenum','%#%'),
			'format' => '&pagenum=%#%',
			'add_args' => array(), //
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'total' => ceil( $total / $page_size ),
			'current' => $curr_page,
			'show_all' => true, // to be available at backoffice
		);

		$page_links = paginate_links( $page_links );
		
		echo $page_links;
		
		
		
		
		
		
		
		// Search box and filters
		$curr_search = empty( $_GET['gv_search'] ) ? '' : $_GET['gv_search'];
		$curr_start = empty( $_GET['gv_start'] ) ? '' : $_GET['gv_start'];
		$curr_end = empty( $_GET['gv_end'] ) ? '' : $_GET['gv_end'];
		?>
		<form id="lead_form" method="get" action="">
			<p class="search-box">
				<label for="gv_search"><?php esc_html_e('Search Entries:', 'gravity-view' ); ?></label>
				<input type="text" name="gv_search" id="gv_search" value="<?php echo $curr_search; ?>" />
				
				<label for="gv_start_date"><?php esc_html_e('Filter by date:', 'gravity-view' ); ?></label>
				<input name="gv_start" id="gv_start_date" type="text" class="gv-datepicker" placeholder="<?php esc_attr_e('Start date', 'gravity-view' ); ?>" value="<?php echo $curr_start; ?>">
				<input name="gv_end" id="gv_end_date" type="text" class="gv-datepicker" placeholder="<?php esc_attr_e('End date', 'gravity-view' ); ?>" value="<?php echo $curr_end; ?>">
				
				<input type="submit" class="button" id="gv_search_button" value="<?php esc_attr_e( 'Search', 'gravity-view' ); ?>" />
			</p>
		</form>
		
		<?php
			
		
		
		// date filters
		
		
		
		
	}

	
}



class GravityView_Widget {
	
	// Widget admin label
	protected $widget_label;
	
	// Widget admin id
	protected $widget_id;
	
	// hold widget View options
	private $widget_options;
	
	function __construct( $widget_label , $widget_id ) {
		
		$this->widget_label = $widget_label;
		$this->widget_id = $widget_id;
		
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
				<a class="button-secondary" href="#widget-settings" title="<?php esc_attr_e( 'Advanced Settings', 'gravity-view' ); ?>"><span class=""><?php esc_html_e( 'config', 'gravity-view'); ?></span></a>
				<div class="gv-dialog-options" title="<?php printf( __( '%1$s options', 'gravity-view' ), $this->widget_label ); ?>">
					<?php echo $this->render_advanced_settings( $widgets ); ?>
				</div>
			</td>
			
		</tr>
		
		<?php
	}
	
	
	function render_advanced_settings( $widgets ) {
		// to be defined by child class
	}
	
	
	
	/** Frontend logic */
	
	function render_frontend_hooks( $view_id ) {
		
		if( empty( $view_id ) ) {
			return;
		}
		// get View widget configuration
		$widgets = get_widget_options( $view_id );
		
		
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
	
	
	
}




