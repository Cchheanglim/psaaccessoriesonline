# PsaOnlineAccessories

An online shop for jewelry, sunglasses, bags, hair clips and phone charms, delivered across Phnom Penh. Prices are shown in US dollars and riel, and customers pay by Bakong KHQR, ABA Pay, ACLEDA or cash on delivery.

Built with Laravel 12, Blade, Tailwind CSS 4 and PostgreSQL. Web II final project.

## Project structure

The app follows the standard [laravel/laravel](https://github.com/laravel/laravel) skeleton.

| Path | What it holds |
| --- | --- |
| `app/Http/Controllers` | Storefront, cart, checkout, orders, auth, and `Admin/` controllers |
| `app/Http/Middleware` | `EnsureUserHasRole` (admin/staff access) and `SecurityHeaders` |
| `app/Policies/OrderPolicy.php` | Who may view an order, upload a slip, verify, cancel |
| `app/Models` | `User`, `Product`, `Order`, `OrderItem`, `PaymentMethod` |
| `database/` | Migrations, factories and seeders |
| `resources/views` | Blade templates (storefront, admin, legal pages) |
| `resources/css` | Tailwind entry (`app.css`) and the storefront theme (`storefront.css`) |
| `public/assets/js/store.js` | Storefront behaviour: add to bag, quantities, filters, currency |
| `resources/prototype/` | The original static HTML design prototype, kept for reference only. It is not served by the website. |
| `tests/` | Feature tests, including `SecurityTest.php` |
| `Dockerfile`, `render.yaml` | Production container and Render deployment |

## Roles

| Role | Can do |
| --- | --- |
| Buyer | Shop, check out, see their own orders |
| Staff | Everything a buyer can, plus review payment slips, update delivery status, add and edit products |
| Admin | Everything, plus cancel orders, delete products, manage users and payment methods |

## Running locally

Requirements: PHP 8.2+ with `pdo_pgsql`, Composer, Node 20.19+ and PostgreSQL.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Edit `.env` for local development:

- `APP_ENV=local`, `APP_URL=http://127.0.0.1:8000`
- your local database details, with `DB_SSLMODE=prefer`
- `SESSION_SECURE_COOKIE=false`, because local development is plain HTTP
- `LOG_CHANNEL=stack` if you want logs in `storage/logs`

Then:

```bash
php artisan migrate --seed
npm run build          # or `npm run dev` while editing
php artisan serve
```

Outside production, the seeder creates `admin@example.test` with the password `admin-local-only-1`, and `buyer@example.test` with `password123`. To choose your own, set `ADMIN_SEED_EMAIL` and `ADMIN_SEED_PASSWORD` before seeding.

Run the tests with:

```bash
php artisan test
```

## Deploying (Render + Supabase)

### 1. Database on Supabase

1. Create a Supabase project and note the database password.
2. Click **Connect** and copy the **Session pooler** details: host, port `5432`, database `postgres`, user `postgres.<project-ref>`.
   Do not use the direct connection (it is IPv6 only, which Render cannot reach) or the transaction pooler on port 6543 (it breaks Laravel's prepared statements).

### 2. Create the tables and the first admin

From your own computer, point a temporary `.env` at Supabase and run:

```bash
APP_ENV=production ADMIN_SEED_EMAIL=you@example.com ADMIN_SEED_PASSWORD='a-long-unique-password' \
  php artisan migrate --seed --force
```

In production the seeder refuses to run without `ADMIN_SEED_PASSWORD` and never creates the demo buyer. Sign in and change the password afterwards.

`--seed` also loads the placeholder product catalogue. Replace those products (titles, photos, descriptions) with your real ones from the admin area before launch. Running the seeder again is safe; it updates rather than duplicates.

### 3. Web service on Render

1. Push the repository to GitHub.
2. In Render choose **New > Blueprint** and select the repository. Render reads `render.yaml` and builds the `Dockerfile`.
3. Fill in the secret values it asks for:
   - `APP_KEY`: run `php artisan key:generate --show --no-ansi` and paste the output, including `base64:`
   - `APP_URL`: your final `https://` address
   - `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`: from the Supabase Session pooler
   - `MAIL_FROM_ADDRESS`, `BAKONG_KHQR_MERCHANT_ID`, `BAKONG_KHQR_MERCHANT_NAME`
4. Each deploy runs any new migrations automatically on start-up. Set `RUN_MIGRATIONS=false` to turn that off.

To try the production image locally:

```bash
docker build -t psaonlineaccessories .
docker run -p 8080:10000 --env-file .env psaonlineaccessories
```

## Launch checklist

- [ ] Custom domain added in Render (**Settings > Custom Domains**) and DNS records set at your registrar
- [ ] `APP_URL` updated to the custom domain
- [ ] Favicon (`public/favicon.ico`, `favicon.svg`, `apple-touch-icon.png`)
- [ ] Privacy Policy at `/privacy` and Terms and Conditions at `/terms`, reviewed by you
- [ ] No "Made with AI" tags or AI tool metadata
- [ ] Real product photos and descriptions in place of the placeholder catalogue
- [ ] Revoked the Google Maps key and changed the database password that were once committed to git (see below)

## Security notes

- `.env` is never committed. `.env.example` holds placeholders only.
- A Google Maps API key, an `APP_KEY` and a database password were committed in earlier versions of `.env.example`. They have been removed from the file but **remain in git history**. Treat them as public: revoke the Maps key in Google Cloud and never reuse that password or key.
- Payment slips are stored privately (`storage/app/private/slips`) and are only served to the order's owner and staff.
- Run `php artisan test` before each deploy. `tests/Feature/SecurityTest.php` checks access control, order privacy, upload rules, rate limits, headers and production-safe configuration.
