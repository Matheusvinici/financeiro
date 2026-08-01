# AGENTS.md

## Project
Laravel 11 barbershop management system (`barbearia` DB) with WhatsApp bot integration. Full financial control.

## Auth
- **guard `web`**: Admin users (full access) — `App\Models\User`
- **guard `barbeiro`**: Barbers (limited to own appointments) — `App\Models\Barbeiro`

Barbers log in at `/barbeiro/login`. Admin at `/login`.

## Commands

| Command | Purpose |
|---------|---------|
| `composer dev` | Runs server + queue + logs + schedule + Vite concurrently |
| `php artisan migrate` | Run migrations |
| `php artisan db:seed --class=PermissionSeeder` | Seed roles and permissions |
| `php artisan db:seed --class=DatabaseSeeder` | Seed admin user + settings |
| `npm run build` / `npm run dev` | Vite asset build |
| `php artisan notifications:table` | Create notifications migration |
| `php artisan reminders:send` | Manually send email reminders to clients |
| `php artisan test` / `vendor/bin/phpunit` | Run tests |

## Architecture

- **Routes**: `routes/web.php` (admin + barber) and `routes/api.php` (WhatsApp bot API)
- **Admin namespace**: `App\Http\Controllers\Admin\`
- **Barber namespace**: `App\Http\Controllers\Barbeiro\`
- **Bot namespace**: `App\Http\Controllers\Bot\`
- **Layout**: AdminLTE 4 / Bootstrap 5
- **Queue**: `database` driver (jobs table)
- **Notifications**: Laravel Database Notifications (bell icon in AdminLTE)

## WhatsApp Bot

- **Project**: `whatsapp-bot/` (Node.js + whatsapp-web.js + Express)
- **Flow**: Barber → Day → Time → Service → Confirm
- **Reminder**: Bot sends reminder 1h before appointment (or use built-in email reminders)
- **Webhook**: Bot calls GET/POST on `https://yourdomain.com/api/bot/...`
- **Run**: `npm start` in `whatsapp-bot/` directory

## Email Reminders

- **Command**: `php artisan reminders:send` (runs every minute via schedule:work)
- **Schedule**: `routes/console.php` — runs `reminders:send` every minute
- **Notificação**: `App\Notifications\LembreteAgendamento` (database + mail channels)
- **Client**: Cliente model uses `Notifiable` trait, sends email via configured mailer (SMTP)
- **Timing**: 1h, 30min, 15min before appointment (checks `lembrete_1h_at`, `lembrete_30min_at`, `lembrete_15min_at` columns)
- **Active tabs**: Desktop Notification API (browser) alerts staff of new appointments via polling

## Financial Module

- **Despesas**: CRUD with categories, payment methods, paid/pending
- **Caixa**: Daily opening/closing with cash flow tracking
- **Relatorios**: Reports by period, by barber, by service

## Setup

1. Create MySQL DB `barbearia` and update `.env`
2. `php artisan migrate`
3. `php artisan db:seed --class=PermissionSeeder`
4. `php artisan db:seed --class=DatabaseSeeder`
5. `composer dev`
6. `cd whatsapp-bot && npm start` (scan QR code, optional)

## Gotchas

- Bot requires `whatsapp-bot/.env` with `APP_URL` pointing to the Laravel server
- For local bot testing, use a tunnel like `ngrok` or serve over LAN
- Barbers login with email + password (different from admin users)
- After seeding, admin credentials: admin@admin.com / 123456
- DB name is `barbearia` (not `idiomas2026`)
- Email reminders require valid SMTP config in `.env` (already configured with Gmail)
