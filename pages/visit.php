<?php
declare(strict_types=1);

$patient = $svc['patients']->get($patientId);
if (!$patient) {
    flash('error', 'That chart is not in the cabinet.');
    redirect('patients');
}

$catalog = $svc['medicines']->all();
$errors = [];
$now = date('Y-m-d\TH:i');
$fields = [
    'visited_at' => post_string('visited_at') ?: $now,
    'chief_complaint' => post_string('chief_complaint'),
    'examination' => post_string('examination'),
    'diagnosis' => post_string('diagnosis'),
    'treatment' => post_string('treatment'),
    'bp_systolic' => post_string('bp_systolic'),
    'bp_diastolic' => post_string('bp_diastolic'),
    'pulse' => post_string('pulse'),
    'temp_c' => post_string('temp_c'),
    'weight_kg' => post_string('weight_kg'),
    'notes' => post_string('notes'),
];

if (request_method() === 'POST') {
    csrf_verify();
    $lines = [];
    $posted = $_POST['meds'] ?? [];
    if (is_array($posted)) {
        foreach ($posted as $row) {
            if (!is_array($row)) {
                continue;
            }
            $lines[] = [
                'medicine_id' => (int) ($row['medicine_id'] ?? 0),
                'quantity' => (float) ($row['quantity'] ?? 0),
                'dose_instructions' => trim((string) ($row['dose_instructions'] ?? '')),
            ];
        }
    }
    try {
        $visitId = $svc['visits']->create($patientId, $fields, $lines);
        flash('ok', 'Visit saved.');
        redirect('visits/' . $visitId);
    } catch (StockException | InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    }
}

$catalogJson = [];
foreach ($catalog as $m) {
    $catalogJson[] = [
        'id' => (int) $m['id'],
        'name' => $m['name'],
        'strength' => $m['strength'],
        'unit' => $m['unit'],
        'on_hand' => (float) $m['quantity_on_hand'],
    ];
}

ob_start();
?>
<header class="chart-head">
    <p class="eyebrow">On <?= e($patient['chart_no']) ?></p>
    <h1 class="patient-name"><?= e($patient['first_name'] . ' ' . $patient['last_name']) ?></h1>
    <?php if (!empty($patient['allergies'])): ?>
        <p class="pmshx-strip">
            <span class="pmshx-label">PMSHX</span>
            <?= e($patient['allergies']) ?>
        </p>
    <?php endif; ?>
</header>

<?php foreach ($errors as $err): ?>
    <p class="flash flash-error"><?= e($err) ?></p>
<?php endforeach; ?>

<form class="stack-form" method="post" id="visit-form">
    <?= csrf_field() ?>
    <label>When
        <input type="datetime-local" name="visited_at" required value="<?= e($fields['visited_at']) ?>">
    </label>
    <label>Chief complaint
        <textarea name="chief_complaint" rows="2"><?= e($fields['chief_complaint']) ?></textarea>
    </label>
    <label>Examination
        <textarea name="examination" rows="3"><?= e($fields['examination']) ?></textarea>
    </label>
    <label>Diagnosis
        <textarea name="diagnosis" rows="2"><?= e($fields['diagnosis']) ?></textarea>
    </label>
    <label>Treatment
        <textarea name="treatment" rows="2"><?= e($fields['treatment']) ?></textarea>
    </label>
    <fieldset class="vitals">
        <legend>Vitals</legend>
        <label>BP sys <input type="number" name="bp_systolic" min="0" max="300" value="<?= e($fields['bp_systolic']) ?>"></label>
        <label>BP dia <input type="number" name="bp_diastolic" min="0" max="200" value="<?= e($fields['bp_diastolic']) ?>"></label>
        <label>Pulse <input type="number" name="pulse" min="0" max="250" value="<?= e($fields['pulse']) ?>"></label>
        <label>Temp °C <input type="number" step="0.1" name="temp_c" value="<?= e($fields['temp_c']) ?>"></label>
        <label>Weight kg <input type="number" step="0.1" name="weight_kg" value="<?= e($fields['weight_kg']) ?>"></label>
    </fieldset>
    <label>Notes
        <textarea name="notes" rows="2"><?= e($fields['notes']) ?></textarea>
    </label>

    <section class="meds-block" data-catalog="<?= e(json_encode($catalogJson, JSON_UNESCAPED_UNICODE) ?: '[]') ?>">
        <h2>Medicines used</h2>
        <p class="hint">Stock comes off the shelf when you save this visit.</p>
        <div id="med-rows"></div>
        <button type="button" class="btn btn-quiet" id="add-med"<?= $catalog ? '' : ' disabled' ?>>Add medicine</button>
        <?php if (!$catalog): ?>
            <p class="empty">Add a medicine to the cabinet first.</p>
        <?php endif; ?>
    </section>

    <button class="btn" type="submit">Save visit</button>
    <a class="text-link" href="<?= e(url('patients/' . $patientId)) ?>">Cancel</a>
</form>
<?php
render_app([
    'title' => 'New visit',
    'nav' => 'patients',
    'tab' => $patient['chart_no'],
    'user' => $user,
    'body' => ob_get_clean(),
]);
