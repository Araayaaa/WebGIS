FROM php:8.2-apache

RUN a2enmod rewrite
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html
