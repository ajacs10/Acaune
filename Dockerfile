FROM php:8.2-cli

WORKDIR /var/www/html

RUN docker-php-ext-install pdo_mysql

COPY . /var/www/html

EXPOSE 8088

CMD ["php", "-S", "0.0.0.0:8088", "router.php"]