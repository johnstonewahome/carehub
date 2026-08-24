<?php
declare(strict_types=1);

$errors = [];
$data = [
    'name' => post_string('name'),
    'generic_name' => post_string('generic_name'),
    'form' => post_string('form') ?: 'tablet',
    'strength' => post_string('strength'),
    'unit' => post_string('unit') ?: 'tablets',
    'reorder_level' => post_string('reorder_level') ?: '0',
    'notes' => post_string('notes'),
    'initial_quantity' => post_string('initial_quantity') ?: '0',
];

if (request_method() === 'POST') {
    csrf_verify();
    try {
        $id = $svc['medicines']->create($data);
        flash('ok', 'Medicine added to the shelf.');
        redirect('medicines/' . $id);
    } catch (InvalidArgumentException | StockException $e) {
        $errors[] = $e->getMessage();
    }
}

ob_start();
?>
<header class="desk-head">
    <p class="eyebrow">New bottle</p>
    <h1>Add medicine</h1>
</header>
<?php foreach ($errors as $err): ?>
    <p class="flash flash-error"><?= e($err) ?></p>
<?php endforeach; ?>
<form class="stack-form" method="post">
    <?= csrf_field() ?>
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
            <input name="strength" placeholder="500 mg" value="<?= e($data['strength']) ?>">
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
    <label>Opening stock
        <input type="number" step="0.01" min="0" name="initial_quantity" value="<?= e($data['initial_quantity']) ?>">
    </label>
    <label>Notes
        <textarea name="notes" rows="2"><?= e($data['notes']) ?></textarea>
    </label>
    <button class="btn" type="submit">Save medicine</button>
</form>
<?php
render_app([
    'title' => 'New medicine',
    'nav' => 'medicines',
    'tab' => 'New bottle',
    'user' => $user,
    'body' => ob_get_clean(),
]);
