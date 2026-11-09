# NovaERP

A small but complete demo ERP: **inventory, sales, purchasing, invoices, dashboard, roles and an audit log**.
Built with **Laravel 11 + Vue 3 + Inertia.js + Tailwind CSS**, using PostgreSQL (MySQL also supported).

## Features

- **Roles**: Admin, Sales, Warehouse - each sees only the menus and pages they may use
- **Inventory**: categories, products, search / filters / pagination, low-stock alerts, manual stock adjustments, full stock-movement history
- **Sales**: customers, sales orders (draft -> confirmed -> shipped / cancelled), automatic invoice on confirm, 5% VAT, printable invoice (Print / Save as PDF)
- **Purchasing**: suppliers, purchase orders (draft -> ordered -> received), receiving goods increases stock
- **Dashboard**: monthly revenue, open orders, low stock, unpaid invoices, 6-month sales chart
- **Audit log**: who created / changed / deleted what, with old and new values (admin only)
- **Tests**: feature tests for the order -> stock -> invoice flow and role protection

## Engineering decisions (good interview talking points)

1. **Stock is changed only through `StockService`**, which writes a signed row in `stock_movements` for every change, so history is always traceable.
2. **Confirming an order runs in one DB transaction with row locks** (`lockForUpdate`, products locked in id order to avoid deadlocks). Two people confirming at once cannot oversell.
3. **Prices are re-read on the server** when an order is saved and copied onto the order lines (price snapshot), so later price changes never alter old orders.
4. **Thin controllers, fat services** (`app/Services`), validation in controllers, authorisation by role middleware.
5. **Soft deletes** on products, customers, suppliers so history never breaks.
6. **Database-agnostic code** (no enums, grouping done in PHP) - works on Postgres, MySQL and SQLite.

## Demo logins (password for all: `password`)

| Role | Email |
|---|---|
| Admin | admin@demo.com |
| Sales | sales@demo.com |
| Warehouse | warehouse@demo.com |

---

# Deploy without a local machine

You only need a browser and a GitHub account.

## 1. Put the code on GitHub

Easiest: install **GitHub Desktop** (or use git), create a new repository, copy the unzipped project into it, commit and push.
Using the GitHub website instead: *Add file > Upload files* accepts about 100 files at a time, so upload the folders in 2-3 batches. Hidden files such as `.gitignore` and `.env.example` must be included.

> `public/build` (the compiled Vue app) is **already built and must be committed**. Do not delete it.

## 2. Create the free database (Neon Postgres)

- Easiest: in your Vercel project open **Storage > Create Database > Neon** and create it, **or**
- go to https://neon.tech, create a free project, and copy the **connection string**.

It looks like `postgresql://user:password@ep-xxxx-pooler.region.aws.neon.tech/neondb?sslmode=require`.
Keep it - it becomes `DB_URL` below.

## 3. Generate an APP_KEY (no PHP needed)

Open any browser, press F12, open the **Console** tab, paste this and press Enter:

```js
'base64:' + btoa(String.fromCharCode(...crypto.getRandomValues(new Uint8Array(32))))
```

Copy the result (including `base64:`). Do not share it.

## 4. Deploy to Vercel (Path A)

1. Vercel > **Add New > Project** > import your GitHub repo.
2. **Framework Preset: Other.** Leave Build Command, Output Directory and Install Command empty.
3. Add **Environment Variables**:

| Name | Value |
|---|---|
| `APP_KEY` | the key from step 3 |
| `DB_URL` | your Neon connection string |
| `SETUP_TOKEN` | any long random text (for step 5) |
| `APP_URL` | your Vercel URL (add after the first deploy, then redeploy) |

   The other settings (`DB_CONNECTION=pgsql`, cookie sessions, `/tmp` storage...) are already in `vercel.json`.
4. Click **Deploy**. Vercel installs the PHP packages itself.

## 5. Create the tables and demo data (one time)

Open `https://YOUR-APP.vercel.app/setup/YOUR_SETUP_TOKEN` in the browser.
You should see the migration output and "Setup finished". Then **delete the `SETUP_TOKEN` variable** in Vercel and redeploy, so the page is disabled.

Now open the site and log in with `admin@demo.com` / `password`.

## Path B - Render (use this if Vercel gives you trouble)

Vercel has no official PHP support (this uses the community `vercel-php` runtime). If it fails and you cannot fix it quickly:

1. https://render.com > **New > Web Service** > connect the GitHub repo > Runtime **Docker** > plan **Free**.
2. Environment variables: `APP_KEY`, `DB_URL`, `DB_CONNECTION=pgsql`.
3. Deploy. Tables and demo data are created automatically on start. No setup URL needed.

The free service sleeps when idle; open it a few minutes before an interview.

---

# Troubleshooting

| Symptom | Fix |
|---|---|
| Page is blank / 500 | Set `APP_DEBUG=true` temporarily in Vercel env, redeploy, read the error, then set it back to `false`. Also check *Vercel > Logs*. |
| `No application encryption key` | `APP_KEY` missing or not starting with `base64:`. |
| `could not find driver` | The host's PHP lacks `pdo_pgsql`. Use Path B (Render), or switch to MySQL: `DB_CONNECTION=mysql` with a TiDB Cloud connection (`DB_URL=mysql://...`). |
| `Endpoint ID is not specified` (Neon) | Older PostgreSQL client. Put the endpoint in the password: `endpoint=ep-xxxx;YOUR_PASSWORD` (see Neon docs, "connection errors"). Or use Supabase instead. |
| `prepared statement ... does not exist` | Use Neon's **non-pooled** connection string for `DB_URL`. |
| Styles / JS missing (404 on `/build/...`) | Make sure `public/build` is committed to GitHub. If it still 404s, delete the `/build/(.*)` line from `vercel.json`; Laravel then serves the files itself. |
| Login works but redirects back to login | Make sure `APP_KEY` is the same value in all environments and `SESSION_DRIVER=cookie`. |
| `/setup/...` shows 404 | `SETUP_TOKEN` not set (or you already removed it - that is the secure state). |

# Local development (optional, for later)

```bash
composer install
cp .env.example .env && php artisan key:generate   # set DB_* in .env
php artisan migrate --seed
npm install && npm run dev        # terminal 1
php artisan serve                 # terminal 2
php artisan test                  # needs the pdo_sqlite extension
```

After changing any Vue file run `npm run build` and commit `public/build` again.

# Project layout

```
app/Services/            business rules (StockService, SalesOrderService, PurchaseOrderService)
app/Http/Controllers/    thin controllers
app/Models/              Eloquent models (+ Auditable / Searchable traits)
database/migrations/     schema
database/seeders/        realistic demo data
resources/js/Pages/      Vue pages (Inertia)
resources/js/Components/ reusable UI pieces
routes/web.php           routes and role protection
tests/Feature/           order, stock and permission tests
vercel.json, Dockerfile  deployment
```
