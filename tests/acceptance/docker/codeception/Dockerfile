FROM php:7.4-cli

# Install required system packages
RUN apt-get update && \
    apt-get -y install git iputils-ping default-mysql-client sudo nano less zip unzip zlib1g-dev libzip-dev libpng-dev --no-install-recommends && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Add required extensions
RUN docker-php-ext-install bcmath gd zip mysqli pdo pdo_mysql

# Install composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php -- \
        --filename=composer \
        --install-dir=/usr/local/bin

# Add WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
RUN chmod +x wp-cli.phar
RUN mv wp-cli.phar /usr/local/bin/wp

# Install Nodejs
RUN cd ~
RUN curl -sL https://deb.nodesource.com/setup_12.x -o nodesource_setup.sh
RUN bash nodesource_setup.sh
RUN apt install -y nodejs -

# Prepare application
WORKDIR /wp-core/wp-content/plugins/gravityview

ENV WP_ROOT=/wp-core

