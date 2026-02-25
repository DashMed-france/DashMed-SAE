FROM php:8.2-apache

# Install PDO MySQL + fileinfo (for mime_content_type)
RUN docker-php-ext-install pdo pdo_mysql && \
    docker-php-ext-enable fileinfo

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Setup DocumentRoot to point to /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# PHP upload limits
RUN { \
    echo 'upload_max_filesize = 20M'; \
    echo 'post_max_size = 22M'; \
    echo 'memory_limit = 128M'; \
    echo 'max_execution_time = 60'; \
} > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html
