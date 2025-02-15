# Education Management System API

This is a backend API for an education management system developed based on Laravel 10.x, mainly used to manage functions such as teachers, students, courses, and bills.

## Main Features

### 1. User Authentication
- OAuth2 authentication based on Laravel Passport
- Support for teacher/student multi-role login
- Complete permission control middleware

### 2. Course Management
- Teachers: Create, edit, and view courses
- Students: View selected courses
- Support course search and pagination

### 3. Billing System
- Teachers: Create and send bills
- Students: View and pay bills
- Complete bill status flow

### 4. Payment Integration
- Integrated Omise payment system
- Support credit card payment
- Complete payment status tracking

### 5. Data Statistics
- Teachers: Statistics on the number of courses and total number of bills
- Students: Statistics on the number of selected courses and bills pending payment

## Technical Features

- Adopt Controller-Service layered architecture
- Use Trait to reuse common functions
- Unified response format and error handling
- Follow PSR-12 coding standards
- Use PHP 8.1+ new features
- Complete code comments
- Complete test scenarios

## Environment Requirements

- PHP >= 8.2
- PostgreSQL >= 13
- Composer
- Redis (optional, for caching)

## Quick Start

1. **Clone the project**
```bash
git clone [project address]
cd edu-management-backend
```

2. **Install dependencies**
```bash
composer install
```

3. **Environment configuration**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure the database**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **Run migrations**
```bash
php artisan migrate
```

6. **Configure Passport (optional)**
```bash
php artisan passport:install
```

## Deployment Notes

1. Production environment configuration
```env
APP_ENV=production
APP_DEBUG=false
```

2. Install dependencies
```bash
composer install --optimize-autoloader --no-dev
```

3. Cache configuration and routes
```bash
php artisan config:cache
php artisan route:cache
```

4. Configure Omise payment keys
```env
OMISE_PUBLIC_KEY=your_public_key
OMISE_SECRET_KEY=your_secret_key
```

## Docker Deployment

1. **Build image**
```bash
docker build -t edu-management-api .
```

2. **Run container**
Deployment:
```bash
docker run -d \
    --name edu-api \
    -p 8080:80 \
    -v $(pwd)/.env:/var/www/html/.env \ <------- You can provide the environment variables required by Laravel by mounting the .env file
    -e FORCE_MIGRATION=true \           <------- If you need to run migrations
    -e PASSPORT_INSTALLED=true \        <------- If you need to install Passport
    -e APP_NAME={APP_NAME}              <------- You can also provide environment variables through -e
    -e APP_KEY={APP_KEY}                <------- You can also provide environment variables through -e
    -e APP_ENV=production               <------- You can also provide environment variables through -e
    -e APP_DEBUG=false                  <------- You can also provide environment variables through -e
    -e your environment variables...
    edu-management-api
```

3. **Access service**
The service will run on http://localhost:8080.

4. **View initialization logs**
```bash
docker logs edu-api
```

### Deployment Instructions

- Set the `FORCE_MIGRATION=true` and `PASSPORT_INSTALLED=true` environment variables to run database migrations and Passport installation
- You can provide environment variables either by mounting the `.env` file or by using the docker `-e` parameter. These environment variables will override the configuration in the `.env` file.
- The initialization script will automatically check the database connection and wait for the database to be ready
- You can monitor the initialization process by viewing the container logs

### Troubleshooting

If you encounter initialization problems, you can:

1. Check the database connection configuration
```bash
docker exec edu-api php artisan db:monitor
```

2. Manually run migrations
```bash
docker exec edu-api php artisan migrate
```

3. Manually install Passport
```bash
docker exec edu-api php artisan passport:install
```

## License

The MIT License (MIT).
