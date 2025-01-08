
FROM php:8.2.4-apache

# Install PDO MySQL extension and any other dependencies
RUN docker-php-ext-install pdo pdo_mysql

# Copy project files to the container
COPY ./public_html /var/www/public_html

# Set the DocumentRoot in Apache to public_html
RUN sed -i 's|/var/www/html|/var/www/public_html|g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|/var/www/html|/var/www/public_html|g' /etc/apache2/apache2.conf

# Set proper permissions for the public_html directory
RUN find /var/www/public_html -type d -exec chmod 755 {} \; \
    && find /var/www/public_html -type f -exec chmod 644 {} \;

# Set ownership to the Apache user
RUN chown -R www-data:www-data /var/www/public_html

# Expose the HTTP port
EXPOSE 80
