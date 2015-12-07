<?php

/**
 * Add custom options for address fields
 */
class GravityView_Field_Approval extends GravityView_Field {

	var $name = 'approval';

	var $group = 'gravityview';

	public function __construct() {

		$this->label = esc_attr__( 'Approval', 'gravityview' );

		$this->description =  esc_attr__( 'Approve entries from the View. Requires users have `gravityview_moderate_entries` capability or higher.', 'gravityview' );

		parent::__construct();

		add_filter( 'gravityview_entry_default_fields', array( $this, 'filter_gravityview_entry_default_field' ), 10, 3 );

		add_action( 'gravityview/fields/approval/load_scripts', array( $this, 'scripts' ) );

	}

	function scripts() {

		if( wp_script_is( 'gravityview-field-approval' ) ) {
			return;
		}

		wp_enqueue_script( 'gravityview-field-approval', GRAVITYVIEW_URL . 'assets/js/field-approval.js', GravityView_Plugin::version, true );

		wp_localize_script( 'gravityview-field-approval', 'gvApproval', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'gravityview_ajaxgfentries'),
			'text' => array(
				'label_approve' => __( 'Approve', 'gravityview' ) ,
				'label_disapprove' => __( 'Disapprove', 'gravityview' ),
				'approve_title' => __( 'Entry not approved for directory viewing. Click to approve this entry.', 'gravityview'),
				'unapprove_title' => __( 'Entry approved for directory viewing. Click to disapprove this entry.', 'gravityview'),
			),
		));

	}

	/**
	 * Add Fields to the field list
	 * @param array $entry_default_fields Array of fields shown by default
	 * @param string|array $form form_ID or form object
	 * @param string $context  Either 'single', 'directory', 'header', 'footer'
	 *
	 * @return mixed
	 */
	public function filter_gravityview_entry_default_field( $entry_default_fields, $form, $context ) {

		if( !isset( $entry_default_fields[ "{$this->name}" ] ) ) {
			$entry_default_fields[ "{$this->name}" ] = array(
				'label' => $this->label,
				'desc'	=> $this->description,
				'type' => $this->name,
			);
		}

		return $entry_default_fields;
	}

}

new GravityView_Field_Approval;
