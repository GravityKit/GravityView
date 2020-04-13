#!/bin/bash

# Allows WP CLI to run with the right permissions
wp-su() {
	sudo -E -u www-data wp "$@"
}

# Set correct permissions
cd /wp-core
mkdir -p wp-content/uploads
chown -R www-data:www-data wp-content
chmod 755 wp-content
sudo chmod -R 777 wp-content/uploads

# Use shared volume for WP-CLI cache
export WP_CLI_CACHE_DIR=/wp-core/.wp-cli/cache

# Install WordPress
if ! $(wp-su core is-installed); then

	echo "Installing WordPress"

	wp-su core install --url=${WORDPRESS_URL} --title="GravityView Acceptance" --admin_user=${WORDPRESS_ADMIN_USER} --admin_password=${WORDPRESS_ADMIN_PASS} --admin_email=${WORDPRESS_ADMIN_EMAIL}

	wp-su core config --dbhost=${WORDPRESS_DB_HOST} --dbname=${WORDPRESS_DB_NAME} --dbuser=${WORDPRESS_DB_USER} --dbpass=${WORDPRESS_DB_PASSWORD} --extra-php="define( 'SCRIPT_DEBUG', true );" --force

fi

# Install Gravity Forms
if ! $(wp-su plugin is-installed gravityformscli); then

	echo "Installing Gravity Forms"

	wp-su plugin install gravityformscli --activate

	wp-su gf install --key=${GRAVITYFORMS_KEY} --activate

fi

# Activate GravityView
if ! $(wp-su plugin is-active gravityview); then

	echo "Activating GravityView"

	wp-su plugin activate gravityview

fi

# Install vendor files
cd /project && composer install

# Run acceptance tests
cd /wp-core/wp-content/plugins/gravityview

/project/vendor/bin/codecept run acceptance $@
exitcode=$?

exit $exitcode
