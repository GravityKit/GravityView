#!/usr/bin/env bash

if [ "$1" == 'help' ]; then
    print_gv_help
	exit 1
fi

DB_NAME="${1-gravityview_test}"
DB_USER="${2-root}"
DB_PASS="${3-root}"
DB_HOST="${4-localhost}"
WP_VERSION="${5-latest}"
PATH_TO_GF_ZIP="${6}"
WP_TESTS_DIR="${PWD}/tmp/wordpress-tests-lib"

WP_CORE_DIR="${PWD}/tmp/wordpress/"

# TRAVIS_GRAVITY_FORMS_DL_URL variable will be set in TravisCI
GRAVITY_FORMS_DL_PATH_OR_URL="${6-$TRAVIS_GRAVITY_FORMS_DL_URL}"

# Get current WordPress plugin directory
TESTS_PLUGINS_DIR="$(dirname "${PWD}")"

# -e Exit immediately if a command exits with a non-zero status
set -e

print_gv_help() {
    echo "usage: $0 [db-name (default: root)] [db-user (default: root)] [db-pass (default: root)] [db-host (default: localhost)] [wp-version (default: latest)] [gravity-forms-zip-url]"
    echo "example using remote .zip: $0 gravityview_test root root localhost latest http://example.com/path/to/gravityview.zip"
    echo "example using local path: $0 gravityview_test root root localhost latest ../gravityforms/"
    echo "If Gravity Forms is not installed locally, you must provide either a path to a local Gravity Forms directory, or a full URL that points to a .zip file of Gravity Forms. If it is, you can leave the argument blank."
}

install_wp() {
	mkdir -p $WP_CORE_DIR

	if [ $WP_VERSION == 'latest' ]; then
		local ARCHIVE_NAME="master"
	else
		local ARCHIVE_NAME="$WP_VERSION"
	fi

	curl -L https://github.com/WordPress/WordPress/archive/${ARCHIVE_NAME}.tar.gz --output /tmp/wordpress.tar.gz --silent
	tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR

	wget -nv -O $WP_CORE_DIR/wp-content/db.php https://raw.github.com/markoheijnen/wp-mysqli/master/db.php
}

install_gravity_forms(){
    mkdir -p $WP_CORE_DIR

    # If you have passed a path, check if it exists. If it does, use that as the Gravity Forms location
    if [[ $GRAVITY_FORMS_DL_PATH_OR_URL != '' && -d $GRAVITY_FORMS_DL_PATH_OR_URL ]]; then

        rsync -ar --exclude=.git "$GRAVITY_FORMS_DL_PATH_OR_URL" "$PWD"/tmp/gravityforms/

    # Otherwise,
    elif [[ $GRAVITY_FORMS_DL_PATH_OR_URL != '' ]]; then

        # Pull from remote
	    curl -L "$GRAVITY_FORMS_DL_PATH_OR_URL" --output "$PWD"/tmp/gravityforms.zip

	    # -o will overwrite files. -q is quiet mode
	    unzip -o -q "$PWD"/tmp/gravityforms.zip -d "$PWD"/tmp/

    # Otherwise, if you have Gravity Forms installed locally, use that.
    else
        if [[ -d "$TESTS_PLUGINS_DIR"/gravityforms ]]; then
            rsync -ar --exclude=.git "$TESTS_PLUGINS_DIR"/gravityforms "$PWD"/tmp/
        else
            print_gv_help
            exit 1
        fi
	fi
}

install_rest_api() {
	curl -L https://github.com/WP-API/api-core/archive/develop.tar.gz --output /tmp/api-core.tar.gz --silent

	mkdir -p $PWD/tmp/api-core
	tar --strip-components=1 -zxf /tmp/api-core.tar.gz -C $PWD/tmp/api-core
}

install_test_suite() {
	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i .bak'
	else
		local ioption='-i'
	fi

	# set up testing suite
	mkdir -p $WP_TESTS_DIR
	cd $WP_TESTS_DIR
	svn co --quiet https://develop.svn.wordpress.org/trunk/tests/phpunit/includes/

	wget -nv -O wp-tests-config.php https://develop.svn.wordpress.org/trunk/wp-tests-config-sample.php
	sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR':" wp-tests-config.php
	sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" wp-tests-config.php
	sed $ioption "s/yourusernamehere/$DB_USER/" wp-tests-config.php
	sed $ioption "s/yourpasswordhere/$DB_PASS/" wp-tests-config.php
	sed $ioption "s|localhost|${DB_HOST}|" wp-tests-config.php
}


install_db() {
	# parse DB_HOST for port or socket references
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]};
	local DB_SOCK_OR_PORT=${PARTS[1]};
	local EXTRA=""

	if ! [ -z $DB_HOSTNAME ] ; then
		if [ $(echo $DB_SOCK_OR_PORT | grep -e '^[0-9]\{1,\}$') ]; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif ! [ -z $DB_SOCK_OR_PORT ] ; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [ -z $DB_HOSTNAME ] ; then
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

	# create database
	mysqladmin CREATE $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA;
}

install_gravity_forms
install_wp
install_rest_api
install_test_suite
install_db
