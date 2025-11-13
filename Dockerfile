# PHP + Apache の公式イメージを利用
FROM php:8.2-apache

# Composerをインストール
RUN apt-get update && apt-get install -y unzip git \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Composerを追加
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# プロジェクトをコピー
COPY . /var/www/html/

# public をドキュメントルートに設定
WORKDIR /var/www/html
RUN rm -rf /var/www/html/index.html
RUN mv public/* /var/www/html/

# Apacheを起動
CMD ["apache2-foreground"]

WORKDIR /var/www/html

# publicフォルダの中身をコピーしてドキュメントルートに配置
COPY public/ /var/www/html/

# Composer install を実行して vendor を生成
RUN composer install

# Apacheを起動
CMD ["apache2-foreground"]

