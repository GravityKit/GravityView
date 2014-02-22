<?php
/**
 * 
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2013, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */



class GravityView_Admin_EditForm {

	
	function __construct() {
		
		// Add button to left menu
		add_filter( 'gform_add_field_buttons', array( $this, 'add_field_buttons' ) );
		
		// Set defaults
		add_action( 'gform_editor_js_set_default_values', array( $this, 'set_defaults' ) );
		
		// adding styles and scripts
	//	add_action('admin_enqueue_scripts', array( $this, 'add_scripts_and_styles') );
		
	}
	
	
	/**
	 * Inject new add field buttons in the gravity form editor page
	 * 
	 * @access public
	 * @param mixed $field_groups
	 * @return void
	 */
	function add_field_buttons( $field_groups ) {
	
		$gravityview_fields = array(
			'name' => 'gravityview_fields',
			'label' => 'GravityView Fields',
			'fields' => array(
				array(
					'class' => 'button',
					'value' => __( 'Approved', 'gravity-view' ),
					'onclick' => "StartAddField('gravityviewapproved');"
				),
			)
		);

		array_push( $field_groups, $gravityview_fields );

		return $field_groups;
	}
	
	
	
	function set_defaults() {
		?>
		case 'gravityviewapproved':
			field.label = "<?php _e( 'Approved? (Admin-only)', 'gravity-view' ); ?>";

			field.adminLabel = "<?php _e( 'Approved?', 'gravity-view' ); ?>";
			field.adminOnly = true;

			field.choices = null;
			field.inputs = null;

			if( !field.choices ) {
				field.choices = new Array( new Choice("<?php _e( 'Approved', 'gravity-view' ); ?>") );
			}
			
			field.inputs = new Array();
			for( var i=1; i<=field.choices.length; i++ ) {
				field.inputs.push(new Input(field.id + (i/10), field.choices[i-1].text));
			}
			
			field.type = 'checkbox';

			break;
		<?php 
	}
	
	
	
	
	
	
	
	
	
	function add_scripts_and_styles( $hook ) {
		global $current_screen;
		
		if( !in_array( $hook , array( 'post.php' , 'post-new.php' ) ) || ( !empty($current_screen->post_type) && 'gravityview' != $current_screen->post_type ) ) {
			return;
		}
		
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		
		//enqueue scripts
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		
		wp_register_script( 'gravityview_views_scripts', GRAVITYVIEW_URL . 'includes/js/admin-views.js', array( 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'jquery-ui-dialog' ) );
		wp_enqueue_script( 'gravityview_views_scripts');
		


/*wp_localize_script( 'gravityview_views_scripts', 'active_langs', array( 'all' => $this->active_langs['all'], 'default_lang' => $this->active_langs['default'], 'default_label' => __('Default','gpoliglota') ) );*/

		wp_localize_script('gravityview_views_scripts', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'gravityview_ajaxviews' ) ) );
		
		//enqueue styles
		wp_register_style( 'gravityview_views_styles', GRAVITYVIEW_URL . 'includes/css/admin-views.css', array() );
		wp_enqueue_style( 'gravityview_views_styles' );
	}


}








?>
