<?php
declare(strict_types=1);

if (current_user()) {
    redirect('');
}

if (request_method() === 'POST') {
    csrf_verify();
    $email = post_string('email');
    $password = (string) ($_POST['password'] ?? '');
    if (attempt_login($email, $password)) {
        flash('ok', 'Cabinet open.');
        redirect('');
    }
    flash('error', 'Email or password is wrong.');
}

ob_start();
?>
<form class="stack-form" method="post" action="<?= e(url('login')) ?>">
    <?= csrf_field() ?>
    <label>
        Email
        <input type="email" name="email" required autocomplete="username" value="<?= e(post_string('email')) ?>">
    </label>
    <label>
        Password
        <input type="password" name="password" required autocomplete="current-password">
    </label>
    <button type="submit" class="btn">Open cabinet</button>
</form>
<p class="hint">Default after install: admin@carehub.local / ChangeMe!23</p>
<?php
render_gate([
    'title' => 'Sign in',
    'body' => ob_get_clean(),
]);
