<img src="https://gravityview.co/wp-content/themes/gravityview/images/GravityView-262x80@2x.png" width="262" height="80" alt="GravityView (Floaty loves you!)" />

[GravityView](https://gravityview.co/?utm_source=github&utm_medium=readme&utm_campaign=readme) is a commercial plugin available from [https://gravityview.co](http://gravityview.co?utm_source=github&utm_medium=readme&utm_campaign=readme). The plugin is hosted here on a public Github repository in order to better faciliate community contributions from developers and users. If you have a suggestion, a bug report, or a patch for an issue, feel free to submit it here.

If you are using the plugin on a live site, please purchase a valid license from the [website](https://gravityview.co/?utm_source=github&utm_medium=readme&utm_campaign=readme). We cannot provide support to anyone that does not hold a valid license key.

----------

### Installing GravityView from Github

The plugin includes Git submodules that need to be included in the download for the plugin to be functional. In order to create a proper `.zip` file:

1. Clone the GravityView repo on your computer using the Github app
2. Install the [git-archive-all](https://github.com/Kentzo/git-archive-all) script
3. Use the following command in the Terminal:

```
cd /path/to/gravityview/
python /usr/bin/git-archive-all ../gravityview.zip
```

This will create a `gravityview.zip` file in the directory above the cloned GravityView plugin on your computer, which includes the submodules.


#### Run Unit Tests

The plugin uses PHPUnit as part of development process. Installing the testing environment is best done using a flavor of Vagrant (try [Varying Vagrant Vagrants](https://github.com/Varying-Vagrant-Vagrants/VVV)).

1. From your terminal SSH into your Vagrant box using the `vagrant ssh` command
2. `cd` into the root of your Gravity View directory
3. Run `bash tests/bin/install.sh gravityview_test root root localhost` where `root root` is substituted for your mysql username and password (VVV users can run the command as is).
4. Upon success you can run `phpunit`
