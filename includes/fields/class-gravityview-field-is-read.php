<?php
/**
 * @file class-gravityview-field-is-starred.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Is_Read extends GravityView_Field {

	var $name = 'is_read';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot' );

	var $group = 'meta';

	var $contexts = array( 'single', 'multiple', 'export' );

	var $icon = 'dashicons-book-alt';

	var $entry_meta_key = 'is_approved';

	var $entry_meta_is_default_column = true;

	var $is_numeric = true;

	var $is_sortable = true;

	/**
	 * GravityView_Field_Is_Starred constructor.
	 */
	public function __construct() {

		$this->label = esc_html__( 'Read Status', 'gravityview' );
		$this->default_search_label = __( 'Is Read', 'gravityview' );
		$this->description = esc_html__( 'Display whether the entry has been read.', 'gravityview' );

		$this->add_hooks();

		parent::__construct();
	}

	private function add_hooks() {
	    /** @see \GV\Field::get_value_filters */
		add_filter( "gravityview/field/{$this->name}/value", array( $this, 'get_value' ), 10, 6 );
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		$field_options['is_read_label'] = array(
			'type' => 'text',
			'label' => __( 'Read Label', 'gravityview' ),
			'desc' => __( 'If the entry has been read, display this value', 'gravityview' ),
			'placeholder' => __('Read', 'gravityview' ),
		);

		$field_options['is_unread_label'] = array(
			'type' => 'text',
			'label' => __( 'Unread Label', 'gravityview' ),
			'desc' => __( 'If the entry has not been read, display this value', 'gravityview' ),
			'placeholder' => __('Unread', 'gravityview' ),
		);

		return $field_options;
	}

	/**
	 * Display the value based on the field settings
	 *
	 * @since 2.0
	 *
	 * @param string $value The value.
	 * @param \GV\Field The field we're doing this for.
	 * @param \GV\View $view The view for this context if applicable.
	 * @param \GV\Source $source The source (form) for this context if applicable.
	 * @param \GV\Entry $entry The entry for this context if applicable.
	 * @param \GV\Request $request The request for this context if applicable.
	 *
	 * @return string Image of the star
	 */
	public function get_value( $value, $field, $view, $source, $entry, $request ) {

		if ( empty( $value ) ) {
			return \GV\Utils::get( $field, 'is_unread_label', esc_html__( 'Unread', 'gravityview') );
		}

		return \GV\Utils::get( $field, 'is_read_label', esc_html__( 'Read', 'gravityview') );
	}


	/**
	 * Add JS to the bottom of the View if there is a star field and user has `gravityview_edit_entries` cap
	 *
	 * @param \GV\Template_Context $context The template context
	 * @since 2.0
	 *
	 * @return void
	 */
	public function print_script( $context ) {
		return;
		if ( ! GravityView_Roles_Capabilities::has_cap( 'gravityview_edit_entries' ) ) {
			return;
		}

		?>
		<script>
			jQuery( document ).ready( function ( $ ) {
				var is_read = $(this).data('is_read'),
						update = ( is_starred ? 0 : 1 ),
						entry_id = $(this).data('entry-id'),
						$star = $( this );

				$.ajax({
					type: "POST",
					url: "<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>",
					data: {
						action: 'rg_update_lead_property',
						rg_update_lead_property: '<?php echo wp_create_nonce( 'rg_update_lead_property' ) ?>',
						lead_id: entry_id,
						name: 'is_read',
						value: 1
					}
				})
						.done(function() {
							$star.data( 'is_read', update );
						})
						.fail(function() {
							alert(<?php echo json_encode( __( 'There was an error updating the entry.', 'gravityview' ) ); ?>);
						});
			});
		</script>
		<?php
	}

}

new GravityView_Field_Is_Read;
