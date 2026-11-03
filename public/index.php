<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/core/bootstrap.php';

$page = $_GET['page'] ?? 'dashboard';
$routes = [
    'login' => dirname(__DIR__) . '/app/controllers/AuthController.php',
    'logout' => dirname(__DIR__) . '/app/controllers/AuthController.php',
    'dashboard' => dirname(__DIR__) . '/app/controllers/DashboardController.php',
    'properties' => dirname(__DIR__) . '/app/controllers/PropertyController.php',
    'tenants' => dirname(__DIR__) . '/app/controllers/TenantController.php',
    'units' => dirname(__DIR__) . '/app/controllers/UnitController.php',
    'payments' => dirname(__DIR__) . '/app/controllers/PaymentController.php',
    'maintenance' => dirname(__DIR__) . '/app/controllers/MaintenanceController.php',
    'disputes' => dirname(__DIR__) . '/app/controllers/DisputeController.php',
    'dispute_image' => dirname(__DIR__) . '/app/controllers/DisputeImageController.php',
    'reports' => dirname(__DIR__) . '/app/controllers/ReportController.php',
];
if (!isset($routes[$page])) { http_response_code(404); $page = 'not_found'; }
if ($page === 'not_found') { $title = 'Page not found'; require dirname(__DIR__) . '/app/views/layouts/header.php'; echo '<main class="container"><h1>Page not found</h1><p>The requested page does not exist.</p></main>'; require dirname(__DIR__) . '/app/views/layouts/footer.php'; exit; }
require $routes[$page];
