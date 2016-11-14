<?php
/**
 * @file class-gravityview-field-is-approved.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Is_Approved extends GravityView_Field {

	public $name = 'is_approved';

	public $search_operators = array( 'is', 'isnot' );

	public $contexts = array( 'single', 'multiple' );

	public $group = 'meta';

	public $is_sortable = true;

	public $is_numeric = true;

	public $is_searchable = true;

	/**
	 * @var string Approval status is stored in entry meta under this key
	 * @since 1.18
	 */
	var $entry_meta_key = 'is_approved';

	/**
	 * @var bool Don't add to the "columns to display" list; GravityView adds our own approval column
	 * @since 1.18
	 */
	var $entry_meta_is_default_column = false;

	public $_custom_merge_tag = 'approval_status';

	public function __construct() {

		$this->label = esc_html__( 'Approval Status', 'gravityview' );
		$this->description = esc_html__( 'Display the entry\'s current approval status.', 'gravityview' );
		$this->default_search_label = __( 'Approval:', 'gravityview' );

		$this->add_hooks();

		parent::__construct();
	}

	private function add_hooks() {
		add_filter( 'gravityview_entry_default_fields', array( $this, 'add_default_field' ), 10, 3 );

		add_filter( 'gravityview_field_entry_value_is_approved_pre_link', array( $this, 'filter_field_value' ), 10, 4 );
	}

	/**
	 * Convert entry approval status value to label in the field output. Uses labels from the field setting.
	 *
	 * @since 1.18
	 *
	 * @param string $output HTML value output
	 * @param array  $entry The GF entry array
	 * @param array  $field_settings Settings for the particular GV field
	 * @param array  $field Field array, as fetched from GravityView_View::getCurrentField()
	 *
	 * @return string The field setting label for the current status. Uses defaults, if not configured.
	 */
	public function filter_field_value( $output = '', $entry = array(), $field_settings = array(), $gv_field_output = array() ) {

		$status = GravityView_Entry_Approval_Status::maybe_convert_status( $output );
		$status_key = GravityView_Entry_Approval_Status::get_key( $status );

		// "approved_label", "unapproved_label", "disapproved_label" setting keys
		$field_setting_key = sprintf( '%s_label', $status_key );

		$default_label = GravityView_Entry_Approval_Status::get_label( $status );

		$value = rgar( $field_settings, $field_setting_key, $default_label );

		return sprintf( '<span class="gv-approval-%s">%s</span>', esc_attr( $status_key ), $value );
	}

	/**
	 *
	 *
	 * @filter `gravityview_entry_default_fields`
	 *
	 * @param  array $entry_default_fields Array of fields shown by default
	 * @param  string|array $form form_ID or form object
	 * @param  string $zone Either 'single', 'directory', 'header', 'footer'
	 *
	 * @return array
	 */
	function add_default_field( $entry_default_fields, $form, $zone ) {

		if( 'edit' !== $zone ) {
			$entry_default_fields[ $this->name ] = array(
				'label' => $this->label,
				'desc'  => $this->description,
				'type'  => $this->name,
			);
		}

		return $entry_default_fields;
	}

	/**
	 * Add custom merge tags to merge tag options
	 *
	 * @since 1.16
	 *
	 * @param array $form GF Form array
	 * @param GF_Field[] $fields Array of fields in the form
	 *
	 * @return array Modified merge tags
	 */
	protected function custom_merge_tags( $form = array(), $fields = array() ) {

		$merge_tags = array(
			array(
				'label' => __('Approval Status', 'gravityview'),
				'tag' => '{approval_status}'
			),
		);

		return $merge_tags;
	}

	/**
	 * Display the approval status of an entry
	 *
	 * @see http://docs.gravityview.co/article/389-approvalstatus-merge-tag Read how to use the `{approval_status}` merge tag
	 *
	 * @since 1.18
	 *
	 * @param array $matches Array of Merge Tag matches found in text by preg_match_all
	 * @param string $text Text to replace
	 * @param array $form Gravity Forms form array
	 * @param array $entry Entry array
	 * @param bool $url_encode Whether to URL-encode output
	 * @param bool $esc_html Whether to apply `esc_html()` to output
	 *
	 * @return string Text, with user variables replaced, if they existed
	 */
	public function replace_merge_tag( $matches = array(), $text = '', $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		$return = $text;

		/**
		 * @var array $match {
		 *      @type string $match[0] Full matched merge tag ("{gv_approval}")
		 *      @type string $match[1] Modifier ("value", "label", or empty string)
		 * }
		 */
		foreach ( $matches as $match ) {

			if ( empty( $entry ) ) {
				do_action( 'gravityview_log_error', __METHOD__ . ': No entry data available. Returning empty string.' );
				$replacement = '';
			} else {
				$replacement = GravityView_Entry_Approval::get_entry_status( $entry, $match[1] );
			}

			$return = str_replace( $match[0], $replacement, $return );
		}

		return $return;
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		$field_options['approved_label'] = array(
			'type' => 'text',
			'label' => __( 'Approved Label', 'gravityview' ),
			'desc' => __( 'If the entry is approved, display this value', 'gravityview' ),
			'placeholder' => GravityView_Entry_Approval_Status::get_label('approved'),
		);

		$field_options['disapproved_label'] = array(
			'type' => 'text',
			'label' => __( 'Disapproved Label', 'gravityview' ),
			'desc' => __( 'If the entry is not approved, display this value', 'gravityview' ),
			'placeholder' => GravityView_Entry_Approval_Status::get_label('disapproved'),
		);

		$field_options['unapproved_label'] = array(
			'type' => 'text',
			'label' => __( 'Unapproved Label', 'gravityview' ),
			'desc' => __( 'If the entry has not yet been approved or disapproved, display this value', 'gravityview' ),
			'placeholder' => GravityView_Entry_Approval_Status::get_label('unapproved'),
		);

		return $field_options;
	}

}

new GravityView_Field_Is_Approved;