FROM php:8.2-apache

# 必要なパッケージと PHP 拡張をインストール
RUN apt-get update && apt-get install -y unzip git \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Composer をコピー
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache のドキュメントルートを設定
WORKDIR /var/www/html

# composer.json と composer.lock を先にコピー
COPY composer.json composer.lock ./

# 依存関係をインストール
RUN composer install --no-dev --optimize-autoloader

# ルート直下の index.php をドキュメントルートにコピー
COPY index.php /var/www/html/

# セッション保存用ディレクトリを作成し、権限を設定
RUN mkdir -p /var/lib/php/sessions \
    && chmod 777 /var/lib/php/sessions \
    && chown -R www-data:www-data /var/lib/php/sessions

# PHP にセッション保存先を設定
RUN echo "session.save_path = /var/lib/php/sessions" >> /usr/local/etc/php/conf.d/session.ini

# Apache を起動
CMD ["apache2-foreground"]
