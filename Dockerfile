FROM php:8.2-apache

RUN apt-get update && apt-get install -y unzip git \
    && docker-php-ext-install mysqli pdo pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# composer.json と composer.lock を先にコピー
COPY composer.json composer.lock ./

# vendor を生成
RUN composer install --no-dev --optimize-autoloader

# プロジェクト全体をコピー
COPY . .

# セッション保存用ディレクトリを作成
RUN mkdir -p /var/lib/php/sessions && chmod 777 /var/lib/php/sessions

RUN echo "session.save_path = /var/lib/php/sessions" >> /usr/local/etc/php/conf.d/session.ini

CMD ["apache2-foreground"]



