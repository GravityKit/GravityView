<?php

use GravityKit\GravityView\Foundation\Helpers\Core;

/**
 * Represents a placeholder object for a GravityView plugin.
 *
 * @since 2.26
 */
final class GravityView_Object_Placeholder {
	/**
	 * The placeholder types.
	 *
	 * @since 2.26
	 */
	private const TYPE_INLINE = 'inline';
	private const TYPE_CARD = 'card';

	/**
	 * The plugin statuses.
	 *
	 * @since 2.26
	 */
	private const STATUS_NOT_INSTALLED = 0;
	private const STATUS_INACTIVE = 1;
	private const STATUS_ACTIVE = 2;

	/**
	 * The title on the placeholder.
	 *
	 * @since 2.26
	 *
	 * @var string
	 */
	public $title;

	/**
	 * The description on the placeholder.
	 *
	 * @since 2.26
	 *
	 * @var string
	 */
	public $description;

	/**
	 * The icon on the placeholder.
	 *
	 * @since 2.26
	 *
	 * @var string
	 */
	public $icon;

	/**
	 * The plugin base name.
	 *
	 * @since 2.26
	 *
	 * @var string
	 */
	public $text_domain;

	/**
	 * The link to buy the plugin.
	 *
	 * @since 2.26
	 *
	 * @var string
	 */
	public $buy_now_link;

	/**
	 * The placeholder type.
	 *
	 * @since 2.26
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Microcache for the plugin information.
	 *
	 * @since 2.26
	 *
	 * @var array|null
	 */
	private $plugin;

	/**
	 * Creates the value object.
	 *
	 * @since 2.26
	 */
	private function __construct(
		string $title,
		string $description,
		string $icon,
		string $text_domain,
		string $buy_now_link = ''
	) {
		$this->title        = $title;
		$this->description  = $description;
		$this->icon         = $icon;
		$this->text_domain  = $text_domain;
		$this->buy_now_link = $buy_now_link;
	}

	/**
	 * Create a placeholder of the "inline" type.
	 *
	 * @since 2.26
	 *
	 * @param string $title        The title.
	 * @param string $description  The description.
	 * @param string $icon         The icon.
	 * @param string $text_domain  The plugin text domain.
	 * @param string $buy_now_link The (optional)) buy_now_link.
	 *
	 * @return self The placeholder object.
	 */
	public static function inline(
		string $title,
		string $description,
		string $icon,
		string $text_domain,
		string $buy_now_link = ''
	): self {

		$placeholder       = new self( $title, $description, $icon, $text_domain, $buy_now_link );
		$placeholder->type = self::TYPE_INLINE;

		return $placeholder;
	}

	/**
	 * Create a placeholder of the "card" type.
	 *
	 * @since 2.26
	 *
	 * @param string $title        The title.
	 * @param string $description  The description.
	 * @param string $icon         The icon.
	 * @param string $text_domain  The plugin text domain.
	 * @param string $buy_now_link The (optional)) buy_now_link.
	 *
	 * @return self The placeholder object.
	 */
	public static function card(
		string $title,
		string $description,
		string $icon,
		string $text_domain,
		string $buy_now_link = ''
	): self {

		$placeholder       = new self( $title, $description, $icon, $text_domain, $buy_now_link );
		$placeholder->type = self::TYPE_CARD;

		return $placeholder;
	}

	/**
	 * Returns whether the plugin is included in one of the licences.
	 *
	 * @since 2.26
	 *
	 * @throws Exception
	 *
	 * @return bool Whether the plugin is included in one of the licences.
	 */
	private function is_included(): bool {
		/**
		 * @var $product_manager \GravityKit\GravityView\Foundation\Licenses\ProductManager
		 */
		$product_manager = GravityKitFoundation::licenses()->product_manager();
		$products        = $product_manager ? $product_manager->get_products_data() : [];

		$product = $products[ $this->text_domain ] ?? null;
		if ( ! $product ) {
			return false;
		}

		// There is license for this product.
		return count( $product['licenses'] ?? [] ) > 0;
	}

	/**
	 * Returns the plugin info.
	 *
	 * @since 2.26
	 *
	 * @return array
	 */
	private function get_plugin(): array {
		if ( null !== $this->plugin ) {
			return $this->plugin;
		}

		$this->plugin = Core::get_installed_plugin_by_text_domain( $this->text_domain ) ?? [];

		return $this->plugin;
	}

	/**
	 * Returns the status for the plugin.
	 *
	 * @since 2.26
	 *
	 * @return int The status for the plugin.
	 */
	private function get_status(): int {
		$plugin = $this->get_plugin();

		if ( ! $plugin ) {
			return self::STATUS_NOT_INSTALLED;
		}

		return $plugin['active'] ? self::STATUS_ACTIVE : self::STATUS_INACTIVE;
	}

	/**
	 * Renders the placeholder using placeholder template.
	 *
	 * @since 2.26
	 */
	public function render(): void {
		if ( self::STATUS_ACTIVE === $this->get_status() ) {
			return;
		}

		$attributes = [ 'data-text-domain' => $this->text_domain ];

		$buy_now_link = $this->get_buy_now_link_with_utms();

		if ( self::STATUS_INACTIVE === $this->get_status() ) {
			$plugin          = $this->get_plugin();
			$plugin_basename = $plugin['path'] ?? '';

			$caps                      = 'activate_plugins';
			$attributes['data-action'] = 'activate';
			$button_text               = __( 'Activate Now', 'gk-gravityview' );
			$button_href               = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $plugin_basename ), 'activate-plugin_' . $plugin_basename );
		} elseif ( self::STATUS_NOT_INSTALLED === $this->get_status() && $this->is_included() ) {
			$caps                      = 'install_plugins';
			$attributes['data-action'] = 'install';
			$button_text               = __( 'Install & Activate', 'gk-gravityview' );
			$button_href               = $buy_now_link;
		} else {
			$caps        = 'read';
			$button_text = __( 'Buy Now', 'gk-gravityview' );
			$button_href = $buy_now_link;
		}

		$params = compact( 'caps', 'button_href', 'button_text', 'attributes', 'buy_now_link' );
		$params = array_merge( $params, [
			'type'         => (string) $this->type,
			'icon'         => (string) $this->icon,
			'title'        => (string) $this->title,
			'description'  => (string) $this->description,
		] );

		// Render the template in a scoped function.
		( static function () use ( $params ) {
			extract( $params );
			require GRAVITYVIEW_DIR . 'includes/admin/metaboxes/views/placeholder.php';
		} )();
	}

	/**
	 * Returns the Buy Now link with UTM parameters added.
	 *
	 * @since 2.27
	 *
	 * @return string The buy now link.
	 */
	private function get_buy_now_link_with_utms(): string {
		return add_query_arg( [
			'utm_source'   => 'plugin',
			'utm_medium'   => 'buy_now',
			'utm_campaign' => 'placeholders',
			'utm_term'     => $this->text_domain,
		], $this->buy_now_link );
	}
}
