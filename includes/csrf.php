<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    $ok = is_string($sent) && hash_equals(csrf_token(), $sent);
    if (!$ok) {
        http_response_code(400);
        flash('error', 'This form expired. Try again.');
        redirect(ltrim(request_path(), '/'));
    }
}
