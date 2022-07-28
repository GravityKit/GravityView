Feature: List the values of a wp-config.php file

  Scenario: List constants, variables and files included from wp-config.php
    Given an empty directory
    And WP files
    And a wp-config-extra.php file:
      """
      require_once 'custom-include.php';
      """
    And a custom-include.php file:
      """
      <?php // This won't work without this file being empty. ?>
      """

    When I run `wp core config {CORE_CONFIG_SETTINGS} --extra-php < wp-config-extra.php`
    Then STDOUT should contain:
      """
      Generated 'wp-config.php' file.
      """

    When I run `wp config list --fields=name,type`
    Then STDOUT should be a table containing rows:
      | name               | type     |
      | DB_NAME            | constant |
      | DB_USER            | constant |
      | DB_PASSWORD        | constant |
      | DB_HOST            | constant |
      | custom-include.php | includes |

    When I try `wp config list`
    Then STDOUT should be a table containing rows:
      | name | value | type |

  Scenario: List constants, variables and files included from wp-custom-config.php
    Given an empty directory
    And WP files
    And a wp-config-extra.php file:
      """
      require_once 'custom-include.php';
      """
    And a custom-include.php file:
      """
      <?php // This won't work without this file being empty. ?>
      """

    When I run `wp core config {CORE_CONFIG_SETTINGS} --config-file='wp-custom-config.php' --extra-php < wp-config-extra.php`
    Then STDOUT should contain:
      """
      Generated 'wp-custom-config.php' file.
      """

    When I run `wp config list --fields=name,type --config-file='wp-custom-config.php'`
    Then STDOUT should be a table containing rows:
      | name               | type     |
      | DB_NAME            | constant |
      | DB_USER            | constant |
      | DB_PASSWORD        | constant |
      | DB_HOST            | constant |
      | custom-include.php | includes |

    When I try `wp config list --config-file='wp-custom-config.php'`
    Then STDOUT should be a table containing rows:
      | name | value | type |

  Scenario: Filter the list of values of a wp-config.php file
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS}`
    Then STDOUT should contain:
      """
      Generated 'wp-config.php' file.
      """

    When I run `wp config list --fields=name`
    Then STDOUT should be a table containing rows:
      | name             |
      | table_prefix     |
      | DB_NAME          |
      | DB_USER          |
      | DB_PASSWORD      |
      | DB_HOST          |
      | DB_CHARSET       |
      | DB_COLLATE       |
      | AUTH_KEY         |
      | SECURE_AUTH_KEY  |
      | LOGGED_IN_KEY    |
      | NONCE_KEY        |
      | AUTH_SALT        |
      | SECURE_AUTH_SALT |
      | LOGGED_IN_SALT   |
      | NONCE_SALT       |

    When I run `wp config list --fields=name DB_`
    Then STDOUT should be a table containing rows:
      | name        |
      | DB_NAME     |
      | DB_USER     |
      | DB_PASSWORD |
      | DB_HOST     |
      | DB_CHARSET  |
      | DB_COLLATE  |
    Then STDOUT should not contain:
      """
      table_prefix
      """
    Then STDOUT should not contain:
      """
      AUTH_KEY
      """

    When I run `wp config list --fields=name DB_HOST`
    Then STDOUT should be a table containing rows:
      | name    |
      | DB_HOST |
    Then STDOUT should not contain:
      """
      table_prefix
      """
    Then STDOUT should not contain:
      """
      AUTH_KEY
      """
    Then STDOUT should not contain:
      """
      DB_NAME
      """

    When I try `wp config list --fields=name --strict`
    Then STDERR should be:
      """
      Error: The --strict option can only be used in combination with a filter.
      """

    When I try `wp config list --fields=name DB_ --strict`
    Then STDERR should be:
      """
      Error: No matching entries found in 'wp-config.php'.
      """

    When I run `wp config list --fields=name DB_USER DB_PASSWORD`
    Then STDOUT should be a table containing rows:
      | name        |
      | DB_USER     |
      | DB_PASSWORD |
    Then STDOUT should not contain:
      """
      table_prefix
      """
    Then STDOUT should not contain:
      """
      AUTH_KEY
      """
    Then STDOUT should not contain:
      """
      DB_HOST
      """

    When I run `wp config list --fields=name DB_USER DB_PASSWORD --strict`
    Then STDOUT should be a table containing rows:
      | name        |
      | DB_USER     |
      | DB_PASSWORD |
    Then STDOUT should not contain:
      """
      table_prefix
      """
    Then STDOUT should not contain:
      """
      AUTH_KEY
      """
    Then STDOUT should not contain:
      """
      DB_HOST
      """

    When I run `wp config list --fields=name _KEY _SALT`
    Then STDOUT should be a table containing rows:
      | name             |
      | AUTH_KEY         |
      | SECURE_AUTH_KEY  |
      | LOGGED_IN_KEY    |
      | NONCE_KEY        |
      | AUTH_SALT        |
      | SECURE_AUTH_SALT |
      | LOGGED_IN_SALT   |
      | NONCE_SALT       |
    Then STDOUT should not contain:
      """
      table_prefix
      """
    Then STDOUT should not contain:
      """
      DB_HOST
      """

  Scenario: Filter the list of values of a wp-custom-config.php file
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS} --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
      """
      Generated 'wp-custom-config.php' file.
      """

    When I run `wp config list --fields=name  --config-file='wp-custom-config.php'`
    Then STDOUT should be a table containing rows:
      | name             |
      | table_prefix     |
      | DB_NAME          |
      | DB_USER          |
      | DB_PASSWORD      |
      | DB_HOST          |
      | DB_CHARSET       |
      | DB_COLLATE       |
      | AUTH_KEY         |
      | SECURE_AUTH_KEY  |
      | LOGGED_IN_KEY    |
      | NONCE_KEY        |
      | AUTH_SALT        |
      | SECURE_AUTH_SALT |
      | LOGGED_IN_SALT   |
      | NONCE_SALT       |

    When I run `wp config list --fields=name DB_ --config-file='wp-custom-config.php'`
    Then STDOUT should be a table containing rows:
      | name        |
      | DB_NAME     |
      | DB_USER     |
      | DB_PASSWORD |
      | DB_HOST     |
      | DB_CHARSET  |
      | DB_COLLATE  |
    Then STDOUT should not contain:
      """
      table_prefix
      """
    Then STDOUT should not contain:
      """
      AUTH_KEY
      """

    When I run `wp config list --fields=name DB_HOST --config-file='wp-custom-config.php'`
    Then STDOUT should be a table containing rows:
      | name    |
      | DB_HOST |
    Then STDOUT should not contain:
      """
      table_prefix
      """
    Then STDOUT should not contain:
      """
      AUTH_KEY
      """
    Then STDOUT should not contain:
      """
      DB_NAME
      """

    When I try `wp config list --fields=name --strict --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: The --strict option can only be used in combination with a filter.
      """

    When I try `wp config list --fields=name DB_ --strict --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: No matching entries found in 'wp-custom-config.php'.
      """

    When I run `wp config list --fields=name DB_USER DB_PASSWORD --config-file='wp-custom-config.php'`
    Then STDOUT should be a table containing rows:
      | name        |
      | DB_USER     |
      | DB_PASSWORD |
    Then STDOUT should not contain:
      """
      table_prefix
      """
    Then STDOUT should not contain:
      """
      AUTH_KEY
      """
    Then STDOUT should not contain:
      """
      DB_HOST
      """

    When I run `wp config list --fields=name DB_USER DB_PASSWORD --strict --config-file='wp-custom-config.php'`
    Then STDOUT should be a table containing rows:
      | name        |
      | DB_USER     |
      | DB_PASSWORD |
    Then STDOUT should not contain:
      """
      table_prefix
      """
    Then STDOUT should not contain:
      """
      AUTH_KEY
      """
    Then STDOUT should not contain:
      """
      DB_HOST
      """

    When I run `wp config list --fields=name _KEY _SALT --config-file='wp-custom-config.php'`
    Then STDOUT should be a table containing rows:
      | name             |
      | AUTH_KEY         |
      | SECURE_AUTH_KEY  |
      | LOGGED_IN_KEY    |
      | NONCE_KEY        |
      | AUTH_SALT        |
      | SECURE_AUTH_SALT |
      | LOGGED_IN_SALT   |
      | NONCE_SALT       |
    Then STDOUT should not contain:
      """
      table_prefix
      """
    Then STDOUT should not contain:
      """
      DB_HOST
      """

  Scenario: Configuration can be formatted for dotenv files
    Given an empty directory
    And WP files
    And a wp-config.php file:
      """
      <?php
      define( 'DB_NAME', 'wp_cli_test' );
      define( 'DB_USER', 'wp_cli_test' );
      define( 'DB_PASSWORD', 'password1' );
      define( 'DB_HOST', '127.0.0.1:33068' );
      define( 'DB_CHARSET', 'utf8' );
      define( 'DB_COLLATE', '' );

      define('AUTH_KEY',         '7mj0&+HVh{90t.S]m{u)$\'tCCB$:.[7}jAf`)~hS{ZL#v+&F#kA^p|*R<YaMFR,p');
      define('SECURE_AUTH_KEY',  'c_0aQDj2.s}]rC+,JmU(VG!g4LapYREo+akySvE.M;lS4|Y(u%:f-|5wV_8$Niwm');
      define('LOGGED_IN_KEY',    '-wRn2hkn >J=FA3Si$i8uco>+6vB0&aej6X4r@2dc]V}|iFE!{CjOA*u#g4@Y.2j');
      define('NONCE_KEY',        'O..4n~e~(~:7NGyA!q.(`:X,(RcR(n_o|&(*hKrX2+9D=,&1k2k-;>Y_@X+<CwRv');
      define('AUTH_SALT',        '8+hWU&.Zb ^Fix,Y*|XzaC-*&@?Nw%u(2-G_:6vz0RH(QE5*PP;!h6z{!t>,!6g!');
      define('SECURE_AUTH_SALT', 'VNH|C>w-z?*dtP4ofy!v%RumM.}ug]mx7$QZW|C-R4T`d-~x|xvL{Xc_5C89K(,^');
      define('LOGGED_IN_SALT',   'Iwtez|Q`M l7lup; x&ml8^C|Lk&X[3/-l!$`P3GM$7:WI&X$Hn)unjZ9u~g4m[c');
      define('NONCE_SALT',       'QxcY|80 $f_dRkn*Liu|Ak*aas41g(q5X_h+m8Z$)tf6#TZ+Q,D#%n]g -{=mj1)');

      $table_prefix = 'wp_';

      define( 'WP_ALLOW_MULTISITE', true );
      define( 'MULTISITE', true );
      define( 'SUBDOMAIN_INSTALL', false );
      $base = '/';
      define( 'DOMAIN_CURRENT_SITE', 'example.com' );
      define( 'PATH_CURRENT_SITE', '/' );
      define( 'SITE_ID_CURRENT_SITE', 1 );
      define( 'BLOG_ID_CURRENT_SITE', 1 );

      /* That's all, stop editing! Happy publishing. */

      if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', dirname( __FILE__ ) . '/' );
      }

      require_once ABSPATH . 'wp-settings.php';
      """

    When I run `wp config list --fields=name`
    Then STDOUT should be a table containing rows:
      | name                 |
      | table_prefix         |
      | base                 |
      | DB_NAME              |
      | DB_USER              |
      | DB_PASSWORD          |
      | DB_HOST              |
      | DB_CHARSET           |
      | DB_COLLATE           |
      | AUTH_KEY             |
      | SECURE_AUTH_KEY      |
      | LOGGED_IN_KEY        |
      | NONCE_KEY            |
      | AUTH_SALT            |
      | SECURE_AUTH_SALT     |
      | LOGGED_IN_SALT       |
      | NONCE_SALT           |
      | WP_ALLOW_MULTISITE   |
      | MULTISITE            |
      | SUBDOMAIN_INSTALL    |
      | DOMAIN_CURRENT_SITE  |
      | PATH_CURRENT_SITE    |
      | SITE_ID_CURRENT_SITE |
      | BLOG_ID_CURRENT_SITE |

    When I run `wp config list --format=dotenv`
    Then STDOUT should be:
      """
      DB_NAME='wp_cli_test'
      DB_USER='wp_cli_test'
      DB_PASSWORD='password1'
      DB_HOST='127.0.0.1:33068'
      DB_CHARSET='utf8'
      DB_COLLATE=''
      AUTH_KEY='7mj0&+HVh{90t.S]m{u)$\'tCCB$:.[7}jAf`)~hS{ZL#v+&F#kA^p|*R<YaMFR,p'
      SECURE_AUTH_KEY='c_0aQDj2.s}]rC+,JmU(VG!g4LapYREo+akySvE.M;lS4|Y(u%:f-|5wV_8$Niwm'
      LOGGED_IN_KEY='-wRn2hkn >J=FA3Si$i8uco>+6vB0&aej6X4r@2dc]V}|iFE!{CjOA*u#g4@Y.2j'
      NONCE_KEY='O..4n~e~(~:7NGyA!q.(`:X,(RcR(n_o|&(*hKrX2+9D=,&1k2k-;>Y_@X+<CwRv'
      AUTH_SALT='8+hWU&.Zb ^Fix,Y*|XzaC-*&@?Nw%u(2-G_:6vz0RH(QE5*PP;!h6z{!t>,!6g!'
      SECURE_AUTH_SALT='VNH|C>w-z?*dtP4ofy!v%RumM.}ug]mx7$QZW|C-R4T`d-~x|xvL{Xc_5C89K(,^'
      LOGGED_IN_SALT='Iwtez|Q`M l7lup; x&ml8^C|Lk&X[3/-l!$`P3GM$7:WI&X$Hn)unjZ9u~g4m[c'
      NONCE_SALT='QxcY|80 $f_dRkn*Liu|Ak*aas41g(q5X_h+m8Z$)tf6#TZ+Q,D#%n]g -{=mj1)'
      WP_ALLOW_MULTISITE=1
      MULTISITE=1
      SUBDOMAIN_INSTALL=''
      DOMAIN_CURRENT_SITE='example.com'
      PATH_CURRENT_SITE='/'
      SITE_ID_CURRENT_SITE=1
      BLOG_ID_CURRENT_SITE=1
      """
