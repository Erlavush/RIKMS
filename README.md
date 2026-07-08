# RIKMS

RIKMS is a Laravel 12 prototype for a research and innovation knowledge management system. It includes a dashboard, repository browsing, an upload wizard, mocked AI metadata extraction, SDG tagging, access control, review flows, submission tracking, access requests, and placeholder admin pages.

## Tech Stack

- Laravel 12
- PHP 8.3-compatible application code
- SQLite for local demo data
- Blade templates
- Tailwind CSS 4
- Vite
- Minimal vanilla JavaScript

## Local Setup

Install PHP and Composer dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Create the environment file and app key:

```bash
cp .env.example .env
php artisan key:generate
```

Create the SQLite database, run migrations, and seed demo data:

```bash
touch database/database.sqlite
php artisan migrate:fresh --seed
```

Build frontend assets:

```bash
npm run build
```

Run the development server:

```bash
php artisan serve
```

## Demo Login

- Email: `test@example.com`
- Password: `password`
- Role: `agency_admin`

## Notes

Uploaded research documents are stored on the Laravel local disk under `storage/app/private/research-documents`. AI extraction is mocked in `App\Services\AiMetadataExtractionService`; it does not auto-publish, auto-approve, or bypass review.
