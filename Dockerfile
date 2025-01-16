FROM php:8.2-fpm

# 安装系统依赖
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    nginx

# 清理 apt 缓存
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# 安装 PHP 扩展
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 设置工作目录
WORKDIR /var/www/html

# 复制项目文件
COPY . /var/www/html

# 设置目录权限
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage

# 安装项目依赖
RUN composer install --optimize-autoloader --no-dev

# 复制 Nginx 配置文件
COPY docker/nginx/default.conf /etc/nginx/sites-available/default

# 复制 PHP-FPM 配置
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# 暴露端口
EXPOSE 80

# 复制启动脚本
COPY docker/init.sh /usr/local/bin/init.sh
RUN chmod +x /usr/local/bin/init.sh

# 启动服务
CMD ["/usr/local/bin/init.sh"]
