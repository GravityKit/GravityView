<?php

/**
 * Actions to be performed on the Gravity Forms Entries list screen
 */
class GravityView_GF_Entries_List {

	function __construct() {

		// Add Edit link to the entry actions
		add_action( 'gform_entries_first_column_actions', array( $this, 'add_edit_link' ), 10, 5 );

		// Add script to enable edit link
		add_action( 'admin_head-forms_page_gf_entries', array( $this, 'add_edit_script') );

	}

	/**
	 * When clicking the edit link, convert the Entries form to go to the edit screen.
	 *
	 * Gravity Forms requires $_POST['screen_mode'] to be set to get to the "Edit" mode. This enables direct access to the edit mode.
	 *
	 * @hack
	 * @return void
	 */
	public function add_edit_script() {

		// We're on a single entry page, or at least not the Entries page.
		if( !empty( $_GET['view'] ) && $_GET['view'] !== 'entries' ) { return; }
	?>
		<script>
		jQuery( document ).ready( function( $ ) {
			$('.edit_entry a').click(function(e) {
				e.preventDefault();
				$( e.target ).parents('form')
					.prepend('<input name="screen_mode" type="hidden" value="edit" />')
					.attr('action', $(e.target).attr('href') )
					.submit();
			});
		});
		</script>
	<?php
	}

	/**
	 * Add an Edit link to the GF Entry actions row
	 * @param int $form_id      ID of the current form
	 * @param int $field_id     The ID of the field in the first column, where the row actions are shown
	 * @param string $value        The value of the `$field_id` field
	 * @param array  $lead         The current entry data
	 * @param string $query_string URL query string for a link to the current entry. Missing the `?page=` part, which is strange. Example: `gf_entries&view=entry&id=35&lid=5212&filter=&paged=1`
	 */
	function add_edit_link( $form_id = NULL, $field_id = NULL, $value = NULL, $lead = array(), $query_string = NULL ) {

		$params = array(
			'page' => 'gf_entries',
			'view' => 'entry',
			'id'	=> (int)$form_id,
			'lid'	=>	(int)$lead["id"],
			'screen_mode'	=> 'edit',
		);
		?>

		<span class="edit edit_entry">
			|
		    <a title="<?php esc_attr_e( 'Edit this entry', 'gravityview'); ?>" href="<?php echo esc_url( add_query_arg( $params, admin_url( 'admin.php?page='.$query_string ) ) ); ?>"><?php esc_html_e( 'Edit', 'gravityview' ); ?></a>
		</span>
		<?php
	}

}

new GravityView_GF_Entries_List;
