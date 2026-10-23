# Plumbing Bill Shop (Core PHP + MySQL)

## Setup (XAMPP, Windows)
1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy this whole folder to `C:\xampp\htdocs\plumbing_bill_shop`.
3. Open http://localhost/phpmyadmin -> **Import** tab -> choose `database/plumbing_bill_shop.sql` -> **Go**.
   (This creates the `Plumbing_Bill_Shop` database, all 11 tables and sample data.)
4. Open http://localhost/plumbing_bill_shop/

## Logins
| Role   | Username | Password  |
|--------|----------|-----------|
| Admin  | admin    | admin123  |
| Worker | worker   | worker123 |

Admin: everything. Worker: dashboard, billing, customers, product list, stock report.

## If something is different on your PC
- Folder renamed? Change `BASE_URL` in `config/db.php`.
- MySQL has a root password or another port? Change `DB_PASS` / `DB_HOST` in `config/db.php`.
- Shop name and address on the invoice: `SHOP_NAME` and `SHOP_ADDR` in `config/db.php`.

## Folder map
- `config/db.php` - PDO connection and shop settings
- `includes/` - init, helper functions, header, sidebar, navbar, footer, generic CRUD page (`crud.php`)
- `auth/` login and logout
- `categories/ subcategories/ units/ customers/ dealers/` - CRUD pages (soft delete, search, pagination)
- `products/` - list, add/edit form, dependent subcategory dropdown, "+" button to add a unit
- `purchases/` - purchase entry (transaction, stock goes up)
- `billing/` - bill entry (transaction, stock check, stock goes down), bill list, printable invoice
- `reports/` - sales (daily / monthly / date range) and stock reports
- `database/plumbing_bill_shop.sql` - full schema and sample data

## Security / business rules built in
PDO prepared statements, `password_hash` / `password_verify`, CSRF token on every form, role checks,
server-side validation, soft delete (`is_deleted`), transactions for purchases and billing,
stock check before billing, bill totals and prices calculated on the server.
