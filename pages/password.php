<?php
declare(strict_types=1);

$errors = [];

if (request_method() === 'POST') {
    csrf_verify();
    try {
        $new = (string) ($_POST['new'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');
        if ($new !== $confirm) {
            throw new RuntimeException('New password and confirmation do not match');
        }
        change_password((int) $user['id'], (string) ($_POST['current'] ?? ''), $new);
        flash('ok', 'Password changed.');
        redirect('password');
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }
}

ob_start();
?>
<header class="desk-head">
    <p class="eyebrow">Lock</p>
    <h1>Change password</h1>
</header>
<?php foreach ($errors as $err): ?>
    <p class="flash flash-error"><?= e($err) ?></p>
<?php endforeach; ?>
<form class="stack-form" method="post">
    <?= csrf_field() ?>
    <label>Current password
        <input type="password" name="current" required autocomplete="current-password">
    </label>
    <label>New password
        <input type="password" name="new" required minlength="8" autocomplete="new-password">
    </label>
    <label>Confirm new password
        <input type="password" name="confirm" required minlength="8" autocomplete="new-password">
    </label>
    <button class="btn" type="submit">Change password</button>
</form>
<?php
render_app([
    'title' => 'Password',
    'nav' => '',
    'tab' => 'Lock',
    'user' => $user,
    'body' => ob_get_clean(),
]);
