<img src="https://www.gravitykit.com/wp-content/themes/Website/images/GravityView-262x80@2x.png" width="262" height="80" alt="GravityView (Floaty loves you!)" />

![CircleCI](https://circleci.com/gh/GravityKit/GravityView/tree/develop.svg?style=svg&circle-token=CCIPRJ_HANdBG7RCeCEaYW4SJtZEK_c978aa92e6f77ee3fd94b9d2d74b3b8eae7dd80a)

[GravityView](https://www.gravitykit.com/?utm_source=github&utm_medium=readme&utm_campaign=readme) is a commercial plugin available from [https://www.gravitykit.com](http://www.gravitykit.com?utm_source=github&utm_medium=readme&utm_campaign=readme). The plugin is hosted here on a public GitHub repository to better facilitate community contributions from developers and users. If you have a suggestion, a bug report, or a patch for an issue, feel free to submit it here.

If you are using the plugin on a live site, please purchase a valid license from the [website](https://www.gravitykit.com/?utm_source=github&utm_medium=readme&utm_campaign=readme). We cannot provide support to anyone that does not hold a valid license key.

----------
### Installation Instructions

To install the plugin, download [the latest release](https://github.com/GravityKit/GravityView/releases) to your WordPress plugins folder and then activate it.

### For Developers

If you wish to make changes to the plugin, you need to install the necessary dependencies and compile assets. First, a couple of prerequisites:

1. Make sure that you have the full plugin source code by either cloning this repo or downloading the source code (not the versioned release) from the [Releases section](https://github.com/GravityKit/GravityView/releases).

2. Install [Composer](https://getcomposer.org/)

3. Install [Node.js](https://nodejs.org/en/)
   - We recommend a Node.js version manager [for Linux/macOS](https://github.com/nvm-sh/nvm) or [Windows](https://github.com/coreybutler/nvm-windows)
   - Run `npm install -g grunt-cli` if this the first time you've installed Node.js or switched to a new version

Next, install dependencies:
1. Run `composer install-public` to install Composer dependencies, including development dependencies, or `composer install-public-no-dev` if you don't need the development dependencies
   - If you have access to private GravityKit repositories, you can run `composer install` or `composer install --no-dev` instead
   
2. Run `npm install` to install Node.js dependencies
 
To compile/minify UI assets, run `grunt` or use the following commands separately:

1. `grunt sass` & `grunt postcss` to compile and minify CSS files

2. `grunt uglify` to minify JavaScript files

3. `grunt imagemin` to minify images

You do not have to run the commands if submitting a pull request as the minification process is handled by our CI/CD pipeline.

#### Unit Tests

We offer preconfigured Docker containers and a custom Bash script to facilitate running unit tests using multiple PHP versions in a predictable environment. Visit our [Tooling](https://github.com/gravityview/Tooling/blob/main/docker-unit-tests/) repo for information regarding how to configure and run tests.   

If you wish to run tests using your local environment, use the following instructions:

1. Clone the [WordPress Develop](https://github.com/WordPress/wordpress-develop) and [Gravity Forms](https://github.com/gravityforms/gravityforms) repositories

2. In the cloned repository folder, copy `wp-tests-config-sample.php` to `wp-tests-config.php`, and edit `wp-tests-config.php` to define the following constants:
    ```php
    define('DB_NAME', getenv('DB_NAME'));
    define('DB_USER', getenv('DB_USER'));
    define('DB_PASSWORD', getenv('DB_PASSWORD'));
    define('DB_HOST', getenv('DB_HOST'));
    ```

3. Run PHPUnit using the following command (ensure to replace placeholders with your actual system values):
    ```bash
    DB_NAME=db_name \
    DB_USER=db_user \
    DB_PASSWORD=db_password \
    DB_HOST=db_host \
    GF_PLUGIN_DIR=/path/to/gravityforms \
    WP_TESTS_DIR=/path/to/wordpress-develop/tests/phpunit \
    vendor/bin/phpunit --no-coverage
    ```
   
    Alternatively, you can copy `phpunit.xml.dist` to `phpunit.xml`, and edit `phpunit.xml` to set the environment variables there:
    ```xml
    <php>
        <const name="DOING_GRAVITYVIEW_TESTS" value="1" />
        <env name="DB_NAME" value="db_name"/>
        <env name="DB_USER" value="db_user"/>
        <env name="DB_PASSWORD" value="db_password"/>
        <env name="DB_HOST" value="db_host"/>
        <env name="GF_PLUGIN_DIR" value="/path/to/gravityforms"/>
        <env name="WP_TESTS_DIR" value="/path/to/wordpress-develop/tests/phpunit"/>
   </php>
    ```

#### End-to-End Tests

We use Playwright for end-to-end (E2E) testing against a WordPress environment bootstrapped in Docker using [wp-env](https://developer.wordpress.org/block-editor/getting-started/devenv/get-started-with-wp-env/).

To set up and run E2E tests:

1. Run `npm install` to install Node.js dependencies.

2. Copy `.env.sample` to `.env` and update it with the correct path to the Gravity Forms plugin and license keys.

3. Run `npm run tests:wp-env:setup` to configure the environment, then run `npm run tests:wp-env:run` to execute the tests.

To reset the database between test runs, use `npm run tests:e2e:clean`.

----------

### Acknowledgements

We are thankful to the following open source software that help enhance our plugin:

- [Flexibility](https://github.com/10up/flexibility) - Adds support for CSS flexbox to Internet Explorer 8 &amp; 9
- [Gamajo Template Loader](https://github.com/GaryJones/Gamajo-Template-Loader) - Makes it easy to load template files with user overrides
- [jQuery Cookie plugin](https://github.com/carhartl/jquery-cookie) - Access and store cookie values with jQuery
- [PHPEnkoder](https://github.com/jnicol/standalone-phpenkoder) - Email address obfuscation
