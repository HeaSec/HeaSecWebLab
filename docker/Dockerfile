FROM php:7.3-apache

# ---- 1. 系统依赖（仅 gd 所需图像库）----
# Buster 已归档，若主源失效取消下方注释切换到 archive.debian.org
# RUN sed -i 's|deb.debian.org|archive.debian.org|g' /etc/apt/sources.list \
#     && sed -i '/security/d' /etc/apt/sources.list
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libpng-dev libjpeg-dev libfreetype6-dev; \
    rm -rf /var/lib/apt/lists/*

# ---- 2. PHP 扩展（复刻 phpStudy 启用项）----
RUN set -eux; \
    docker-php-ext-configure gd --with-jpeg-dir=/usr --with-freetype-dir=/usr; \
    docker-php-ext-install -j"$(nproc)" mysqli pdo_mysql gd

# ---- 3. Apache 模块 ----
RUN a2enmod rewrite headers deflate filter ssl

# ---- 4. 复刻配置 ----
COPY docker/php/zz-heasec.ini      /usr/local/etc/php/conf.d/zz-heasec.ini
COPY docker/apache/000-heasec.conf /etc/apache2/sites-available/000-default.conf

# ---- 5. 项目代码 ----
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
