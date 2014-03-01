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
		
		
		// displaying info
		$first = empty( $offset ) ? 1 : $offset + 1;
		$last = $offset + $page_size > $total ? $total : $offset + $page_size;
		echo '<span class="">'. sprintf(__( 'Displaying %1$s - %2$s of %3$s', 'gravity-view' ), $first , $last , $total ) . '</span>';
		
		// -> correct the last page info
		
		
		
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
	}

	
}






