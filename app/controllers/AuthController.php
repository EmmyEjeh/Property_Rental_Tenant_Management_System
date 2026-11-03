<?php
declare(strict_types=1);
$title = 'Sign in';
if ($page === 'logout') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
    verify_csrf(); logout_user(); redirect(base_url('?page=login'));
}
if (current_user()) redirect(base_url('?page=dashboard'));
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($username === '' || $password === '') $error = 'Enter your username and password.';
    else {
        try { if (attempt_login($username, $password)) redirect(base_url('?page=dashboard')); else $error = 'The username or password is incorrect.'; }
        catch (Throwable $e) { error_log($e->getMessage()); $error = 'The service is not ready. Check the database setup and try again.'; }
    }
}
require dirname(__DIR__) . '/views/layouts/header.php';
require dirname(__DIR__) . '/views/auth/login.php';
require dirname(__DIR__) . '/views/layouts/footer.php';
