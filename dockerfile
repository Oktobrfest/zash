FROM php:7.2-apache

# php:7.2-apache is Debian buster (EOL); normal apt repos 404, must use archive repos
RUN echo "deb http://archive.debian.org/debian buster main" > /etc/apt/sources.list \
    && echo "deb http://archive.debian.org/debian-security buster/updates main" >> /etc/apt/sources.list \
    && printf 'Acquire::Check-Valid-Until "false";\n' > /etc/apt/apt.conf.d/99no-check-valid

# install build deps + tools needed to fetch CakePHP core + plugins during build
RUN apt-get update --allow-releaseinfo-change \
    && apt-get install -y --no-install-recommends \
      ca-certificates \
      curl \
      unzip \
      libpng-dev \
      libjpeg-dev \
      libfreetype6-dev \
      procps \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install mysqli pdo pdo_mysql bcmath \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install gd

ENV APACHE_DOCUMENT_ROOT /var/www/html/app/webroot

RUN a2enmod rewrite \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

COPY . /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install OfxParser
RUN composer require asgrim/ofxparser

RUN set -eux; \
    mkdir -p /var/www/html/app/lib; \
    curl -fsSL -o /tmp/cakephp.zip https://github.com/cakephp/cakephp/zipball/2.10.24; \
    unzip -q /tmp/cakephp.zip -d /tmp; \
    CAKE_DIR="$(ls -d /tmp/cakephp-cakephp-* | head -n 1)"; \
    rm -rf /var/www/html/app/lib/Cake; \
    mv "${CAKE_DIR}/lib/Cake" /var/www/html/app/lib/Cake; \
    for d in Console Lib Locale Model Test Vendor View; do \
      if [ -d "${CAKE_DIR}/app/${d}" ]; then \
        cp -rn "${CAKE_DIR}/app/${d}" /var/www/html/app/ 2>/dev/null || true; \
      fi; \
    done; \
    
    rm -rf /tmp/cakephp.zip "${CAKE_DIR}"

# Webzash expects BoostCake plugin; 
RUN set -eux; \
    mkdir -p /var/www/html/app/Plugin; \
    curl -fsSL -o /tmp/boostcake.tar.gz https://codeload.github.com/slywalker/cakephp-plugin-boost_cake/tar.gz/refs/tags/1.0.6; \
    tar -xzf /tmp/boostcake.tar.gz -C /tmp; \
    BOOST_DIR="$(ls -d /tmp/cakephp-plugin-boost_cake-* | head -n 1)"; \
    rm -rf /var/www/html/app/Plugin/BoostCake; \
    mv "${BOOST_DIR}" /var/www/html/app/Plugin/BoostCake; \
    rm -rf /tmp/boostcake.tar.gz /tmp/cakephp-plugin-boost_cake-*

# CakePHP needs tmp/ + logs/ writable 
RUN set -eux; \
    mkdir -p /var/www/html/app/tmp /var/www/html/app/logs; \
    chown -R www-data:www-data /var/www/html/app/tmp /var/www/html/app/logs; \
    chmod -R 775 /var/www/html/app/tmp /var/www/html/app/logs

EXPOSE 80
CMD ["apache2-foreground"]
