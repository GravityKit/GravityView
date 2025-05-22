<?php

namespace GV\Search\Fields;

use BadMethodCallException;
use GravityView_Cache;

/**
 * Represents a search field with possible choices.
 *
 * @since $ver$
 * @template T The type of the value.
 * @extends Search_Field<T>
 */
abstract class Search_Field_Choices extends Search_Field {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function setting_keys(): array {
		$keys   = parent::setting_keys();
		$keys[] = 'sieve_choices';

		return $keys;
	}

	/**
	 * Whether the field has choices.
	 *
	 * @since $ver$
	 */
	protected function has_choices(): bool {
		return $this->get_choices() !== [];
	}

	/**
	 * Returns the choices for the field.
	 *
	 * @since $ver$
	 *
	 * @return array{text: string, value:string} The choices.
	 */
	abstract protected function get_choices(): array;

	/**
	 * Returns the unique values that are stored for this field.
	 *
	 * @since $ver$
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
	 * @since $ver$
	 *
	 * @return bool
	 */
	protected function is_sievable(): bool {
		return false;
	}

	/**
	 * Whether the choices should be sieved.
	 *
	 * @since $ver$
	 *
	 * @return bool
	 */
	final protected function should_be_sieved(): bool {
		if ( ! $this->is_sievable() ) {
			return false;
		}

		$should_sieve = ( $this->settings['sieve_choices'] ?? false );

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
	 * @since $ver$
	 *
	 * @return array{text: string, value:string} The choices.
	 */
	final protected function sieved_choices(): array {
		if ( ! $this->is_sievable() ) {
			return [];
		}

		$form_id = $this->view ? $this->view->form->ID ?? null : null;
		$view_id = $this->view->ID ?? null;

		if ( ! $form_id || ! $view_id ) {
			return $this->get_choices();
		}

		$cache = new GravityView_Cache( $form_id, [ 'sieve', $this->get_key(), $view_id ] );

		$cached_choices = $cache->get();
		if ( ! $cached_choices ) {
			$values         = $this->get_sieved_values();
			$cached_choices = [];

			foreach ( $this->get_choices() as $choice ) {
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
	 * @since $ver$
	 */
	protected function collect_template_data(): array {
		$data = parent::collect_template_data();

		if ( $this->has_choices() ) {
			$data['choices'] = $this->should_be_sieved() ? $this->sieved_choices() : $this->get_choices();
		}

		return $data;
	}
}
