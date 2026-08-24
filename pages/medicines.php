<?php
declare(strict_types=1);

$q = trim((string) ($_GET['q'] ?? ''));
$list = $svc['medicines']->search($q);

ob_start();
?>
<header class="desk-head">
    <p class="eyebrow">Shelf</p>
    <h1>Medicines</h1>
    <form class="search-desk" method="get" action="<?= e(url('medicines')) ?>">
        <label class="sr-only" for="q">Search medicines</label>
        <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Name or generic">
        <button class="btn" type="submit">Search</button>
        <a class="btn btn-quiet" href="<?= e(url('medicines/new')) ?>">New medicine</a>
    </form>
</header>

<?php if (!$list): ?>
    <p class="empty">The shelf is empty. <a class="text-link" href="<?= e(url('medicines/new')) ?>">Add the first medicine</a></p>
<?php else: ?>
    <ul class="vial-list vial-list-full">
        <?php foreach ($list as $m): ?>
            <?php
            $onHand = (float) $m['quantity_on_hand'];
            $reorder = (float) $m['reorder_level'];
            $low = $onHand <= $reorder;
            $pct = $reorder > 0 ? max(6, min(100, ($onHand / max($reorder * 2, 1)) * 100)) : ($onHand > 0 ? 70 : 6);
            ?>
            <li>
                <a href="<?= e(url('medicines/' . $m['id'])) ?>">
                    <span class="vial<?= $low ? ' is-low' : '' ?>" style="--fill: <?= e((string) $pct) ?>%">
                        <span class="vial-fill"></span>
                    </span>
                    <span class="who"><?= e($m['name']) ?></span>
                    <span class="meta"><?= e($m['generic_name'] ?: $m['form']) ?><?= $m['strength'] ? ' · ' . e($m['strength']) : '' ?></span>
                    <span class="mono<?= $low ? ' warn' : '' ?>"><?= e(format_qty($m['quantity_on_hand'])) ?> <?= e($m['unit']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php
render_app([
    'title' => 'Medicines',
    'nav' => 'medicines',
    'tab' => 'Shelf',
    'user' => $user,
    'body' => ob_get_clean(),
]);
