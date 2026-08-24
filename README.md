# CareHub

A one-clinician clinic app: patient charts over time, plus a medicine shelf that counts down when you use stock on a visit.

Built for shared hosting. PHP 8.0+ and a `.sql` file. No MySQL server, no Composer, no Node.

## Database

All tables and seed records live in [`database/schema.sql`](database/schema.sql). On install (or first run) PHP loads that script into `database/carehub.sqlite`. Upload the `.sql` file with the app; you do not create a database in cPanel.

Default sign-in after the seed: `admin@carehub.local` / `ChangeMe!23`

## What it does

- Sign in (one user)
- Search and register patients
- Open a hanging-file chart with a visit timeline
- Record a visit, vitals, and medicines used
- Receive, adjust, and watch low-stock medicines
- Change your password

## Install on shared hosting

1. Upload this folder to `public_html` (or a subfolder).
2. Make sure PHP has the `pdo_sqlite` extension (usual on shared hosts).
3. Make the `database/` folder writable.
4. Open `/install.php` in the browser and set clinic name plus your sign-in.
5. Delete `install.php` after it succeeds.

If the app lives in a subfolder, set `base_path` to that folder name (`carehub`) so links keep working.

Needs Apache `mod_rewrite`. The `.sql` / `.sqlite` files are blocked from the web by `.htaccess`.

Copy `config.example.php` to `config.php` if you want to skip the wizard; the SQLite file is created from `schema.sql` on first page load.

## Local development

```bash
cp config.example.php config.php
php -S localhost:8080 router.php
```

Run tests (in-memory SQLite, no extra setup):

```bash
php tests/run.php
```

## Default seed

`schema.sql` seeds three patients and five medicines so the cabinet is not empty. Paracetamol is intentionally below its reorder line to show the low-stock strip.
