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
			'label' => __( 'Download CSV', 'gk-gravityview' ),
			'type'  => 'csv',
		];
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function __construct() {
		$this->widget_description = 'Insert a link to a CSV / TSV download.';

		parent::__construct( 'CSV download Link', 'csv_link', self::defaults(), self::settings() );
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

		$view = $context->view;

		if (
			! $view instanceof View
			|| false === (bool) $view->settings->get( 'csv_enable', false )
		) {
			return;
		}

		$type         = rgar( $widget_args, 'type', 'csv' );
		$label        = rgar( $widget_args, 'title', 'Download CSV' );
		$in_paragraph = (bool) rgar( $widget_args, 'in_paragraph', false );

		$permalink = get_permalink( $view->id );

		if ( ! $permalink ) {
			return;
		}

		$link = sprintf(
			'<a href="%s/%s">%s</a>',
			esc_attr( rtrim( $permalink, '/' ) ),
			esc_attr( $type ),
			esc_attr( $label )
		);

		$in_paragraph
			? printf( '<p>%s</p>', $link )
			: print( $link );
	}
}

new GravityView_Widget_Csv_Link();
