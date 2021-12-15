<?php

use WP_CLI\Utils;
use WP_CLI\Inflector;

/**
 * Generates code for post types, taxonomies, plugins, child themes, etc.
 *
 * ## EXAMPLES
 *
 *     # Generate a new plugin with unit tests
 *     $ wp scaffold plugin sample-plugin
 *     Success: Created plugin files.
 *     Success: Created test files.
 *
 *     # Generate theme based on _s
 *     $ wp scaffold _s sample-theme --theme_name="Sample Theme" --author="John Doe"
 *     Success: Created theme 'Sample Theme'.
 *
 *     # Generate code for post type registration in given theme
 *     $ wp scaffold post-type movie --label=Movie --theme=simple-life
 *     Success: Created /var/www/example.com/public_html/wp-content/themes/simple-life/post-types/movie.php
 *
 * @package wp-cli
 */
class Scaffold_Command extends WP_CLI_Command {

	/**
	 * Generates PHP code for registering a custom post type.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The internal name of the post type.
	 *
	 * [--label=<label>]
	 * : The text used to translate the update messages.
	 *
	 * [--textdomain=<textdomain>]
	 * : The textdomain to use for the labels.
	 *
	 * [--dashicon=<dashicon>]
	 * : The dashicon to use in the menu.
	 *
	 * [--theme]
	 * : Create a file in the active theme directory, instead of sending to
	 * STDOUT. Specify a theme with `--theme=<theme>` to have the file placed in that theme.
	 *
	 * [--plugin=<plugin>]
	 * : Create a file in the given plugin's directory, instead of sending to STDOUT.
	 *
	 * [--raw]
	 * : Just generate the `register_post_type()` call and nothing else.
	 *
	 * [--force]
	 * : Overwrite files that already exist.
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate a 'movie' post type for the 'simple-life' theme
	 *     $ wp scaffold post-type movie --label=Movie --theme=simple-life
	 *     Success: Created '/var/www/example.com/public_html/wp-content/themes/simple-life/post-types/movie.php'.
	 *
	 * @subcommand post-type
	 *
	 * @alias      cpt
	 */
	public function post_type( $args, $assoc_args ) {

		if ( strlen( $args[0] ) > 20 ) {
			WP_CLI::error( 'Post type slugs cannot exceed 20 characters in length.' );
		}

		$defaults = [
			'textdomain' => '',
			'dashicon'   => 'admin-post',
		];

		$templates = [
			'post_type.mustache',
			'post_type_extended.mustache',
		];

		$this->scaffold( $args[0], $assoc_args, $defaults, '/post-types/', $templates );
	}

	/**
	 * Generates PHP code for registering a custom taxonomy.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The internal name of the taxonomy.
	 *
	 * [--post_types=<post-types>]
	 * : Post types to register for use with the taxonomy.
	 *
	 * [--label=<label>]
	 * : The text used to translate the update messages.
	 *
	 * [--textdomain=<textdomain>]
	 * : The textdomain to use for the labels.
	 *
	 * [--theme]
	 * : Create a file in the active theme directory, instead of sending to
	 * STDOUT. Specify a theme with `--theme=<theme>` to have the file placed in that theme.
	 *
	 * [--plugin=<plugin>]
	 * : Create a file in the given plugin's directory, instead of sending to STDOUT.
	 *
	 * [--raw]
	 * : Just generate the `register_taxonomy()` call and nothing else.
	 *
	 * [--force]
	 * : Overwrite files that already exist.
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate PHP code for registering a custom taxonomy and save in a file
	 *     $ wp scaffold taxonomy venue --post_types=event,presentation > taxonomy.php
	 *
	 * @subcommand taxonomy
	 *
	 * @alias      tax
	 */
	public function taxonomy( $args, $assoc_args ) {
		$defaults = [
			'textdomain' => '',
			'post_types' => "'post'",
		];

		if ( isset( $assoc_args['post_types'] ) ) {
			$assoc_args['post_types'] = $this->quote_comma_list_elements( $assoc_args['post_types'] );
		}

		$templates = [
			'taxonomy.mustache',
			'taxonomy_extended.mustache',
		];

		$this->scaffold( $args[0], $assoc_args, $defaults, '/taxonomies/', $templates );
	}

	private function scaffold( $slug, $assoc_args, $defaults, $subdir, $templates ) {
		$wp_filesystem = $this->init_wp_filesystem();

		$control_defaults = [
			'label'  => preg_replace( '/_|-/', ' ', strtolower( $slug ) ),
			'theme'  => false,
			'plugin' => false,
			'raw'    => false,
		];
		$control_args     = $this->extract_args( $assoc_args, $control_defaults );

		$vars = $this->extract_args( $assoc_args, $defaults );

		$dashicon = $this->extract_dashicon( $assoc_args );
		if ( $dashicon ) {
			$vars['dashicon'] = $dashicon;
		}

		$vars['slug'] = $slug;

		$vars['textdomain'] = $this->get_textdomain( $vars['textdomain'], $control_args );

		$vars['label'] = $control_args['label'];

		$vars['label_ucfirst']        = ucfirst( $vars['label'] );
		$vars['label_plural']         = $this->pluralize( $vars['label'] );
		$vars['label_plural_ucfirst'] = ucfirst( $vars['label_plural'] );

		$machine_name        = $this->generate_machine_name( $slug );
		$machine_name_plural = $this->pluralize( $slug );

		list( $raw_template, $extended_template ) = $templates;

		$raw_output = self::mustache_render( $raw_template, $vars );

		if ( ! $control_args['raw'] ) {
			$vars['machine_name'] = $machine_name;
			$vars['output']       = $raw_output;

			$final_output = self::mustache_render( $extended_template, $vars );
		} else {
			$final_output = $raw_output;
		}

		$path = $this->get_output_path( $control_args, $subdir );
		if ( is_string( $path ) && ! empty( $path ) ) {
			$filename = "{$path}{$slug}.php";

			$force           = Utils\get_flag_value( $assoc_args, 'force' );
			$files_written   = $this->create_files( [ $filename => $final_output ], $force );
			$skip_message    = "Skipped creating '{$filename}'.";
			$success_message = "Created '{$filename}'.";
			$this->log_whether_files_written( $files_written, $skip_message, $success_message );

		} else {
			// STDOUT
			echo $final_output;
		}
	}

	/**
	 * Generates PHP, JS and CSS code for registering a Gutenberg block for a plugin or theme.
	 *
	 * Blocks are the fundamental element of the Gutenberg editor. They are the primary way in which plugins and themes can register their own functionality and extend the capabilities of the editor.
	 *
	 * Visit the [Gutenberg handbook](https://wordpress.org/gutenberg/handbook/block-api/) to learn more about Block API.
	 *
	 * When you scaffold a block you must use either the theme or plugin option. The latter is recommended.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The internal name of the block.
	 *
	 * [--title=<title>]
	 * : The display title for your block.
	 *
	 * [--dashicon=<dashicon>]
	 * : The dashicon to make it easier to identify your block.
	 *
	 * [--category=<category>]
	 * : The category name to help users browse and discover your block.
	 * ---
	 * default: widgets
	 * options:
	 *   - common
	 *   - embed
	 *   - formatting
	 *   - layout
	 *   - widgets
	 * ---
	 *
	 * [--theme]
	 * : Create files in the active theme directory. Specify a theme with `--theme=<theme>` to have the file placed in that theme.
	 *
	 * [--plugin=<plugin>]
	 * : Create files in the given plugin's directory.
	 *
	 * [--force]
	 * : Overwrite files that already exist.
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate a 'movie' block for the 'movies' plugin
	 *     $ wp scaffold block movie --title="Movie block" --plugin=movies
	 *     Success: Created block 'Movie block'.
	 *
	 *     # Generate a 'movie' block for the 'simple-life' theme
	 *     $ wp scaffold block movie --title="Movie block" --theme=simple-life
	 *      Success: Created block 'Movie block'.
	 *
	 *     # Create a new plugin and add two blocks
	 *     # Create plugin called books
	 *     $ wp scaffold plugin books
	 *     # Add a block called book to plugin books
	 *     $ wp scaffold block book --title="Book" --plugin=books
	 *     # Add a second block to plugin called books.
	 *     $ wp scaffold block books --title="Book List" --plugin=books
	 *
	 * @subcommand block
	 */
	public function block( $args, $assoc_args ) {

		$slug = $args[0];
		if ( ! preg_match( '/^[a-z][a-z0-9\-]*$/', $slug ) ) {
			WP_CLI::error( 'Invalid block slug specified. Block slugs can contain only lowercase alphanumeric characters or dashes, and start with a letter.' );
		}

		$defaults = [
			'title'    => str_replace( '-', ' ', $slug ),
			'category' => 'widgets',
		];
		$data     = $this->extract_args( $assoc_args, $defaults );

		$data['slug']             = $slug;
		$data['title_ucfirst']    = ucfirst( $data['title'] );
		$data['title_ucfirst_js'] = esc_js( $data['title_ucfirst'] );

		$dashicon = $this->extract_dashicon( $assoc_args );
		if ( $dashicon ) {
			$data['dashicon'] = $dashicon;
		}

		$control_defaults = [
			'force'  => false,
			'plugin' => false,
			'theme'  => false,
		];
		$control_args     = $this->extract_args( $assoc_args, $control_defaults );

		if ( isset( $control_args['plugin'] ) ) {
			if ( ! preg_match( '/^[A-Za-z0-9\-]*$/', $control_args['plugin'] ) ) {
				WP_CLI::error( 'Invalid plugin name specified. The block editor can only register blocks for plugins that have nothing but lowercase alphanumeric characters or dashes in their slug.' );
			}
		}

		$data['namespace']    = $control_args['plugin'] ? $control_args['plugin'] : $this->get_theme_name( $control_args['theme'] );
		$data['machine_name'] = $this->generate_machine_name( $slug );
		$data['plugin']       = $control_args['plugin'] ? true : false;
		$data['theme']        = ! $data['plugin'];

		$block_dir = $this->get_output_path( $control_args, '/blocks' );
		if ( ! $block_dir ) {
			WP_CLI::error( 'No plugin or theme selected.' );
		}

		$files_to_create = [
			"{$block_dir}/{$slug}.php"        => self::mustache_render( 'block-php.mustache', $data ),
			"{$block_dir}/{$slug}/index.js"   => self::mustache_render( 'block-index-js.mustache', $data ),
			"{$block_dir}/{$slug}/editor.css" => self::mustache_render( 'block-editor-css.mustache', $data ),
			"{$block_dir}/{$slug}/style.css"  => self::mustache_render( 'block-style-css.mustache', $data ),
		];
		$files_written   = $this->create_files( $files_to_create, $control_args['force'] );
		$skip_message    = 'All block files were skipped.';
		$success_message = "Created block '{$data['title_ucfirst']}'.";
		$this->log_whether_files_written( $files_written, $skip_message, $success_message );
	}

	/**
	 * Generates starter code for a theme based on _s.
	 *
	 * See the [Underscores website](https://underscores.me/) for more details.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The slug for the new theme, used for prefixing functions.
	 *
	 * [--activate]
	 * : Activate the newly downloaded theme.
	 *
	 * [--enable-network]
	 * : Enable the newly downloaded theme for the entire network.
	 *
	 * [--theme_name=<title>]
	 * : What to put in the 'Theme Name:' header in 'style.css'.
	 *
	 * [--author=<full-name>]
	 * : What to put in the 'Author:' header in 'style.css'.
	 *
	 * [--author_uri=<uri>]
	 * : What to put in the 'Author URI:' header in 'style.css'.
	 *
	 * [--sassify]
	 * : Include stylesheets as SASS.
	 *
	 * [--woocommerce]
	 * : Include WooCommerce boilerplate files.
	 *
	 * [--force]
	 * : Overwrite files that already exist.
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate a theme with name "Sample Theme" and author "John Doe"
	 *     $ wp scaffold _s sample-theme --theme_name="Sample Theme" --author="John Doe"
	 *     Success: Created theme 'Sample Theme'.
	 *
	 * @alias _s
	 */
	public function underscores( $args, $assoc_args ) {

		$theme_slug = $args[0];
		$theme_path = WP_CONTENT_DIR . '/themes';
		$url        = 'https://underscores.me';
		$timeout    = 30;

		if ( ! preg_match( '/^[a-z_]\w+$/i', str_replace( '-', '_', $theme_slug ) ) ) {
			WP_CLI::error( 'Invalid theme slug specified. Theme slugs can only contain letters, numbers, underscores and hyphens, and can only start with a letter or underscore.' );
		}

		$defaults = [
			'theme_name' => ucfirst( $theme_slug ),
			'author'     => 'Me',
			'author_uri' => '',
		];
		$data     = wp_parse_args( $assoc_args, $defaults );

		$_s_theme_path = "$theme_path/$data[theme_name]";

		$error_msg = $this->check_target_directory( 'theme', $_s_theme_path );
		if ( ! empty( $error_msg ) ) {
			WP_CLI::error( "Invalid theme slug specified. {$error_msg}" );
		}

		$force             = Utils\get_flag_value( $assoc_args, 'force' );
		$should_write_file = $this->prompt_if_files_will_be_overwritten( $_s_theme_path, $force );
		if ( ! $should_write_file ) {
			WP_CLI::log( 'No files created' );
			die;
		}

		$theme_description = "Custom theme: {$data['theme_name']}, developed by {$data['author']}";

		$body                                  = [];
		$body['underscoresme_name']            = $data['theme_name'];
		$body['underscoresme_slug']            = $theme_slug;
		$body['underscoresme_author']          = $data['author'];
		$body['underscoresme_author_uri']      = $data['author_uri'];
		$body['underscoresme_description']     = $theme_description;
		$body['underscoresme_generate_submit'] = 'Generate';
		$body['underscoresme_generate']        = '1';
		if ( Utils\get_flag_value( $assoc_args, 'sassify' ) ) {
			$body['underscoresme_sass'] = 1;
		}

		if ( Utils\get_flag_value( $assoc_args, 'woocommerce' ) ) {
			$body['underscoresme_woocommerce'] = 1;
		}

		$tmpfname  = wp_tempnam( $url );
		$post_args = [
			'timeout'  => $timeout,
			'body'     => $body,
			'stream'   => true,
			'filename' => $tmpfname,
		];

		$response = wp_remote_post( $url, $post_args );

		// Workaround to get scaffolding to work within Travis CI.
		// See https://github.com/wp-cli/scaffold-command/issues/181
		if ( is_wp_error( $response )
			&& false !== strpos( $response->get_error_message(), 'gnutls_handshake() failed' )
		) {
			// Certificate problem, falling back to unsecured request instead.
			$alt_url = str_replace( 'https://', 'http://', $url );
			WP_CLI::warning( "Secured request to {$url} failed, using {$alt_url} as a fallback." );
			$response = wp_remote_post( $alt_url, $post_args );
		}

		if ( is_wp_error( $response ) ) {
			WP_CLI::error( $response );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $response_code ) {
			WP_CLI::error( "Couldn't create theme (received {$response_code} response)." );
		}

		$this->maybe_create_themes_dir();

		$this->init_wp_filesystem();

		$unzip_result = unzip_file( $tmpfname, $theme_path );
		unlink( $tmpfname );

		if ( true === $unzip_result ) {
			$files_to_create = [
				"{$theme_path}/{$theme_slug}/.editorconfig" => file_get_contents( self::get_template_path( '.editorconfig' ) ),
			];
			$this->create_files( $files_to_create, false );
			WP_CLI::success( "Created theme '{$data['theme_name']}'." );
		} else {
			WP_CLI::error( "Could not decompress your theme files ('{$tmpfname}') at '{$theme_path}': {$unzip_result->get_error_message()}" );
		}

		if ( Utils\get_flag_value( $assoc_args, 'activate' ) ) {
			WP_CLI::run_command( [ 'theme', 'activate', $theme_slug ] );
		} elseif ( Utils\get_flag_value( $assoc_args, 'enable-network' ) ) {
			WP_CLI::run_command( [ 'theme', 'enable', $theme_slug ], [ 'network' => true ] );
		}
	}

	/**
	 * Generates child theme based on an existing theme.
	 *
	 * Creates a child theme folder with `functions.php` and `style.css` files.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The slug for the new child theme.
	 *
	 * --parent_theme=<slug>
	 * : What to put in the 'Template:' header in 'style.css'.
	 *
	 * [--theme_name=<title>]
	 * : What to put in the 'Theme Name:' header in 'style.css'.
	 *
	 * [--author=<full-name>]
	 * : What to put in the 'Author:' header in 'style.css'.
	 *
	 * [--author_uri=<uri>]
	 * : What to put in the 'Author URI:' header in 'style.css'.
	 *
	 * [--theme_uri=<uri>]
	 * : What to put in the 'Theme URI:' header in 'style.css'.
	 *
	 * [--activate]
	 * : Activate the newly created child theme.
	 *
	 * [--enable-network]
	 * : Enable the newly created child theme for the entire network.
	 *
	 * [--force]
	 * : Overwrite files that already exist.
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate a 'sample-theme' child theme based on TwentySixteen
	 *     $ wp scaffold child-theme sample-theme --parent_theme=twentysixteen
	 *     Success: Created '/var/www/example.com/public_html/wp-content/themes/sample-theme'.
	 *
	 * @subcommand child-theme
	 */
	public function child_theme( $args, $assoc_args ) {
		$theme_slug = $args[0];

		if ( in_array( $theme_slug, [ '.', '..' ], true ) ) {
			WP_CLI::error( "Invalid theme slug specified. The slug cannot be '.' or '..'." );
		}

		$defaults = [
			'theme_name' => ucfirst( $theme_slug ),
			'author'     => 'Me',
			'author_uri' => '',
			'theme_uri'  => '',
		];

		$data                               = wp_parse_args( $assoc_args, $defaults );
		$data['slug']                       = $theme_slug;
		$data['parent_theme_function_safe'] = str_replace( [ ' ', '-' ], '_', $data['parent_theme'] );
		$data['description']                = ucfirst( $data['parent_theme'] ) . ' child theme.';

		$theme_dir = WP_CONTENT_DIR . "/themes/{$theme_slug}";

		$error_msg = $this->check_target_directory( 'theme', $theme_dir );
		if ( ! empty( $error_msg ) ) {
			WP_CLI::error( "Invalid theme slug specified. {$error_msg}" );
		}

		$theme_style_path     = "{$theme_dir}/style.css";
		$theme_functions_path = "{$theme_dir}/functions.php";

		$this->maybe_create_themes_dir();

		$files_to_create = [
			$theme_style_path            => self::mustache_render( 'child_theme.mustache', $data ),
			$theme_functions_path        => self::mustache_render( 'child_theme_functions.mustache', $data ),
			"{$theme_dir}/.editorconfig" => file_get_contents( self::get_template_path( '.editorconfig' ) ),
		];
		$force           = Utils\get_flag_value( $assoc_args, 'force' );
		$files_written   = $this->create_files( $files_to_create, $force );
		$skip_message    = 'All theme files were skipped.';
		$success_message = "Created '{$theme_dir}'.";
		$this->log_whether_files_written( $files_written, $skip_message, $success_message );

		if ( Utils\get_flag_value( $assoc_args, 'activate' ) ) {
			WP_CLI::run_command( [ 'theme', 'activate', $theme_slug ] );
		} elseif ( Utils\get_flag_value( $assoc_args, 'enable-network' ) ) {
			WP_CLI::run_command( [ 'theme', 'enable', $theme_slug ], [ 'network' => true ] );
		}
	}

	private function get_output_path( $assoc_args, $subdir ) {
		if ( $assoc_args['theme'] ) {
			$theme = $assoc_args['theme'];
			if ( is_string( $theme ) ) {
				$path = get_theme_root( $theme ) . "/{$theme}";
			} else {
				$path = get_stylesheet_directory();
			}
			if ( ! is_dir( $path ) ) {
				WP_CLI::error( "Can't find '{$theme}' theme." );
			}
		} elseif ( $assoc_args['plugin'] ) {
			$plugin = $assoc_args['plugin'];
			$path   = WP_PLUGIN_DIR . "/{$plugin}";
			if ( ! is_dir( $path ) ) {
				WP_CLI::error( "Can't find '{$plugin}' plugin." );
			}
		} else {
			return false;
		}

		$path .= $subdir;

		return $path;
	}

	/**
	 * Generates starter code for a plugin.
	 *
	 * The following files are always generated:
	 *
	 * * `plugin-slug.php` is the main PHP plugin file.
	 * * `readme.txt` is the readme file for the plugin.
	 * * `package.json` needed by NPM holds various metadata relevant to the project. Packages: `grunt`, `grunt-wp-i18n` and `grunt-wp-readme-to-markdown`. Scripts: `start`, `readme`, `i18n`.
	 * * `Gruntfile.js` is the JS file containing Grunt tasks. Tasks: `i18n` containing `addtextdomain` and `makepot`, `readme` containing `wp_readme_to_markdown`.
	 * * `.editorconfig` is the configuration file for Editor.
	 * * `.gitignore` tells which files (or patterns) git should ignore.
	 * * `.distignore` tells which files and folders should be ignored in distribution.
	 *
	 * The following files are also included unless the `--skip-tests` is used:
	 *
	 * * `phpunit.xml.dist` is the configuration file for PHPUnit.
	 * * `.travis.yml` is the configuration file for Travis CI. Use `--ci=<provider>` to select a different service.
	 * * `bin/install-wp-tests.sh` configures the WordPress test suite and a test database.
	 * * `tests/bootstrap.php` is the file that makes the current plugin active when running the test suite.
	 * * `tests/test-sample.php` is a sample file containing test cases.
	 * * `.phpcs.xml.dist` is a collection of PHP_CodeSniffer rules.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The internal name of the plugin.
	 *
	 * [--dir=<dirname>]
	 * : Put the new plugin in some arbitrary directory path. Plugin directory will be path plus supplied slug.
	 *
	 * [--plugin_name=<title>]
	 * : What to put in the 'Plugin Name:' header.
	 *
	 * [--plugin_description=<description>]
	 * : What to put in the 'Description:' header.
	 *
	 * [--plugin_author=<author>]
	 * : What to put in the 'Author:' header.
	 *
	 * [--plugin_author_uri=<url>]
	 * : What to put in the 'Author URI:' header.
	 *
	 * [--plugin_uri=<url>]
	 * : What to put in the 'Plugin URI:' header.
	 *
	 * [--skip-tests]
	 * : Don't generate files for unit testing.
	 *
	 * [--ci=<provider>]
	 * : Choose a configuration file for a continuous integration provider.
	 * ---
	 * default: travis
	 * options:
	 *   - travis
	 *   - circle
	 *   - gitlab
	 * ---
	 *
	 * [--activate]
	 * : Activate the newly generated plugin.
	 *
	 * [--activate-network]
	 * : Network activate the newly generated plugin.
	 *
	 * [--force]
	 * : Overwrite files that already exist.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp scaffold plugin sample-plugin
	 *     Success: Created plugin files.
	 *     Success: Created test files.
	 */
	public function plugin( $args, $assoc_args ) {
		$plugin_slug    = $args[0];
		$plugin_name    = ucwords( str_replace( '-', ' ', $plugin_slug ) );
		$plugin_package = str_replace( ' ', '_', $plugin_name );

		if ( in_array( $plugin_slug, [ '.', '..' ], true ) ) {
			WP_CLI::error( "Invalid plugin slug specified. The slug cannot be '.' or '..'." );
		}

		$defaults = [
			'plugin_slug'         => $plugin_slug,
			'plugin_name'         => $plugin_name,
			'plugin_package'      => $plugin_package,
			'plugin_description'  => 'PLUGIN DESCRIPTION HERE',
			'plugin_author'       => 'YOUR NAME HERE',
			'plugin_author_uri'   => 'YOUR SITE HERE',
			'plugin_uri'          => 'PLUGIN SITE HERE',
			'plugin_tested_up_to' => get_bloginfo( 'version' ),
		];
		$data     = wp_parse_args( $assoc_args, $defaults );

		$data['textdomain'] = $plugin_slug;

		if ( ! empty( $assoc_args['dir'] ) ) {
			if ( ! is_dir( $assoc_args['dir'] ) ) {
				WP_CLI::error( "Cannot create plugin in directory that doesn't exist." );
			}
			$plugin_dir = "{$assoc_args['dir']}/{$plugin_slug}";
		} else {
			$plugin_dir = WP_PLUGIN_DIR . "/{$plugin_slug}";
			$this->maybe_create_plugins_dir();

			$error_msg = $this->check_target_directory( 'plugin', $plugin_dir );
			if ( ! empty( $error_msg ) ) {
				WP_CLI::error( "Invalid plugin slug specified. {$error_msg}" );
			}
		}

		$plugin_path        = "{$plugin_dir}/{$plugin_slug}.php";
		$plugin_readme_path = "{$plugin_dir}/readme.txt";

		$files_to_create = [
			$plugin_path                  => self::mustache_render( 'plugin.mustache', $data ),
			$plugin_readme_path           => self::mustache_render( 'plugin-readme.mustache', $data ),
			"{$plugin_dir}/package.json"  => self::mustache_render( 'plugin-packages.mustache', $data ),
			"{$plugin_dir}/Gruntfile.js"  => self::mustache_render( 'plugin-gruntfile.mustache', $data ),
			"{$plugin_dir}/.gitignore"    => self::mustache_render( 'plugin-gitignore.mustache', $data ),
			"{$plugin_dir}/.distignore"   => self::mustache_render( 'plugin-distignore.mustache', $data ),
			"{$plugin_dir}/.editorconfig" => file_get_contents( self::get_template_path( '.editorconfig' ) ),
		];
		$force           = Utils\get_flag_value( $assoc_args, 'force' );
		$files_written   = $this->create_files( $files_to_create, $force );

		$skip_message    = 'All plugin files were skipped.';
		$success_message = 'Created plugin files.';
		$this->log_whether_files_written( $files_written, $skip_message, $success_message );

		if ( ! Utils\get_flag_value( $assoc_args, 'skip-tests' ) ) {
			$command_args = [
				'dir'   => $plugin_dir,
				'ci'    => empty( $assoc_args['ci'] ) ? '' : $assoc_args['ci'],
				'force' => $force,
			];
			WP_CLI::run_command( [ 'scaffold', 'plugin-tests', $plugin_slug ], $command_args );
		}

		if ( Utils\get_flag_value( $assoc_args, 'activate' ) ) {
			WP_CLI::run_command( [ 'plugin', 'activate', $plugin_slug ] );
		} elseif ( Utils\get_flag_value( $assoc_args, 'activate-network' ) ) {
			WP_CLI::run_command( [ 'plugin', 'activate', $plugin_slug ], [ 'network' => true ] );
		}
	}

	/**
	 * Generates files needed for running PHPUnit tests in a plugin.
	 *
	 * The following files are generated by default:
	 *
	 * * `phpunit.xml.dist` is the configuration file for PHPUnit.
	 * * `.travis.yml` is the configuration file for Travis CI. Use `--ci=<provider>` to select a different service.
	 * * `bin/install-wp-tests.sh` configures the WordPress test suite and a test database.
	 * * `tests/bootstrap.php` is the file that makes the current plugin active when running the test suite.
	 * * `tests/test-sample.php` is a sample file containing the actual tests.
	 * * `.phpcs.xml.dist` is a collection of PHP_CodeSniffer rules.
	 *
	 * Learn more from the [plugin unit tests documentation](https://make.wordpress.org/cli/handbook/plugin-unit-tests/).
	 *
	 * ## ENVIRONMENT
	 *
	 * The `tests/bootstrap.php` file looks for the WP_TESTS_DIR environment
	 * variable.
	 *
	 * ## OPTIONS
	 *
	 * [<plugin>]
	 * : The name of the plugin to generate test files for.
	 *
	 * [--dir=<dirname>]
	 * : Generate test files for a non-standard plugin path. If no plugin slug is specified, the directory name is used.
	 *
	 * [--ci=<provider>]
	 * : Choose a configuration file for a continuous integration provider.
	 * ---
	 * default: travis
	 * options:
	 *   - travis
	 *   - circle
	 *   - gitlab
	 *   - bitbucket
	 * ---
	 *
	 * [--force]
	 * : Overwrite files that already exist.
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate unit test files for plugin 'sample-plugin'.
	 *     $ wp scaffold plugin-tests sample-plugin
	 *     Success: Created test files.
	 *
	 * @subcommand plugin-tests
	 */
	public function plugin_tests( $args, $assoc_args ) {
		$this->scaffold_plugin_theme_tests( $args, $assoc_args, 'plugin' );
	}

	/**
	 * Generates files needed for running PHPUnit tests in a theme.
	 *
	 * The following files are generated by default:
	 *
	 * * `phpunit.xml.dist` is the configuration file for PHPUnit.
	 * * `.travis.yml` is the configuration file for Travis CI. Use `--ci=<provider>` to select a different service.
	 * * `bin/install-wp-tests.sh` configures the WordPress test suite and a test database.
	 * * `tests/bootstrap.php` is the file that makes the current theme active when running the test suite.
	 * * `tests/test-sample.php` is a sample file containing the actual tests.
	 * * `.phpcs.xml.dist` is a collection of PHP_CodeSniffer rules.
	 *
	 * Learn more from the [plugin unit tests documentation](https://make.wordpress.org/cli/handbook/plugin-unit-tests/).
	 *
	 * ## ENVIRONMENT
	 *
	 * The `tests/bootstrap.php` file looks for the WP_TESTS_DIR environment
	 * variable.
	 *
	 * ## OPTIONS
	 *
	 * [<theme>]
	 * : The name of the theme to generate test files for.
	 *
	 * [--dir=<dirname>]
	 * : Generate test files for a non-standard theme path. If no theme slug is specified, the directory name is used.
	 *
	 * [--ci=<provider>]
	 * : Choose a configuration file for a continuous integration provider.
	 * ---
	 * default: travis
	 * options:
	 *   - travis
	 *   - circle
	 *   - gitlab
	 *   - bitbucket
	 * ---
	 *
	 * [--force]
	 * : Overwrite files that already exist.
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate unit test files for theme 'twentysixteenchild'.
	 *     $ wp scaffold theme-tests twentysixteenchild
	 *     Success: Created test files.
	 *
	 * @subcommand theme-tests
	 */
	public function theme_tests( $args, $assoc_args ) {
		$this->scaffold_plugin_theme_tests( $args, $assoc_args, 'theme' );
	}

	private function scaffold_plugin_theme_tests( $args, $assoc_args, $type ) {
		$wp_filesystem = $this->init_wp_filesystem();

		if ( ! empty( $args[0] ) ) {
			$slug = $args[0];
			if ( in_array( $slug, [ '.', '..' ], true ) ) {
				WP_CLI::error( "Invalid {$type} slug specified. The slug cannot be '.' or '..'." );
			}
			if ( 'theme' === $type ) {
				$theme = wp_get_theme( $slug );
				if ( $theme->exists() ) {
					$target_dir = $theme->get_stylesheet_directory();
				} else {
					WP_CLI::error( "Invalid {$type} slug specified. The theme '{$slug}' does not exist." );
				}
			} else {
				$target_dir = WP_PLUGIN_DIR . "/{$slug}";
			}
			if ( empty( $assoc_args['dir'] ) && ! is_dir( $target_dir ) ) {
				WP_CLI::error( "Invalid {$type} slug specified. No such target directory '{$target_dir}'." );
			}

			$error_msg = $this->check_target_directory( $type, $target_dir );
			if ( ! empty( $error_msg ) ) {
				WP_CLI::error( "Invalid {$type} slug specified. {$error_msg}" );
			}
		}

		if ( ! empty( $assoc_args['dir'] ) ) {
			$target_dir = $assoc_args['dir'];
			if ( ! is_dir( $target_dir ) ) {
				WP_CLI::error( "Invalid {$type} directory specified. No such directory '{$target_dir}'." );
			}
			if ( empty( $slug ) ) {
				$slug = Utils\basename( $target_dir );
			}
		}

		if ( empty( $slug ) || empty( $target_dir ) ) {
			WP_CLI::error( "Invalid {$type} specified." );
		}

		$name    = ucwords( str_replace( '-', ' ', $slug ) );
		$package = str_replace( ' ', '_', $name );

		$tests_dir = "{$target_dir}/tests";
		$bin_dir   = "{$target_dir}/bin";

		$wp_filesystem->mkdir( $tests_dir );
		$wp_filesystem->mkdir( $bin_dir );

		$wp_versions_to_test = [];
		// Parse plugin readme.txt
		if ( file_exists( "{$target_dir}/readme.txt" ) ) {
			$readme_content = file_get_contents( "{$target_dir}/readme.txt" );

			preg_match( '/Requires at least\:(.*)\n/m', $readme_content, $matches );
			if ( isset( $matches[1] ) && $matches[1] ) {
				$wp_versions_to_test[] = trim( $matches[1] );
			}
		}
		$wp_versions_to_test[] = 'latest';
		$wp_versions_to_test[] = 'trunk';

		$template_data = [
			"{$type}_slug"    => $slug,
			"{$type}_package" => $package,
		];

		$force           = Utils\get_flag_value( $assoc_args, 'force' );
		$files_to_create = [
			"{$tests_dir}/bootstrap.php"   => self::mustache_render( "{$type}-bootstrap.mustache", $template_data ),
			"{$tests_dir}/test-sample.php" => self::mustache_render( "{$type}-test-sample.mustache", $template_data ),
		];
		if ( 'travis' === $assoc_args['ci'] ) {
			$files_to_create[ "{$target_dir}/.travis.yml" ] = self::mustache_render( 'plugin-travis.mustache', compact( 'wp_versions_to_test' ) );
		} elseif ( 'circle' === $assoc_args['ci'] ) {
			$files_to_create[ "{$target_dir}/.circleci/config.yml" ] = self::mustache_render( 'plugin-circle.mustache', compact( 'wp_versions_to_test' ) );
		} elseif ( 'gitlab' === $assoc_args['ci'] ) {
			$files_to_create[ "{$target_dir}/.gitlab-ci.yml" ] = self::mustache_render( 'plugin-gitlab.mustache' );
		} elseif ( 'bitbucket' === $assoc_args['ci'] ) {
			$files_to_create[ "{$target_dir}/bitbucket-pipelines.yml" ] = self::mustache_render( 'plugin-bitbucket.mustache' );
		}

		$files_written = $this->create_files( $files_to_create, $force );

		$to_copy = [
			'install-wp-tests.sh' => $bin_dir,
			'phpunit.xml.dist'    => $target_dir,
			'.phpcs.xml.dist'     => $target_dir,
		];

		foreach ( $to_copy as $file => $dir ) {
			$file_name         = "{$dir}/{$file}";
			$force             = Utils\get_flag_value( $assoc_args, 'force' );
			$should_write_file = $this->prompt_if_files_will_be_overwritten( $file_name, $force );
			if ( ! $should_write_file ) {
				continue;
			}
			$files_written[] = $file_name;

			$wp_filesystem->copy( self::get_template_path( $file ), $file_name, true );
			if ( 'install-wp-tests.sh' === $file ) {
				if ( ! $wp_filesystem->chmod( "{$dir}/{$file}", 0755 ) ) {
					WP_CLI::warning( "Couldn't mark 'install-wp-tests.sh' as executable." );
				}
			}
		}

		$skip_message    = 'All test files were skipped.';
		$success_message = 'Created test files.';
		$this->log_whether_files_written( $files_written, $skip_message, $success_message );
	}

	/**
	 * Checks that the `$target_dir` is a child directory of the WP themes or plugins directory, depending on `$type`.
	 *
	 * @param string $type       "theme" or "plugin"
	 * @param string $target_dir The theme/plugin directory to check.
	 *
	 * @return null|string Returns null on success, error message on error.
	 */
	private function check_target_directory( $type, $target_dir ) {
		$parent_dir = dirname( self::canonicalize_path( str_replace( '\\', '/', $target_dir ) ) );

		if ( 'theme' === $type && str_replace( '\\', '/', WP_CONTENT_DIR . '/themes' ) !== $parent_dir ) {
			return sprintf( 'The target directory \'%1$s\' is not in \'%2$s\'.', $target_dir, WP_CONTENT_DIR . '/themes' );
		}

		if ( 'plugin' === $type && str_replace( '\\', '/', WP_PLUGIN_DIR ) !== $parent_dir ) {
			return sprintf( 'The target directory \'%1$s\' is not in \'%2$s\'.', $target_dir, WP_PLUGIN_DIR );
		}

		// Success.
		return null;
	}

	protected function create_files( $files_and_contents, $force ) {
		$wp_filesystem = $this->init_wp_filesystem();
		$wrote_files   = [];

		foreach ( $files_and_contents as $filename => $contents ) {
			$should_write_file = $this->prompt_if_files_will_be_overwritten( $filename, $force );
			if ( ! $should_write_file ) {
				continue;
			}

			$wp_filesystem->mkdir( dirname( $filename ) );

			if ( ! $wp_filesystem->put_contents( $filename, $contents ) ) {
				WP_CLI::error( "Error creating file: {$filename}" );
			} elseif ( $should_write_file ) {
				$wrote_files[] = $filename;
			}
		}
		return $wrote_files;
	}

	protected function prompt_if_files_will_be_overwritten( $filename, $force ) {
		$should_write_file = true;
		if ( ! file_exists( $filename ) ) {
			return true;
		}

		WP_CLI::warning( 'File already exists.' );
		WP_CLI::log( $filename );
		if ( ! $force ) {
			do {
				$answer      = cli\prompt(
					'Skip this file, or replace it with scaffolding?',
					$default = false,
					$marker  = '[s/r]: '
				);
			} while ( ! in_array( $answer, [ 's', 'r' ], true ) );
			$should_write_file = 'r' === $answer;
		}

		$outcome = $should_write_file ? 'Replacing' : 'Skipping';
		WP_CLI::log( $outcome . PHP_EOL );

		return $should_write_file;
	}

	protected function log_whether_files_written( $files_written, $skip_message, $success_message ) {
		if ( empty( $files_written ) ) {
			WP_CLI::log( $skip_message );
		} else {
			WP_CLI::success( $success_message );
		}
	}

	/**
	 * Extracts dashicon name when provided or return null otherwise.
	 *
	 * @param array $assoc_args
	 * @return string|null
	 */
	private function extract_dashicon( $assoc_args ) {
		$dashicon = Utils\get_flag_value( $assoc_args, 'dashicon' );
		if ( ! $dashicon ) {
			return null;
		}
		return preg_replace( '/dashicon(-|s-)/', '', $dashicon );
	}

	/**
	 * If you're writing your files to your theme directory your textdomain also needs to be the same as your theme.
	 * Same goes for when plugin is being used.
	 */
	private function get_textdomain( $textdomain, $args ) {
		if ( strlen( $textdomain ) ) {
			return $textdomain;
		}

		if ( $args['theme'] ) {
			return $this->get_theme_name( $args['theme'] );
		}

		if ( $args['plugin'] && true !== $args['plugin'] ) {
			return $args['plugin'];
		}

		return 'YOUR-TEXTDOMAIN';
	}

	/**
	 * Generates the machine name for function declarations.
	 *
	 * @param string $slug Slug name to convert.
	 * @return string
	 */
	private function generate_machine_name( $slug ) {
		return str_replace( '-', '_', $slug );
	}

	/**
	 * Pluralizes a noun.
	 *
	 * @see    Inflector::pluralize()
	 * @param  string $word Word to be pluralized.
	 * @return string
	 */
	private function pluralize( $word ) {
		return Inflector::pluralize( $word );
	}

	protected function extract_args( $assoc_args, $defaults ) {
		$out = [];

		foreach ( $defaults as $key => $value ) {
			$out[ $key ] = Utils\get_flag_value( $assoc_args, $key, $value );
		}

		return $out;
	}

	protected function quote_comma_list_elements( $comma_list ) {
		return "'" . implode( "', '", explode( ',', $comma_list ) ) . "'";
	}

	/**
	 * Creates the themes directory if it doesn't already exist.
	 */
	protected function maybe_create_themes_dir() {

		$themes_dir = WP_CONTENT_DIR . '/themes';
		if ( ! is_dir( $themes_dir ) ) {
			wp_mkdir_p( $themes_dir );
		}

	}

	/**
	 * Creates the plugins directory if it doesn't already exist.
	 */
	protected function maybe_create_plugins_dir() {

		if ( ! is_dir( WP_PLUGIN_DIR ) ) {
			wp_mkdir_p( WP_PLUGIN_DIR );
		}

	}

	/**
	 * Initializes WP_Filesystem.
	 */
	protected function init_wp_filesystem() {
		global $wp_filesystem;
		WP_Filesystem();

		return $wp_filesystem;
	}

	/**
	 * Localizes the template path.
	 */
	private static function mustache_render( $template, $data = [] ) {
		return Utils\mustache_render( dirname( dirname( __FILE__ ) ) . "/templates/{$template}", $data );
	}

	/**
	 * Gets the template path based on installation type.
	 */
	private static function get_template_path( $template ) {
		$command_root  = Utils\phar_safe_path( dirname( __DIR__ ) );
		$template_path = "{$command_root}/templates/{$template}";

		if ( ! file_exists( $template_path ) ) {
			WP_CLI::error( "Couldn't find {$template}" );
		}

		return $template_path;
	}

	/*
	 * Returns the canonicalized path, with dot and double dot segments resolved.
	 *
	 * Copied from Symfony\Component\DomCrawler\AbstractUriElement::canonicalizePath().
	 * Implements RFC 3986, section 5.2.4.
	 *
	 * @param string $path The path to make canonical.
	 *
	 * @return string The canonicalized path.
	 */
	private static function canonicalize_path( $path ) {
		if ( '' === $path || '/' === $path ) {
			return $path;
		}

		if ( '.' === substr( $path, -1 ) ) {
			$path .= '/';
		}

		$output = [];

		foreach ( explode( '/', $path ) as $segment ) {
			if ( '..' === $segment ) {
				array_pop( $output );
			} elseif ( '.' !== $segment ) {
				$output[] = $segment;
			}
		}

		return implode( '/', $output );
	}

	/**
	 * Gets an active theme's name when true provided or the same name otherwise.
	 *
	 * @param string|bool $theme Theme name or true.
	 * @return string
	 */
	private function get_theme_name( $theme ) {
		if ( true === $theme ) {
			$theme = wp_get_theme()->template;
		}
		return strtolower( $theme );
	}
}
