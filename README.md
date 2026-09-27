# Radiant Dental Care — API

Laravel 13 REST API (`/api/v1`) for the bilingual dental clinic platform. Frontend: [`dental-clinic`](https://github.com/ahmedehab96-c/dental-clinic).

واجهة برمجية (Laravel 13) لمنصة عيادة الأسنان ثنائية اللغة. الواجهة الأمامية: [`dental-clinic`](https://github.com/ahmedehab96-c/dental-clinic).

## 🔐 Demo accounts — حسابات التجربة

Created by `php artisan migrate --seed` — password for all: **`password`**

تُنشأ عند تشغيل `php artisan migrate --seed` — كلمة المرور للجميع: **`password`**

| Role — الدور | Email — البريد |
|---|---|
| Admin — مشرف | `admin@radiantdental.care` |
| Doctor — طبيب | `doctor@radiantdental.care` |
| Patient — مريض | `patient@example.com` |

> Demo only — change these before any real deployment. للتجربة فقط.

## Setup — التشغيل

Requires PHP 8.3+, Composer, MySQL (or SQLite).

```bash
composer install
cp .env.example .env
php artisan key:generate
# set DB_* in .env — اضبط بيانات قاعدة البيانات
php artisan migrate --seed
php artisan storage:link
php artisan serve        # http://localhost:8000
php artisan queue:work   # sends queued emails — لإرسال البريد
```

Set `FRONTEND_URL` in `.env` to the React app's URL (default `http://localhost:5173`) for CORS and email links.

## Features — المزايا

- **Auth:** Sanctum bearer tokens; roles `admin`, `doctor`, `patient`, all enforced server-side.
- **Public API:** doctors, services, blog, gallery, testimonials, FAQs, clinic settings, availability, booking.
- **Patient:** own appointments, cancel, profile.
- **Doctor:** dashboard stats, own appointments only, status workflow (pending → confirmed → completed / cancelled), profile.
- **Admin:** CRUD for doctors, services, blog, gallery, testimonials, FAQs, users and appointments, with image uploads.
- **Notifications:** in-app (immediate) and email (queued), bilingual. See [`docs/email-and-queue.md`](docs/email-and-queue.md).

## Tests

```bash
php artisan test
```

Tests run on in-memory SQLite with the `array` mailer, so no real email is sent.
