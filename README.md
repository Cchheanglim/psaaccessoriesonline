# PsaOnline: Cute Gifts & Little Treats, Delivered in Phnom Penh

> **Web Development II, Final Project**
> Live site: **https://psaonline.onrender.com**

PsaOnline is an online shop for gifts and cute finds in Phnom Penh: plush and crochet toys, bag and phone charms, gift sets and bouquets, hair clips, socks, tees, shoes, bags and watches (71 products). All prices are in **US dollars**. Customers pay with **Bakong KHQR** (any Cambodian banking app) or **cash on delivery**, and orders are delivered within Phnom Penh.

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
11. [Design](#design)
12. [Credits](#credits)

---

## Features

### For shoppers

- **Shop & browse:** a floating product showcase on the home page, gift ideas and budget picks (Under $5, Under $10), a catalog with category filters, search, price filters and a **Sort By** menu, and product pages with photo galleries.
- **Bag & checkout:** quantity changes (with a confirmation before removing an item) and delivery fee rules: $1.50, free from $15, and free for the whole order when the bag has a **free-delivery** item.
- **Delivery pin, like delivery apps:** checkout finds the customer's live location; they drag the map under a fixed pin to their exact gate, or tap **Locate me**. The nearest street address is suggested (OpenStreetMap), and the area is checked against Phnom Penh.
- **Payment:** the payment page shows the shop's real KHQR code and account details; the customer uploads their transfer slip (with a preview). Cash on delivery skips this step.
- **Order tracking:** a live tracker (placed → payment → preparing → out for delivery → delivered), receipt and printable view.
- **Chat with the shop:** each order has a message thread with the staff member who approved it.
- **Live notifications:** new notifications pop up on their own (no reload), with a bell count and the count in the browser tab. Nothing is stored for them: the server builds them from orders, status changes, payment slips and messages, and the browser remembers when the bell was last opened. Staff only get the ones their role's permissions cover.
- **Membership (Plus → Pro → Max):** everyone starts as **Plus**. After spending **$100** on delivered orders a customer becomes **Pro (6% off every order)**, and from **$500** **Max (17% off)** (Admins can change these amounts on the Website page). The discount comes off the items at checkout. If a customer doesn't order for **18 days**, the membership drops back to Plus and the spending count starts again. The account page shows what they've spent, how much more they need, and the day their tier ends if they don't order.
- **Promo codes:** customers type a code at checkout (or tap **Use** under *My coupons* on their account page). A code takes a percent or dollar amount off the items, after the member discount; delivery is never discounted. The server checks every rule again when the order is placed.
- **Sales:** products on sale show the normal price crossed out, the percent off and when the sale ends.
- **Prices in USD:** everything is stored and charged in US dollars; checkout and the order page also show the amount in riel ($1 = 4,000៛).
- **Reviews:** after delivery, customers rate items (1–5 stars + comment). Product ratings come only from real reviews.
- **Account:** profile photo and banner, wishlist, recently viewed, saved addresses, password change.
- **Psa Bunny, the AI shopping helper:** an animated bunny mascot opens a mini chat. It knows the live catalog, prices, stock, delivery and payment rules and the signed-in customer's orders, and replies in Khmer or English.
- **Phone-first design:** bottom navigation, 44 px tap targets, dark mode, reduced-motion support, clean URLs (`/products` instead of `/products.html`).

### For staff and admins (staff portal)

- **One portal layout:** a sidebar that only lists the pages the signed-in role may use, the same buttons, tables and forms on every page, light and dark mode, and a phone layout with a slide-out menu.
- **Dashboard:** sales, average order, items sold and discounts for today, 7 days, 30 days or this year; a "Needs attention" list (payments to check, orders to send out, low stock, unread messages); best sellers, sales by category and payment method, and a CSV export. Every number comes from real orders.
- **Orders:** tabs for *Needs action*, *Awaiting payment*, *Preparing*, *Out for delivery*, *Delivered*, *Cancelled*, search, and one next-step button per order. Each order's page has the slip, address, pinned location, items to pack, discounts, messages and the actions the role allows (cancelling restores stock).
- **Messages inbox:** all customer chats with unread counts, filtered by *Mine / Not assigned / All*. Staff can delete any message or a whole chat; customers can delete their own messages. A deleted message keeps its row with *deleted_at* and *deleted_by*, and the chat shows "Message deleted by ...". A chat with no new message for 7 days is deleted automatically, and deleted messages are removed for good after 15 more days (both numbers are Shop rules).
- **Promo codes:** create codes (percent or dollars off, minimum spend, total and per-customer limits, start and end dates), turn them off, choose which ones customers see under *My coupons*, and see how often each was used and how much it took off. A used code keeps its discount and is turned off instead of deleted.
- **Products & stock** (one page, several tabs):
  - **Products:** create, edit and delete products (photos, Khmer names, specifications, stock, visibility, and a **sale**: percent off with an optional end day); tick products and move them to another category.
  - **Categories:** add categories and sub-categories, rename, hide from the shop, delete when empty.
  - **Home showcase:** choose and order the products in the home page's floating showcase.
  - **Sort By:** edit the shop's Sort By menu; add hand-picked groups such as "New Drop" or "Free Delivery".
  - **Suppliers** and **Buying stock:** purchase orders go draft → ordered → received; receiving adds the items to stock and keeps what each one cost.
- **Website:** every text customers read is editable here, grouped as *Header* (shop name, tagline, logo, top-bar messages, search hint), *Home page* (headline, buttons, section titles and texts), *Footer* (about text, payments, phone, email, address, hours, copyright), *Social media* (paste the link to each account; customers see the username and the link opens that exact account) and *Shop rules* (delivery fee, free delivery from, Pro/Max spend and discount, days before a membership ends, chat clean-up days; Admins only). Every shop page shares one footer, and a preview shows each change before saving.
- **Payment methods:** add, edit, turn off and delete payment methods with their own QR image.
- **Staff & roles:** create accounts, change a person's role, suspend accounts, and under **Roles & permissions** create new roles (for example "Delivery rider") and tick exactly what each role may do.
- Staff and admin accounts can't shop (bag, wishlist and checkout are turned off for them), so sales and stock only come from customers.

---

## How it works

```
Browser (public/*.html + Tailwind + vanilla JS)
   │  fetch /api/...  (session cookie + CSRF token)
   ▼
Laravel 12 JSON API  (routes/web.php → app/Http/Controllers/Api/*)
   │                         └── /api/chat/assistant → Google Gemini (key stays on the server)
   ▼
Supabase PostgreSQL
```

- **Storefront pages** live in `public/*.html` and share `public/assets/js/store.js` (data loading, bag, wishlist, notifications, showcase) and `public/assets/css/style.css`.
- **Every page loads `/api/bootstrap`** for the signed-in user, catalog, categories, showcase, Sort By menu, payment methods and their orders. If the server can't be reached, pages fall back to built-in demo data so they can still be previewed.
- **Staff pages** live in `resources/portal/` (not `public/`), so the web server can't hand them out directly; Laravel only serves them to signed-in accounts with a staff role. Every staff action is also checked again on the server.
- **Security:** session login with CSRF protection, server-side validation on every form, rate limits on login, ordering, chat and messages, prices and stock always taken from the database, and a check that blocks changes sent from a browser tab that was opened under a different account.
- **AI assistant:** the browser sends the conversation to `/api/chat/assistant`; Laravel adds the shop facts, catalog and the customer's orders and calls Gemini, with a backup model and built-in answers if the AI is busy.

---

## Tech stack

| Area | Technology |
|---|---|
| Backend | PHP 8.2+, **Laravel 12** (JSON API, Eloquent, validation, rate limiting) |
| Database | **Supabase PostgreSQL** (production), SQLite (tests / local) |
| Frontend | HTML, **Tailwind CSS** (CDN), vanilla JavaScript, Leaflet maps |
| AI | **Google Gemini** (API key from Google AI Studio) |
| Maps | Leaflet + OpenStreetMap tiles, Nominatim reverse geocoding |
| Hosting | **Render** (Docker, Apache), auto-deploy from `main` |
| Tests | PHPUnit (Laravel feature tests) |

---

## Run it on your computer

Requirements: **PHP 8.2+** with the `pdo_sqlite` (or `pdo_pgsql`) extension. Composer is optional: the `vendor/` folder is already in the repository.

### Option A: your own local database (recommended for development)

```bash
git clone https://github.com/Cchheanglim/psaaccessoriesonline.git
cd psaaccessoriesonline
cp .env.example .env
php artisan key:generate
```

In `.env`, use SQLite (keep the quotes if the path has spaces):

```
DB_CONNECTION=sqlite
DB_DATABASE="/full/path/to/psaaccessoriesonline/database/local.sqlite"
```

Then create the database, fill it with the catalog, payment methods and demo accounts, and start the server:

```bash
touch database/local.sqlite          # Windows: type nul > database\local.sqlite
php artisan migrate --seed
php artisan serve
```

Open **http://127.0.0.1:8000**. The database file is ignored by Git.

### Option B: the team's shared Supabase database

Copy `.env.example` to `.env` and fill in `DB_USERNAME` / `DB_PASSWORD` with the Supabase details shared privately, then `php artisan config:clear` and `php artisan serve`.

> **Never run `php artisan migrate`, `migrate:fresh` or `db:seed` against the shared database from your computer.** The live database is migrated only by the Render deploy, after a backup. Several migrations reshape existing data and can't be undone without that backup.

### Optional: the AI assistant

Create a free key at **https://aistudio.google.com** → *Get API key* and add it to `.env` (never commit it):

```
GEMINI_API_KEY=your-key
GEMINI_MODEL=gemini-flash-lite-latest
```

Without a key, Psa Bunny still answers the basics (delivery, payment, Telegram).

---

## Accounts & roles

| Role | Can do |
|---|---|
| **Buyer** | Shop, order, pay, track, chat about their orders, review delivered items, get member discounts and use promo codes |
| **Staff** | Whatever an Admin ticks for it. To start: products, stock, suppliers, orders and payments, messages, reports |
| **Roles an Admin creates** | Whatever is ticked for them, for example a "Delivery rider" who can only send out and deliver orders and answer messages |
| **Admin** | Everything, always (so nobody gets locked out); the only role that can create roles and change permissions |

- Anyone can register as a Buyer on `/register`.
- An Admin gives people roles and edits what each role may do on `/admin-users` → *Roles & permissions*.
- There are 12 permissions (approve payments, dispatch & deliver, cancel orders, customer messages, edit products & website, delete from catalog, promo codes, stock & buying, suppliers, reports, payment methods, accounts). The list is fixed in the code (`App\Models\Permission`); `role_permissions` stores which role has which, and the server checks the right one on every staff action.
- Someone who manages accounts without being an Admin can't touch Admin accounts and can only hand out roles that can't do more than their own.
- **Local demo accounts** (Option A) are created by `database/seeders/UserSeeder.php`. Accounts for the live site are shared privately.

---

## Tests

```bash
php artisan test
```

**73 feature tests (727 assertions)** run against an in-memory SQLite database. They cover:

- registration, login, suspended accounts, and staff accounts not being able to order;
- roles & permissions: creating, editing and deleting roles, each permission being enforced, Admin always keeping everything, and nobody handing out more than they have;
- the Website page (every setting is a row; staff edit text, Admins edit shop rules, checkout follows them), deleting messages (customers their own, staff any or the whole chat; who and when are kept) and the 7-day / 15-day chat clean-up;
- membership (Pro at $100 with 6% off, Max at $500 with 17% off, lapsing after 18 days), promo codes (limits, dates, minimum spend, per customer, stacking after the member discount) and product sales;
- ordering with database prices, stock checks, delivery rules (including free-delivery groups) and payment methods;
- payment slips, the staff workflow (verify → dispatch → deliver), rejections and cancellations restoring stock;
- categories, the home showcase, the Sort By menu, and the social account links (only real links to that platform are accepted);
- suppliers and purchase orders (draft → ordered → received adds stock);
- order messages, notifications and the staff inbox;
- reviews and rating calculation;
- the database migrations themselves: old data is moved into the new tables with nothing lost, and the migration refuses to run if stored totals don't match their items;
- the AI assistant (Gemini calls faked), clean URLs and staff-only pages.

---

## Deployment

The site runs on **Render** with the `Dockerfile` (PHP + Apache). Pushing to `main` deploys automatically; on start, `docker/start.sh` caches the config and routes and runs `php artisan migrate --force` (only new migrations). **Back up the Supabase database before merging changes that add migrations.**

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
│   ├── AdminApiController.php        # staff/admin: products, categories, orders, users, payment methods,
│   │                                 # showcase, Sort By menu, headline, social accounts
│   ├── StockApiController.php        # suppliers and purchase orders (buying stock)
│   ├── NotificationController.php    # notification bell + staff messages inbox
│   └── ChatAssistantController.php   # Psa Bunny AI assistant (Gemini)
├── Http/Middleware/
│   ├── EnsureStaff.php               # staff-only portal pages
│   └── EnsureSameAccount.php         # refuses changes from a tab opened as another account
├── Models/                           # one model per table (28)
└── Support/
    ├── Storefront.php                # converts database rows into the JSON the pages use
    ├── Inventory.php                 # the only place stock changes (stock movements)
    └── Notifier.php                  # creates customer/staff notifications
database/
├── migrations/                       # 21 migrations
├── data/catalog*.json                # the 71-product catalog (names, Khmer names, prices, photos)
└── seeders/                          # UserSeeder, CatalogSeeder, PaymentMethodSeeder
docs/                                 # database diagrams (draw.io) and the project guideline
public/                               # storefront pages (home, products, product-detail, cart,
│                                     # checkout, order pages, account, settings, chat, legal)
└── assets/
    ├── css/style.css                 # design tokens, components, dark mode, motion
    ├── css/portal.css                # staff portal layout, buttons, tables, forms, badges
    ├── images/products/              # product photos
    └── js/
        ├── store.js                  # shared data/API layer, bag, wishlist, notifications, showcase
        ├── admin-catalog.js          # Products & stock tabs (categories, showcase, Sort By, suppliers, ...)
        ├── portal-shell.js           # staff portal sidebar and top bar (shows only what the role may use)
        ├── rbac.js                   # the signed-in role's permissions in the UI
        ├── mascot-chat.js            # Psa Bunny mascot + mini chat
        └── drop-pin.js               # delivery pin map (checkout + saved addresses)
resources/portal/                     # staff portal pages (served only to staff)
routes/web.php                        # pages, clean URLs and the /api routes
tests/Feature/                        # PHPUnit feature tests
docker/, Dockerfile, render.yaml      # deployment
```

---

## API overview

All endpoints are under `/api`, use the session cookie, and require the CSRF token for changes.

| Method & path | Who | Purpose |
|---|---|---|
| `GET /bootstrap` | anyone | user, catalog, categories, showcase, Sort By menu, payment methods, own orders (staff: all orders) |
| `POST /auth/register`, `/auth/login`, `/auth/logout` | anyone | accounts |
| `PATCH /me`, `PUT /me/password` | signed in | profile, photo, banner, address, password |
| `POST /orders` | customers | place an order (prices, sales, member discount, promo code, stock and delivery fee from the database) |
| `POST /promo-codes/check` | anyone | preview a promo code for the items in the bag |
| `POST /orders/{number}/slip` | owner | upload the payment slip |
| `GET`/`POST /orders/{number}/messages` | owner, staff | order chat |
| `DELETE /orders/{number}/messages/{id}`, `DELETE /orders/{number}/messages` | own message / Customer messages | delete a message, or the whole chat (staff) |
| `GET`/`PUT /admin/site-settings` | Edit products & website (shop rules: Admin) | the Website page |
| `POST /orders/{number}/reviews` | owner, after delivery | review items |
| `GET /products/{sku}/reviews` | anyone | product reviews |
| `GET /notifications`, `POST /notifications/read` | signed in | notification bell |
| `POST /chat/assistant` | anyone | Psa Bunny AI assistant |
| `GET /admin/conversations` | Customer messages | messages inbox |
| `PATCH /admin/orders/{number}` | Approve payments / Dispatch & deliver / Cancel orders | verify, reject, dispatch, deliver, cancel |
| `POST`/`PATCH`/`DELETE /admin/products...`, `POST /admin/products/move` | Edit products & shop (delete: Delete from catalog; stock counts: Stock & buying) | catalog |
| `POST`/`PATCH`/`DELETE /admin/categories...` | Edit products & shop (delete: Delete from catalog) | categories |
| `PUT /admin/showcase` | Edit products & shop | home showcase |
| `POST`/`PATCH`/`DELETE /admin/sort-options...`, `PUT /admin/sort-options/order` | Edit products & shop (delete: Delete from catalog) | Sort By menu |
| `GET`/`POST`/`PATCH`/`DELETE /admin/suppliers...` | Suppliers (delete: Delete from catalog) | suppliers |
| `GET`/`POST`/`PATCH`/`DELETE /admin/purchase-orders...` | Stock & buying | buying stock (draft, order, receive, cancel) |
| `GET`/`POST`/`PATCH`/`DELETE /admin/promo-codes...` | Promo codes | promo codes |
| `POST`/`PATCH /admin/users...` | Accounts | accounts and the role each person has |
| `GET`/`POST`/`PATCH`/`DELETE /admin/roles...` | Admin only | roles and their ticked permissions |
| `POST`/`PATCH`/`DELETE /admin/payment-methods...` | Payment methods | payment methods |

---

## Database

**28 tables in third normal form (3NF)**, as drawn in `docs/Final Draft.drawio` (open it at app.diagrams.net). Every fact is stored once, and values that can be calculated are not stored. For example, an order's total is worked out from its items, stock on hand is the sum of the stock movements, a customer's membership tier is worked out from their delivered orders, and the notification bell is built from orders and messages.

| Module | Tables |
|---|---|
| Users & roles | `users`, `roles`, `permissions`, `role_permissions` (role_id + permission_id) |
| Customers | `customers` (1:1 with users: a shopper; staff have none), `customer_addresses`, `loyalty_tiers` (Plus / Pro / Max: spend needed and % off), `promo_codes` |
| Products | `categories` (with sub-categories), `products`, `product_details` (the description, 1:1), `product_specifications`, `product_images`, `product_reviews` |
| Stock & suppliers | `suppliers`, `purchase_orders`, `purchase_order_items`, `stock_movements` |
| Orders & payment | `orders` (customer_id), `order_items`, `order_statuses`, `order_status_history` (order_status_id), `payments`, `payment_methods` |
| Messages | `order_messages` (deleted_at, deleted_by) |
| Shop content | `site_settings` (one row per piece of website text and per shop rule: `setting_key`, `setting_value`, `updated_by`, `updated_at`), `sort_options`, `sort_option_products` (the home showcase is `products.showcase_position`) |

Some values are stored on purpose because they are facts of that moment and can't be recalculated later: the price and cost of each item sold (`order_items.unit_price_usd`, `unit_cost_usd`), the delivery fee and member discount of each order (and which promo code it used; what the code took off is calculated, because a used code's discount can't change), the cost of each item bought, and the amount of each payment. Theme, language and notification choices stay in the visitor's browser and are not stored in the database. Money is stored in US dollars only; riel is worked out when it is shown ($1 = 4,000៛). A customer's tier is not stored either: it is worked out from their delivered orders since their last 18-day gap.

---

## Design

- Palette: **sorbet orange** `#FFA552`, **espresso brown** `#2B1D1D`, **cotton cream** `#F9F3EA`. Light and dark mode.
- Type: Plus Jakarta Sans, with Kantumruy Pro for Khmer names.
- Mascot: **Psa Bunny**, drawn as a simple vector and animated with CSS (ear wiggle, blink, hop), which respects the "reduce motion" setting.

---

## Credits

- Maps © **OpenStreetMap** contributors, via **Leaflet**; address suggestions from OpenStreetMap **Nominatim**.
- Social media icons from **Simple Icons** (CC0).
- Product photos are sample images collected for this class project (many from Pinterest) and belong to their owners; brand names shown belong to their owners. They are used for demonstration only.
- Built with Laravel, Tailwind CSS and PHPUnit.
