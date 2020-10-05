#!/bin/bash

WP_FOLDER=/wp-core
PLUGIN_FOLDER=$WP_FOLDER/wp-content/plugins/gravityview

# Allows WP CLI to run with the right permissions
wp-su() {
	sudo -E -u www-data wp "$@"
}

# Set correct permissions
cd $WP_FOLDER
sudo -E -u www-data mkdir -p wp-content/uploads
chmod 777 wp-content/uploads
chown www-data:www-data wp-content
chmod 755 wp-content

# Use shared volume for WP-CLI cache
export WP_CLI_CACHE_DIR=$WP_FOLDER/.wp-cli/cache

# Wait for the DB
while ! mysqladmin ping -h${MYSQL_HOST} --silent; do
    echo 'Waiting for the database'
    sleep 1
done

# Create wp-config.php & disable email verification
if [ ! -f $WP_FOLDER/wp-config.php ]; then

  wp-su core config --dbhost=${MYSQL_HOST} --dbname=${MYSQL_DATABASE} --dbuser=${MYSQL_USER} --dbpass=${MYSQL_PASSWORD} --extra-php="define( 'SCRIPT_DEBUG', true );" --force

  if ! $(cat $WP_FOLDER/wp-content/themes/twentytwenty/functions.php | grep -q 'send_password_change_email'); then

    echo "add_filter( 'admin_email_check_interval', '__return_false' );" >> $WP_FOLDER/wp-content/themes/twentytwenty/functions.php

  fi

fi

# Install WordPress
if ! $(wp-su core is-installed); then

	echo "Installing WordPress"

	wp-su core install --url=${WP_URL} --title="GravityView Core Acceptance" --admin_user=${WP_ADMIN_USER} --admin_password=${WP_ADMIN_PASS} --admin_email=${WP_ADMIN_EMAIL}

fi

# Install Gravity Forms CLI
if ! $(wp-su plugin is-installed gravityformscli); then

	echo "Installing Gravity Forms CLI"

	wp-su plugin install gravityformscli --activate

fi

# Install Gravity Forms
if ! $(wp-su plugin is-installed gravityforms); then

	echo "Installing Gravity Forms"

	wp-su gf install --key=${GRAVITYFORMS_KEY} --activate

fi

# Activate GravityView
if ! $(wp-su plugin is-active gravityview); then

	echo "Activating GravityView"

	wp-su plugin activate gravityview

fi

# Install vendor files and run tests
cd $PLUGIN_FOLDER

composer install

./vendor/bin/codecept run acceptance $@

exit $?
