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

- PHP >= 8.1
- MySQL >= 5.7
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
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **运行迁移**
```bash
php artisan migrate
```

6. **配置 Passport**
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

## License

The MIT License (MIT).
