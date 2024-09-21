FROM php:7.2-apache

# Install necessary extensions and dependencies
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    sudo \
    less \
    bash-completion \
    procps \
    curl \
    && docker-php-ext-install mysqli pdo pdo_mysql bcmath \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install gd

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install OfxParser
RUN composer require asgrim/ofxparser

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set the document root to /var/www/html/app/webroot
ENV APACHE_DOCUMENT_ROOT /app/webroot

# Update Apache configuration
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

# Add your user (UID 1000) to the container
RUN useradd -u 1000 -m z

# Set permissions and change ownership to your user (UID 1000)
RUN chown -R z:z /var/www/html

# Switch to your user (UID 1000)
USER z

# Expose port 80
EXPOSE 80

CMD ["apache2-foreground"]
