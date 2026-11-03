<?php
declare(strict_types=1);

function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void { if (!current_user()) redirect(base_url('?page=login')); }
function require_role(string ...$roles): void {
    require_login();
    if (!in_array(current_user()['role'] ?? '', $roles, true)) { http_response_code(403); exit('You do not have permission to access this page.'); }
}
function attempt_login(string $username, string $password): bool {
    $stmt = db()->prepare('SELECT userId, username, passwordHash, role, fullName FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['passwordHash'])) return false;
    session_regenerate_id(true);
    unset($user['passwordHash']);
    $_SESSION['user'] = $user;
    audit('login', 'users', (string)$user['userId']);
    return true;
}
function logout_user(): void {
    if (current_user()) audit('logout', 'users', (string)current_user()['userId']);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_destroy();
}
