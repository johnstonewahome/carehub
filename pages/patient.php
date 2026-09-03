<?php
declare(strict_types=1);

$patient = $svc['patients']->get($patientId);
if (!$patient) {
    http_response_code(404);
    flash('error', 'That chart is not in the cabinet.');
    redirect('patients');
}

$errors = [];
$data = [
    'first_name' => $patient['first_name'],
    'last_name' => $patient['last_name'],
    'sex' => $patient['sex'],
    'dob' => $patient['dob'] ?? '',
    'phone' => $patient['phone'] ?? '',
    'address' => $patient['address'] ?? '',
    'allergies' => $patient['allergies'] ?? '',
    'medical_history' => $patient['medical_history'] ?? '',
];

if (request_method() === 'POST') {
    csrf_verify();
    $data = [
        'first_name' => post_string('first_name'),
        'last_name' => post_string('last_name'),
        'sex' => post_string('sex') ?: 'other',
        'dob' => post_string('dob'),
        'phone' => post_string('phone'),
        'address' => post_string('address'),
        'allergies' => post_string('allergies'),
        'medical_history' => post_string('medical_history'),
    ];
    try {
        $svc['patients']->update($patientId, $data);
        flash('ok', 'Chart details saved.');
        redirect('patients/' . $patientId);
    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    }
}

$visits = $svc['visits']->forPatient($patientId);
$age = age_from_dob($patient['dob'] ?? null);
$submitLabel = 'Save chart details';

ob_start();
?>
<header class="chart-head">
    <p class="mono chart-no"><?= e($patient['chart_no']) ?></p>
    <h1 class="patient-name"><?= e($patient['first_name'] . ' ' . $patient['last_name']) ?></h1>
    <p class="chart-meta">
        <?= e($patient['sex']) ?>
        <?php if ($age !== null): ?> · <?= e((string) $age) ?> years<?php endif; ?>
        <?php if (!empty($patient['phone'])): ?> · <?= e($patient['phone']) ?><?php endif; ?>
    </p>
    <?php if (!empty($patient['allergies'])): ?>
        <p class="pmshx-strip">
            <span class="pmshx-label">PMSHX</span>
            <?= e($patient['allergies']) ?>
        </p>
    <?php endif; ?>
    <?php if (!empty($patient['medical_history'])): ?>
        <p class="pmshx-strip">
            <span class="pmshx-label">Medical history</span>
            <?= e($patient['medical_history']) ?>
        </p>
    <?php endif; ?>
    <p><a class="btn" href="<?= e(url('patients/' . $patientId . '/visits/new')) ?>">New visit</a></p>
</header>

<section class="gutter-wrap">
    <h2>Visit timeline</h2>
    <?php if (!$visits): ?>
        <p class="empty">This folder has no visits yet. <a class="text-link" href="<?= e(url('patients/' . $patientId . '/visits/new')) ?>">Record the first visit</a></p>
    <?php else: ?>
        <ol class="gutter">
            <?php foreach ($visits as $visit): ?>
                <li>
                    <a href="<?= e(url('visits/' . $visit['id'])) ?>">
                        <time class="mono" datetime="<?= e($visit['visited_at']) ?>"><?= e(format_date($visit['visited_at'], 'd M Y')) ?></time>
                        <span class="gutter-body">
                            <strong><?= e($visit['chief_complaint'] ?: 'Visit') ?></strong>
                            <?php if ($visit['diagnosis']): ?>
                                <span><?= e($visit['diagnosis']) ?></span>
                            <?php endif; ?>
                            <?php if ($visit['medications']): ?>
                                <span class="meta">
                                    <?= e(implode(', ', array_map(static fn($m) => $m['name'], $visit['medications']))) ?>
                                </span>
                            <?php endif; ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>

<details class="edit-folder">
    <summary>Edit chart details</summary>
    <?php foreach ($errors as $err): ?>
        <p class="flash flash-error"><?= e($err) ?></p>
    <?php endforeach; ?>
    <?php require __DIR__ . '/partials/patient_form.php'; ?>
</details>
<?php
render_app([
    'title' => $patient['first_name'] . ' ' . $patient['last_name'],
    'nav' => 'patients',
    'tab' => $patient['chart_no'],
    'user' => $user,
    'body' => ob_get_clean(),
]);
