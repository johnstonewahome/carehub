<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (!is_installed()) {
    header('Location: install.php');
    exit;
}

boot_session();

$path = request_path();
$method = request_method();

if ($path === '/login') {
    require __DIR__ . '/pages/login.php';
    exit;
}
if ($path === '/logout') {
    logout();
    boot_session();
    flash('ok', 'Signed out.');
    redirect('login');
}

$user = require_login();
$svc = services();

if ($path === '/' && $method === 'GET') {
    require __DIR__ . '/pages/home.php';
    exit;
}

if ($path === '/password') {
    require __DIR__ . '/pages/password.php';
    exit;
}

if ($path === '/patients' && $method === 'GET') {
    require __DIR__ . '/pages/patients.php';
    exit;
}

if ($path === '/patients/new') {
    require __DIR__ . '/pages/patient_new.php';
    exit;
}

if (preg_match('#^/patients/(\d+)$#', $path, $m)) {
    $patientId = (int) $m[1];
    require __DIR__ . '/pages/patient.php';
    exit;
}

if (preg_match('#^/patients/(\d+)/visits/new$#', $path, $m)) {
    $patientId = (int) $m[1];
    require __DIR__ . '/pages/visit.php';
    exit;
}

if (preg_match('#^/visits/(\d+)$#', $path, $m) && $method === 'GET') {
    $visitId = (int) $m[1];
    require __DIR__ . '/pages/visit_show.php';
    exit;
}

if ($path === '/medicines' && $method === 'GET') {
    require __DIR__ . '/pages/medicines.php';
    exit;
}

if ($path === '/medicines/new') {
    require __DIR__ . '/pages/medicine_new.php';
    exit;
}

if (preg_match('#^/medicines/(\d+)$#', $path, $m)) {
    $medicineId = (int) $m[1];
    require __DIR__ . '/pages/medicine.php';
    exit;
}

http_response_code(404);
ob_start();
?>
<p class="lede">That folder is not in this cabinet.</p>
<p><a class="text-link" href="<?= e(url()) ?>">Back to the desk</a></p>
<?php
render_app([
    'title' => 'Not found',
    'nav' => '',
    'tab' => 'Missing',
    'user' => $user,
    'body' => ob_get_clean(),
]);
