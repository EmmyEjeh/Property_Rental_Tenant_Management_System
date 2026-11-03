<?php
declare(strict_types=1);

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $path): never { header('Location: ' . $path); exit; }
function base_url(string $path = ''): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $base = rtrim(dirname($script), '/');
    if ($base === '.' || $base === '/') $base = '';
    return $base . '/' . ltrim($path, '/');
}
function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void {
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['_csrf'] ?? '', $sent)) {
        http_response_code(419); exit('The form session expired. Go back, refresh the page, and try again.');
    }
}
function flash(string $key, ?string $message = null): ?string {
    if ($message !== null) { $_SESSION['_flash'][$key] = $message; return null; }
    $value = $_SESSION['_flash'][$key] ?? null; unset($_SESSION['_flash'][$key]); return $value;
}
function audit(string $action, string $entityType, ?string $entityId = null, array $details = []): void {
    try {
        $stmt = db()->prepare('INSERT INTO audit_logs (userId, action, entityType, entityId, details, ipAddress) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$_SESSION['user']['userId'] ?? null, $action, $entityType, $entityId, $details ? json_encode($details, JSON_THROW_ON_ERROR) : null, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) {
        error_log('Audit logging failed: ' . $e->getMessage());
    }
}
