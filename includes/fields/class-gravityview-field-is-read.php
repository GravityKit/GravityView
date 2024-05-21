<?php
/**
 * @file class-gravityview-field-is-read.php
 * @package GravityView
 * @subpackage includes\fields
 * @since TODO
 */

/**
 * Field to display whether the entry has been read.
 *
 * @since TODO
 */
class GravityView_Field_Is_Read extends GravityView_Field {

	var $name = 'is_read';

	var $is_searchable = true;

	var $entry_meta_key = 'is_read';

	var $search_operators = [ 'is', 'isnot' ];

	var $group = 'meta';

	var $contexts = [ 'single', 'multiple', 'export' ];

	var $icon = 'dashicons-book-alt';

	var $entry_meta_is_default_column = true;

	var $is_numeric = true;

	var $is_sortable = true;

	/**
	 * GravityView_Field_Is_Read constructor.
	 */
	public function __construct() {

		$this->label                = esc_html__( 'Read Status', 'gk-gravityview' );
		$this->default_search_label = __( 'Is Read', 'gk-gravityview' );
		$this->description          = esc_html__( 'Display whether the entry has been read.', 'gk-gravityview' );

		$this->add_hooks();

		parent::__construct();
	}

	/**
	 * Add hooks for the field.
	 */
	private function add_hooks() {
		/** @see \GV\Field::get_value_filters */
		add_filter( 'gravityview/field/is_read/value', [ $this, 'get_value' ], 10, 6 );
		add_action( 'gravityview/template/after', [ $this, 'print_script' ], 10, 1 );
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		$field_options['is_read_label'] = [
			'type'  => 'text',
			'label' => __( 'Read Label', 'gk-gravityview' ),
			'desc'  => __( 'If the entry has been read, display this value', 'gk-gravityview' ),
			'value' => __( 'Read', 'gk-gravityview' ),
		];

		$field_options['is_unread_label'] = [
			'type'  => 'text',
			'label' => __( 'Unread Label', 'gk-gravityview' ),
			'desc'  => __( 'If the entry has not been read, display this value', 'gk-gravityview' ),
			'value' => __( 'Unread', 'gk-gravityview' ),
		];

		return $field_options;
	}

	/**
	 * Display the value based on the field settings
	 *
	 * @since 2.0
	 *
	 * @param string                                   $value The value.
	 * @param \GV\Field The field we're doing this for.
	 * @param \GV\View                                 $view The view for this context if applicable.
	 * @param \GV\Source                               $source The source (form) for this context if applicable.
	 * @param \GV\Entry                                $entry The entry for this context if applicable.
	 * @param \GV\Request                              $request The request for this context if applicable.
	 *
	 * @return string Value of the field
	 */
	public function get_value( $value, $field, $view, $source, $entry, $request ) {

		if ( empty( $value ) ) {
			return \GV\Utils::get( $field, 'is_unread_label', esc_html__( 'Unread', 'gk-gravityview' ) );
		}

		return $this->get_is_read_label( $field );
	}

	/**
	 * Get the label for "Read" for a field.
	 *
	 * @param \GV\Field $field The field.
	 *
	 * @return string The string to use for "Read".
	 */
	protected function get_is_read_label( $field ) {
		return \GV\Utils::get( $field, 'is_read_label', esc_html__( 'Read', 'gk-gravityview' ) );
	}

	/**
	 * Returns the first "Read Status" field from the context.
	 *
	 * @param \GV\Template_Context $context The context.
	 *
	 * @return \GV\Field|null The field or null if not found.
	 */
	protected function get_field_from_context( $context ) {
		foreach ( $context->fields->all() as $field ) {
			if ( $this->name === $field->type ) {
				return $field;
			}
		}

		return null;
	}

	/**
	 * Add JS to the bottom of the View if there is a read field and user has `gravityview_edit_entries` cap
	 *
	 * @param \GV\Template_Context $context The template context
	 * @since 2.0
	 *
	 * @return void
	 */
	public function print_script( $context ) {

		if ( defined( 'DOING_GRAVITYVIEW_TESTS' ) ) {
			return;
		}

		if ( ! GravityView_Roles_Capabilities::has_cap( 'gravityview_edit_entries' ) ) {
			return;
		}

		/**
		 * @filter `gk/gravityview/field/is_read/print_script` Disable the script that marks the entry as read.
		 * @since TODO
		 * @param bool $print_script Should the script be printed? Default: true.
		 * @param \GV\Template_Context $context The template context.
		 */
		if ( ! apply_filters( 'gk/gravityview/field/is_read/print_script', true, $context ) ) {
			return;
		}

		$entry = gravityview()->request->is_entry();

		if ( ! $entry ) {
			return;
		}

		if ( ! empty( $entry['is_read'] ) ) {
			return;
		}

		$field = $this->get_field_from_context( $context );
		$read_label = $this->get_is_read_label( $field );
		?>
		<script>
			jQuery( document ).ready( function ( $ ) {

				var entry_id = <?php echo (int) $context->entry->ID; ?>;
					read_field = $('[class*=is_read]');
					read_label = '<?php echo esc_html( $read_label ); ?>';

				$.ajax({
					type: "POST",
					url: "<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>",
					data: {
						action: 'rg_update_lead_property',
						rg_update_lead_property: '<?php echo wp_create_nonce( 'rg_update_lead_property' ); ?>',
						lead_id: entry_id,
						name: 'is_read',
						value: 1
					}
				}).done(function() {
						if(read_field.parents('tbody').length > 0){
							read_field.find('td').text(read_label);
						}else{
							read_field.text(read_label);
						}
					})
					.fail(function() {
						alert(<?php echo json_encode( __( 'There was an error updating the entry.', 'gk-gravityview' ) ); ?>);
					});
			});
		</script>
		<?php
	}

}

new GravityView_Field_Is_Read();