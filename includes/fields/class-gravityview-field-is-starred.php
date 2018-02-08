<?php
/**
 * @file class-gravityview-field-is-starred.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Is_Starred extends GravityView_Field {

	var $name = 'is_starred';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot' );

	var $group = 'meta';

	var $contexts = array( 'single', 'multiple', 'export' );

	private static $has_star_field = false;

	/**
	 * GravityView_Field_Date_Created constructor.
	 */
	public function __construct() {

		$this->label = esc_html__( 'Entry Star', 'gravityview' );
		$this->default_search_label = __( 'Is Starred', 'gravityview' );
		$this->description = esc_html__( 'Display the entry\'s "star" status.', 'gravityview' );


		$this->add_hooks();

		parent::__construct();
	}

	private function add_hooks() {
		add_filter( 'gravityview_field_entry_value_' . $this->name . '_pre_link', array( $this, 'get_content' ), 10, 4 );
		add_action( 'gravityview_after', array( $this, 'print_script') );
		add_filter( 'gravityview_entry_default_fields', array( $this, 'add_default_field' ), 10, 3 );
	}

	/**
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
	 * Show the star image
	 *
	 * @since 2.0
	 *
	 * @param string $output HTML value output
	 * @param array  $entry The GF entry array
	 * @param array  $field_settings Settings for the particular GV field
	 * @param array  $field Current field being displayed
	 *
	 * @return string Image of the star
	 */
	public function get_content( $output = '', $entry = array(), $field_settings = array(), $field = array() ) {

	    $star_url = GFCommon::get_base_url() .'/images/star' . intval( $entry['is_starred'] ) .'.png';

		$entry_id = '';

		if ( GravityView_Roles_Capabilities::has_cap( 'gravityview_edit_entries' ) ) {
			$entry_id = "data-entry-id='{$entry['id']}'";
		}

		// if( $show_as_star )
		$output = '<img class="gv-star-image" '.$entry_id.' data-is_starred="'. intval( $entry['is_starred'] ) .'" src="'. esc_attr( $star_url ) .'" />';

		self::$has_star_field = true;

		return $output;
	}

	/**
	 * Add JS to the bottom of the View if there is a star field and user has `gravityview_edit_entries` cap
     *
     * @since 2.0
     *
     * @return void
	 */
	public function print_script() {

		if( ! self::$has_star_field ) {
			return;
		}

		if ( ! GravityView_Roles_Capabilities::has_cap( 'gravityview_edit_entries' ) ) {
            return;
		}

		?>
<script>
	jQuery( document ).ready( function ( $ ) {
		$('[class*=is_starred] img.gv-star-image[data-entry-id]').on('click', function() {

			var is_starred = $(this).data('is_starred'),
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
					name: 'is_starred',
					value: update
				}
			})
            .done(function() {
                $star
                    .attr('src', $star.attr('src').replace( "star" + is_starred + ".png", "star" + update + ".png" ) )
                    .data( 'is_starred', update );
			})
            .fail(function() {
                alert(<?php echo json_encode( __( 'There was an error updating the entry.', 'gravityview' ) ); ?>);
            });
		});
	});
</script>
<?php
	}

}

new GravityView_Field_Is_Starred;