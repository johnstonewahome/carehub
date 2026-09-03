<?php
declare(strict_types=1);

$q = trim((string) ($_GET['q'] ?? ''));
$list = $svc['patients']->search($q);

ob_start();
?>
<header class="desk-head">
    <p class="eyebrow">Hanging files</p>
    <h1>Patients</h1>
    <form class="search-desk" method="get" action="<?= e(url('patients')) ?>">
        <label class="sr-only" for="q">Search patients</label>
        <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Name, chart number, or phone">
        <button class="btn" type="submit">Search</button>
        <a class="btn btn-quiet" href="<?= e(url('patients/new')) ?>">New patient</a>
        <a class="btn btn-quiet" href="<?= e(url('export.xlsx')) ?>">Export Excel</a>
    </form>
</header>

<?php if (!$list): ?>
    <p class="empty">No patients in this cabinet yet. <a class="text-link" href="<?= e(url('patients/new')) ?>">Register the first chart</a></p>
<?php else: ?>
    <ul class="chart-list">
        <?php foreach ($list as $p): ?>
            <li>
                <a href="<?= e(url('patients/' . $p['id'])) ?>">
                    <span class="mono"><?= e($p['chart_no']) ?></span>
                    <span class="who"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></span>
                    <span class="meta"><?= e($p['phone'] ?: 'no phone') ?> · <?= e($p['sex']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php
render_app([
    'title' => 'Patients',
    'nav' => 'patients',
    'tab' => 'Patients',
    'user' => $user,
    'body' => ob_get_clean(),
]);
