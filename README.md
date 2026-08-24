# CareHub

A one-clinician clinic app: patient charts over time, plus a medicine shelf that counts down when you use stock on a visit.

Built for shared hosting. PHP 8.0+ and MySQL. No Composer, no Node.

## What it does

- Sign in (one user)
- Search and register patients
- Open a hanging-file chart with a visit timeline
- Record a visit, vitals, and medicines used
- Receive, adjust, and watch low-stock medicines
- Change your password

## Install on shared hosting

1. Create a MySQL database and user in cPanel (or equivalent).
2. Upload this folder to `public_html` (or a subfolder).
3. Open `/install.php` in the browser.
4. Enter the database details, clinic name, and your sign-in email/password.
5. Delete `install.php` after it succeeds.

If you prefer phpMyAdmin:

1. Import [`database/schema.sql`](database/schema.sql).
2. Copy `config.example.php` to `config.php` and fill in the database settings.
3. Sign in with `admin@carehub.local` / `ChangeMe!23`, then change the password.

If the app lives in a subfolder, set `base_path` to that folder name (`carehub`) so links keep working.

Needs Apache `mod_rewrite`. PHP needs `pdo_mysql`.

## Local development

```bash
# after creating the MySQL database and importing schema.sql
php -S localhost:8080 router.php
```

Run stock tests:

```bash
php tests/run.php
```

Tests use the `carehub_test` database (override with `CAREHUB_TEST_HOST`, `CAREHUB_TEST_DB`, `CAREHUB_TEST_USER`, `CAREHUB_TEST_PASS`).

## Default seed

Schema seeds three patients and five medicines so the cabinet is not empty. Paracetamol is intentionally below its reorder line to show the low-stock strip.
