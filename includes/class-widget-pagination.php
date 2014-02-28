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



class GravityView_Widget_Pagination {
	
	function __construct() {
		
		add_action( 'gravityview_before', array( $this, 'render_pagination' ) );
		
	}
	
	
	public function render_pagination() {
		global $gravity_view;
		
		$offset = $gravity_view->paging['offset'];
		$page_size = $gravity_view->paging['page_size'];
		$total = $gravity_view->total_entries;
		
		
		// todo: correct translation
		echo '<span class="gv-pagination">' . esc_html__( 'Displaying', 'gravity-view' ) . $offset * $page_size + 1  .' to '. ( ( $offset + 1 ) * $page_size - 1 )  .' of '. $total .'</span>';
		
	}

	
}






