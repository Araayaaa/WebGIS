# Use official PHP 8.2 with Apache
FROM php:8.2-apache

# Install MySQL/MariaDB extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Ensure only one MPM is loaded (prefork required for mod_php)
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load \
          /etc/apache2/mods-enabled/mpm_*.conf && \
    a2enmod mpm_prefork

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . .

# Fix file permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Create directories for uploads/logs
RUN mkdir -p /var/www/html/uploads /var/log/apache2 && \
    chown -R www-data:www-data /var/www/html/uploads

# Environment variables
ENV PHP_MEMORY_LIMIT=256M \
    PHP_POST_MAX_SIZE=50M \
    PHP_UPLOAD_MAX_FILESIZE=50M

# Configure Apache
RUN echo '<Directory /var/www/html>' > /etc/apache2/conf-available/app.conf && \
    echo '    RewriteEngine On' >> /etc/apache2/conf-available/app.conf && \
    echo '    RewriteBase /' >> /etc/apache2/conf-available/app.conf && \
    echo '    RewriteCond %{REQUEST_FILENAME} !-f' >> /etc/apache2/conf-available/app.conf && \
    echo '    RewriteCond %{REQUEST_FILENAME} !-d' >> /etc/apache2/conf-available/app.conf && \
    echo '    RewriteRule ^(.*)$ index.php [L]' >> /etc/apache2/conf-available/app.conf && \
    echo '</Directory>' >> /etc/apache2/conf-available/app.conf && \
    a2enconf app

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Start Apache
CMD ["apache2-foreground"]
