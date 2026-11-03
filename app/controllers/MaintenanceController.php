<?php
declare(strict_types=1);
require_login();
require_role('manager');

$title = 'Maintenance Requests';
$pdo = db();
$userId = (int) current_user()['userId'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create') {
        $tenancyId = filter_input(INPUT_POST, 'tenancyId', FILTER_VALIDATE_INT) ?: 0;
        $description = trim((string)($_POST['description'] ?? ''));
        if ($tenancyId < 1 || $description === '' || mb_strlen($description) > 5000) {
            $error = 'Select an active tenancy and enter a description (maximum 5,000 characters).';
        } else {
            $check = $pdo->prepare("SELECT tn.tenancyId FROM tenancies tn JOIN units u ON u.unitId = tn.unitId JOIN properties p ON p.propertyId = u.propertyId WHERE tn.tenancyId = ? AND p.managerId = ? AND tn.status = 'active'");
            $check->execute([$tenancyId, $userId]);
            if (!$check->fetchColumn()) {
                $error = 'The selected tenancy is not active or does not belong to your managed properties.';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO maintenance_requests (tenancyId, description, status) VALUES (?, ?, 'pending')");
                    $stmt->execute([$tenancyId, $description]);
                    $requestId = (int)$pdo->lastInsertId();
                    audit('create', 'maintenance_requests', (string)$requestId, ['tenancyId' => $tenancyId]);
                    flash('success', 'Maintenance request registered successfully.');
                    redirect(base_url('?page=maintenance'));
                } catch (Throwable $e) {
                    error_log('Maintenance request create failed: ' . $e->getMessage());
                    $error = 'The maintenance request could not be saved. Check the database and try again.';
                }
            }
        }
    } elseif ($action === 'status') {
        $requestId = filter_input(INPUT_POST, 'requestId', FILTER_VALIDATE_INT) ?: 0;
        $status = (string)($_POST['status'] ?? '');
        if ($requestId < 1 || !in_array($status, ['pending', 'in_progress', 'resolved'], true)) {
            $error = 'Choose a valid maintenance request and status.';
        } else {
            $stmt = $pdo->prepare("UPDATE maintenance_requests mr JOIN tenancies tn ON tn.tenancyId = mr.tenancyId JOIN units u ON u.unitId = tn.unitId JOIN properties p ON p.propertyId = u.propertyId SET mr.status = ? WHERE mr.requestId = ? AND p.managerId = ?");
            $stmt->execute([$status, $requestId, $userId]);
            if ($stmt->rowCount() > 0) {
                audit('update_status', 'maintenance_requests', (string)$requestId, ['status' => $status]);
                flash('success', 'Maintenance request status updated.');
                redirect(base_url('?page=maintenance'));
            }
            $error = 'The request was not found under your managed properties, or it already has that status.';
        }
    }
}

$tenancyStmt = $pdo->prepare("SELECT tn.tenancyId, t.fullName, p.name AS propertyName, u.unitNumber FROM tenancies tn JOIN tenants t ON t.tenantId = tn.tenantId JOIN units u ON u.unitId = tn.unitId JOIN properties p ON p.propertyId = u.propertyId WHERE p.managerId = ? AND tn.status = 'active' ORDER BY t.fullName");
$tenancyStmt->execute([$userId]);
$activeTenancies = $tenancyStmt->fetchAll();

$listStmt = $pdo->prepare("SELECT mr.requestId, mr.description, mr.dateSubmitted, mr.status, t.fullName, p.name AS propertyName, u.unitNumber FROM maintenance_requests mr JOIN tenancies tn ON tn.tenancyId = mr.tenancyId JOIN tenants t ON t.tenantId = tn.tenantId JOIN units u ON u.unitId = tn.unitId JOIN properties p ON p.propertyId = u.propertyId WHERE p.managerId = ? ORDER BY mr.dateSubmitted DESC, mr.requestId DESC");
$listStmt->execute([$userId]);
$requests = $listStmt->fetchAll();

require dirname(__DIR__) . '/views/layouts/header.php';
require dirname(__DIR__) . '/views/maintenance/index.php';
require dirname(__DIR__) . '/views/layouts/footer.php';
