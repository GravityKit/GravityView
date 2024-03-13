<?php

use GV\Template_Context;
use GV\View;
use GV\Widget;

/**
 * Widget to add an export link.
 *
 * @since $ver$
 */
final class GravityView_Widget_Export_Link extends Widget {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public $icon = 'dashicons-database-export';

	/**
	 * The widget ID.
	 *
	 * @since $ver$
	 */
	public const WIDGET_ID = 'export_link';

	/**
	 * A short description.
	 *
	 * @since $ver$
	 * @var string
	 */
	private $widget_short;

	/**
	 * Returns the settings for this widget.
	 *
	 * @since $ver$
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
				// translators: %s is replaced by a code block.
				'desc'  => sprintf( esc_html__( 'These classes will be added to the anchor tag (%s).', 'gk-gravityview' ), '<code>&lt;a&gt;</code>' ),
			],
		];
	}

	/**
	 * Returns the default settings.
	 *
	 * @since $ver$
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
	 * @since $ver$
	 */
	public function __construct() {
		$this->widget_short       = esc_html__( 'Insert a button to a CSV / TSV download.', 'gk-gravityview' );
		$disabled_warning = esc_html__( 'In order to use this feature you need to Allow Export.', 'gk-gravityview' );
		$all_entries_notice = esc_html__( 'Note: all matching entries will be downloaded.', 'gk-gravityview' );

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
	 * @since $ver$
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
	 * @since $ver$
	 */
	public function render_frontend( $widget_args, $content = '', $context = '' ): void {
		global $wp_query;

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
				return 'mode' === $key || preg_match( '/^filter_?/i', $key );
			},
			ARRAY_FILTER_USE_BOTH
		);

		$rest_url = add_query_arg(
			array_filter(
				array_merge(
					$page_query_params,
					[
						'_nonce'     => $nonce,
						'use_labels' => $use_labels,
					]
				)
			),
			sprintf( '%sgravityview/v1/views/%d/entries.%s', get_rest_url(), $view->ID, $type )
		);

		$link = strtr( '<a href="{url}" download rel="nofollow" class="{classes}" type="{mime_type}">{label}</a>', [
			'{url}'       => esc_url( $rest_url ),
			'{classes}'   => gravityview_sanitize_html_class( $classes ),
			'{mime_type}' => "text/{$mime_type}",
			'{label}'     => esc_html( $label ),
		] );

		$in_paragraph
			? printf( '<p>%s</p>', $link )
			: print( $link );
	}

	/**
	 * Create a nonce for a guest, as the REST API is stateless.
	 *
	 * @since $ver$
	 *
	 * @param View|null $view The view object.
	 *
	 * @return string The nonce.
	 */
	private function get_nonce( $view ): string {
		if ( ! $view instanceof View ) {
			return '';
		}

		$user_id = wp_get_current_user()->ID;
		if ( $user_id ) {
			wp_set_current_user( 0 );
		}

		$nonce = wp_create_nonce( sprintf( '%s.%d', $this->get_widget_id(), $view->ID ) );

		if ( $user_id ) {
			wp_set_current_user( $user_id );
		}

		return $nonce ?: '';
	}
}

new GravityView_Widget_Export_Link();
