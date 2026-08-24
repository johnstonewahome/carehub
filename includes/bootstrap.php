<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/Stock.php';
require_once __DIR__ . '/Patients.php';
require_once __DIR__ . '/Visits.php';
require_once __DIR__ . '/Medicines.php';
require_once __DIR__ . '/layout.php';

function boot_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => base_path() === '' ? '/' : base_path(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('carehub');
    session_start();
}

function services(): array
{
    $db = db();
    $stock = new Stock($db);
    return [
        'db' => $db,
        'stock' => $stock,
        'patients' => new Patients($db),
        'visits' => new Visits($db, $stock),
        'medicines' => new Medicines($db, $stock),
    ];
}
