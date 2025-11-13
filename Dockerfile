# PHP + Apache の公式イメージを利用
FROM php:8.2-apache

# Composerをインストール
RUN apt-get update && apt-get install -y unzip git \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Composerを追加
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# プロジェクトをコピー
COPY . /var/www/html/

# publicフォルダの中身をドキュメントルートに配置
WORKDIR /var/www/html
RUN rm -rf /var/www/html/index.html
COPY public/ /var/www/html/

# Composer install を実行して vendor を生成
RUN composer install

# セッション保存用ディレクトリを作成
RUN mkdir -p /var/lib/php/sessions && chmod 777 /var/lib/php/sessions

# PHP設定を追加
RUN echo "session.save_path = /var/lib/php/sessions" >> /usr/local/etc/php/conf.d/session.ini

# Apacheを起動
CMD ["apache2-foreground"]



