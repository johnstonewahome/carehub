<?php
declare(strict_types=1);

function render_app(array $view): void
{
    $title = $view['title'] ?? 'CareHub';
    $nav = $view['nav'] ?? '';
    $tab = $view['tab'] ?? (string) config('clinic_name', 'CareHub');
    $user = $view['user'] ?? current_user();
    $body = $view['body'] ?? '';
    $clinic = (string) config('clinic_name', 'CareHub Clinic');
    $flashes = take_flashes();
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · CareHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=IBM+Plex+Mono:wght@400;500&family=Sora:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body class="room">
<div class="cabinet">
    <aside class="rail">
        <a class="brand" href="<?= e(url()) ?>">
            <span class="brand-mark">CH</span>
            <span class="brand-name">CareHub</span>
            <span class="brand-sub"><?= e($clinic) ?></span>
        </a>
        <nav class="tabs" aria-label="Cabinet">
            <a class="file-tab<?= $nav === 'home' ? ' is-open' : '' ?>" href="<?= e(url()) ?>">Home</a>
            <a class="file-tab<?= $nav === 'patients' ? ' is-open' : '' ?>" href="<?= e(url('patients')) ?>">Patients</a>
            <a class="file-tab<?= $nav === 'medicines' ? ' is-open' : '' ?>" href="<?= e(url('medicines')) ?>">Medicines</a>
        </nav>
        <?php if ($user): ?>
        <div class="rail-foot">
            <span class="rail-user"><?= e($user['name']) ?></span>
            <a href="<?= e(url('password')) ?>">Change password</a>
            <a href="<?= e(url('logout')) ?>">Sign out</a>
        </div>
        <?php endif; ?>
    </aside>
    <main class="folder">
        <div class="folder-tab" aria-hidden="true"><?= e($tab) ?></div>
        <div class="folder-body">
            <?php foreach ($flashes as $flash): ?>
                <p class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></p>
            <?php endforeach; ?>
            <?= $body ?>
        </div>
    </main>
</div>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
    <?php
}

function render_gate(array $view): void
{
    $title = $view['title'] ?? 'CareHub';
    $body = $view['body'] ?? '';
    $clinic = (string) config('clinic_name', 'CareHub Clinic');
    $flashes = take_flashes();
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · CareHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=IBM+Plex+Mono:wght@400;500&family=Sora:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body class="room room-closed">
<div class="closed-cabinet">
    <p class="lid-label">Consulting room</p>
    <h1 class="lid-title">CareHub</h1>
    <p class="lid-clinic"><?= e($clinic) ?></p>
    <?php foreach ($flashes as $flash): ?>
        <p class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></p>
    <?php endforeach; ?>
    <?= $body ?>
</div>
</body>
</html>
    <?php
}
