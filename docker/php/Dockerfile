FROM php:7.0-fpm

# Install selected extensions and other stuff
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install pdo_mysql 
RUN docker-php-ext-install bcmath

# ADD composer /usr/bin/composer
# ADD ./.ssh/id_rsa ~/.ssh/id_rsa
# ADD ./.ssh/id_rsa.pub ~/.ssh/id_rsa.pub

WORKDIR "/var/www/app"