<?php

namespace GV;

use GravityView_Merge_Tags;
use GVCommon;
use WP_Rewrite;

/**
 * Responsible for registering the correct permalinks.
 *
 * @since 2.29.0
 */
final class Permalinks {
	/**
	 * The plugin settings.
	 *
	 * @since 2.29.0
	 *
	 * @var Plugin_Settings
	 */
	private Plugin_Settings $settings;

	/**
	 * The default slug values.
	 *
	 * @since 2.29.0
	 *
	 * @var array|string[]
	 */
	private static array $default_slugs = [
		'view_slug'      => 'view',
		'entry_endpoint' => 'entry',
		'entry_slug'     => '{entry_id}',
	];

	/**
	 * Returns a list of reserved WordPress terms {@see https://codex.wordpress.org/Reserved_Terms}.
	 *
	 * @since 2.29.0
	 *
	 * @return string[] The reserved terms.
	 */
	public static function get_reserved_terms(): array {
		$reserved_terms = [
			'action',
			'attachment',
			'attachment_id',
			'author',
			'author_name',
			'calendar',
			'cat',
			'category',
			'category__and',
			'category__in',
			'category__not_in',
			'category_name',
			'comments_per_page',
			'comments_popup',
			'custom',
			'customize_messenger_channel',
			'customized',
			'cpage',
			'day',
			'debug',
			'embed',
			'error',
			'exact',
			'feed',
			'fields',
			'hour',
			'link_category',
			'm',
			'minute',
			'monthnum',
			'more',
			'name',
			'nav_menu',
			'nonce',
			'nopaging',
			'offset',
			'order',
			'orderby',
			'p',
			'page',
			'page_id',
			'paged',
			'pagename',
			'pb',
			'perm',
			'post',
			'post__in',
			'post__not_in',
			'post_format',
			'post_mime_type',
			'post_status',
			'post_tag',
			'post_type',
			'posts',
			'posts_per_archive_page',
			'posts_per_page',
			'preview',
			'robots',
			's',
			'search',
			'second',
			'sentence',
			'showposts',
			'static',
			'status',
			'subpost',
			'subpost_id',
			'tag',
			'tag__and',
			'tag__in',
			'tag__not_in',
			'tag_id',
			'tag_slug__and',
			'tag_slug__in',
			'taxonomy',
			'tb',
			'term',
			'terms',
			'theme',
			'title',
			'type',
			'types',
			'w',
			'withcomments',
			'withoutcomments',
			'year',
		];

		/**
		 * Modifies the list of reserved terms that are excluded from permalinks.
		 *
		 * @filter `gk/gravityview/permalinks/reserved-terms`
		 *
		 * @since  2.29.0
		 *
		 * @param string[] $extra_reserved_terms List of extra reserved terms.
		 * @param string[] $reserved_terms       The list of reserved WordPress terms.
		 */
		$extra_reserved_terms = apply_filters( 'gk/gravityview/permalinks/reserved-terms', [], $reserved_terms );
		$extra_reserved_terms = array_filter( $extra_reserved_terms, 'is_string' );

		// Using array_merge to avoid the ability to change the reserved terms of WordPress.
		return array_merge( $reserved_terms, $extra_reserved_terms );
	}

	/**
	 * Creates the Permalinks feature.
	 *
	 * @since 2.29.0
	 *
	 * @param Plugin_Settings $settings The settings object.
	 */
	public function __construct( Plugin_Settings $settings ) {
		$this->settings = $settings;

		add_filter( 'gravityview_slug', [ $this, 'set_view_slug' ], 1 );
		add_filter( 'gravityview_directory_endpoint', [ $this, 'set_entry_endpoint' ], 1 );
		add_filter( 'gravityview_custom_entry_slug', [ $this, 'is_custom_entry_slug' ], 1 );
		add_filter( 'gravityview_entry_slug', [ $this, 'set_entry_slug' ], 1, 3 );

		add_filter( 'gk/foundation/settings/data/plugins', [ $this, 'add_permalink_settings' ], 11 );
		add_filter( 'gk/foundation/inline-scripts', [ $this, 'add_global_settings_scripts' ] );

		add_filter( 'gravityview/view/settings/defaults', [ $this, 'add_view_settings' ] );

		add_action( 'init', [ $this, 'maybe_update_rewrite_rules' ], 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'add_view_settings_scripts' ], 1500 );
	}

	/**
	 * Returns imploded regex group that matched reserved terms.
	 *
	 * @since 2.29.0
	 *
	 * @return string The regex.
	 */
	private static function get_reserved_terms_regex_group(): string {
		$reserved_terms = array_map(
			static fn( string $term ): string => preg_quote( $term, '/' ),
			self::get_reserved_terms()
		);

		return '(' . implode( '|', $reserved_terms ) . ')';
	}

	/**
	 * Validates a slug.
	 *
	 * @since 2.29.0
	 *
	 * @param string $slug                   The slug to validate.
	 * @param bool   $exclude_reserved_terms Whether to exclude reserved terms from the slug.
	 *
	 * @return bool Whether the provided slug is valid.
	 */
	private static function validate_slug( string $slug, bool $exclude_reserved_terms = false ): bool {
		// Slug needs to be at least 3 characters.
		if ( strlen( $slug ) < 3 ) {
			return false;
		}

		if (
			$exclude_reserved_terms
			&& preg_match( '/^' . self::get_reserved_terms_regex_group() . '(\/|$)/i', $slug )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Updates the View slug.
	 *
	 * @since 2.29.0
	 *
	 * @param string $slug The original slug.
	 *
	 * @return string The new slug.
	 */
	public function set_view_slug( $slug ): string {
		// Only overwrite the slug if it hasn't been changed already.
		if ( $slug !== self::$default_slugs['view_slug'] ?? $slug ) {
			return $slug;
		}

		$new_slug = trim( (string) $this->settings->get( 'view_slug' ) ) ?: $slug;

		return (string) self::validate_slug( $new_slug, true ) ? $new_slug : $slug;
	}

	/**
	 * Updates the endpoint for the entry.
	 *
	 * @since 2.29.0
	 *
	 * @param string $endpoint The original endpoint.
	 *
	 * @return string The new endpoint.
	 */
	public function set_entry_endpoint( $endpoint ): string {
		// Only overwrite the endpoint if it hasn't been changed already.
		if ( $endpoint !== self::$default_slugs['entry_endpoint'] ?? $endpoint ) {
			return $endpoint;
		}

		$new_endpoint = trim( (string) $this->settings->get( 'entry_endpoint' ) ) ?: $endpoint;

		return (string) self::validate_slug( $new_endpoint ) ? $new_endpoint : $endpoint;
	}

	/**
	 * Updates the entry slug if one is set.
	 *
	 * @since 2.29.0
	 *
	 * @param string     $slug     The original slug.
	 * @param string|int $entry_id The entry ID.
	 * @param array      $entry    The entry.
	 *
	 * @return string The slug.
	 */
	public function set_entry_slug( $slug, $entry_id, array $entry ): string {
		$new_slug = trim( $this->settings->get( 'entry_slug', $slug ) );
		$view     = View::from_post( get_post() );

		if ( $view && (int) $view->form->ID === (int) $entry['form_id'] ) {
			$new_slug = trim( (string) $view->settings->get( 'single_entry_slug' ) ?: $new_slug );
		}

		if ( $new_slug === $slug || strpos( $new_slug, '{entry_id}' ) === false ) {
			return (string) $slug;
		}

		$form = GVCommon::get_form( $entry['form_id'] );

		return sanitize_title( GravityView_Merge_Tags::replace_variables( $new_slug, $form, $entry ) );
	}

	/**
	 * Returns whether the custom entry slug is enabled.
	 *
	 * @since 2.29.0
	 *
	 * @param bool $is_custom_slug Whether the custom slug is enabled.
	 *
	 * @return bool Whether the custom entry slug is enabled
	 */
	public function is_custom_entry_slug( bool $is_custom_slug ): bool {
		$is_global_entry_slug = '' !== trim( (string) $this->settings->get( 'entry_slug', '' ) );
		$is_view_entry_slug   = false;

		$view = View::from_post( get_post() );
		if ( $view ) {
			$entry_slug         = (string) $view->settings->get( 'single_entry_slug' );
			$is_view_entry_slug = (bool) trim( $entry_slug );
		}

		return ( $is_global_entry_slug || $is_view_entry_slug ) ? true : $is_custom_slug;
	}

	/**
	 * Returns the settings for the permalink structure.
	 *
	 * @since 2.29.0
	 *
	 * @return array
	 */
	private function permalink_settings(): array {
		$base_url = str_replace( 'https://', '', get_site_url( null, '', 'https' ) );

		$preview = static function ( string $id, string $value = '' ): string {
			$slug_default = self::$default_slugs[ $id ] ?? 'unknown';
			$value        = $value ?: $slug_default;
			if ( 'entry_slug' === $id ) {
				$value = str_replace( '{entry_id}', '123', $value );
			}

			return sprintf(
				'<span data-slug-preview="%s" data-slug-default="%s">%s</span>',
				$id,
				$slug_default,
				$value
			);
		};

		// Translators: [url] is replaced by the preview URL.
		$example_label = esc_html__( 'Example: [url]', 'gk-gravityview' );
		// Translators: [slug] is replaced by the slug.
		$default_label = esc_html__( 'Default: [slug]', 'gk-gravityview' );

		$view_slug             = (string) $this->settings->get( 'view_slug' );
		$entry_endpoint        = (string) $this->settings->get( 'entry_endpoint' );
		$entry_slug            = (string) $this->settings->get( 'entry_slug' );
		$slug_validation_rules = $this->slug_validation();

		return [
			[
				'id'          => 'view_slug',
				'type'        => 'text',
				'title'       => esc_html__( 'View Slug', 'gk-gravityview' ),
				'description' => strtr(
					implode(
						'<br/><br/>',
						[
							$example_label,
							esc_html__(
								'The slug must be at least 3 characters, and cannot contain certain words. See documentation for more information.',
								'gk-gravityview'
							),
						],
					),
					[
						'[url]' => sprintf(
							'%s/%s/some-view/entry/123',
							$base_url,
							$preview( 'view_slug', $view_slug )
						),
					],
				),
				'placeholder' => strtr( $default_label, [ '[slug]' => 'view' ] ),
				'value'       => $view_slug,
				'validation'  => $slug_validation_rules,
				'link'        => [
					'title' => esc_html__( 'Read documentation', 'gk-gravityview' ),
					'url'   => 'https://docs.gravitykit.com/article/57-customizing-urls',
				],
			],
			[
				'id'          => 'entry_endpoint',
				'type'        => 'text',
				'title'       => esc_html__( 'Entry Endpoint', 'gk-gravityview' ),
				'description' => strtr(
					implode(
						'<br/><br/>',
						[
							$example_label,
							// Translators: [slug] is replaced by the correct merge tag.
							esc_html__(
								'The slug must be at least 3 characters, and cannot contain certain words. See documentation for more information.',
								'gk-gravityview'
							),
						],
					),
					[
						'[url]' => sprintf(
							'%s/view/some-view/%s/123',
							$base_url,
							$preview( 'entry_endpoint', $entry_endpoint )
						),
					],
				),
				'placeholder' => strtr( $default_label, [ '[slug]' => 'entry' ] ),
				'value'       => $entry_endpoint,
				'validation'  => $slug_validation_rules,
				'link'        => [
					'title' => esc_html__( 'Read documentation', 'gk-gravityview' ),
					'url'   => 'https://docs.gravitykit.com/article/57-customizing-urls',
				],
			],
			[
				'id'          => 'entry_slug',
				'type'        => 'text',
				'title'       => esc_html__( 'Entry Slug', 'gk-gravityview' ),
				'description' => strtr(
					implode(
						'<br/><br/>',
						[
							$example_label,
							// Translators: [slug] is replaced by the correct merge tag.
							esc_html__(
								'The entry slug requires at least the [slug] merge tag, to ensure uniqueness.',
								'gk-gravityview'
							),
						],
					),
					[
						'[url]'  => sprintf(
							'%s/view/some-view/entry/%s',
							$base_url,
							$preview( 'entry_slug', $entry_slug )
						),
						'[slug]' => '<code>{entry_id}</code>',
					],
				),
				'placeholder' => strtr( $default_label, [ '[slug]' => '{entry_id}' ] ),
				'value'       => $entry_slug,
				'validation'  => $this->entry_slug_validation(),
			],
		];
	}

	/**
	 * Adds a Permalinks Section to the GravityView global settings.
	 *
	 * @since 2.29.0
	 *
	 * @param array $settings The original settings.
	 *
	 * @return array The full settings array.
	 */
	public function add_permalink_settings( array $settings ): array {
		if ( ! isset( $settings[ Plugin_Settings::SETTINGS_PLUGIN_ID ] ) ) {
			return $settings;
		}

		$settings[ Plugin_Settings::SETTINGS_PLUGIN_ID ]['sections'][] = [
			'title'       => esc_html__( 'Permalinks', 'gk-gravityview' ),
			'description' => esc_html__(
				'GravityView allows you to create a custom URL structure for your Views.',
				'gk-gravityview'
			),
			'settings'    => $this->permalink_settings(),
		];

		return $settings;
	}

	/**
	 * Adds the entry slug setting for a single View.
	 *
	 * @since 2.29.0
	 *
	 * @param array $settings The View settings.
	 *
	 * @return array The new View settings.
	 */
	public function add_view_settings( array $settings ): array {
		$settings['single_entry_slug'] = [
			'label'             => __( 'Entry Slug', 'gk-gravityview' ),
			'type'              => 'text',
			// Translators: [entry_id] will be replaced by the actual merge tag.
			'desc'              => strtr(
				esc_html__(
					'Change the slug for an entry. Make sure to at least include [entry_id] to avoid URL collisions.',
					'gk-gravityview'
				),
				[ '[entry_id]' => '<code>{entry_id}</code>' ],
			),
			'group'             => 'default',
			'value'             => '',
			'show_in_shortcode' => false,
			'full_width'        => true,
			'placeholder'       => str_replace(
				'[slug]',
				$this->settings->get( 'entry_slug', '' ) ?: '{entry_id}',
				esc_html__( 'Default: [slug]', 'gk-gravityview' )
			),
			'article'           => [
				'id'  => '54c67bb5e4b07997ea3f3f58',
				'url' => 'https://docs.gravitykit.com/article/57-customizing-urls',
			],
		];

		return $settings;
	}

	/**
	 * Adds inline JavaScript for the View settings.
	 *
	 * @since 2.29.0
	 */
	public function add_view_settings_scripts(): void {
		if ( ! wp_script_is( 'gravityview_views_scripts', 'registered' ) ) {
			return;
		}

		$js = <<<JS
			( function( $ ) {
				$( function() {
					const getErrorMessage = ( value ) => {
						if ( value.length === 0 ) {
							return '';
						}

						if (value.length < 3) {
							return '[ERROR_AT_LEAST_3]';
						}

						if ( ! value.match( /{entry_id}/s ) ) {
							 return '[ERROR_MISSING_ENTRY_ID]';
						}

						if ( ! value.match( /(^(?:[a-zA-Z0-9_\-]*|\{[^\}]*\})*$)/s ) ) {
							return '[ERROR_NO_SPACES]';
						}

						return '';
					}

					$( '#gravityview_se_single_entry_slug' ).on( 'input', function () {
						const value = $( this ).val();
						const parent = $( this ).closest( 'label' );
						const error = getErrorMessage( value );
						const is_valid = '' === error;

						parent.toggleClass( 'form-invalid form-required', ! is_valid  );
						$( '#publish ')
							.attr( 'disabled', ! is_valid )
							.toggleClass( 'disabled' , ! is_valid );

						parent.find( 'span.error-message' ).remove();
						if ( !is_valid ) {
							parent.append( $( '<span class="error-message" style="margin-top:2px; font-size: 12px">' + error + '</span>' ) );
						}
					} );
				} );
			} )( jQuery );
		JS;

		$js = strtr(
			$js,
			[
				'[ERROR_AT_LEAST_3]'       => strtr(
				// Translators: [count] is replaced by the amount of characters.
					esc_html__( 'At least [count] characters are required.', 'gk-gravityview' ),
					[ '[count]' => 3 ],
				),
				'[ERROR_MISSING_ENTRY_ID]' => strtr(
				// Translators: [slug] will contain the slug value.
					__( 'Must contain "[slug]".', 'gk-gravityview' ),
					[ '[slug]' => '{entry_id}' ]
				),
				'[ERROR_NO_SPACES]'        => esc_html__(
					'Only letters, numbers, underscores and dashes are allowed.',
					'gk-gravityview',
				),
			]
		);

		wp_add_inline_script( 'gravityview_views_scripts', $js );
	}

	/**
	 * Returns whether the current request is a backend validation.
	 *
	 * @since 2.29.0
	 *
	 * @return bool whether the current request is a backend validation.
	 */
	private function is_backend_validation(): bool {
		return 'save_settings' === ( $_REQUEST['ajaxRoute'] ?? '' );
	}

	/**
	 * Returns the validation for generic slugs, based on the current environment.
	 *
	 * @since 2.29.0
	 *
	 * @return array The validation rules.
	 */
	private function slug_validation(): array {
		if ( ! $this->is_backend_validation() ) {
			return [
				[
					'rule'    => 'matches:(^[a-zA-Z0-9_{}\-]*$)',
					'message' => esc_html__(
						'Only letters, numbers, underscores and dashes are allowed.',
						'gk-gravityview',
					),
				],
				[
					'rule'    => 'matches:(^$|.{3,}$)',
					'message' => strtr(
					// Translators: [count] is replaced by the amount of characters.
						esc_html__( 'At least [count] characters are required.', 'gk-gravityview' ),
						[ '[count]' => 3 ],
					),
				],
				[
					'rule'    => 'matches:^(?!' . self::get_reserved_terms_regex_group() . '(\/|$)).*',
					'message' => esc_html__( 'You have used a reserved word.', 'gk-gravityview' ),
				],
			];
		}

		return [
			'rule' => function ( array $settings, ?string $value = null ) {
				if ( empty( $value ) && ! is_numeric( $value ) ) {
					return true;
				}

				return self::validate_slug( $value, 'view_slug' === (string) ( $settings['id'] ?? '' ) );
			},
		];
	}

	/**
	 * Returns the validation rules for the entry slug, based on the current environment.
	 *
	 * @since 2.29.0
	 *
	 * @return array The validation rules.
	 */
	private function entry_slug_validation(): array {
		if ( ! $this->is_backend_validation() ) {
			return [
				[
					'rule'    => 'matches:^$|{entry_id}',
					'message' => strtr(
					// Translators: [slug] will contain the slug value.
						__( 'Must contain "[slug]".', 'gk-gravityview' ),
						[ '[slug]' => '{entry_id}' ]
					),
				],
				[
					'rule'    => 'matches:^[a-zA-Z0-9_{}\-]*$',
					'message' => esc_html__(
						'Only letters, numbers, underscores and dashes are allowed.',
						'gk-gravityview',
					),
				],
			];
		}

		return [
			'rule' => function ( array $settings, ?string $value = null ): bool {
				// Empty is allowed.
				if ( empty( $value ) && ! is_numeric( $value ) ) {
					return true;
				}

				// Otherwise it needs at least `{entry_id}`.
				if ( strpos( $value, '{entry_id}' ) === false ) {
					return false;
				}

				return preg_match( '/^[a-zA-Z0-9_{}\-]*$/', $value ) !== false;
			},
		];
	}

	/**
	 * Updates the rewrite rules if the required ones are missing.
	 *
	 * @since 2.29.0
	 */
	public function maybe_update_rewrite_rules(): void {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		if ( ! $wp_rewrite instanceof WP_Rewrite ) {
			return;
		}

		$view_slug      = apply_filters( 'gravityview_slug', 'view' );
		$entry_endpoint = apply_filters( 'gravityview_directory_endpoint', 'entry' );

		$found = [];
		$rules = $wp_rewrite->wp_rewrite_rules();

		if ( is_array( $rules ) ) {
			foreach ( $rules as $rule => $_ ) {
				if ( strpos( $rule, $view_slug . '/' ) === 0 ) {
					$found['view'] = true;
				}

				if ( strpos( $rule, $entry_endpoint . '(/' ) === 0 ) {
					$found['entry'] = true;
				}
			}
		}

		if ( count( $found ) < 2 ) {
			$wp_rewrite->flush_rules();
		}
	}

	/**
	 * Adds Inline javascript for GravityViews Foundation settings.
	 *
	 * @since 2.29.0
	 */
	public function add_global_settings_scripts( array $scripts ): array {
		$script = <<<JS
			window.addEventListener( 'gk/foundation/settings/initialized', () => {
				document.addEventListener( 'input', ( e ) => {
					if ( [ 'view_slug', 'entry_endpoint', 'entry_slug' ].indexOf( e.target.name ) < 0 ) {
						return;
					}
					// Update all preview element when the corresponding input is changed.
					document.querySelectorAll( `[data-slug-preview="\${e.target.name}"]` ).forEach( ( element ) => {
						const default_value = element.dataset.slugDefault ?? 'unknown';
						element.innerHTML = ( e.target.value || default_value );

						if ( 'entry_slug' === e.target.name ) {
							element.innerHTML = element.innerHTML.replaceAll( '{entry_id}', '123' );
						}
					} );
				} );
			} );
		JS;

		$scripts[] = [
			'script' => $script,
		];

		return $scripts;
	}
}
