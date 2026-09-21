<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

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

## BOG Checkout Integration

This project includes full BOG checkout flow integration with `tatogi/bog-payment-laravel`:

- Cart -> BOG redirect
- BOG callback handling
- Success / fail pages
- Saved cards management
- Mock mode for local/demo testing without real BOG credentials

### Installation Steps

1. Install package:
```bash
composer require tatogi/bog-payment-laravel:^1.1
php artisan vendor:publish --provider="Bog\Payment\BogPaymentServiceProvider"
php artisan migrate
```

2. Configure environment variables:
```env
BOG_CLIENT_ID=
BOG_CLIENT_SECRET=
BOG_AUTH_URL=https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token
BOG_API_BASE_URL=https://ipay.ge/opay/api/v1
BOG_ORDERS_URL=https://ipay.ge/opay/api/v1/checkout/orders
BOG_PAYMENT_DETAILS_URL=https://ipay.ge/opay/api/v1/checkout/payment
BOG_CALLBACK_URL=${APP_URL}/bog/callback
BOG_MOCK_MODE=true
```

### Real vs Mock Mode

- Real mode:
  - Set valid `BOG_CLIENT_ID` and `BOG_CLIENT_SECRET`.
  - Set `BOG_MOCK_MODE=false`.
- Mock mode:
  - Set `BOG_MOCK_MODE=true`, credentials may stay empty.
  - Checkout redirects to local mock gateway and still processes callback/status end-to-end.

### Routes (Web)

- `GET /checkout`
- `POST /checkout/bog/start`
- `POST /checkout/bog/saved-card`
- `GET /checkout/bog/success`
- `GET /checkout/bog/fail`
- `GET /profile/cards`
- `DELETE /profile/cards/{card}`
- `PATCH /profile/cards/{card}/default`

Mock routes (enabled in mock mode):

- `POST /mock/bog/token`
- `POST /mock/bog/checkout/orders`
- `GET /mock/bog/checkout/payment/{orderId}`
- `GET /mock/bog/gateway/{orderId}`
- `POST /mock/bog/gateway/{orderId}/complete`

### Manual Test (Quick Scenario)

1. Run migrations and seeders:
```bash
php artisan migrate
php artisan db:seed
```
2. Open `GET /checkout`.
3. Click `Pay with BOG`.
4. In mock mode, select `Simulate Success` or `Simulate Fail`.
5. Verify:
   - redirect to success/fail page
   - `bog_payments` status updated
   - when `Save card` is checked and user is logged in, new row appears in `bog_cards`.
