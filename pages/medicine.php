<?php
declare(strict_types=1);

$medicine = $svc['medicines']->get($medicineId);
if (!$medicine) {
    flash('error', 'That medicine is not on the shelf.');
    redirect('medicines');
}

$errors = [];
$data = [
    'name' => $medicine['name'],
    'generic_name' => $medicine['generic_name'] ?? '',
    'form' => $medicine['form'],
    'strength' => $medicine['strength'] ?? '',
    'unit' => $medicine['unit'],
    'reorder_level' => (string) $medicine['reorder_level'],
    'notes' => $medicine['notes'] ?? '',
];

if (request_method() === 'POST') {
    csrf_verify();
    $action = post_string('action') ?: 'update';
    try {
        if ($action === 'receive') {
            $qty = post_float('quantity', 0) ?? 0;
            $reason = post_string('reason') ?: 'Stock received';
            $svc['stock']->receive($medicineId, $qty, $reason);
            flash('ok', 'Stock received.');
            redirect('medicines/' . $medicineId);
        }
        if ($action === 'adjust') {
            $qty = post_float('quantity', 0) ?? 0;
            $reason = post_string('reason');
            $svc['stock']->adjust($medicineId, $qty, $reason);
            flash('ok', 'Stock adjusted.');
            redirect('medicines/' . $medicineId);
        }
        $data = [
            'name' => post_string('name'),
            'generic_name' => post_string('generic_name'),
            'form' => post_string('form') ?: 'tablet',
            'strength' => post_string('strength'),
            'unit' => post_string('unit'),
            'reorder_level' => post_string('reorder_level'),
            'notes' => post_string('notes'),
        ];
        $svc['medicines']->update($medicineId, $data);
        flash('ok', 'Medicine details saved.');
        redirect('medicines/' . $medicineId);
    } catch (InvalidArgumentException | StockException $e) {
        $errors[] = $e->getMessage();
        $medicine = $svc['medicines']->get($medicineId) ?? $medicine;
    }
}

$movements = $svc['medicines']->movements($medicineId);
$onHand = (float) $medicine['quantity_on_hand'];
$reorder = (float) $medicine['reorder_level'];
$low = $onHand <= $reorder;

ob_start();
?>
<header class="chart-head">
    <p class="eyebrow">Shelf bottle</p>
    <h1><?= e($medicine['name']) ?></h1>
    <p class="chart-meta">
        <?= e($medicine['generic_name'] ?: $medicine['form']) ?>
        <?= $medicine['strength'] ? ' · ' . e($medicine['strength']) : '' ?>
    </p>
    <p class="mono<?= $low ? ' warn' : ' ok' ?>">
        <?= e(rtrim(rtrim((string) $medicine['quantity_on_hand'], '0'), '.')) ?> <?= e($medicine['unit']) ?> on hand
        <?php if ($low): ?> · at or below reorder line of <?= e(rtrim(rtrim((string) $medicine['reorder_level'], '0'), '.')) ?><?php endif; ?>
    </p>
</header>

<?php foreach ($errors as $err): ?>
    <p class="flash flash-error"><?= e($err) ?></p>
<?php endforeach; ?>

<div class="split">
    <form class="stack-form" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="receive">
        <h2>Receive stock</h2>
        <label>Quantity
            <input type="number" step="0.01" min="0.01" name="quantity" required>
        </label>
        <label>Note
            <input name="reason" placeholder="Supplier delivery">
        </label>
        <button class="btn" type="submit">Receive stock</button>
    </form>
    <form class="stack-form" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="adjust">
        <h2>Adjust stock</h2>
        <label>Delta (use minus to take off)
            <input type="number" step="0.01" name="quantity" required>
        </label>
        <label>Reason
            <input name="reason" required placeholder="Broken pack counted out">
        </label>
        <button class="btn btn-quiet" type="submit">Adjust stock</button>
    </form>
</div>

<details class="edit-folder" open>
    <summary>Details</summary>
    <form class="stack-form" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update">
        <label>Name
            <input name="name" required value="<?= e($data['name']) ?>">
        </label>
        <label>Generic name
            <input name="generic_name" value="<?= e($data['generic_name']) ?>">
        </label>
        <div class="field-row">
            <label>Form
                <select name="form">
                    <?php foreach (['tablet' => 'Tablet / capsule', 'syrup' => 'Syrup', 'injection' => 'Injection', 'cream' => 'Cream', 'other' => 'Other'] as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= $data['form'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Strength
                <input name="strength" value="<?= e($data['strength']) ?>">
            </label>
        </div>
        <div class="field-row">
            <label>Unit
                <input name="unit" required value="<?= e($data['unit']) ?>">
            </label>
            <label>Reorder when at or below
                <input type="number" step="0.01" min="0" name="reorder_level" value="<?= e($data['reorder_level']) ?>">
            </label>
        </div>
        <label>Notes
            <textarea name="notes" rows="2"><?= e($data['notes']) ?></textarea>
        </label>
        <button class="btn" type="submit">Save details</button>
    </form>
</details>

<section class="block">
    <h2>Movement history</h2>
    <?php if (!$movements): ?>
        <p class="empty">No stock has moved yet. Receive an opening quantity.</p>
    <?php else: ?>
        <table class="ledger">
            <thead>
                <tr><th>When</th><th>Type</th><th>Qty</th><th>Note</th></tr>
            </thead>
            <tbody>
            <?php foreach ($movements as $row): ?>
                <tr>
                    <td class="mono"><?= e(format_date($row['created_at'], 'd M Y H:i')) ?></td>
                    <td><?= e($row['type']) ?></td>
                    <td class="mono"><?= e(rtrim(rtrim((string) $row['quantity'], '0'), '.')) ?></td>
                    <td>
                        <?= e($row['reason'] ?: '') ?>
                        <?php if (!empty($row['visit_id']) && !empty($row['chart_no'])): ?>
                            · <a class="text-link" href="<?= e(url('visits/' . $row['visit_id'])) ?>"><?= e($row['chart_no'] . ' ' . $row['first_name'] . ' ' . $row['last_name']) ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php
render_app([
    'title' => $medicine['name'],
    'nav' => 'medicines',
    'tab' => 'Bottle',
    'user' => $user,
    'body' => ob_get_clean(),
]);
