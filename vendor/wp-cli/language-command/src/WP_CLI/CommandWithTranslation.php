<?php

namespace WP_CLI;

use WP_CLI;
use WP_CLI_Command;

/**
 * Base class for WP-CLI commands that deal with translations
 *
 * @package wp-cli
 */
abstract class CommandWithTranslation extends WP_CLI_Command {
	protected $obj_type;

	protected $obj_fields;

	/**
	 * Callback to sort array by a 'language' key.
	 */
	protected function sort_translations_callback( $a, $b ) {
		return strnatcasecmp( $a['language'], $b['language'] );
	}

	/**
	 * Updates installed languages for the current object type.
	 */
	public function update( $args, $assoc_args ) {
		$updates = $this->get_translation_updates();

		if ( empty( $updates ) ) {
			WP_CLI::success( 'Translations are up to date.' );

			return;
		}

		if ( empty( $args ) ) {
			$args = array( null ); // Used for core.
		}

		$upgrader      = 'WP_CLI\\LanguagePackUpgrader';
		$results       = array();
		$num_to_update = 0;

		foreach ( $args as $slug ) {
			// Gets a list of all languages.
			$all_languages = $this->get_all_languages( $slug );

			$updates_per_type = array();

			// Formats the updates list.
			foreach ( $updates as $update ) {
				if ( null !== $slug && $update->slug !== $slug ) {
					continue;
				}

				$name = 'WordPress'; // Core.

				if ( 'plugin' === $update->type ) {
					$plugins     = get_plugins( '/' . $update->slug );
					$plugin_data = array_shift( $plugins );
					$name        = $plugin_data['Name'];
				} elseif ( 'theme' === $update->type ) {
					$theme_data = wp_get_theme( $update->slug );
					$name       = $theme_data['Name'];
				}

				// Gets the translation data.
				$translation = wp_list_filter( $all_languages, array( 'language' => $update->language ) );
				$translation = (object) reset( $translation );

				$update->Type    = ucfirst( $update->type );
				$update->Name    = $name;
				$update->Version = $update->version;

				if ( isset( $translation->english_name ) ) {
					$update->Language = $translation->english_name;
				}

				if ( ! isset( $updates_per_type[ $update->type ] ) ) {
					$updates_per_type[ $update->type ] = array();
				}
				$updates_per_type[ $update->type ][] = $update;
			}

			$obj_type          = rtrim( $this->obj_type, 's' );
			$available_updates = isset( $updates_per_type[ $obj_type ] ) ? $updates_per_type[ $obj_type ] : null;

			if ( ! is_array( $available_updates ) ) {
				continue;
			}

			$num_to_update += count( $available_updates );

			if ( ! Utils\get_flag_value( $assoc_args, 'dry-run' ) ) {
				// Update translations.
				foreach ( $available_updates as $update ) {
					WP_CLI::line( "Updating '{$update->Language}' translation for {$update->Name} {$update->Version}..." );

					$result = Utils\get_upgrader( $upgrader )->upgrade( $update );

					$results[] = $result;
				}
			}
		}

		// Only preview which translations would be updated.
		if ( Utils\get_flag_value( $assoc_args, 'dry-run' ) ) {
			$update_count = count( $updates );

			WP_CLI::line(
				sprintf(
					'Found %d translation %s that would be processed:',
					$update_count,
					WP_CLI\Utils\pluralize( 'update', $update_count )
				)
			);

			Utils\format_items( 'table', $updates, array( 'Type', 'Name', 'Version', 'Language' ) );

			return;
		}

		$num_updated = count( array_filter( $results ) );

		$line = sprintf( "Updated $num_updated/$num_to_update %s.", WP_CLI\Utils\pluralize( 'translation', $num_updated ) );

		if ( $num_to_update === $num_updated ) {
			WP_CLI::success( $line );
		} elseif ( $num_updated > 0 ) {
			WP_CLI::warning( $line );
		} else {
			WP_CLI::error( $line );
		}
	}

	/**
	 * Get all updates available for all translations.
	 *
	 * @see wp_get_translation_updates()
	 *
	 * @return array
	 */
	protected function get_translation_updates() {
		$available = $this->get_installed_languages();

		$func = function() use ( $available ) {
			return $available;
		};

		switch ( $this->obj_type ) {
			case 'plugins':
				add_filter( 'plugins_update_check_locales', $func );

				wp_clean_plugins_cache();
				// Check for Plugin translation updates.
				wp_update_plugins();

				remove_filter( 'plugins_update_check_locales', $func );

				$transient = 'update_plugins';
				break;
			case 'themes':
				add_filter( 'themes_update_check_locales', $func );

				wp_clean_themes_cache();
				// Check for Theme translation updates.
				wp_update_themes();

				remove_filter( 'themes_update_check_locales', $func );

				$transient = 'update_themes';
				break;
			default:
				delete_site_transient( 'update_core' );

				// Check for Core translation updates.
				wp_version_check();

				$transient = 'update_core';
				break;
		}

		$updates   = array();
		$transient = get_site_transient( $transient );

		if ( empty( $transient->translations ) ) {
			return $updates;
		}

		foreach ( $transient->translations as $translation ) {
			$updates[] = (object) $translation;
		}

		return $updates;
	}

	/**
	 * Download a language pack.
	 *
	 * @see wp_download_language_pack()
	 *
	 * @param string $download Language code to download.
	 * @param string $slug Plugin or theme slug. Not used for core.
	 * @return string|\WP_Error Returns the language code if successfully downloaded, or a WP_Error object on failure.
	 */
	protected function download_language_pack( $download, $slug = null ) {
		$translations        = $this->get_all_languages( $slug );
		$translation_to_load = null;

		foreach ( $translations as $translation ) {
			if ( $translation['language'] === $download ) {
				$translation_to_load = $translation;
				break;
			}
		}

		if ( ! $translation_to_load ) {
			return new \WP_Error( 'not_found', "Language '{$download}' not available." );
		}
		$translation = (object) $translation;

		$translation->type = rtrim( $this->obj_type, 's' );

		// Make sure caching in LanguagePackUpgrader works.
		if ( ! isset( $translation->slug ) ) {
			$translation->slug = $slug;
		}

		$upgrader = 'WP_CLI\\LanguagePackUpgrader';
		$result   = Utils\get_upgrader( $upgrader )->upgrade( $translation, array( 'clear_update_cache' => false ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! $result ) {
			return new \WP_Error( 'not_installed', "Could not install language '{$download}'." );
		}

		return $translation->language;
	}

	/**
	 * Return a list of installed languages.
	 *
	 * @param string $slug Optional. Plugin or theme slug. Defaults to 'default' for core.
	 *
	 * @return array
	 */
	protected function get_installed_languages( $slug = 'default' ) {
		$available   = wp_get_installed_translations( $this->obj_type );
		$available   = ! empty( $available[ $slug ] ) ? array_keys( $available[ $slug ] ) : array();
		$available[] = 'en_US';

		return $available;
	}

	/**
	 * Return a list of all languages.
	 *
	 * @param string $slug Optional. Plugin or theme slug. Not used for core.
	 *
	 * @return array
	 */
	protected function get_all_languages( $slug = null ) {
		require_once ABSPATH . '/wp-admin/includes/translation-install.php';
		require ABSPATH . WPINC . '/version.php'; // Include an unmodified $wp_version

		$args = array(
			'version' => $wp_version,
		);

		if ( $slug ) {
			$args['slug'] = $slug;

			if ( 'plugins' === $this->obj_type ) {
				$plugins     = get_plugins( '/' . $slug );
				$plugin_data = array_shift( $plugins );
				if ( isset( $plugin_data['Version'] ) ) {
					$args['version'] = $plugin_data['Version'];
				}
			} elseif ( 'themes' === $this->obj_type ) {
				$theme_data = wp_get_theme( $slug );
				if ( isset( $theme_data['Version'] ) ) {
					$args['version'] = $theme_data['Version'];
				}
			}
		}

		$response = translations_api( $this->obj_type, $args );

		if ( is_wp_error( $response ) ) {
			WP_CLI::error( $response );
		}

		$translations = ! empty( $response['translations'] ) ? $response['translations'] : array();

		$en_us = array(
			'language'     => 'en_US',
			'english_name' => 'English (United States)',
			'native_name'  => 'English (United States)',
			'updated'      => '',
		);

		$translations[] = $en_us;

		uasort( $translations, array( $this, 'sort_translations_callback' ) );

		return $translations;
	}

	/**
	 * Get Formatter object based on supplied parameters.
	 *
	 * @param array $assoc_args Parameters passed to command. Determines formatting.
	 * @return Formatter
	 */
	protected function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->obj_fields, $this->obj_type );
	}
}
