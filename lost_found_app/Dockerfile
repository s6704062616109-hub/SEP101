FROM php:8.1-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite
# สร้างโฟลเดอร์สำหรับอัปโหลดและให้สิทธิ์
RUN mkdir -p /var/www/html/uploads/posts /var/www/html/uploads/profiles
RUN chown -R www-data:www-data /var/www/html/uploads
RUN chmod -R 777 /var/www/html/uploads