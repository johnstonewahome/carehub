<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/layout.php';

if (is_installed()) {
    header('Location: index.php');
    exit;
}

session_name('carehub');
session_start();

$errors = [];
$fields = [
    'clinic_name' => post_string('clinic_name') ?: 'CareHub Clinic',
    'admin_name' => post_string('admin_name') ?: 'Clinic admin',
    'admin_email' => post_string('admin_email') ?: 'admin@carehub.local',
    'admin_password' => (string) ($_POST['admin_password'] ?? ''),
    'base_path' => post_string('base_path'),
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        $errors[] = 'This form expired. Try again.';
    }
    if (strlen($fields['admin_password']) < 8) {
        $errors[] = 'Admin password must be at least 8 characters.';
    }
    if ($errors === []) {
        try {
            $sqlitePath = dirname(__FILE__) . '/database/carehub.sqlite';
            if (is_file($sqlitePath)) {
                unlink($sqlitePath);
            }
            $pdo = sqlite_connect($sqlitePath);
            apply_schema($pdo);
            $id = $pdo->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetchColumn();
            if ($id === false) {
                throw new RuntimeException('schema.sql did not create a sign-in user.');
            }
            $update = $pdo->prepare(
                'UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ?'
            );
            $update->execute([
                $fields['admin_name'],
                $fields['admin_email'],
                password_hash($fields['admin_password'], PASSWORD_DEFAULT),
                (int) $id,
            ]);

            $config = [
                'clinic_name' => $fields['clinic_name'],
                'database_path' => 'database/carehub.sqlite',
                'base_path' => trim($fields['base_path'], '/'),
            ];
            $export = var_export($config, true);
            $written = file_put_contents(
                __DIR__ . '/config.php',
                "<?php\ndeclare(strict_types=1);\nreturn {$export};\n"
            );
            if ($written === false) {
                throw new RuntimeException('Could not write config.php. Check folder permissions.');
            }
            header('Location: index.php');
            exit;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

ob_start();
?>
<p class="hint">The clinic records live in <span class="mono">database/schema.sql</span>. Install copies that file into a local SQLite database on this server.</p>
<?php foreach ($errors as $err): ?>
    <p class="flash flash-error"><?= e($err) ?></p>
<?php endforeach; ?>
<form class="stack-form" method="post">
    <?= csrf_field() ?>
    <label>Clinic name
        <input name="clinic_name" required value="<?= e($fields['clinic_name']) ?>">
    </label>
    <label>Subfolder (leave blank at domain root)
        <input name="base_path" placeholder="carehub" value="<?= e($fields['base_path']) ?>">
    </label>
    <label>Your name
        <input name="admin_name" required value="<?= e($fields['admin_name']) ?>">
    </label>
    <label>Sign-in email
        <input type="email" name="admin_email" required value="<?= e($fields['admin_email']) ?>">
    </label>
    <label>Sign-in password
        <input type="password" name="admin_password" required minlength="8" value="<?= e($fields['admin_password']) ?>">
    </label>
    <button class="btn" type="submit">Install CareHub</button>
</form>
<?php
render_gate([
    'title' => 'Install',
    'body' => ob_get_clean(),
]);
