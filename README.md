<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## InstaApp - Image & Text Upload API

Aplikasi Laravel dengan API untuk upload gambar dan text menggunakan Bearer Token authentication (Laravel Sanctum).

## Requirements

- PHP >= 8.2
- Composer
- Database (MySQL, PostgreSQL, atau SQLite)
- Node.js & NPM (untuk frontend assets, optional)

## Installation & Setup

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Environment Configuration

Pastikan file `.env` sudah ada dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=instaapp
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Generate Application Key

```bash
php artisan key:generate
```

### 4. Run Migrations

```bash
php artisan migrate
```

Ini akan membuat tabel:
- `users` (untuk autentikasi)
- `personal_access_tokens` (untuk Sanctum tokens)
- `posts` (untuk menyimpan gambar dan text)

### 5. Create Storage Link

Untuk mengakses gambar yang di-upload melalui public URL:

```bash
php artisan storage:link
```

### 6. Start Development Server

```bash
php artisan serve
```

Server akan berjalan di `http://localhost:8000`

## API Endpoints

Base URL: `http://localhost:8000/api`

### Public Endpoints (No Authentication)

#### Register
```http
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

#### Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

Response akan mengembalikan token:
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "1|xxxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

### Protected Endpoints (Require Bearer Token)

Gunakan token yang didapat dari login di header:
```
Authorization: Bearer {your_token_here}
```

#### Upload Image & Text
```http
POST /api/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data

image: (file)
content: "Caption untuk gambar"
```

#### Get All Posts
```http
GET /api/posts
Authorization: Bearer {token}
```

#### Get Post by ID
```http
GET /api/posts/{id}
Authorization: Bearer {token}
```

#### Logout
```http
POST /api/logout
Authorization: Bearer {token}
```

## Testing dengan cURL

### 1. Register
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
  }'
```

### 2. Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

### 3. Upload (dengan token)
```bash
curl -X POST http://localhost:8000/api/upload \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "image=@/path/to/image.jpg" \
  -F "content=Ini adalah caption"
```

### 4. Get Posts
```bash
curl -X GET http://localhost:8000/api/posts \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Testing dengan Postman

1. Import collection atau buat request manual
2. Untuk protected endpoints, tambahkan header:
   - Key: `Authorization`
   - Value: `Bearer {your_token}`
3. Untuk upload, gunakan form-data dengan:
   - Key: `image` (type: File)
   - Key: `content` (type: Text)

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
