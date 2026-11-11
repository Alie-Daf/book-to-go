# Book To Go

A Laravel 12 web application for accommodation listings and bookings.

## Requirements

- PHP 8.2 or later
- Composer
- MySQL or MariaDB (or SQLite, if configured)
- Node.js and npm (only if rebuilding frontend assets)

## Local setup

1. Clone this repository:
   ```bash
   git clone https://github.com/YOUR-USERNAME/YOUR-REPOSITORY.git
   cd YOUR-REPOSITORY
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Create your local environment file:
   ```bash
   cp .env.example .env
   ```

4. Generate an application key:
   ```bash
   php artisan key:generate
   ```

5. Configure your database in `.env`.

   For MySQL/MariaDB, set `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`,
   `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` to match your local database.
   Create the database before running migrations.

6. Run migrations:
   ```bash
   php artisan migrate
   ```

7. (Optional) Run the project's database seeders:
   ```bash
   php artisan db:seed
   ```
   Review `database/seeders/` first and use seeders only in a development database.

8. Install/build frontend dependencies if needed:
   ```bash
   npm install
   npm run build
   ```

9. Start the development server:
   ```bash
   php artisan serve
   ```
   Then open http://127.0.0.1:8000.

## Useful commands

```bash
php artisan migrate:status
php artisan route:list
php artisan test
```

## Important security and data notes

- Never commit your `.env` file, application key, passwords, API keys, or production credentials.
- User uploads, identity documents, payment proofs, session files, logs, and local database files are not included in this repository.
- The original SQL export was intentionally excluded because it contains application records and may include personal or sensitive user data. Use migrations and reviewed development seeders to create a clean local database.
- Do not commit real identity documents, payment evidence, or other private uploads. Use synthetic test data only.
- Before deploying, set `APP_ENV=production`, `APP_DEBUG=false`, configure production credentials securely, and follow Laravel's deployment documentation.

## Framework

- Laravel 12
- PHP 8.2+
