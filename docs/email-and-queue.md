# Email notifications & queue

Appointment notifications go out on two channels (`app/Notifications/AppointmentNotification.php`):

| Recipient | In-app (`database`) | Email (`mail`) |
|---|---|---|
| Patient account | ✅ immediate | ✅ queued |
| Guest booking (no account) | — | ✅ queued, to the booking email |
| Linked doctor account | ✅ immediate | ✅ queued |
| Admins | ✅ immediate | — |

In-app notifications are written during the request (`sync`), so unread counts are always current. Emails are pushed to the queue, so booking or changing a status never waits on the mail server.

## 1. Mail `.env`

```dotenv
MAIL_MAILER=log              # local default: emails are written to storage/logs/laravel.log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="no-reply@your-domain"
MAIL_FROM_NAME="${APP_NAME}"
FRONTEND_URL=http://localhost:5173   # used for the "View my appointments" button
```

Keep real SMTP credentials in `.env` only. Never commit them.

## 2. Queue

`QUEUE_CONNECTION=database` uses the `jobs` / `failed_jobs` tables that the default migrations create. No Redis is needed.

## 3. Worker

```bash
php artisan queue:work --tries=3
```

If no worker is running, the app keeps working: emails simply wait in `jobs` until a worker starts. In production, run the worker under a process supervisor (e.g. Supervisor or systemd).

## 4. Testing email locally

- Default: `MAIL_MAILER=log`, run the worker, then read the rendered HTML in `storage/logs/laravel.log`.
- Visual preview: run Mailpit (`mailpit`, UI at http://localhost:8025), set `MAIL_MAILER=smtp`, `MAIL_PORT=1025`, `MAIL_HOST=127.0.0.1`.
- Automated tests never send real email. `phpunit.xml` uses the `array` mailer and the `sync` queue.

## 5. Failures

- A mail job is tried 3 times, 1 min then 5 min apart, then recorded in `failed_jobs`.
- Inspect failed jobs with `php artisan queue:failed`. Retry with `php artisan queue:retry all`, or clear with `php artisan queue:flush`.
- Delivery errors are reported and never roll back the appointment action or its in-app notification.

## Language

Emails render in the recipient's locale: an explicit `->locale()` if one is set, else a future `User::preferredLocale()`, else `APP_LOCALE`. Arabic emails render right-to-left. The copy lives in `lang/{en,ar}/appointment_mail.php`.
