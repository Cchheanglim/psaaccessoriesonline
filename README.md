# PsaOnline — Gen-Z Streetwear & Aesthetic Accessories Marketplace

> **Web Development II Final Project**  
> Built with **Laravel 12**, Blade Template Engine, Tailwind CSS, and Bakong Universal KHQR Payment Integration.

---

## 🌟 Overview

**PsaOnline** is an indie e-commerce web platform curated for Gen-Z viral fashion trends, including Y2K chrome jewelry, 90s tinted shades, puffy cloud nylon bags, and aesthetic charms. The project supports Cambodia's dual-currency ecosystem (USD and KHR at fixed 1 USD = 4,100 KHR) and instant Bakong Universal KHQR mobile banking checkout.

---

## 🚀 Running the shop & sharing it with the team

### How it fits together

- The storefront and admin portal are the pages in `public/*.html`, with `public/assets/js/store.js` and `rbac.js`.
- Those pages load their data from Laravel's JSON API (`/api/...`, see `routes/web.php` and `app/Http/Controllers/Api/`).
- Laravel stores everything in the shared **Supabase PostgreSQL** database: products, accounts, orders and payment methods. Everyone on the team sees the same data.
- Cart, wishlist, viewed history, theme, currency and saved addresses stay in each browser.
- If the API can't be reached (for example `npm run dev` without `php artisan serve`), the pages fall back to the built-in demo data. The login page shows the demo accounts only in that mode.

### Run it on your computer (teammates)

1. `git clone <repo-url>` and `cd` into the folder.
2. `composer install`
3. Copy `.env.example` to `.env`. Fill in `DB_USERNAME` and `DB_PASSWORD` with the Supabase details you were sent privately. Never commit `.env`.
4. `php artisan config:clear`
5. `php artisan serve`, then open **http://127.0.0.1:8000**

`npm install` and `npm run dev` (http://localhost:3000) are optional. The Vite server forwards `/api` to `php artisan serve`, so it shows the same live data while both are running.

Don't run `migrate:fresh` or `db:seed` against the shared database: that would wipe or duplicate everyone's data. New migrations are fine to run with `php artisan migrate`.

### Accounts & roles

- Anyone can create a **Buyer** account on `register.html`.
- An **Admin** can make someone Staff or Admin on `admin-users.html`.
- Staff can verify and dispatch orders and edit products. Only Admins can delete products, cancel orders, and manage users and payment methods. The server enforces these rules (`AdminApiController`), not just the page.

### Putting it online

Any PHP 8.2+ host that can run Laravel will work (Laravel Cloud, Render or Railway with Docker, a VPS…). Point it at this repo and set these environment variables:

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=            # generate a new one: php artisan key:generate --show
APP_URL=https://your-domain
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.<project-ref>
DB_PASSWORD=<supabase password>
DB_SSLMODE=require
DB_PERSISTENT=true
SESSION_DRIVER=cookie       # works when the host runs several copies of the app
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
```

The build only needs `composer install --no-dev --optimize-autoloader`. The database already has its tables.

---

## 📁 Laravel Framework Architecture (`https://github.com/laravel/laravel.git`)

The repository adheres strictly to the official Laravel 12 application skeleton:

```
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Controller.php                     # Base Laravel Controller
│   │       ├── HomeController.php                 # Landing page & featured drops
│   │       ├── ProductController.php              # Product catalog, filter & detail
│   │       ├── CartController.php                 # Shopping bag session management
│   │       ├── CheckoutController.php             # Checkout, addresses & order creation
│   │       ├── OrderController.php                # Order receipts & slip upload
│   │       ├── AuthController.php                 # Buyer/Admin login & registration
│   │       ├── BuyerDashboardController.php       # Buyer order tracking profile
│   │       └── Admin/
│   │           ├── AdminDashboardController.php   # Revenue, KPI stats & recent orders
│   │           ├── AdminOrderController.php       # Order verification & slip approval
│   │           ├── AdminProductController.php     # Product inventory drops CRUD
│   │           ├── AdminUserController.php        # Staff & buyer permissions
│   │           └── AdminPaymentMethodController.php # Bakong KHQR & ABA settings
│   ├── Models/
│   │   ├── User.php                               # User model (buyer / staff / admin)
│   │   ├── Product.php                            # Accessory drops catalog model
│   │   ├── Order.php                              # Order entity with dual currency
│   │   ├── OrderItem.php                          # Line items relation
│   │   └── PaymentMethod.php                      # Payment gateway configuration
│   └── Providers/
│       └── AppServiceProvider.php
├── bootstrap/
│   ├── app.php                                    # Laravel 12 application bootstrap
│   └── providers.php                              # Service providers list
├── config/
│   ├── app.php                                    # App name, timezone, locale
│   ├── auth.php                                   # Authentication guards & providers
│   ├── database.php                               # SQLite & MySQL connections
│   └── session.php                                # Session driver & cookie config
├── database/
│   ├── factories/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_products_table.php
│   │   ├── 0001_01_01_000002_create_orders_table.php
│   │   ├── 0001_01_01_000003_create_order_items_table.php
│   │   └── 0001_01_01_000004_create_payment_methods_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php                     # Master database seeder
│       ├── UserSeeder.php                         # Admin & demo buyer accounts
│       └── ProductSeeder.php                      # Initial catalog with viral Gen-Z items
├── public/                                        # Public Document Root
│   ├── index.php                                  # Laravel Front Controller
│   ├── index.html                                 # Web preview entry point
│   ├── home.html                                  # Storefront
│   ├── products.html                              # Catalog & filter
│   ├── product-detail.html                        # Product detail showcase
│   ├── cart.html                                  # Shopping bag
│   ├── checkout.html                              # Checkout & Phnom Penh delivery
│   ├── order-detail--buyer-pending.html           # KHQR scan & slip upload
│   ├── order-detail.html                          # Order tracking stepper & receipt
│   ├── dashboard-buyer.html                       # Customer dashboard
│   ├── dashboard-admin.html                       # Admin dashboard
│   ├── admin-orders.html                          # Order fulfillment & slip review
│   ├── admin-products.html                        # Inventory management
│   ├── assets/
│   │   ├── css/style.css                          # Forest Jade & Mint palette + animations
│   │   └── js/store.js                            # Interactive cart & catalog engine
│   ├── .htaccess
│   └── robots.txt
├── resources/
│   ├── css/
│   │   └── app.css                                # Tailwind & Forest tokens
│   ├── js/
│   │   └── app.js                                 # Frontend interactivity
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php                      # Storefront Blade master layout
│       │   └── admin.blade.php                    # Admin Blade master layout
│       ├── home.blade.php                         # Hero, ticker & product drops
│       ├── products/
│       │   ├── index.blade.php                    # Catalog listing
│       │   └── show.blade.php                     # Product details
│       ├── cart/
│       │   └── index.blade.php                    # Bag & subtotal
│       ├── checkout/
│       │   └── index.blade.php                    # Delivery & payment selection
│       ├── orders/
│       │   ├── pending.blade.php                  # KHQR scan & slip upload
│       │   └── show.blade.php                     # Receipt & courier timeline
│       ├── buyer/
│       │   └── dashboard.blade.php                # Buyer profile & orders
│       ├── auth/
│       │   ├── login.blade.php                    # Sign in
│       │   └── register.blade.php                 # Registration
│       └── admin/
│           ├── dashboard.blade.php                # Admin dashboard
│           ├── orders.blade.php                   # Order list
│           ├── slip-review.blade.php              # Payment slip verification
│           ├── products.blade.php                 # Products inventory
│           ├── users.blade.php                    # Team & buyers
│           └── payment-methods.blade.php          # KHQR configuration
├── routes/
│   ├── web.php                                    # Web routes with controllers
│   ├── api.php                                    # REST API for mobile/AJAX
│   └── console.php                                # Artisan console commands
├── storage/                                       # Storage for cache, sessions & slips
├── artisan                                        # Laravel Artisan CLI
├── composer.json                                  # PHP dependencies & PSR-4 autoload
├── package.json                                   # Frontend dependencies
├── phpunit.xml                                    # Test suite configuration
└── vite.config.ts                                 # Vite asset & dev server bundler
```

---

## 🛠️ Setup & Running

### Option A: Local Laravel Environment (PHP 8.2+)
```bash
# 1. Clone repository
git clone https://github.com/your-username/psaonline.git
cd psaonline

# 2. Install dependencies
composer install
npm install

# 3. Environment configuration
cp .env.example .env
php artisan key:generate

# 4. Run migrations & database seeders
php artisan migrate --seed

# 5. Start servers
php artisan serve
npm run dev
```

### Option B: AI Studio Development Server
```bash
# The app is automatically served via Vite on port 3000:
npm run dev
```

---

## 🎨 Color Palette & Design Tokens
- **Deep Forest Night**: `#051F20` (Headers, brand contrast, primary action buttons)
- **Deep Pine**: `#0B2B26` (Secondary accents & hero gradients)
- **Lush Jade**: `#163832` (Interactive badges & pills)
- **Forest Sage Accent**: `#235347` (Icon highlights & focus rings)
- **Soft Muted Sage**: `#8EB69B` (Card borders, dividers & subtles)
- **Pale Mint Cream**: `#DAF1DE` (Pill backgrounds & trust badges)
- **Clean White**: `#FFFFFF` (Product card containers — clean backgrounds for product photography)

---

## 💳 Payment Gateways Supported
- **Bakong Universal KHQR**: National Bank of Cambodia standard QR for all local banks (ABA, ACLEDA, Canadia, Wing, etc.)
- **ABA PAY**: Direct in-app deeplink
- **Cash On Delivery (COD)**: Available for Phnom Penh deliveries
