<img src="https://gravityview.co/wp-content/themes/gravityview/images/GravityView-262x80@2x.png" width="262" height="80" alt="GravityView (Floaty loves you!)" />

[![Build Status](https://travis-ci.org/gravityview/GravityView.svg?branch=2.0)](https://travis-ci.org/gravityview/GravityView) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gravityview/GravityView/badges/quality-score.png?b=2.0)](https://scrutinizer-ci.com/g/gravityview/GravityView/?branch=2.0) [![Coverage Status](https://coveralls.io/repos/gravityview/GravityView/badge.svg?branch=2.0&service=github)](https://coveralls.io/github/gravityview/GravityView?branch=2.0)

[GravityView](https://gravityview.co/?utm_source=github&utm_medium=readme&utm_campaign=readme) is a commercial plugin available from [https://gravityview.co](http://gravityview.co?utm_source=github&utm_medium=readme&utm_campaign=readme). The plugin is hosted here on a public GitHub repository in order to better facilitate community contributions from developers and users. If you have a suggestion, a bug report, or a patch for an issue, feel free to submit it here.

If you are using the plugin on a live site, please purchase a valid license from the [website](https://gravityview.co/?utm_source=github&utm_medium=readme&utm_campaign=readme). We cannot provide support to anyone that does not hold a valid license key.

----------

#### Run Unit Tests

The plugin uses PHPUnit as part of development process. Installing the testing environment is best done using a flavor of Vagrant (try [Varying Vagrant Vagrants](https://github.com/Varying-Vagrant-Vagrants/VVV)).

1. From your terminal SSH into your Vagrant box using the `vagrant ssh` command
2. `cd` into the root of your GravityView directory (VVV users can use `cd /srv/www/wordpress-default/wp-content/plugins/gravityview/`)
3. Run `bash tests/bin/install.sh gravityview_test root root localhost` where `root root` is substituted for your mysql username and password (VVV users can run the command as is).
    - If you are running locally and have Gravity Forms installed, the script will check for `/gravityforms/` directory in your plugins folder. If it exists, it will use that directory.
    - If the script doesn't find a Gravity Forms directory, it will need the path to Gravity Forms directory or the URL of a .zip file passed as the 7th parameter. Example: `bash tests/bin/install.sh gravityview_test root root localhost latest false http://example.com/path/to/gravityview.zip` or `bash tests/bin/install.sh gravityview_test root root localhost latest false ../gravityview/`
4. Upon success you can run `phpunit`

__If you want to generate a code coverage report__ you can run the following `phpunit --coverage-html "./tmp/coverage"` and then a report will be generated in the `/tmp/coverage/` subdirectory of the GravityView plugin.

----------

#### Thanks to:

- [BrowserStack](https://www.browserstack.com) for automated browser testing
- [Flexibility](https://github.com/10up/flexibility) - Adds support for CSS flexbox to Internet Explorer 8 &amp; 9
- [Gamajo Template Loader](https://github.com/GaryJones/Gamajo-Template-Loader) - Makes it easy to load template files with user overrides
- [jQuery Cookie plugin](https://github.com/carhartl/jquery-cookie) - Access and store cookie values with jQuery
- [PHPEnkoder](https://github.com/jnicol/standalone-phpenkoder) script encodes the email addresses