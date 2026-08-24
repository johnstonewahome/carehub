<?php
declare(strict_types=1);

function current_user(): ?array
{
    $id = $_SESSION['user_id'] ?? null;
    if (!is_int($id) && !is_numeric($id)) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, name, email FROM users WHERE id = ?');
    $stmt->execute([(int) $id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('error', 'Sign in to open the cabinet.');
        redirect('login');
    }
    return $user;
}

function attempt_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $row['id'];
    return true;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function change_password(int $userId, string $current, string $new): void
{
    if (strlen($new) < 8) {
        throw new RuntimeException('New password must be at least 8 characters');
    }
    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();
    if (!$hash || !password_verify($current, (string) $hash)) {
        throw new RuntimeException('Current password is wrong');
    }
    $update = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $update->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
}
