<?php
/**
 * GravityView Error Message Handler
 *
 * Provides capability-based error messages with selective differentiation
 * for security-safe scenarios while maintaining defense in depth for
 * enumeration-prone errors.
 *
 * @since 2.50.0
 * @package GravityView
 */

/**
 * Error message handler class
 *
 * @since 2.50.0
 */
class GravityView_Error_Messages {

	/**
	 * View-level errors - safe to differentiate for administrators.
	 * 
	 * These errors reveal VIEW configuration, not entry existence.
	 * Users with 'edit_gravityview' see actionable, detailed messages.
	 *
	 * @since 2.50.0
	 * @var array
	 */
	private static $view_errors = [
		'embed_only',
		'no_direct_access',
		'not_public',
		'rest_disabled',
		'csv_disabled',
		'no_form_attached',
	];

	/**
	 * Entry moderation errors - checked against moderation capability.
	 * 
	 * Users with 'gravityview_moderate_entries' may see specific messages.
	 * Currently generic, but structured separately for future expansion.
	 *
	 * @since 2.50.0
	 * @var array
	 */
	private static $entry_moderation_errors = [
		'entry_not_approved',
	];

	/**
	 * Entry existence errors - MUST always stay generic for security.
	 * 
	 * These errors reveal entry existence/state and pose enumeration risks.
	 * Always returns generic message, even for administrators, to prevent
	 * information disclosure attacks.
	 *
	 * @since 2.50.0
	 * @var array
	 */
	private static $entry_existence_errors = [
		'entry_not_found',
		'entry_form_mismatch',
		'entry_slug_mismatch',
		'entry_not_active',
	];

	/**
	 * Get appropriate error message based on error code and capability.
	 *
	 * @since 2.50.0
	 *
	 * @param string         $error_code Error code from WP_Error
	 * @param GV\View|null   $view       GV\View object
	 * @param string         $context    Optional context (shortcode, oembed, rest)
	 * @param GV\Entry|null  $entry      GV\Entry object
	 *
	 * @return string Formatted error message
	 */
	public static function get( $error_code, $view = null, $context = 'shortcode', $entry = null ) {

		// Allow passing a WP_Error object directly.
		if ( is_wp_error( $error_code ) ) {
			$error_code = $error_code->get_error_code();
		}

		// Normalize error code.
		$error_code = str_replace( 'gravityview/', '', $error_code );

		// Normalize entry ID for logging (callers may pass object, array, or numeric).
		$entry_id = '(not set)';
		if ( is_object( $entry ) && isset( $entry->ID ) ) {
			$entry_id = $entry->ID;
		} elseif ( is_array( $entry ) && ( isset( $entry['ID'] ) || isset( $entry['id'] ) ) ) {
			$entry_id = $entry['ID'] ?? $entry['id'];
		} elseif ( is_numeric( $entry ) ) {
			$entry_id = $entry;
		}

		// Log the error.
		gravityview()->log->debug(
			'Access denied: {error} | View: {view_id} | Context: {context}',
			[
				'error'    => $error_code,
				'view_id'  => $view ? $view->ID : '(not set)',
				'entry_id' => $entry_id,
				'context'  => $context,
			]
		);

		// Check if user can view detailed messages
		if ( ! self::can_view_detailed_message( $error_code, $view ) ) {
			return self::get_generic_message();
		}

		// Generate detailed message for privileged users
		return self::get_detailed_message( $error_code, $view, $context );
	}

	/**
	 * Check if the current user can view detailed error messages.
	 *
	 * @since 2.50.0
	 *
	 * @param string      $error_code Normalized error code.
	 * @param GV\View|null $view       View object.
	 * @return bool True if user can view detailed messages.
	 */
	private static function can_view_detailed_message( $error_code, $view ) {
		// View errors - requires edit_gravityview capability
		if ( in_array( $error_code, self::$view_errors, true ) ) {
			return $view && GVCommon::has_cap( 'edit_gravityview', $view->ID );
		}

		// Entry moderation errors - requires gravityview_moderate_entries capability
		if ( in_array( $error_code, self::$entry_moderation_errors, true ) ) {
			return GVCommon::has_cap( 'gravityview_moderate_entries', $view ? $view->ID : 0 );
		}

		// Entry existence errors - NEVER show details (enumeration risk)
		if ( in_array( $error_code, self::$entry_existence_errors, true ) ) {
			return false;
		}

		// Unknown errors - no details without edit capability
		return $view && GVCommon::has_cap( 'edit_gravityview', $view->ID );
	}

	/**
	 * Get detailed error message for privileged users.
	 *
	 * @since 2.50.0
	 *
	 * @param string     $error_code Normalized error code.
	 * @param GV\View    $view       View object.
	 * @param string     $context    Context (shortcode, oembed, rest).
	 * @return string Formatted error message.
	 */
	private static function get_detailed_message( $error_code, $view, $context ) {
		$link_template = self::get_link_template( $view, $context );

		switch ( $error_code ) {
			case 'embed_only':
				return strtr(
					// translators: [action]...[/action] wraps the edit link, [learn]...[/learn] wraps the documentation link
					esc_html__( 'This View is set to "Embed Only" and cannot be accessed directly. [action]Change this setting[/action] or [learn]learn more[/learn].', 'gk-gravityview' ),
					$link_template
				);

			case 'no_direct_access':
				return strtr(
					// translators: [learn]...[/learn] wraps the documentation link
					esc_html__( 'Direct access to this View has been disabled by the gravityview_direct_access filter. [learn]Learn about this filter[/learn].', 'gk-gravityview' ),
					$link_template
				);

			case 'not_public':
				$status = get_post_status( $view->ID );
				return strtr(
					// translators: %s is the post status (e.g., "draft"), [action]...[/action] wraps the edit link, [learn]...[/learn] wraps the documentation link
					sprintf( esc_html__( 'This View is %s and not publicly visible. [action]Change the publishing status[/action] or [learn]learn about View visibility[/learn].', 'gk-gravityview' ), '<strong>' . esc_html( $status ) . '</strong>' ),
					$link_template
				);

			case 'rest_disabled':
				return strtr(
					// translators: [action]...[/action] wraps the edit link, [learn]...[/learn] wraps the documentation link
					esc_html__( 'REST API access is disabled for this View. [action]Enable REST API access[/action] or [learn]learn more[/learn].', 'gk-gravityview' ),
					$link_template
				);

			case 'csv_disabled':
				return strtr(
					// translators: [action]...[/action] wraps the edit link, [learn]...[/learn] wraps the documentation link
					esc_html__( 'CSV export is disabled for this View. [action]Enable CSV export[/action] or [learn]learn more[/learn].', 'gk-gravityview' ),
					$link_template
				);

			case 'no_form_attached':
				/**
				 * This View has no data source. There's nothing to show really.
				 * ...apart from a nice message if the user can do anything about it.
				 */
				if ( GVCommon::has_cap( array( 'edit_gravityviews', 'edit_gravityview' ), $view->ID ) ) {
					return wp_kses_post( sprintf( __( 'This View is not configured properly. Start by <a href="%s">selecting a form</a>.', 'gk-gravityview' ), esc_url( get_edit_post_link( $view->ID, false ) ) ) );
				}

				return strtr(
					// translators: [action]...[/action] wraps the edit link, [learn]...[/learn] wraps the documentation link
					esc_html__( 'This View has no Gravity Forms form attached. [action]Configure the data source[/action] or [learn]learn more[/learn].', 'gk-gravityview' ),
					$link_template
				);
		}

		return self::get_generic_message();
	}

	/**
	 * Build link template for message formatting.
	 *
	 * @since 2.50.0
	 *
	 * @param GV\View $view    View object.
	 * @param string  $context Context (shortcode, oembed, rest).
	 * @return array Template replacements for strtr().
	 */
	private static function get_link_template( $view, $context ) {
		// Entry moderation errors may not have a view context
		if ( ! $view ) {
			return [
				'[action]'  => '',
				'[/action]' => '',
				'[learn]'   => '',
				'[/learn]'  => '',
			];
		}

		$edit_url  = get_edit_post_link( $view->ID );
		$docs_link = self::get_docs_link();

		if ( 'rest' === $context ) {
			return [
				'[action]'  => '',
				'[/action]' => ' (' . esc_url( $edit_url ) . ')',
				'[learn]'   => '',
				'[/learn]'  => ' (' . esc_url( $docs_link ) . ')',
			];
		}

		return [
			'[action]'  => '<a href="' . esc_url( $edit_url ) . '">',
			'[/action]' => '</a>',
			'[learn]'   => '<a href="' . esc_url( $docs_link ) . '">',
			'[/learn]'  => '</a>',
		];
	}

	/**
	 * Get generic fallback message.
	 *
	 * @since 2.50.0
	 *
	 * @return string Generic error message.
	 */
	private static function get_generic_message() {
		return __( 'You are not allowed to view this content.', 'gk-gravityview' );
	}

	/**
	 * Get documentation link for specific error.
	 *
	 * @since 2.50.0
	 *
	 * @return string Documentation URL.
	 */
	private static function get_docs_link() {
		return 'https://docs.gravitykit.com/article/486-you-are-not-allowed-to-view-this-content';
	}
}
