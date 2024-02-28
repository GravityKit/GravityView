<?php

use GV\Template_Context;
use GV\View;
use GV\Widget;

/**
 * Widget to add a CSV download link.
 *
 * @since $ver$
 */
final class GravityView_Widget_Csv_Link extends Widget {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public $icon = 'dashicons-database-export';

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected $show_on_single = false;

	/**
	 * Returns the settings for this widget.
	 *
	 * @since $ver$
	 * @return array[] The settings.
	 */
	private static function settings(): array {
		return [
			'type'         => [
				'label'   => __( 'Type', 'gk-gravityview' ),
				'type'    => 'radio',
				'choices' => [
					'csv' => 'CSV',
					'tsv' => 'TSV',
				],
			],
			'title'        => [
				'type'       => 'text',
				'class'      => 'widefat',
				'label'      => __( 'Label', 'gk-gravityview' ),
				'desc'       => __( 'Enter the label of the link.', 'gk-gravityview' ),
				'value'      => '',
				'merge_tags' => false,
				'required'   => true,
			],
			'in_paragraph' => [
				'type'  => 'checkbox',
				'label' => __( 'Wrap link in paragraph', 'gk-gravityview' ),
				'desc'  => __( 'Will wrap the link in a <code>&lt;p&gt;</code> tag.', 'gk-gravityview' ),
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
			'title' => __( 'Download CSV', 'gk-gravityview' ),
			'type'  => 'csv',
		];
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function __construct() {
		$this->widget_description = sprintf(
			'<p>%s</p><p class="notice notice-alt notice-large notice-warning hidden csv-disabled-notice">%s</p>',
			esc_html__( 'Insert a button to a CSV / TSV download.', 'gk-gravityview' ),
			esc_html__( 'In order to use this feature you need to Allow Export.', 'gk-gravityview' )
		);

		parent::__construct( 'CSV download button', 'csv_link', self::defaults(), self::settings() );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
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
		$type            = strtolower( rgar( $widget_args, 'type', 'csv' ) );
		if ( ! in_array( $type, $available_types, true ) ) {
			$type = 'csv';
		}

		$label        = rgar( $widget_args, 'title', 'Download CSV' );
		$in_paragraph = (bool) rgar( $widget_args, 'in_paragraph', false );

		$rest_url = add_query_arg(
			[ '_nonce' => $nonce ],
			sprintf( '%sgravityview/v1/views/%d/entries.%s', get_rest_url(), $view->ID, $type )
		);

		$link = sprintf(
			'<a href="%s" target="_blank" rel="noopener nofollow">%s</a>',
			esc_attr( $rest_url ),
			esc_attr( $label )
		);

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

new GravityView_Widget_Csv_Link();
