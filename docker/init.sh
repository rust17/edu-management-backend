#!/bin/bash

# 等待数据库连接就绪
echo "Waiting for database connection..."
while ! php artisan db:monitor > /dev/null 2>&1; do
    sleep 1
done

php artisan config:cache
php artisan route:cache

# 检查是否需要运行迁移
php artisan migrate:status | grep "No migrations found" > /dev/null 2>&1
NEED_MIGRATION=$?

if [ $NEED_MIGRATION -eq 0 ] || [ "$FORCE_MIGRATION" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate
fi

# 是否需要安装 Passport
if [ "$PASSPORT_INSTALLED" = "true" ]; then
    echo "Installing Passport..."
    php artisan passport:install --force
    echo "Passport installation completed."
else
    echo "Passport already installed."
fi

# 启动 PHP-FPM
php-fpm --fpm-config /usr/local/etc/php-fpm.d/www.conf
echo "PHP-FPM started"

# 启动 Nginx
nginx -g "daemon off;"
if [ $? -ne 0 ]; then
    echo "Failed to start Nginx"
    exit 1
fi
