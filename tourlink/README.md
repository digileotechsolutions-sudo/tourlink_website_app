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

## Google Sign-In Setup

TourLink uses Google Identity Services to receive an ID token and verifies that token on the server against Google's rotating RSA signing keys. It does not use a Google client secret or store Google access tokens.

1. In Google Cloud Console, create an OAuth client with application type **Web application**.
2. Add `https://havenedgerealtors.com` as an authorized JavaScript origin. This GIS credential flow does not use a redirect URI.
3. Set the public Web Client ID in the server-side `GOOGLE_CLIENT_ID` environment variable. Do not add a client secret to the application.
4. Deploy the application and run `php artisan migrate --force` to add the Google identity columns.
5. Rebuild and deploy the Vite assets so the login and registration pages include the GIS button.

New Google users must provide a phone number and account type, then complete TourLink's existing verification and approval process. Existing accounts are linked only when Google reports a verified email matching the stored address.

## Email Delivery and Password Resets

Account-verification OTPs and password-reset links use a configured real email transport: authenticated SMTP (`MAIL_MAILER=smtp`, with host, username, password, and sender configured), the host's configured Laravel `sendmail` transport, or Resend (`RESEND_API_KEY` and `EMAIL_FROM`, with `OTP_EMAIL_TRANSPORT=resend` or `auto`). Resend takes priority in `auto`; otherwise configured SMTP, a configured default mailer, or the standard sendmail executable (when installed) is used. `log` and `array` mailers are never treated as production delivery. Configure the transport and sender in the deployed application's environment and refresh Laravel's cached configuration after changing it. Password reset and OTP requests fail explicitly when no real delivery transport is configured; they are not silently reported as sent. The default sender address falls back to `TOURLINK_SUPPORT_EMAIL`, then `tourlink@havenedgerealtors.com`.

`OTP_DELIVERY=log` is restricted to local/testing environments and is not a production email transport. Production registrations and resend requests require working authenticated SMTP, host sendmail, or Resend configuration.

Use `php artisan tourlink:check-verification` to check the effective email transport on the server. For an end-to-end delivery test, run `php artisan tourlink:check-verification --email=you@example.com` with an inbox you control, then check its inbox and spam folder. The command prints a temporary test code; do not share it.

After changing deployed mail configuration, refresh cached configuration with `php artisan config:clear` (or rebuild the config cache during deployment).

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
