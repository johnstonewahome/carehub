<?php
declare(strict_types=1);

$visit = $svc['visits']->get($visitId);
if (!$visit) {
    flash('error', 'That visit is not in the cabinet.');
    redirect('patients');
}

ob_start();
?>
<header class="chart-head">
    <p class="eyebrow"><a class="text-link" href="<?= e(url('patients/' . $visit['patient_id'])) ?>"><?= e($visit['chart_no']) ?></a></p>
    <h1 class="patient-name"><?= e($visit['first_name'] . ' ' . $visit['last_name']) ?></h1>
    <p class="mono"><?= e(format_date($visit['visited_at'], 'd M Y H:i')) ?></p>
    <?php if (!empty($visit['allergies'])): ?>
        <p class="allergy-strip">Allergies: <?= e($visit['allergies']) ?></p>
    <?php endif; ?>
</header>

<dl class="chart-fields">
    <div><dt>Chief complaint</dt><dd><?= e($visit['chief_complaint'] ?: '—') ?></dd></div>
    <div><dt>Examination</dt><dd><?= e($visit['examination'] ?: '—') ?></dd></div>
    <div><dt>Diagnosis</dt><dd><?= e($visit['diagnosis'] ?: '—') ?></dd></div>
    <div><dt>Treatment</dt><dd><?= e($visit['treatment'] ?: '—') ?></dd></div>
    <div><dt>Vitals</dt><dd>
        <?php
        $bits = [];
        if ($visit['bp_systolic'] || $visit['bp_diastolic']) {
            $bits[] = 'BP ' . ($visit['bp_systolic'] ?: '—') . '/' . ($visit['bp_diastolic'] ?: '—');
        }
        if ($visit['pulse']) {
            $bits[] = 'P ' . $visit['pulse'];
        }
        if ($visit['temp_c']) {
            $bits[] = 'T ' . rtrim(rtrim((string) $visit['temp_c'], '0'), '.') . '°C';
        }
        if ($visit['weight_kg']) {
            $bits[] = 'Wt ' . rtrim(rtrim((string) $visit['weight_kg'], '0'), '.') . ' kg';
        }
        echo e($bits ? implode(' · ', $bits) : '—');
        ?>
    </dd></div>
    <div><dt>Notes</dt><dd><?= e($visit['notes'] ?: '—') ?></dd></div>
</dl>

<section class="block">
    <h2>Medicines used</h2>
    <?php if (!$visit['medications']): ?>
        <p class="empty">No medicines recorded on this visit.</p>
    <?php else: ?>
        <ul class="chart-list">
            <?php foreach ($visit['medications'] as $med): ?>
                <li>
                    <span class="who"><?= e($med['name']) ?><?= $med['strength'] ? ' · ' . e($med['strength']) : '' ?></span>
                    <span class="mono"><?= e(rtrim(rtrim((string) $med['quantity'], '0'), '.')) ?> <?= e($med['unit']) ?></span>
                    <span class="meta"><?= e($med['dose_instructions'] ?: 'No dose note') ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<p><a class="text-link" href="<?= e(url('patients/' . $visit['patient_id'])) ?>">Back to chart</a></p>
<?php
render_app([
    'title' => 'Visit',
    'nav' => 'patients',
    'tab' => $visit['chart_no'],
    'user' => $user,
    'body' => ob_get_clean(),
]);
