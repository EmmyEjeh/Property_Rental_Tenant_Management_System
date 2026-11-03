<?php
declare(strict_types=1);
require_login();
$title = 'Dashboard';
$stats = ['units' => 0, 'tenants' => 0, 'payments' => 0, 'maintenance' => 0, 'disputes' => 0];
try {
    if (current_user()['role'] === 'manager') {
        $stats['units'] = (int)db()->query('SELECT COUNT(*) FROM units')->fetchColumn();
        $stats['tenants'] = (int)db()->query('SELECT COUNT(*) FROM tenants')->fetchColumn();
        $stats['payments'] = (float)db()->query('SELECT COALESCE(SUM(amount),0) FROM payments')->fetchColumn();
        $stats['maintenance'] = (int)db()->query("SELECT COUNT(*) FROM maintenance_requests WHERE status <> 'resolved'")->fetchColumn();
        $stats['disputes'] = (int)db()->query("SELECT COUNT(*) FROM dispute_cases WHERE status <> 'resolved'")->fetchColumn();
    }
} catch (Throwable $e) { error_log('Dashboard query: ' . $e->getMessage()); flash('error', 'Database tables are not ready yet. Import database/schema.sql first.'); }
require dirname(__DIR__) . '/views/layouts/header.php';
require dirname(__DIR__) . '/views/dashboard/index.php';
require dirname(__DIR__) . '/views/layouts/footer.php';
