<?php
/**
 * Display the Search widget
 *
 * @file class-search-widget.php See for usage
 *
 * @global \GravityView_View $this
 * @global array             $data
 * @var                      $search_fields Search_Field_Collection
 */

use GV\Search\Search_Field_Collection;

$view_id            = \GV\Utils::get( $data, 'view_id', null );
$search_method      = \GV\Utils::get( $data, 'search_method', 'get' );
$search_class       = \GV\Utils::get( $data, 'search_class', '' );
$permalink_fields   = \GV\Utils::get( $data, 'permalink_fields', [] );
$search_form_action = \GV\Utils::get( $data, 'search_form_action', '' );
$search_fields      = \GV\Utils::get( $data, 'search_fields', [] );
$validation_errors  = \GV\Utils::get( $data, 'validation_errors', [] );
?>

<form class="gv-widget-search <?php echo gravityview_sanitize_html_class( $search_class ); ?>"
	  method="<?php echo $search_method; ?>" action="<?php echo esc_url( $search_form_action ); ?>"
	  data-viewid="<?php echo (int) $view_id; ?>">
	<?php
	// Display validation error summary if there are server-side validation errors.
	if ( ! empty( $validation_errors ) ) {
		?>
		<div class="gv-search-validation-summary" role="alert">
			<p><?php esc_html_e( 'Please correct the following errors:', 'gk-gravityview' ); ?></p>
			<ul>
				<?php foreach ( $validation_errors as $field_name => $message ) { ?>
					<li><?php echo esc_html( $message ); ?></li>
				<?php } ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Inside the `<form>` tag of the GravityView search form, before inputs are rendered.
	 *
	 * @since 1.2
	 *
	 * @param GravityView_Widget_Search $this GravityView Widget instance.
	 */
	do_action( 'gravityview_search_widget_fields_before', $this );
	?>

	<div class="gv-widget-search-general-search gv-grid">
		<?php
		$data['section'] = 'search-general';
		$this->render( 'search', 'fields', false, $data );
		?>
	</div>

	<?php
	$advanced_collection = $search_fields->by_position( 'search-advanced*' );
	if ( $advanced_collection->has_visible_fields( true ) ) {
		$has_active_fields = $advanced_collection->has_request_values();

		$expanded = $has_active_fields ? 'true' : 'false';
		$open     = $has_active_fields ? ' gv-search-advanced--open' : '';
		?>
		<a
			id="gv-search-advanced-toggle"
			href="javascript:void(0);"
			role="button"
			aria-expanded="<?php echo esc_attr( $expanded ); ?>"
			aria-controls="gv-search-advanced"
			aria-label="<?php esc_attr_e( 'Toggle Advanced Search', 'gk-gravityview' ); ?>"
		>
			<span aria-hidden="true"><?php esc_html_e( 'Advanced Search', 'gk-gravityview' ); ?></span>
		</a>

		<div id="gv-search-advanced" class="gv-widget-search-advanced-search gv-grid<?php echo esc_attr( $open ); ?>">
			<?php
			/**
			 * Inside the `<form>` tag of the GravityView search form, before advanced inputs are rendered.
			 *
			 * @since 2.42
			 *
			 * @param GravityView_Widget_Search $this GravityView Widget instance.
			 */
			do_action( 'gravityview_search_widget_advanced_fields_before', $this );

			$data['section'] = 'search-advanced';
			$this->render( 'search', 'fields', false, $data );

			/**
			 * Inside the `<form>` tag of the GravityView search form, after advanced inputs are rendered.
			 *
			 * @since 2.42
			 *
			 * @param GravityView_Widget_Search $this GravityView Widget instance.
			 */
			do_action( 'gravityview_search_widget_advanced_fields_after', $this );
			?>
		</div>
	<?php } ?>

	<?php
	foreach ( $permalink_fields as $search_field ) {
		/**
		 * Before each search input is rendered.
		 *
		 * @since 2.0.6.1
		 *
		 * @param GravityView_Widget_Search                                             $this         GravityView Widget instance.
		 * @param array{key:string,label:string,value:string,type:string,choices:array} $search_field Search field configuration.
		 */
		do_action( 'gravityview_search_widget_field_before', $this, $search_field );

		$data['search_field'] = $search_field;

		$this->render( 'search-field', $search_field['input'], false, $data );

		/**
		 * After each search input is rendered.
		 *
		 * @since 2.0.6.1
		 *
		 * @param GravityView_Widget_Search $this         GravityView Widget instance.
		 * @param array                     $search_field Search field configuration.
		 */
		do_action( 'gravityview_search_widget_field_after', $this, $search_field );
	}

	/**
	 * Inside the `<form>` tag of the GravityView search form, after inputs are rendered.
	 *
	 * @since 1.2
	 *
	 * @param GravityView_Widget_Search $this GravityView Widget instance.
	 */
	do_action( 'gravityview_search_widget_fields_after', $this );
	?>
</form>
