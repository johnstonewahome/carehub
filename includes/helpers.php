<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function config(?string $key = null, mixed $default = null): mixed
{
    static $config;
    if ($config === null) {
        $path = dirname(__DIR__) . '/config.php';
        if (!is_file($path)) {
            return $key === null ? [] : $default;
        }
        $config = require $path;
    }
    if ($key === null) {
        return $config;
    }
    return $config[$key] ?? $default;
}

function base_path(): string
{
    $configured = trim((string) config('base_path', ''), '/');
    if ($configured !== '') {
        return '/' . $configured;
    }
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $file = basename($script);
    if (in_array($file, ['index.php', 'router.php', 'install.php'], true)) {
        $dir = dirname($script);
        return $dir === '/' ? '' : rtrim($dir, '/');
    }
    return '';
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $base = base_path();
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }
    return ($base === '' ? '' : $base) . '/' . $path;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return $flashes;
}

function request_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base)) ?: '/';
    }
    $uri = '/' . trim($uri, '/');
    if ($uri === '/index.php') {
        return '/';
    }
    return $uri === '//' ? '/' : $uri;
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function post_string(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function post_float(string $key, ?float $default = null): ?float
{
    $value = $_POST[$key] ?? null;
    if ($value === null || $value === '') {
        return $default;
    }
    if (!is_numeric($value)) {
        return $default;
    }
    return (float) $value;
}

function post_int(string $key, ?int $default = null): ?int
{
    $value = $_POST[$key] ?? null;
    if ($value === null || $value === '') {
        return $default;
    }
    if (!is_numeric($value)) {
        return $default;
    }
    return (int) $value;
}

function format_date(?string $value, string $format = 'd M Y'): string
{
    if (!$value) {
        return '—';
    }
    $dt = date_create($value);
    return $dt ? $dt->format($format) : $value;
}

function age_from_dob(?string $dob): ?int
{
    if (!$dob) {
        return null;
    }
    $born = date_create($dob);
    if (!$born) {
        return null;
    }
    return (int) $born->diff(date_create('today'))->y;
}

function medicine_form_label(string $form): string
{
    return match ($form) {
        'tablet' => 'tablet',
        'syrup' => 'syrup',
        'injection' => 'injection',
        'cream' => 'cream',
        default => 'other',
    };
}

function format_qty(mixed $value): string
{
    if ($value === null || $value === '') {
        return '0';
    }
    $formatted = number_format((float) $value, 2, '.', '');
    $trimmed = rtrim(rtrim($formatted, '0'), '.');
    return $trimmed === '' ? '0' : $trimmed;
}

function is_installed(): bool
{
    return is_file(dirname(__DIR__) . '/config.php');
}

function pmshx_label(): string
{
    return 'Past Medical and Surgical history (PMSHX)';
}
