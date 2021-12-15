Feature: WordPress block code scaffolding

  Background:
    Given a WP install
    Given I run `wp scaffold plugin movies`
    And I run `wp plugin path movies --dir`
    And save STDOUT as {PLUGIN_DIR}
    Given I run `wp theme install p2 --activate`
    And I run `wp theme path p2 --dir`
    And save STDOUT as {THEME_DIR}


  Scenario: Scaffold a block with an invalid slug
    When I try `wp scaffold block The_Godfather`
    Then STDERR should be:
      """
      Error: Invalid block slug specified. Block slugs can contain only lowercase alphanumeric characters or dashes, and start with a letter.
      """

  Scenario: Scaffold a block with a missing plugin and theme
    When I try `wp scaffold block the-godfather`
    Then STDERR should be:
      """
      Error: No plugin or theme selected.
      """

  Scenario: Scaffold a block for an invalid plugin
    When I try `wp scaffold block the-godfather --plugin=unknown`
    Then STDERR should be:
      """
      Error: Can't find 'unknown' plugin.
      """

  Scenario: Scaffold a block for an invalid plugin slug
    When I run `wp scaffold plugin plugin.name.with.dots`
    And I try `wp scaffold block some-block --plugin=plugin.name.with.dots`
    Then STDERR should contain:
      """
      Error: Invalid plugin name specified.
      """

  Scenario: Scaffold a block for a specific plugin
    When I run `wp scaffold block the-green-mile --plugin=movies`
    Then the {PLUGIN_DIR}/blocks/the-green-mile.php file should exist
    And the {PLUGIN_DIR}/blocks/the-green-mile.php file should contain:
      """
      function the_green_mile_block_init() {
      """
    And the {PLUGIN_DIR}/blocks/the-green-mile.php file should contain:
      """
      index_js = 'the-green-mile/index.js';
      """
    And the {PLUGIN_DIR}/blocks/the-green-mile.php file should contain:
      """
	    $editor_css = 'the-green-mile/editor.css';
      """
    And the {PLUGIN_DIR}/blocks/the-green-mile.php file should contain:
      """
	    $style_css = 'the-green-mile/style.css';
      """
    And the {PLUGIN_DIR}/blocks/the-green-mile.php file should contain:
      """
	    register_block_type( 'movies/the-green-mile', [
      """
    And the {PLUGIN_DIR}/blocks/the-green-mile.php file should contain:
      """
	    add_action( 'init', 'the_green_mile_block_init' );
      """
    And the {PLUGIN_DIR}/blocks/the-green-mile/index.js file should exist
    And the {PLUGIN_DIR}/blocks/the-green-mile/index.js file should contain:
      """
      registerBlockType( 'movies/the-green-mile', {
      """
    And the {PLUGIN_DIR}/blocks/the-green-mile/index.js file should contain:
      """
      title: __( 'The green mile', 'movies' ),
      """
    And the {PLUGIN_DIR}/blocks/the-green-mile/index.js file should contain:
      """
      category: 'widgets',
      """
    And the {PLUGIN_DIR}/blocks/the-green-mile/index.js file should contain:
      """
      __( 'Hello from the editor!', 'movies' )
      """
    And the {PLUGIN_DIR}/blocks/the-green-mile/index.js file should contain:
      """
      __( 'Hello from the saved content!', 'movies' )
      """
    And the {PLUGIN_DIR}/blocks/the-green-mile/editor.css file should exist
    And the {PLUGIN_DIR}/blocks/the-green-mile/editor.css file should contain:
      """
      .wp-block-movies-the-green-mile {
      """
    And the {PLUGIN_DIR}/blocks/the-green-mile/style.css file should exist
    And the {PLUGIN_DIR}/blocks/the-green-mile/style.css file should contain:
      """
      .wp-block-movies-the-green-mile {
      """
    And STDOUT should be:
      """
      Success: Created block 'The green mile'.
      """

  Scenario: Scaffold a block with a specific title provided
    When I run `wp scaffold block shawshank-redemption --plugin=movies --title="The Shawshank Redemption"`
    Then the {PLUGIN_DIR}/blocks/shawshank-redemption/index.js file should contain:
      """
      title: __( 'The Shawshank Redemption', 'movies' ),
      """
    And STDOUT should be:
      """
      Success: Created block 'The Shawshank Redemption'.
      """

  Scenario: Scaffold a block with a specific dashicon provided
    When I run `wp scaffold block forrest-gump --plugin=movies --dashicon=movie`
    Then the {PLUGIN_DIR}/blocks/forrest-gump/index.js file should contain:
      """
      icon: 'movie',
      """
    And STDOUT should be:
      """
      Success: Created block 'Forrest gump'.
      """

  Scenario: Scaffold a block with a specific category provided
    When I run `wp scaffold block pulp-fiction --plugin=movies --category=embed`
    Then the {PLUGIN_DIR}/blocks/pulp-fiction/index.js file should contain:
      """
      category: 'embed',
      """
    And STDOUT should be:
      """
      Success: Created block 'Pulp fiction'.
      """

  Scenario: Scaffold a block for an active theme
    When I run `wp scaffold block fight-club --theme`
    Then the {THEME_DIR}/blocks/fight-club.php file should exist
    And the {THEME_DIR}/blocks/fight-club/index.js file should exist
    And the {THEME_DIR}/blocks/fight-club/editor.css file should exist
    And the {THEME_DIR}/blocks/fight-club/style.css file should exist
    And STDOUT should be:
      """
      Success: Created block 'Fight club'.
      """

  Scenario: Scaffold a block for an invalid theme
    When I try `wp scaffold block intouchables --theme=unknown`
    Then STDERR should be:
      """
      Error: Can't find 'unknown' theme.
      """

  Scenario: Scaffold a block for a specific theme
    When I run `wp scaffold block intouchables --theme=p2`
    Then the {THEME_DIR}/blocks/intouchables.php file should exist
    And the {THEME_DIR}/blocks/intouchables/index.js file should exist
    And the {THEME_DIR}/blocks/intouchables/editor.css file should exist
    And the {THEME_DIR}/blocks/intouchables/style.css file should exist
    And STDOUT should be:
      """
      Success: Created block 'Intouchables'.
      """

  Scenario: Plugin- or theme-specific functions are only used in the correct context
    When I run `wp scaffold block plugin-block --plugin=movies`
    And I run `wp scaffold block theme-block --theme=p2`
    Then the {PLUGIN_DIR}/blocks/plugin-block.php file should contain:
      """
      plugins_url
      """
    And the {PLUGIN_DIR}/blocks/plugin-block.php file should not contain:
      """
      get_stylesheet_directory
      """
    And the {THEME_DIR}/blocks/theme-block.php file should contain:
      """
      get_stylesheet_directory
      """
    And the {THEME_DIR}/blocks/theme-block.php file should not contain:
      """
      plugins_url
      """

