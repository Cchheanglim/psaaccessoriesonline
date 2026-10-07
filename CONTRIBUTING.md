# Contributing to PsaOnline

PsaOnline is a team school project — a cute accessories online shop for Phnom Penh,
built with Laravel 12, a static HTML + Tailwind storefront, and a role-based staff portal.
This guide explains who built it and how to work on it.

## The team

Each member owned a module of the system (and its page in the database diagram,
`docs/Final Draft.drawio`):

| Member        | GitHub              | Area owned |
|---------------|---------------------|------------|
| Chheanglim    | @Cchheanglim        | Stock & suppliers; project lead, deployment (Render), database |
| Anou          | @anourattanak-jpg   | Order messages & chat; the full ERD |
| Long Meng     | @MaaTari2           | Products & categories |
| Tha Nuth      | _(add handle)_      | Users, roles & permissions; the module overview |
| Meng Huy      | _(add handle)_      | Customers, membership & site settings |
| Nak           | _(add handle)_      | Orders & payment |

> Teammates: replace the `_(add handle)_` placeholders with your GitHub usernames.

## How it is built

- **Backend:** PHP 8.2+, Laravel 12 — a JSON API (Eloquent, validation, rate limiting).
- **Storefront:** static HTML in `public/`, Tailwind (CDN), one shared `public/assets/js/store.js`.
- **Staff portal:** HTML in `resources/portal/`, served only to signed-in staff.
- **Database:** Supabase PostgreSQL in production, SQLite locally.
- **Deploy:** Docker on Render; migrations run automatically on deploy.

See `README.md` for the full feature list and architecture.

## Getting set up

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed   # local SQLite
php artisan serve
```

Ask a teammate privately for the Supabase and Gemini credentials — **never commit them.**
`.env` is git-ignored; keep it that way.

## Workflow

1. **Branch off `main`** for your work (e.g. `feature/promo-codes`, `fix/showcase`).
2. Keep changes focused on your module where possible.
3. **Run the tests before you push:**
   ```bash
   php artisan test
   ```
   Add or update tests for behaviour you change — the suite is how we keep each other's
   modules working.
4. Open a **pull request** into `main` and ask a teammate to review it.
5. Write clear commit messages (what changed and why).

## Code style

- Match the style already in the file you are editing.
- Store only what cannot be calculated (totals, stock and membership are worked out, not stored).
- Prices and stock always come from the database, never from the browser.
- Escape any text a user typed before putting it into a page.

## Reporting bugs

Open a GitHub issue describing what happened, what you expected, and the steps to reproduce it.

---

Thanks for building PsaOnline together. 🐰
