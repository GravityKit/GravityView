<?php

/**
 * Represents a placeholder object for a GravityView Plugin.
 * @since $ver$
 */
final class GravityView_Object_Placeholder {
	/**
	 * The placeholder types.
	 *
	 * @since $ver$
	 */
	private const TYPE_INLINE = 'inline';
	private const TYPE_CARD = 'card';

	/**
	 * The title on the placeholder.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	public $title;

	/**
	 * The description on the placeholder.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	public $description;

	/**
	 * The icon on the placeholder.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	public $icon;

	/**
	 * The plugin base name.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	public $plugin_basename;

	/**
	 * The link to buy the plugin.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	public $buy_now_link;

	/**
	 * The placeholder type.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Microcache for the plugin status.
	 *
	 * @since $ver$
	 *
	 * @var string|null
	 */
	private $status;

	/**
	 * Creates the value object.
	 * @since $ver$
	 */
	private function __construct(
		string $title,
		string $description,
		string $icon,
		string $plugin_basename,
		string $buy_now_link = ''
	) {
		$this->title           = $title;
		$this->description     = $description;
		$this->icon            = $icon;
		$this->plugin_basename = $plugin_basename;
		$this->buy_now_link    = $buy_now_link;
	}

	/**
	 * Create a placeholder of the "inline" type.
	 *
	 * @param string $title           The title.
	 * @param string $description     The description.
	 * @param string $icon            The icon.
	 * @param string $plugin_basename The plugin base name.
	 * @param string $buy_now_link    The (optional)) buy_now_link.
	 *
	 * @return self The placeholder object.
	 */
	public static function inline(
		string $title,
		string $description,
		string $icon,
		string $plugin_basename,
		string $buy_now_link = ''
	): self {

		$placeholder       = new self( $title, $description, $icon, $plugin_basename, $buy_now_link );
		$placeholder->type = self::TYPE_INLINE;

		return $placeholder;
	}

	/**
	 * Create a placeholder of the "card" type.
	 *
	 * @param string $title           The title.
	 * @param string $description     The description.
	 * @param string $icon            The icon.
	 * @param string $plugin_basename The plugin base name.
	 * @param string $buy_now_link    The (optional)) buy_now_link.
	 *
	 * @return self The placeholder object.
	 */
	public static function card(
		string $title,
		string $description,
		string $icon,
		string $plugin_basename,
		string $buy_now_link = ''
	): self {

		$placeholder       = new self( $title, $description, $icon, $plugin_basename, $buy_now_link );
		$placeholder->type = self::TYPE_CARD;

		return $placeholder;
	}

	/**
	 * Returns whether the plugin is included in one of the licences.
	 * @since $ver$
	 * @return bool Whether the plugin is included in one of the licences.
	 */
	private function is_included(): bool {
		// TODO: Add check to see if license includes the plugin.
		return true;
	}

	/**
	 * Returns the status for the plugin.
	 * @since $ver$
	 * @return string|null The status for the plugin.
	 */
	private function get_status(): ?string {
		if ( null === $this->status ) {
			$this->status = GravityView_Compatibility::get_plugin_status( $this->plugin_basename );
		}

		if ( ! is_string( $this->status ) ) {
			return null;
		}

		return $this->status;
	}

	/**
	 * Renders the placeholder using placeholder template.
	 * @since $ver$
	 * @return void
	 */
	public function render(): void {
		$plugin_basename = $this->plugin_basename;

		if ( 'active' === $this->get_status() ) {
			return;
		}

		if ( 'inactive' === $this->get_status() ) {
			$caps        = 'activate_plugins';
			$button_text = __( 'Activate Now', 'gk-gravityview' );
			$button_href = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $plugin_basename ), 'activate-plugin_' . $plugin_basename );
		} elseif ( null === $this->get_status() && $this->is_included() ) {
			$caps        = 'install_plugins';
			$button_text = __( 'Install & Activate', 'gk-gravityview' );
			$button_href = '#';
		} else {
			$caps        = 'read';
			$button_text = __( 'Buy Now', 'gk-gravityview' );
			$button_href = $this->buy_now_link;
		}

		$params = compact( 'caps', 'button_href', 'button_text' );
		$params = array_merge( $params, [
			'type'         => (string) $this->type,
			'icon'         => (string) $this->icon,
			'title'        => (string) $this->title,
			'description'  => (string) $this->description,
			'buy_now_link' => (string) $this->buy_now_link,
		] );

		// Render the template in a scoped function.
		( static function () use ( $params ) {
			extract( $params );
			require GRAVITYVIEW_DIR . 'includes/admin/metaboxes/views/placeholder.php';
		} )();
	}
}
