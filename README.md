# 教育管理系统 API

这是一个基于 Laravel 10.x 开发的教育管理系统后端 API，主要用于管理教师、学生、课程和账单等功能。

## 主要功能

### 1. 用户认证
- 基于 Laravel Passport 的 OAuth2 认证
- 支持教师/学生多角色登录
- 完善的权限控制中间件

### 2. 课程管理
- 教师：创建、编辑、查看课程
- 学生：查看已选课程
- 支持课程搜索和分页

### 3. 账单系统
- 教师：创建和发送账单
- 学生：查看和支付账单
- 完整的账单状态流转

### 4. 支付集成
- 集成 Omise 支付系统
- 支持信用卡支付
- 完善的支付状态追踪

### 5. 数据统计
- 教师：课程数、账单总数统计
- 学生：已选课程数、待支付账单统计

## 技术特点

- 采用 Controller-Service 分层架构
- 使用 Trait 复用公共功能
- 统一的响应格式和错误处理
- 遵循 PSR-12 编码规范
- 使用 PHP 8.1+ 新特性
- 完善的代码注释
- 测试场景完整

## 环境要求

- PHP >= 8.2
- PostgreSQL >= 13
- Composer
- Redis (可选，用于缓存)

## 快速开始

1. **克隆项目**
```bash
git clone [项目地址]
cd edu-management-backend
```

2. **安装依赖**
```bash
composer install
```

3. **环境配置**
```bash
cp .env.example .env
php artisan key:generate
```

4. **配置数据库**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **运行迁移**
```bash
php artisan migrate
```

6. **配置 Passport（可选）**
```bash
php artisan passport:install
```

## 部署注意事项

1. 生产环境配置
```env
APP_ENV=production
APP_DEBUG=false
```

2. 安装依赖
```bash
composer install --optimize-autoloader --no-dev
```

3. 缓存配置和路由
```bash
php artisan config:cache
php artisan route:cache
```

4. 配置 Omise 支付密钥
```env
OMISE_PUBLIC_KEY=your_public_key
OMISE_SECRET_KEY=your_secret_key
```

## Docker 部署

1. **构建镜像**
```bash
docker build -t edu-management-api .
```

2. **运行容器**
部署：
```bash
docker run -d \
    --name edu-api \
    -p 8080:80 \
    -v $(pwd)/.env:/var/www/html/.env \ <------- 可以通过挂载 .env 文件提供 Laravel 所需的环境变量
    -e FORCE_MIGRATION=true \           <------- 如果需要运行迁移
    -e PASSPORT_INSTALLED=true \        <------- 如果需要安装 Passport
    -e APP_NAME={APP_NAME}              <------- 也可以通过 -e 提供环境变量
    -e APP_KEY={APP_KEY}                <------- 也可以通过 -e 提供环境变量
    -e APP_ENV=production               <------- 也可以通过 -e 提供环境变量
    -e APP_DEBUG=false                  <------- 也可以通过 -e 提供环境变量
    -e 你的环境变量...
    edu-management-api
```

3. **访问服务**
服务将在 http://localhost:8080 上运行。

4. **查看初始化日志**
```bash
docker logs edu-api
```

### 部署说明

- 通过设置 `FORCE_MIGRATION=true`、`PASSPORT_INSTALLED=true` 环境变量来运行数据库迁移和 Passport 安装
- 既可以通过挂载 `.env` 文件来提供环境变量，也可以通过 docker 的 `-e` 参数来提供环境变量，这些环境变量会覆盖 `.env` 文件中的配置
- 初始化脚本会自动检查数据库连接并等待数据库就绪
- 可以通过查看容器日志来监控初始化过程

### 故障排查

如果遇到初始化问题，可以：

1. 检查数据库连接配置
```bash
docker exec edu-api php artisan db:monitor
```

2. 手动运行迁移
```bash
docker exec edu-api php artisan migrate
```

3. 手动安装 Passport
```bash
docker exec edu-api php artisan passport:install
```

## License

The MIT License (MIT).
