# DocFlow

**Technical Document & Approval Tracker** — an internal prototype built for
JESA Morocco to centralise technical document management, revision control,
review workflows and multi-step approval circuits.

> This is a prototype/internal concept, not an official JESA product. See
> `prompt.md` for the full product specification this app was built against.

## Stack

Laravel 12 · Livewire 3 · Flux UI · Tailwind CSS v4 · MySQL/MariaDB ·
Spatie Permission · Spatie Activitylog · Laravel Excel · DomPDF.

Server-rendered throughout — no separate frontend framework or API layer.

## Requirements

- PHP 8.2+ with the `zip`, `gd`, `dom`, `mbstring` extensions (all bundled
  with XAMPP's PHP 8.2 build)
- MySQL 8+ or MariaDB
- Composer
- Node.js 20+ / npm

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate

# create the database, then point .env at it:
#   DB_CONNECTION=mysql
#   DB_DATABASE=doctracker
#   DB_USERNAME=root
#   DB_PASSWORD=

npm install
npm run build

php artisan migrate --seed
php artisan serve
```

Open `http://127.0.0.1:8000` and log in — the seeded accounts (one per
role, password `password`) are listed on the login page in local
environments.

The default seed creates only logins and reference data (roles,
permissions, the 10 engineering disciplines) — no projects or documents.
**See [TESTING.md](TESTING.md)** for a full walkthrough that builds up
real data role by role and exercises every module, or for the commands to
populate a fully pre-filled showcase dataset instead.

For local development with hot-reloading and a queue worker running
together:

```bash
composer dev
```

### A note on `php artisan serve`

PHP's built-in dev server handles **one request at a time**, and on Windows
it cannot be given extra workers (`PHP_CLI_SERVER_WORKERS` is POSIX-only).
Measured on this project: four parallel requests serialise into a staircase,
and a Livewire click that normally answers in ~520 ms takes **~5.4 s** when
eight requests are queued ahead of it. That is what "I click and nothing
happens until I refresh" looks like — the click is sent and its spinner is
running, but the single worker is busy.

If the UI feels unresponsive:

- **Serve through Apache instead** (XAMPP is already required for MySQL).
  Point a vhost at `public/` and you get real concurrency.
- **Turn the debug bar off when you are not reading it** —
  `DEBUGBAR_ENABLED=false` in `.env` took a request from ~210 ms to ~93 ms
  here, more than doubling how many clicks the server absorbs per second.

## Sending real e-mail

Notifications (review assignments, approval requests, submissions) and the
forgot-password flow both go out by e-mail. Out of the box `MAIL_MAILER=log`
writes them to `storage/logs/laravel.log` instead of sending, which is fine
for development and sends nothing to anyone by accident.

To deliver for real, fill in any SMTP provider's credentials — nothing in the
app is provider-specific, so Brevo, Mailgun, Postmark, SendGrid, Gmail or a
corporate relay all work the same way:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com   # or your provider's host
MAIL_PORT=587
MAIL_USERNAME=<from your provider>
MAIL_PASSWORD=<from your provider>
MAIL_FROM_ADDRESS="docflow@jesa.ma"
MAIL_FROM_NAME="JESA DocFlow"
```

Two things that will otherwise bite:

- **Mail is queued.** Nothing sends until a worker runs — `php artisan
  queue:work` (or `composer dev`, which starts one). A message that never
  arrives is usually a queue that is not running, not a mail problem.
- **`APP_URL` becomes every link in every e-mail.** If it says
  `http://localhost` while you serve on port 8000, every button in every
  notification 404s. Set it to the address people actually use.

Check delivery without involving a colleague:

```bash
php artisan tinker --execute="App\Models\User::first()->notify(new App\Notifications\TemporaryPasswordIssued('TEST1234'));"
php artisan queue:work --stop-when-empty
```

The demo accounts use `@yopmail.com`, a throwaway inbox with no signup: open
<https://yopmail.com>, type the address, and read what arrived.

### Forgot password

The flow mails a **temporary password** rather than a reset link. It lasts an
hour, works once, and is stored *beside* the account's real password rather
than replacing it — so submitting somebody else's address on the form cannot
lock them out of an account they can still sign into. Signing in with it puts
the account on a change-password screen it cannot leave (except to log out)
until a real password is chosen.

## Tests

```bash
php artisan test
```

## Notes

- Notifications (review assignments, approval requests, decisions) are
  queued — run `php artisan queue:work` (or `composer dev`, which starts
  it) to have them actually deliver.
- Uploaded documents are stored on a private disk
  (`storage/app/private/documents/`) and are only ever reachable through
  an authorised download route — never a public URL.
- The `JESA` wordmark in the sidebar/login screen is a text placeholder;
  no logo asset was supplied. Swap it in `resources/views/components/brand.blade.php`.
