<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\Integrations;

use GravityKit\GravityView\Foundation\Core;
use GravityKit\GravityView\Foundation\Helpers\Arr;
use GravityKit\GravityView\Foundation\Helpers\Core as CoreHelpers;

class GravityForms {
	/**
	 * @since 1.0.3
	 *
	 * @var GravityForms Class instance.
	 */
	private static $_instance;

	private function __construct() {
		add_filter( 'gform_system_report', [ $this, 'modify_system_report' ] );
	}

	/**
	 * Returns class instance.
	 *
	 * @since 1.0.3
	 *
	 * @return GravityForms
	 */
	public static function get_instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Adds GravityKit products to GF's system report
	 *
	 * @since 1.0.3
	 *
	 * @param array $system_report
	 *
	 * @return array
	 */
	public function modify_system_report( $system_report ) {
		if ( ! Arr::get( $system_report, '0.tables' ) ) {
			return $system_report;
		}

		foreach ( $system_report[0]['tables'] as &$table ) {
			if ( 'Add-Ons' !== Arr::get( $table, 'title_export' ) ) {
				continue;
			}

			$registered_plugins = Core::get_instance()->get_registered_plugins();

			foreach ( $registered_plugins as $registered_plugin ) {
				$plugin_data = CoreHelpers::get_plugin_data( $registered_plugin );

				/**
				 * Controls whether to include a GravityKit product in GF's system report.
				 *
				 * @filter gk/foundation/integrations/gravityforms/add-to-system-report
				 *
				 * @since  1.0.3
				 *
				 * @param bool   $include_in_system_report Default: true.
				 * @param string $text_domain              Product text domain.
				 */
				$include_in_system_report = apply_filters( 'gk/foundation/integrations/gravityforms/add-to-system-report', true, Arr::get( $plugin_data, 'TextDomain' ) );

				if ( ! $include_in_system_report ) {
					continue;
				}

				$author = wp_kses( Arr::get( $plugin_data, 'Author' ), 'post' );

				$table['items'][] = [
					'label'                     => sprintf( '<a href="%s">%s</a>', esc_url( Arr::get( $plugin_data, 'PluginURI', '' ) ), Arr::get( $plugin_data, 'Name' ) ),
					'label_export'              => Arr::get( $plugin_data, 'Name' ),
					'value'                     => sprintf( 'by %s - %s', $author, esc_html( Arr::get( $plugin_data, 'Version' ) ) ),
					'value_export'              => sprintf( 'by %s - %s', $author, esc_html( Arr::get( $plugin_data, 'Version' ) ) ),
					'is_valid'                  => true, // TODO: Show validation errors if they exist.
					'validation_message'        => '',
					'validation_message_export' => '',
				];
			}
		}

		return $system_report;
	}
}
