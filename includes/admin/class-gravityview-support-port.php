<?php

/**
 * @since 1.15
 */
class GravityView_Support_Port {
	/**
	 * @var string The name of the User Meta option used to store whether a user wants to see the Support Port
	 * @since 1.15
	 * @since 2.16 Renamed from `user_pref_name` to `USER_PREF_NAME`
	 */
	const USER_PREF_NAME = 'gravityview_support_port';

	/**
	 * @var string Help Scout beacon key
	 *
	 * @since 2.16 Previous constant `beacon_key` was renamed to `HASH_KEY`
	 */
	const HS_BEACON_KEY = 'b4f6255a-91bc-436c-a5a2-4cca051ad00f';

	/**
	 * @var string The hash key used to generate secure message history
	 *
	 * @since 2.16
	 */
	const HASH_KEY = 'lCXlwbQR707kipR+J0MCqcxrhGOHjGF0ldD6yNbGM0w=';


	public function __construct() {
		$this->add_hooks();
	}

	/**
	 * @since 1.15
	 */
	private function add_hooks() {
		add_action( 'personal_options', [ $this, 'user_field' ] );
		add_action( 'personal_options_update', [ $this, 'update_user_meta_value' ] );
		add_action( 'edit_user_profile_update', [ $this, 'update_user_meta_value' ] );
		add_filter( 'gk/foundation/integrations/helpscout/display', [ $this, 'maybe_display_helpscout_beacon' ] );
		add_filter( 'gravityview/tooltips/tooltip', [ $this, 'maybe_add_article_to_tooltip' ], 10, 6 );
	}

	/**
	 * Modify tooltips to add Beacon article
	 *
	 * @since 2.8.1
	 *
	 * @param string $tooltip HTML of original tooltip
	 * @param array  $article   Optional. Details about support doc article connected to the tooltip. {
	 *   @type string $id   Unique ID of article for Beacon API
	 *   @type string $url  URL of support doc article
	 *   @type string $type Type of Beacon element to open. {@see https://developer.helpscout.com/beacon-2/web/javascript-api/#beaconarticle}
	 * }
	 * @param string $url
	 * @param string $atts
	 * @param string $css_class
	 * @param string $anchor_text
	 * @param string $link_text
	 *
	 * @return string If no article information exists, original tooltip. Otherwise, modified!
	 */
	public function maybe_add_article_to_tooltip( $tooltip = '', $article = [], $url = '', $atts = '', $css_class = '', $anchor_text = '' ) {
		if ( empty( $article['id'] ) ) {
			return $tooltip;
		}

		static $show_support_port;

		if ( ! isset( $show_support_port ) ) {
			$show_support_port = self::show_for_user();
		}

		if ( ! $show_support_port ) {
			return $tooltip;
		}

		$css_class .= ' gv_tooltip';

		if ( ! empty( $article['type'] ) ) {
			$atts = sprintf( 'data-beacon-article-%s="%s"', $article['type'], $article['id'] );
		} else {
			$atts = sprintf( 'data-beacon-article="%s"', $article['id'] );
		}

		$url          = \GV\Utils::get( $article, 'url', '#' );
		$anchor_text .= '<p class="description" style="font-size: 15px; text-align: center;"><strong>' . sprintf( esc_html__( 'Click %s icon for additional information.', 'gk-gravityview' ), '<i class=\'fa fa-question-circle\'></i>' ) . '</strong></p>';
		$link_text    = esc_html__( 'Learn More', 'gk-gravityview' );

		return sprintf(
            '<a href="%s" %s class="%s" title="%s" role="button">%s</a>',
			esc_url( $url ),
			$atts,
			$css_class,
			esc_attr( $anchor_text ),
			$link_text
		);
	}

	/**
	 * Conditionally displays Help Scout beacon on certain pages
	 *
	 * @since 2.16
	 *
	 * @param bool $display
	 *
	 * @return bool
	 */
	public function maybe_display_helpscout_beacon( $display ) {
		global $post;

		if ( ! is_admin() || 'gravityview' !== get_post_type( $post ) ) {
			return $display;
		}

		/**
		 * Whether to display Support Port.
		 *
		 * @since 1.15
		 *
		 * @param boolean $display_support_port Whether to display Support Port. Default: `true`.
		 */
		$display_support_port = apply_filters( 'gravityview/support_port/display', self::show_for_user() );

		if ( empty( $display_support_port ) ) {
			gravityview()->log->debug( 'Not showing Support Port' );

			return false;
		}

		add_filter(
            'gk/foundation/integrations/helpscout/configuration',
            function ( $configuration ) {
				$arr_helpers = GravityKitFoundation::helpers()->array;

				$arr_helpers->set( $configuration, 'init', self::HS_BEACON_KEY );
				$arr_helpers->set( $configuration, 'identify.signature', hash_hmac( 'sha256', $arr_helpers->get( $configuration, 'identify.email', '' ), self::HASH_KEY ) );

				/**
				 * Filter data passed to the Support Port, before localize_script is run.
				 *
				 * @since 2.0
				 * @since 2.16 Removed `contactEnabled`, `translation` and `data` keys.
				 *
				 * @param array $configuration {
				 *   @type array $suggest Article IDs to recommend to the user (per page in the admin).
				 * }
				 */
				$localized_data = apply_filters(
                    'gravityview/support_port/localization_data',
                    [
						'suggest' => $arr_helpers->get( $configuration, 'suggest', [] ),
					]
				);

				$arr_helpers->set( $configuration, 'suggest', $localized_data['suggest'] );

				return $configuration;
			}
        );

		return true;
	}

	/**
	 * Check whether to show Support for a user
	 *
	 * If the user doesn't have the `gravityview_support_port` capability, returns false; then
	 * If global setting is "hide", returns false; then
     * If user preference is not set, return global setting; then
     * If user preference is set, return that setting.
	 *
	 * @since 1.15
     * @since 1.17.5 Changed behavior to respect global setting
	 *
	 * @param int $user Optional. ID of the user to check, defaults to 0 for current user.
	 *
	 * @return bool Whether to show GravityView support port
	 */
	public static function show_for_user( $user = 0 ) {
		if ( ! GVCommon::has_cap( 'gravityview_support_port' ) ) {
			return false;
		}

		$global_setting = GravityKitFoundation::settings()->get_plugin_setting( GravityKitFoundation::ID, 'support_port' );

		if ( empty( $global_setting ) ) {
            return false;
		}

		// Get the per-user Support Port setting
		$user_pref = get_user_option( self::USER_PREF_NAME, $user );

		// Not configured; default to global setting (which is true at this point)
		if ( false === $user_pref ) {
			$user_pref = $global_setting;
		}

		return ! empty( $user_pref );
	}


	/**
	 * Update User Profile preferences for GravityView Support
	 *
	 * @since 1.5
	 *
	 * @param int $user_id
	 *
	 * @return void
	 */
	public function update_user_meta_value( $user_id ) {
		if ( current_user_can( 'edit_user', $user_id ) && isset( $_POST[ self::USER_PREF_NAME ] ) ) {
			update_user_meta( $user_id, self::USER_PREF_NAME, intval( $_POST[ self::USER_PREF_NAME ] ) );
		}
	}

	/**
	 * Modify User Profile
	 *
	 * Modifies the output of profile.php to add GravityView Support preference
	 *
	 * @since 1.15
     * @since 1.17.5 Only show if global setting is active
	 *
	 * @param WP_User $user Current user info
	 *
	 * @return void
	 */
	public function user_field( $user ) {
		$global_setting = GravityKitFoundation::settings()->get_plugin_setting( GravityKitFoundation::ID, 'support_port' );

		if ( empty( $global_setting ) ) {
            return;
		}

		/**
		 * Should the "GravityView Support Port" setting be shown on user profiles?
		 *
		 * @since 1.15
		 *
		 * @param boolean $allow_profile_setting Whether to show the setting. Default: `true`, if the user has the `gravityview_support_port` capability, which defaults to true for Contributors and higher.
		 * @param WP_User $user                  Current user object.
		 */
		$allow_profile_setting = apply_filters( 'gravityview/support_port/show_profile_setting', GVCommon::has_cap( 'gravityview_support_port' ), $user );

		if ( $allow_profile_setting && current_user_can( 'edit_user', $user->ID ) ) {
			?>
			<table class="form-table">
				<tbody>
					<tr class="user-gravityview-support-button-wrap">
						<th scope="row">
                        <?php
							/* translators: "Support Port" can be translated as "Support Portal" or "Support Window" */
							esc_html_e( 'GravityView Support Port', 'gk-gravityview' );
						?>
                        </th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span>
                                <?php
										/* translators: "Support Port" can be translated as "Support Portal" or "Support Window" */
										esc_html_e( 'GravityView Support Port', 'gk-gravityview' );
								?>
                                </span></legend>
								<label>
									<input name="<?php echo esc_attr( self::USER_PREF_NAME ); ?>" type="hidden" value="0"/>
									<input name="<?php echo esc_attr( self::USER_PREF_NAME ); ?>" type="checkbox" value="1" <?php checked( self::show_for_user( $user->ID ) ); ?> />
									<?php esc_html_e( 'Show GravityView Support Port when on a GravityView-related page', 'gk-gravityview' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
        }
	}
}

new GravityView_Support_Port();
