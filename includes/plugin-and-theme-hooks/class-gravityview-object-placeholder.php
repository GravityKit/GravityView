<?php

use GravityKit\GravityView\Foundation\Helpers\Core;

/**
 * Represents a placeholder object for a GravityView plugin.
 *
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
	 * The plugin statuses.
	 *
	 * @since $ver$
	 */
	private const STATUS_NOT_INSTALLED = 0;
	private const STATUS_INACTIVE = 1;
	private const STATUS_ACTIVE = 2;

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
	public $text_domain;

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
	 * Microcache for the plugin information.
	 *
	 * @since $ver$
	 *
	 * @var array|null
	 */
	private $plugin;

	/**
	 * Creates the value object.
	 *
	 * @since $ver$
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
	 * @since $ver$
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
	 * @since $ver$
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
	 * @since $ver$
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
		$products        = $product_manager->get_products_data();

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
	 * @since $ver$
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
	 * @since $ver$
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
	 * @since $ver$
	 */
	public function render(): void {
		if ( self::STATUS_ACTIVE === $this->get_status() ) {
			return;
		}

		$attributes = [ 'data-text-domain' => $this->text_domain ];
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
			$button_href               = $this->buy_now_link;
		} else {
			$caps        = 'read';
			$button_text = __( 'Buy Now', 'gk-gravityview' );
			$button_href = $this->buy_now_link;
		}

		$params = compact( 'caps', 'button_href', 'button_text', 'attributes' );
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
