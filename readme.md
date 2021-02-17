<img src="https://gravityview.co/wp-content/themes/Website/images/GravityView-262x80@2x.png" width="262" height="80" alt="GravityView (Floaty loves you!)" />

![CircleCI](https://circleci.com/gh/gravityview/GravityView/tree/develop.svg?style=svg&circle-token=19fbfae4c960858b2e08be4f7e993df41df5f367)

[GravityView](https://gravityview.co/?utm_source=github&utm_medium=readme&utm_campaign=readme) is a commercial plugin available from [https://gravityview.co](http://gravityview.co?utm_source=github&utm_medium=readme&utm_campaign=readme). The plugin is hosted here on a public GitHub repository to better facilitate community contributions from developers and users. If you have a suggestion, a bug report, or a patch for an issue, feel free to submit it here.

If you are using the plugin on a live site, please purchase a valid license from the [website](https://gravityview.co/?utm_source=github&utm_medium=readme&utm_campaign=readme). We cannot provide support to anyone that does not hold a valid license key.

----------

#### Unit Tests

The plugin uses [PHPUnit](https://phpunit.de/) as part of the development process. We offer preconfigured Docker containers and a custom Bash script to facilitate running tests against multiple PHP and WordPress versions in a predictable environment. Visit our [Tooling](https://github.com/gravityview/Tooling/blob/main/docker-unit-tests/) repo for information regarding how to configure and run tests.   

### Acceptance Tests

The plugin uses [Codeception](https://codeception.com/) for acceptance testing. To configure and run tests:

1. Install and configure [Docker](https://www.docker.com/)
2. Configure environment variables by running:
   - `export GRAVITYFORMS_KEY=[YOUR GRAVITY FORMS KEY HERE]`
   - `export GRAVITYVIEW_KEY=[YOUR GRAVITYVIEW KEY HERE]`
   - `export PLUGIN_DIR=[/path/to/gravityview]`
3. Finally, `cd` to the GravityView plugin directory and run `docker-compose -f tests/acceptance/docker/docker-compose.yml run codeception`
   
See [Codeception commands reference](https://codeception.com/docs/reference/Commands) for a full list of available flags.

----------

#### Acknowledgements

We are thankful to the following services and open source software that help enhance our plugin:

- [BrowserStack](https://www.browserstack.com) - Automated browser testing
- [Flexibility](https://github.com/10up/flexibility) - Adds support for CSS flexbox to Internet Explorer 8 &amp; 9
- [Gamajo Template Loader](https://github.com/GaryJones/Gamajo-Template-Loader) - Makes it easy to load template files with user overrides
- [jQuery Cookie plugin](https://github.com/carhartl/jquery-cookie) - Access and store cookie values with jQuery
- [PHPEnkoder](https://github.com/jnicol/standalone-phpenkoder) - Email address obfuscation
