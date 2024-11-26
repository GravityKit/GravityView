<?php
/**
 * The default date created field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

if ( ! class_exists( 'gf_user_registration' ) ) {
	echo __( 'Install/activate Gravity Forms User Registration Add-On', 'gk-gravityview' );
	return;
}


require_once gf_user_registration()->get_base_path() . '/includes/signups.php';

$entry = $gravityview->entry->as_entry();
if ( ! GravityView_Field_User_Activation::check_if_feeds_are_valid( $entry['form_id'] ) ) {
	echo __( 'No feeds are found or feeds are not set to manual activation', 'gk-gravityview' );
	return;
}

if ( ! class_exists( 'GFUserSignups' ) ) {
	echo __( 'GFUserSignups class does not exist', 'gk-gravityview' );
	return;
}

$user_exist = GravityView_Field_User_Activation::check_if_user_exist( $gravityview->view->form, $entry );
if ( $user_exist ) {
	echo  __( 'The user is already active', 'gk-gravityview' );
	return;
}

$activation_key  = GFUserSignups::get_lead_activation_key( $entry['id'] );
$user_activation = GravityView_Field_User_Activation::check_activation_key( $activation_key );
if ( is_wp_error( $user_activation ) ) {
	echo $user_activation->get_error_message();
	return;
}


do_action( 'gravityview/field/user_activation/load_scripts', $gravityview );

?>

<a href="#" activation-key="<?php echo $activation_key; ?>" class="button gv-user-activation-link" style="cursor: pointer;">
	<?php esc_attr_e( 'Activate User', 'gk-gravityview' ); ?>
</a>
