<?php

namespace GV\Search\Fields;

use BadMethodCallException;
use GravityView_Cache;

/**
 * Represents a search field with possible choices.
 *
 * @since 2.42
 * @template T The type of the value.
 * @extends Search_Field<T>
 */
abstract class Search_Field_Choices extends Search_Field {
	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function setting_keys(): array {
		$keys   = parent::setting_keys();
		$keys[] = 'sieve_choices';

		return $keys;
	}

	/**
	 * Whether the field has choices.
	 *
	 * @since 2.42
	 */
	protected function has_choices(): bool {
		return $this->get_choices() !== [];
	}

	/**
	 * Returns the choices for the field.
	 *
	 * @since 2.42
	 *
	 * @return array{text: string, value:string} The choices.
	 */
	abstract protected function get_choices(): array;

	/**
	 * Returns the unique values that are stored for this field.
	 *
	 * @since 2.42
	 *
	 * @return string[] The values.
	 */
	protected function get_sieved_values(): array {
		throw new BadMethodCallException(
			sprintf(
				'Make sure to implement "%s::%s" or disable sieving.',
				static::class,
				__FUNCTION__
			)
		);
	}

	/**
	 * Whether the choices on the field can be sieved.
	 *
	 * @since 2.42
	 *
	 * @return bool
	 */
	protected function is_sievable(): bool {
		return false;
	}

	/**
	 * Whether the choices should be sieved.
	 *
	 * @since 2.42
	 *
	 * @return bool
	 */
	final protected function should_be_sieved(): bool {
		if ( ! $this->is_sievable() ) {
			return false;
		}

		$should_sieve = (bool) ( $this->settings['sieve_choices'] ?? false );

		return apply_filters(
			'gravityview/search/sieve_choices',
			$should_sieve,
			$this->field,
			$this->context,
			$this->widget_args,
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function get_options(): array {
		$options = parent::get_options();

		if ( ! $this->is_sievable() ) {
			return $options;
		}

		$options['sieve_choices'] = [
			'type'       => 'radio',
			'full_width' => true,
			'label'      => esc_html__( 'Pre-Filter Choices', 'gk-gravityview' ),
			// translators: Do not translate [b], [/b], [link], or [/link]; they are placeholders for HTML and links to documentation.
			'desc'       => strtr(
				esc_html__(
					'For fields with choices: Instead of showing all choices for each field, show only field choices that exist in submitted form entries.',
					'gk-gravityview'
				) .
				sprintf(
					'<p><strong>⚠️ %s</strong> %s</p>',
					esc_html__( 'This setting affects security.', 'gk-gravityview' ),
					esc_html__(
						'[link]Learn about the Pre-Filter Choices setting[/link] before enabling it.',
						'gk-gravityview'
					)
				),
				[
					'[b]'     => '<strong>',
					'[/b]'    => '</strong>',
					'[link]'  => sprintf(
						'<a href="https://docs.gravitykit.com/article/701-s" target="_blank" rel="external noopener nofollower" title="%s">',
						esc_attr__( 'This link opens in a new window.', 'gk-gravityview' )
					),
					'[/link]' => '</a>',
				]
			),
			'value'      => '0',
			'options'    => [
				'0' => esc_html__( 'Show all field choices', 'gk-gravityview' ),
				'1' => esc_html__( 'Only show choices that exist in form entries', 'gk-gravityview' ),
			],
			'priority'   => 1150,
		];

		return $options;
	}

	/**
	 * Returns the (maybe cached) sieved choices.
	 *
	 * @since 2.42
	 *
	 * @return array{text: string, value:string} The choices.
	 */
	final protected function sieved_choices( array $choices ): array {
		if ( ! $this->is_sievable() ) {
			return [];
		}

		$form_id = $this->view ? $this->view->form->ID ?? null : null;
		$view_id = $this->view->ID ?? null;

		if ( ! $form_id || ! $view_id || ! $choices ) {
			return $choices;
		}

		$cache = new GravityView_Cache( $form_id, [ 'sieve', $this->get_key(), $view_id ] );

		$cached_choices = $cache->get();
		if ( ! $cached_choices ) {
			$values         = $this->get_sieved_values();
			$cached_choices = [];

			foreach ( $choices as $choice ) {
				if ( in_array( $choice['text'], $values, true ) || in_array( $choice['value'], $values, true ) ) {
					$cached_choices[] = $choice;
				}
			}

			if ( $cached_choices ) {
				$cache->set( $cached_choices, 'sieve_filter_choices', WEEK_IN_SECONDS );
			}
		}

		return $cached_choices;
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function collect_template_data(): array {
		$data = parent::collect_template_data();

		if ( $this->has_choices() ) {
			if ( $this->should_be_sieved() ) {
				// We use a hook because some plugins (like populate anything) overwrite the choices,
				// so we need to sieve after they changed it.
				add_filter(
					'gravityview_widget_search_filters',
					\Closure::fromCallable( [ $this, 'sieve_choices_callback' ] ),
					1024 // Late registration to sieve after plugins changed the values.
				);
			}
			$data['choices'] = $this->get_choices();
		}

		return $data;
	}

	/**
	 * Sieves the choices on the search fields.
	 *
	 * @since 2.42
	 *
	 * @param array $search_fields The search fields.
	 *
	 * @uses  `gravityview_widget_search_filters`.
	 *
	 * @return array The updated search fields.
	 */
	private function sieve_choices_callback( array $search_fields ): array {
		foreach ( $search_fields as &$search_field ) {
			if (
				! ( $search_field['choices'] ?? [] )
				|| $this->get_type() !== ( $search_field['type'] ?? null )
			) {
				continue;
			}

			$search_field['choices'] = $this->sieved_choices( $search_field['choices'] );
		}

		return $search_fields;
	}
}
