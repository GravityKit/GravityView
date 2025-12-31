<?php

use GV\Template_Context;
use GV\View;
use GV\Widget;

/**
 * Widget to add an export link.
 *
 * @since 2.21
 */
final class GravityView_Widget_Export_Link extends Widget {
	/**
	 * @inheritDoc
	 * @since 2.21
	 */
	public $icon = 'dashicons-database-export';

	/**
	 * The widget ID.
	 *
	 * @since 2.21
	 */
	public const WIDGET_ID = 'export_link';

	/**
	 * A short description.
	 *
	 * @since 2.21
	 * @var string
	 */
	private $widget_short;

	/**
	 * Returns the settings for this widget.
	 *
	 * @since 2.21
	 * @return array[] The settings.
	 */
	private static function settings(): array {
		$defaults = self::defaults();

		return [
			'type'         => [
				'label'   => __( 'Type', 'gk-gravityview' ),
				'type'    => 'radio',
				'choices' => [
					'csv' => 'CSV',
					'tsv' => 'TSV',
				],
				'value'   => $defaults['type'],
			],
			'title'        => [
				'type'       => 'text',
				'class'      => 'widefat',
				'label'      => __( 'Label', 'gk-gravityview' ),
				'desc'       => __( 'Enter the label of the link.', 'gk-gravityview' ),
				'value'      => $defaults['title'],
				'merge_tags' => false,
			],
			'in_paragraph' => [
				'type'  => 'checkbox',
				'label' => __( 'Wrap link in paragraph', 'gk-gravityview' ),
				// translators: %s is replaced by a code block.
				'desc'  => sprintf( esc_html__( 'Will wrap the link in a paragraph HTML tag (%s).', 'gk-gravityview' ), '<code>&lt;p&gt;</code>' ),
			],
			'use_labels'   => [
				'type'  => 'checkbox',
				'label' => __( 'Use labels instead of field IDs', 'gk-gravityview' ),
				'desc'  => __( 'The headers of the file will use the labels instead of the field IDs', 'gk-gravityview' ),
				'value' => $defaults['use_labels'],
			],
			'classes'      => [
				'type'  => 'text',
				'class' => 'widefat',
				'label' => __( 'Custom CSS Class:', 'gk-gravityview' ),
				'desc'  => __( 'This class will be added to the widget container', 'gk-gravityview' ),
			],
		];
	}

	/**
	 * Returns the default settings.
	 *
	 * @since 2.21
	 */
	private static function defaults(): array {
		return [
			'title'      => __( 'Download CSV', 'gk-gravityview' ),
			'type'       => 'csv',
			'use_labels' => true,
		];
	}

	/**
	 * @inheritDoc
	 * @since 2.21
	 */
	public function __construct() {
		$this->widget_short = esc_html__( 'Insert a link to download a CSV or TSV of the current View results.', 'gk-gravityview' );
		$disabled_warning   = esc_html__( 'To use this feature, you must enable the "Allow Export" setting for this View. This setting is located in the Permissions tab of the Settings section.', 'gk-gravityview' );
		$all_entries_notice = esc_html__( 'Note: All matching entries will be downloaded in the generated file.', 'gk-gravityview' );

		$this->widget_description = <<<HTML
<p>{$this->widget_short}</p>
<p class="notice notice-alt notice-large notice-warning hidden csv-disabled-notice">{$disabled_warning}</p>
<p class="notice notice-alt notice-large notice-info">{$all_entries_notice}</p>
HTML;
		parent::__construct( 'Export Link', self::WIDGET_ID, self::defaults(), self::settings() );

		add_filter( 'gravityview_admin_label_item_info', [ $this, 'hide_description_picker' ], 10, 2 );
	}

	/**
	 * Removes the notification part from the description.
	 *
	 * @since 2.21
	 *
	 * @param array                       $items     The description items.
	 * @param GravityView_Admin_View_Item $view_item The view item.
	 *
	 * @return array The adjusted description items.
	 */
	public function hide_description_picker( array $items, GravityView_Admin_View_Item $view_item ): array {
		if (
			! $view_item instanceof GravityView_Admin_View_Widget
			|| GV\Utils::get( $items[0] ?? [], 'value' ) !== $this->widget_description
		) {
			return $items;
		}

		$items[0]['value'] = $this->widget_short;

		return $items;
	}

	/**
	 * @inheritDoc
	 * @since 2.21
	 */
	public function render_frontend( $widget_args, $content = '', $context = '' ): void {
		if (
			! $context instanceof Template_Context
			|| ! $this->pre_render_frontend( $context )
		) {
			return;
		}

		$view  = $context->view;
		$nonce = $this->get_nonce( $view );
		if (
			! $nonce
			|| ! $view->settings->get( 'csv_enable' )
		) {
			return;
		}

		$available_types = [ 'csv', 'tsv' ];
		$type            = strtolower( GV\Utils::get( $widget_args, 'type', 'csv' ) );
		if ( ! in_array( $type, $available_types, true ) ) {
			$type = 'csv';
		}
		$mime_type = 'csv' === $type ? 'text/csv' : 'text/tab-separated-values';

		$label        = GV\Utils::get( $widget_args, 'title', 'Download CSV' );
		$in_paragraph = (bool) GV\Utils::get( $widget_args, 'in_paragraph', false );
		$use_labels   = (bool) GV\Utils::get( $widget_args, 'use_labels', false );
		$classes      = (string) GV\Utils::get( $widget_args, 'classes', '' );

		$page_query_params = array_filter(
			$_GET,
			static function ( $value, string $key ): bool {
				return 'mode' === $key || preg_match( '/^(gv|filter)_?/i', $key );
			},
			ARRAY_FILTER_USE_BOTH
		);

		/**
		 * In order to provide easier JS modification of the URL, we provide both the base URL and the full URL.
		 */
		$rest_nonce_url = sprintf( '%sgravityview/v1/views/%d/entries.%s', get_rest_url(), $view->ID, $type );
		$rest_nonce_url = add_query_arg( [
			'_nonce'     => $nonce, // View-specific nonce for security.
			'_wpnonce'   => wp_create_nonce( 'wp_rest' ), // REST API authentication.
			'use_labels' => $use_labels,
		], $rest_nonce_url );

		$rest_url = add_query_arg(
			$page_query_params,
			$rest_nonce_url
		);

		$link = strtr( '<a href="{url}" data-nonce-url="{nonce_url}" download rel="nofollow" type="{mime_type}">{label}</a>', [
			'{url}'       => esc_url( $rest_url ),
			'{nonce_url}' => esc_url( $rest_nonce_url ),
			'{mime_type}' => $mime_type,
			'{label}'     => esc_html( $label ),
		] );

		$link = $in_paragraph ? sprintf( '<p>%s</p>', $link ) : $link;

		printf( '<div class="gv-widget-export-link %s">%s</div>', gravityview_sanitize_html_class( $classes ), $link );
	}

	/**
	 * Create a nonce for export verification.
	 *
	 * @since 2.21
	 *
	 * @param View|null $view The view object.
	 *
	 * @return string The nonce.
	 */
	private function get_nonce( $view ): string {
		if ( ! $view instanceof View ) {
			return '';
		}

		$nonce = wp_create_nonce( sprintf( '%s.%d', $this->get_widget_id(), $view->ID ) );

		return $nonce ?: '';
	}
}

new GravityView_Widget_Export_Link();
