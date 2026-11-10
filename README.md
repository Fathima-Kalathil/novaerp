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




