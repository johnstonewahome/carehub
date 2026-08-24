# How to run Care Center on a server

Care Center is a PHP app. The database is the file [`database/schema.sql`](database/schema.sql). You do **not** create a MySQL database in cPanel.

On install, PHP copies that `.sql` file into `database/carehub.sqlite` on the server. Patient charts and medicine stock then live in that SQLite file.

## What you need

- Shared hosting or a VPS with **Apache** and **PHP 8.0 or newer**
- PHP extension **pdo_sqlite** (most hosts already have it)
- Apache **mod_rewrite** (usual on cPanel)
- A domain or subdomain pointing at the folder you upload

No Composer. No Node. No MySQL.

## 1. Get the files onto the server

Upload the whole project folder (not only `index.php`).

Typical cPanel path:

- Domain root: `public_html/`
- Subfolder: `public_html/carehub/`

You can use File Manager, FTP, or Git. Include at least:

- `index.php`, `install.php`, `router.php`, `.htaccess`
- `config.example.php`
- `includes/`, `pages/`, `assets/`, `database/`
- `database/schema.sql` (this is the database)

Do **not** upload `config.php` from your laptop unless you wrote it for this server. The installer creates it.

## 2. Folder permissions

The web server must be able to **write** inside `database/` so it can create `carehub.sqlite`.

In cPanel File Manager:

1. Right-click `database`
2. Set permissions to `755` (or `775` if `755` cannot create files)
3. Leave PHP files at `644` and folders at `755`

If install says it could not create the database folder or write the file, permissions are the usual cause.

## 3. Open the installer

In the browser go to:

- Root install: `https://your-domain.com/install.php`
- Subfolder: `https://your-domain.com/carehub/install.php`

Fill in:

| Field | What to enter |
| --- | --- |
| Clinic name | Shown on the cabinet |
| Subfolder | Leave blank if the app is in `public_html`. If it is in `public_html/carehub`, enter `carehub` |
| Your name | Your name on the signed-in rail |
| Sign-in email | The email you will use every day |
| Sign-in password | At least 8 characters |

Submit **Install Care Center**. You should land on the sign-in screen.

## 4. Sign in and lock the install

1. Sign in with the email and password you just set.
2. Open **Change password** if you used a temporary password.
3. **Delete `install.php`** from the server (File Manager or FTP). Leave it in place and anyone who can guess the URL could try to run setup again.

If you never ran the wizard, the seed login from `schema.sql` is:

- Email: `admin@carehub.local`
- Password: `ChangeMe!23`

Change that immediately.

## 5. Use the clinic

| Page | URL (root install) |
| --- | --- |
| Desk / search | `/` |
| Patients | `/patients` |
| Medicines | `/medicines` |

Daily path: search a patient → open the chart → **New visit** → save medicines used (stock comes off the shelf).

## Skip the wizard (optional)

1. Copy `config.example.php` to `config.php` on the server.
2. Edit `clinic_name` and, if needed, `base_path` (the subfolder name, or `''` at domain root).
3. Open the site. PHP creates `database/carehub.sqlite` from `schema.sql` on first load.
4. Sign in with `admin@carehub.local` / `ChangeMe!23` and change the password.

## HTTPS and the domain

In cPanel, turn on SSL (Let’s Encrypt / AutoSSL) and visit the site as `https://…`. Do not leave the clinic on plain `http` if patients’ names are stored there.

## Backup

The live records are **`database/carehub.sqlite`**. Download that file regularly (and after a busy clinic day). `schema.sql` is only the empty template plus sample seed data, not your real charts.

To restore: upload the backup as `database/carehub.sqlite` (site should be briefly unused while you replace it).

## If something fails

**Installer or home page redirects in a loop, or “Not found”.**  
`base_path` is wrong. Blank at domain root. For `https://example.com/carehub/` it must be `carehub`. You can edit `config.php` on the server.

**Blank page or HTTP 500.**  
PHP is older than 8.0, or `pdo_sqlite` is off. In cPanel: Select PHP Version → enable `pdo_sqlite` and `sqlite3`.

**Could not write config.php / database.**  
`database/` (and the project folder) must be writable by PHP. Avoid `777` if `755`/`775` works.

**Pretty URLs 404 (`/patients` fails, `/index.php` works).**  
`mod_rewrite` is off, or `.htaccess` was not uploaded. Confirm `.htaccess` is in the same folder as `index.php`. Some hosts need “AllowOverride All”.

**Download of `.sql` or `.sqlite` in the browser.**  
Upload `.htaccess`. It blocks those files. Do not chmod the database folder to world-readable listing if you can avoid it.

**Sample patients still there after you go live.**  
That seed comes from `schema.sql`. You can keep them as practice charts or ignore them and register your own. There is no bulk-delete screen; leave them or replace `carehub.sqlite` by running install again **before** you store real patients (running install later wipes the SQLite file).

## Nginx or a VPS (not cPanel)

Point the site root at this project folder. PHP-FPM must have `pdo_sqlite`. Send unknown paths to `index.php`. Example:

```nginx
root /var/www/carehub;
index index.php;
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
location ~ \.(sql|sqlite|sqlite3)$ { deny all; }
location ~ /config\.php$ { deny all; }
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

Then open `/install.php` the same way as on shared hosting.

## Local check before you upload

On your computer (not the clinic server):

```bash
cp config.example.php config.php
php -S localhost:8080 router.php
```

Open http://localhost:8080/install.php if `config.php` is missing, or http://localhost:8080/login if it exists.
