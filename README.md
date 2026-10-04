# PsaOnline: Cute Gifts & Little Treats, Delivered in Phnom Penh

> **Web Development II, Final Project**
> Live site: **https://psaonline.onrender.com**

PsaOnline is an online shop for gifts and cute finds in Phnom Penh: plush and crochet toys, bag and phone charms, gift sets and bouquets, hair clips, socks, tees, shoes, bags and watches (71 products). Prices are shown in **US dollars and Khmer riel** (fixed 1 USD = 4,100 ៛). Customers pay with **Bakong KHQR** (any Cambodian banking app) or **cash on delivery**, and orders are delivered within Phnom Penh.

It is a full-stack Laravel 12 application: a phone-first storefront for shoppers, a staff portal for running the shop, and an AI shopping assistant.

---

## Contents

1. [Features](#features)
2. [How it works](#how-it-works)
3. [Tech stack](#tech-stack)
4. [Run it on your computer](#run-it-on-your-computer)
5. [Accounts & roles](#accounts--roles)
6. [Tests](#tests)
7. [Deployment](#deployment)
8. [Project structure](#project-structure)
9. [API overview](#api-overview)
10. [Database](#database)
11. [Credits](#credits)

---

## Features

### For shoppers

- **Shop & browse:** home page with gift ideas and budget picks (Under $5, Under $10), catalog with category filters, search and price filters, product pages with photo galleries.
- **Bag & checkout:** quantity changes (with a confirmation before removing an item), delivery fee rules ($1.50, free from $15), and validated checkout forms.
- **Delivery pin, like delivery apps:** checkout finds the customer's live location; they drag the map under a fixed pin to their exact gate, or tap **Locate me**. The nearest street address is suggested (OpenStreetMap), and the area is checked against Phnom Penh. Saved addresses in Settings keep their pin, and checkout starts from the default one.
- **Payment:** the payment page shows the shop's real KHQR code and account details for the chosen method; the customer uploads their transfer slip (with a preview). Cash on delivery skips this step.
- **Order tracking:** a live tracker (placed → payment → preparing → out for delivery → delivered), receipt, printable view.
- **Chat with the shop:** each order has a message thread with the staff member who approved it ("Chat with Sokha").
- **Notifications:** a bell on every page for order updates (approved, slip rejected, out for delivery, delivered) and replies.
- **Reviews:** after delivery, customers rate items (1–5 stars + comment). Product ratings come only from real reviews.
- **Account:** profile photo and banner, wishlist, recently viewed, saved addresses, password change. Email and phone are private (shown only under Settings → My info).
- **Psa Bunny, the AI shopping helper:** an animated bunny mascot on shopper pages opens a mini chat. It knows the live catalog, prices, stock, delivery and payment rules, and the signed-in customer's orders, and replies in Khmer or English with product links.
- **Phone-first design:** bottom navigation, 44 px tap targets, no sideways scrolling, dark mode, reduced-motion support, clean URLs (`/products` instead of `/products.html`).

### For staff and admins (staff portal)

- **Orders:** list and order page with the customer's slip, delivery address, exact pinned location (Google Maps link), items to pack, and one-tap actions: approve or reject the slip → out for delivery → delivered (admins can cancel, which restores stock).
- **Messages inbox:** all customer chats with unread counts, filtered by *Mine / Not assigned / All*. Whoever approves an order becomes the customer's contact.
- **Notifications:** new orders, uploaded slips and customer messages.
- **Catalog:** create and edit products (multiple photos, Khmer names, specifications, stock, visibility).
- **Payment methods:** add KHQR / bank methods with their own QR image; they appear at checkout automatically.
- **Users & roles:** create staff, change roles, suspend accounts.
- **Reports:** sales, order counts and category breakdown from real orders.

---

## How it works

```
Browser (public/*.html + Tailwind + vanilla JS)
   │  fetch /api/...  (session cookie + CSRF token)
   ▼
Laravel 12 JSON API  (routes/web.php → app/Http/Controllers/Api/*)
   │                         └── /api/chat/assistant → Google Gemini (key stays on the server)
   ▼
Supabase PostgreSQL  (products, users, orders, messages, reviews, notifications)
```

- **Storefront pages** live in `public/*.html` and share `public/assets/js/store.js` (data loading, bag, wishlist, currency, notifications) and `public/assets/css/style.css`.
- **Every page loads `/api/bootstrap`** for the signed-in user, catalog, payment methods and their orders. If the server can't be reached, pages fall back to built-in demo data so they can still be previewed.
- **Staff pages** live in `resources/portal/` (not `public/`), so the web server can't hand them out directly; Laravel only serves them to signed-in Staff/Admin accounts. Every staff action is also checked again on the server (`AdminApiController`).
- **Security:** session login with CSRF protection, server-side validation on every form, rate limits on login, ordering, chat and messages, and prices/stock always taken from the database (never from the browser).
- **AI assistant:** the browser sends the conversation to `/api/chat/assistant`; Laravel adds the shop facts, catalog and the customer's orders and calls Gemini. A lighter model is used first with an automatic backup model, and the chat falls back to built-in answers if the AI is busy.

---

## Tech stack

| Area | Technology |
|---|---|
| Backend | PHP 8.2+, **Laravel 12** (JSON API, Eloquent, validation, rate limiting) |
| Database | **Supabase PostgreSQL** (production), SQLite (tests / local) |
| Frontend | HTML, **Tailwind CSS** (CDN), vanilla JavaScript, Leaflet maps |
| AI | **Google Gemini** API (Google AI Studio) |
| Maps | Leaflet + OpenStreetMap tiles, Nominatim reverse geocoding |
| Hosting | **Render** (Docker, Apache), auto-deploy from `main` |
| Tests | PHPUnit (Laravel feature tests) |

---

## Run it on your computer

Requirements: **PHP 8.2+** with the `pdo_sqlite` (or `pdo_pgsql`) extension. Composer is optional: the `vendor/` folder is already in the repository.

### Option A: your own local database (quickest, no passwords needed)

```bash
git clone https://github.com/Cchheanglim/psaaccessoriesonline.git
cd psaaccessoriesonline
cp .env.example .env
php artisan key:generate
```

In `.env`, use SQLite:

```
DB_CONNECTION=sqlite
DB_DATABASE=/full/path/to/psaaccessoriesonline/database/database.sqlite
```

Then create the database, fill it with the catalog, payment methods and demo accounts, and start the server:

```bash
touch database/database.sqlite          # Windows: type nul > database\database.sqlite
php artisan migrate --seed
php artisan serve
```

Open **http://127.0.0.1:8000**.

### Option B: the team's shared Supabase database

Copy `.env.example` to `.env` and fill in `DB_USERNAME` / `DB_PASSWORD` with the Supabase details shared privately, then `php artisan config:clear` and `php artisan serve`.

> Don't run `migrate:fresh` or `db:seed` against the shared database: it would wipe or duplicate everyone's data. `php artisan migrate` (new migrations only) is safe. To load new catalog products, run only `php artisan db:seed --class=CatalogSeeder --force`; it updates products by their id and never touches orders or stock.

### Optional: the AI assistant

Create a free key at **https://aistudio.google.com** → *Get API key* and add it to `.env`:

```
GEMINI_API_KEY=your-key
GEMINI_MODEL=gemini-flash-lite-latest
```

Without a key, Psa Bunny still answers the basics (delivery, payment, Telegram).

---

## Accounts & roles

| Role | Can do |
|---|---|
| **Buyer** | Shop, order, pay, track, chat about their orders, review delivered items |
| **Staff** | Everything in the portal except: deleting products, cancelling orders, managing users and payment methods |
| **Admin** | Everything, including roles, payment methods and reports |

- Anyone can register as a Buyer on `/register`.
- An Admin promotes people to Staff or Admin on `/admin-users`.
- **Local demo accounts** (Option A) are created by `database/seeders/UserSeeder.php`. Accounts for the live site are shared privately.

---

## Tests

```bash
php artisan test
```

**30 feature tests (161 assertions)** run against an in-memory SQLite database. They cover:

- registration, login, suspended accounts and role checks for every admin endpoint;
- ordering with database prices, stock checks, disabled and admin-created payment methods;
- payment slips, the staff workflow (verify → dispatch → deliver) and cancellations restoring stock;
- order messages, notifications, read/unread state and the staff inbox;
- reviews (only after delivery, only for items in the order) and rating calculation;
- profile photos and banners, input limits that match the database;
- the AI assistant (Gemini calls faked): instructions contain the catalog, backup model on rate limits, friendly errors;
- clean URLs and staff-only pages.

---

## Deployment

The site runs on **Render** with the `Dockerfile` (PHP + Apache). Pushing to `main` deploys automatically; on start, `docker/start.sh` caches the config and routes and runs `php artisan migrate --force` (only new migrations).

Environment variables (see `render.yaml`):

| Variable | Notes |
|---|---|
| `APP_KEY`, `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false` | Laravel basics |
| `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT=5432`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE=require` | Supabase connection (session pooler) |
| `SESSION_DRIVER=cookie`, `SESSION_SECURE_COOKIE=true` | Sessions that work across app instances |
| `GEMINI_API_KEY` | Google AI Studio key (secret, set in the Render dashboard) |
| `GEMINI_MODEL`, `GEMINI_FALLBACK_MODEL` | Default `gemini-flash-lite-latest` / `gemini-flash-latest` |

---

## Project structure

```
app/
├── Http/Controllers/Api/
│   ├── StoreApiController.php        # bootstrap, auth, profile, orders, slips, messages, reviews
│   ├── AdminApiController.php        # staff/admin: products, orders, users, payment methods
│   ├── NotificationController.php    # notification bell + staff messages inbox
│   └── ChatAssistantController.php   # Psa Bunny AI assistant (Gemini)
├── Http/Middleware/EnsureStaff.php   # staff-only portal pages
├── Models/                           # User, Product, Order, OrderItem, OrderMessage,
│                                     # ProductReview, PaymentMethod, UserNotification
└── Support/
    ├── Storefront.php                # converts database rows into the JSON the pages use
    └── Notifier.php                  # creates customer/staff notifications
database/
├── migrations/                       # 11 migrations
├── data/catalog*.json                # the 71-product catalog (names, Khmer names, prices, photos)
└── seeders/                          # UserSeeder, CatalogSeeder, PaymentMethodSeeder
public/                               # storefront pages (home, products, product-detail, cart,
│                                     # checkout, order pages, account, settings, chat, legal)
└── assets/
    ├── css/style.css                 # design tokens, components, dark mode, motion
    ├── images/products/              # product photos
    └── js/
        ├── store.js                  # shared data/API layer, bag, wishlist, notifications
        ├── rbac.js                   # roles & permissions in the UI
        ├── mascot-chat.js            # Psa Bunny mascot + mini chat
        └── drop-pin.js               # delivery pin map (checkout + saved addresses)
resources/portal/                     # staff portal pages (served only to staff)
routes/web.php                        # pages, clean URLs and the /api routes
tests/Feature/                        # PHPUnit feature tests
docker/, Dockerfile, render.yaml      # deployment
```

The `resources/views` Blade templates and their controllers are an earlier version of the shop; they are kept under `/legacy/...` for reference and are not used by the live site.

---

## API overview

All endpoints are under `/api`, use the session cookie, and require the CSRF token for changes.

| Method & path | Who | Purpose |
|---|---|---|
| `GET /bootstrap` | anyone | user, catalog, payment methods, own orders (staff: all orders) |
| `POST /auth/register`, `/auth/login`, `/auth/logout` | anyone | accounts |
| `PATCH /me`, `PUT /me/password` | signed in | profile, photo, banner, password |
| `POST /orders` | signed in | place an order (prices and stock from the database) |
| `POST /orders/{number}/slip` | owner | upload the payment slip |
| `GET`/`POST /orders/{number}/messages` | owner, staff | order chat |
| `POST /orders/{number}/reviews` | owner, after delivery | review items |
| `GET /products/{sku}/reviews` | anyone | product reviews |
| `GET /notifications`, `POST /notifications/read` | signed in | notification bell |
| `POST /chat/assistant` | anyone | Psa Bunny AI assistant |
| `GET /admin/conversations` | staff | messages inbox |
| `PATCH /admin/orders/{number}` | staff (cancel: admin) | verify, reject, dispatch, deliver, cancel |
| `POST`/`PATCH`/`DELETE /admin/products...` | staff (delete: admin) | catalog |
| `POST`/`PATCH /admin/users...` | admin | users & roles |
| `POST`/`PATCH`/`DELETE /admin/payment-methods...` | admin | payment methods |

---

## Database

| Table | Holds |
|---|---|
| `users` | accounts, role (buyer / staff / admin), status, profile photo and banner |
| `products` | catalog: SKU, English/Khmer names, category, USD/KHR price, stock, photos, specifications, rating |
| `orders` / `order_items` | orders with delivery address, pinned coordinates, payment method, payment and order status, slip, and the staff member handling it; line items keep the name and price at the time of purchase |
| `payment_methods` | built-in and admin-created methods, with QR image and account details |
| `order_messages` | chat between the customer and staff about an order, with read state |
| `product_reviews` | ratings and comments, one per item per delivered order |
| `user_notifications` | the notification bell |

---

## Design

- Palette: **sorbet orange** `#FFA552`, **espresso brown** `#2B1D1D`, **cotton cream** `#F9F3EA`. Light and dark mode.
- Type: Plus Jakarta Sans, with Kantumruy Pro for Khmer names.
- Mascot: **Psa Bunny**, drawn as a simple vector and animated with CSS (ear wiggle, blink, hop), which respects the "reduce motion" setting.

---

## Credits

- Maps © **OpenStreetMap** contributors, via **Leaflet**; address suggestions from OpenStreetMap **Nominatim**.
- AI replies by **Google Gemini** (Google AI Studio).
- Product photos are sample images collected for this class project (many from Pinterest) and belong to their owners; brand names shown belong to their owners. They are used for demonstration only.
- Built with Laravel, Tailwind CSS and PHPUnit.
