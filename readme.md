## Bugs

If you find a bug, [let us know here](https://github.com/katzwebservices/GravityView/issues)

## Internal

### Plugin launch procedure:

* Pull in new translations from [Transifex](https://www.transifex.com/projects/p/gravityview/)
	1. `cd /[ path to ]/wp-content/plugins/gravityview`
	2. `tx pull -a` - Get the latest Transifex translations
	3. `find . -name \*.po -execdir sh -c 'msgfmt "$0" -o \`basename $0 .po\`.mo' '{}' \;` - Convert translations to .mo from .po files
	4. Update `readme.txt` to thank the translators
	5. Update `includes/admin-welcome.php` to add translators to contributors page
* Commit to GitHub
	1. Create a Release
	2. `python /usr/bin/git-archive-all ../gravityview.zip` - Create a ZIP in the `/plugins/` directory (uses the [git-archive-all](https://github.com/Kentzo/git-archive-all) script that includes subprojects in .zip archives)
* Push live
	1. Upload to the website
	2. Update the Version Number
	3. Upload new ReadMe file
* Announce
	1. Create HootSuite Twitter/FB/G+ message
	2. Send email via MailChimp
	3. Write blog post
