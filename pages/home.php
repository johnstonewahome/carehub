<?php
declare(strict_types=1);

$q = trim((string) ($_GET['q'] ?? ''));
$patients = $svc['patients'];
$visits = $svc['visits'];
$medicines = $svc['medicines'];

$matches = $q !== '' ? $patients->search($q, 12) : [];
$low = $medicines->lowStock();
$recent = $visits->recent(6);

ob_start();
?>
<header class="desk-head">
    <p class="eyebrow">Today’s desk</p>
    <h1>Find a chart</h1>
    <form class="search-desk" method="get" action="<?= e(url()) ?>" role="search">
        <label class="sr-only" for="q">Search patients</label>
        <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Name, chart number, or phone" autofocus>
        <button type="submit" class="btn">Search</button>
        <a class="btn btn-quiet" href="<?= e(url('patients/new')) ?>">New patient</a>
    </form>
</header>

<?php if ($q !== ''): ?>
<section class="block">
    <h2>Matches</h2>
    <?php if (!$matches): ?>
        <p class="empty">No chart matches “<?= e($q) ?>”. <a class="text-link" href="<?= e(url('patients/new')) ?>">Register this patient</a></p>
    <?php else: ?>
        <ul class="chart-list">
            <?php foreach ($matches as $p): ?>
                <li>
                    <a href="<?= e(url('patients/' . $p['id'])) ?>">
                        <span class="mono"><?= e($p['chart_no']) ?></span>
                        <span class="who"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></span>
                        <span class="meta"><?= e($p['phone'] ?: 'no phone') ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php endif; ?>

<section class="block">
    <h2>Low stock</h2>
    <?php if (!$low): ?>
        <p class="empty">Nothing is at or below its reorder line.</p>
    <?php else: ?>
        <ul class="vial-list">
            <?php foreach ($low as $m): ?>
            <?php
            $onHand = (float) $m['quantity_on_hand'];
            $reorder = (float) $m['reorder_level'];
            $pct = $reorder > 0 ? max(6, min(100, ($onHand / max($reorder * 2, 1)) * 100)) : 20;
            ?>
            <li>
                <a href="<?= e(url('medicines/' . $m['id'])) ?>">
                    <span class="vial is-low" style="--fill: <?= e((string) $pct) ?>%">
                        <span class="vial-fill"></span>
                    </span>
                        <span class="who"><?= e($m['name']) ?></span>
                        <span class="mono warn"><?= e(rtrim(rtrim($m['quantity_on_hand'], '0'), '.')) ?> <?= e($m['unit']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="block">
    <h2>Recent visits</h2>
    <?php if (!$recent): ?>
        <p class="empty">No visits yet. Open a patient and save today’s visit.</p>
    <?php else: ?>
        <ul class="chart-list">
            <?php foreach ($recent as $v): ?>
                <li>
                    <a href="<?= e(url('visits/' . $v['id'])) ?>">
                        <span class="mono"><?= e(format_date($v['visited_at'], 'd M Y H:i')) ?></span>
                        <span class="who"><?= e($v['first_name'] . ' ' . $v['last_name']) ?></span>
                        <span class="meta"><?= e($v['chief_complaint'] ?: $v['diagnosis'] ?: 'Visit') ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php
render_app([
    'title' => 'Desk',
    'nav' => 'home',
    'tab' => 'Desk',
    'user' => $user,
    'body' => ob_get_clean(),
]);
