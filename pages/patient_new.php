<?php
declare(strict_types=1);

$errors = [];
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

if (request_method() === 'POST') {
    csrf_verify();
    try {
        $id = $svc['patients']->create($data);
        flash('ok', 'Chart opened.');
        redirect('patients/' . $id);
    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    }
}

ob_start();
?>
<header class="desk-head">
    <p class="eyebrow">New hanging file</p>
    <h1>Register patient</h1>
</header>
<?php foreach ($errors as $err): ?>
    <p class="flash flash-error"><?= e($err) ?></p>
<?php endforeach; ?>
<?php require __DIR__ . '/partials/patient_form.php'; ?>
<?php
render_app([
    'title' => 'New patient',
    'nav' => 'patients',
    'tab' => 'New chart',
    'user' => $user,
    'body' => ob_get_clean(),
]);
