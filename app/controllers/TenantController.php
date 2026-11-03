<?php
declare(strict_types=1);
require_login();
require_role('manager');

$title = 'Tenant and Tenancy Management';
$pdo = db();
$userId = (int) current_user()['userId'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $tenantId = filter_input(INPUT_POST, 'tenantId', FILTER_VALIDATE_INT) ?: 0;

    if ($action === 'save') {
        $fullName = trim((string)($_POST['fullName'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $unitId = filter_input(INPUT_POST, 'unitId', FILTER_VALIDATE_INT) ?: 0;
        $leaseStart = trim((string)($_POST['leaseStart'] ?? ''));
        $leaseEnd = trim((string)($_POST['leaseEnd'] ?? ''));
        $cautionInput = trim((string)($_POST['cautionFee'] ?? '0'));

        $validDates = static function (string $start, string $end): bool {
            $a = DateTime::createFromFormat('!Y-m-d', $start);
            $b = DateTime::createFromFormat('!Y-m-d', $end);
            return $a && $b && $a->format('Y-m-d') === $start && $b->format('Y-m-d') === $end && $end >= $start;
        };

        if ($fullName === '' || strlen($fullName) > 100 || $phone === '' || strlen($phone) > 20) {
            $error = 'Enter a tenant name (maximum 100 characters) and phone number (maximum 20 characters).';
        } elseif ($email !== '' && (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $error = 'Enter a valid email address of no more than 100 characters, or leave it blank.';
        } elseif ($unitId < 1 || !$validDates($leaseStart, $leaseEnd) || !is_numeric($cautionInput) || (float)$cautionInput < 0) {
            $error = 'Choose a vacant unit, enter valid lease dates, and enter a non-negative caution fee.';
        } else {
            // A manager may only assign units belonging to properties under their account.
            $unitStmt = $pdo->prepare("SELECT u.unitId, u.status FROM units u JOIN properties p ON p.propertyId = u.propertyId WHERE u.unitId = ? AND p.managerId = ?");
            $unitStmt->execute([$unitId, $userId]);
            $selectedUnit = $unitStmt->fetch();
            if (!$selectedUnit) {
                $error = 'The selected unit is not available under your account.';
            } else {
                try {
                    $pdo->beginTransaction();
                    if ($tenantId > 0) {
                        $owner = $pdo->prepare('SELECT tenantId FROM tenants WHERE tenantId = ?');
                        $owner->execute([$tenantId]);
                        if (!$owner->fetchColumn()) {
                            throw new RuntimeException('The selected tenant could not be found.');
                        }
                        $active = $pdo->prepare("SELECT tenancyId, unitId FROM tenancies WHERE tenantId = ? AND status = 'active' FOR UPDATE");
                        $active->execute([$tenantId]);
                        $activeTenancy = $active->fetch();
                        if (!$activeTenancy) {
                            throw new RuntimeException('This tenant has no active tenancy. Register a new tenant instead of editing an ended tenancy.');
                        }
                        if ((int)$activeTenancy['unitId'] !== $unitId) {
                            if ($selectedUnit['status'] !== 'vacant') {
                                throw new RuntimeException('The selected unit is already occupied. Choose a vacant unit.');
                            }
                            $oldUnit = $pdo->prepare("UPDATE units SET status = 'vacant' WHERE unitId = ?");
                            $oldUnit->execute([(int)$activeTenancy['unitId']]);
                        }
                        $updateTenant = $pdo->prepare('UPDATE tenants SET fullName = ?, phone = ?, email = ?, unitId = ?, leaseStart = ?, leaseEnd = ?, cautionFee = ? WHERE tenantId = ?');
                        $updateTenant->execute([$fullName, $phone, $email !== '' ? $email : null, $unitId, $leaseStart, $leaseEnd, number_format((float)$cautionInput, 2, '.', ''), $tenantId]);
                        $updateTenancy = $pdo->prepare("UPDATE tenancies SET unitId = ?, startDate = ?, endDate = ?, cautionFee = ? WHERE tenancyId = ? AND status = 'active'");
                        $updateTenancy->execute([$unitId, $leaseStart, $leaseEnd, number_format((float)$cautionInput, 2, '.', ''), (int)$activeTenancy['tenancyId']]);
                        audit('update', 'tenants', (string)$tenantId, ['fullName' => $fullName]);
                        flash('success', 'Tenant and active tenancy details updated.');
                    } else {
                        if ($selectedUnit['status'] !== 'vacant') {
                            throw new RuntimeException('The selected unit is already occupied. Choose a vacant unit.');
                        }
                        $insertTenant = $pdo->prepare('INSERT INTO tenants (fullName, phone, email, unitId, leaseStart, leaseEnd, cautionFee) VALUES (?, ?, ?, ?, ?, ?, ?)');
                        $insertTenant->execute([$fullName, $phone, $email !== '' ? $email : null, $unitId, $leaseStart, $leaseEnd, number_format((float)$cautionInput, 2, '.', '')]);
                        $newTenantId = (int)$pdo->lastInsertId();
                        $insertTenancy = $pdo->prepare("INSERT INTO tenancies (tenantId, unitId, startDate, endDate, cautionFee, status) VALUES (?, ?, ?, ?, ?, 'active')");
                        $insertTenancy->execute([$newTenantId, $unitId, $leaseStart, $leaseEnd, number_format((float)$cautionInput, 2, '.', '')]);
                        $markUnit = $pdo->prepare("UPDATE units SET status = 'occupied' WHERE unitId = ?");
                        $markUnit->execute([$unitId]);
                        audit('create', 'tenants', (string)$newTenantId, ['fullName' => $fullName, 'unitId' => $unitId]);
                        flash('success', 'Tenant registered and tenancy created successfully. The unit is now marked occupied.');
                    }
                    $pdo->commit();
                    redirect(base_url('?page=tenants'));
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $error = $e instanceof RuntimeException ? $e->getMessage() : 'The tenant could not be saved. Check that the unit is vacant and the details are valid.';
                    if (!$e instanceof RuntimeException) error_log('Tenant save failed: ' . $e->getMessage());
                }
            }
        }
    } elseif ($action === 'end' && $tenantId > 0) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT t.tenantId, tn.tenancyId, tn.unitId FROM tenants t JOIN tenancies tn ON tn.tenantId = t.tenantId JOIN units u ON u.unitId = tn.unitId JOIN properties p ON p.propertyId = u.propertyId WHERE t.tenantId = ? AND p.managerId = ? AND tn.status = 'active' FOR UPDATE");
            $stmt->execute([$tenantId, $userId]);
            $record = $stmt->fetch();
            if (!$record) {
                throw new RuntimeException('No active tenancy was found for this tenant.');
            }
            $end = $pdo->prepare("UPDATE tenancies SET status = 'ended', endDate = LEAST(endDate, CURDATE()) WHERE tenancyId = ?");
            $end->execute([(int)$record['tenancyId']]);
            $clearTenant = $pdo->prepare('UPDATE tenants SET unitId = NULL WHERE tenantId = ?');
            $clearTenant->execute([$tenantId]);
            $vacate = $pdo->prepare("UPDATE units SET status = 'vacant' WHERE unitId = ?");
            $vacate->execute([(int)$record['unitId']]);
            audit('end', 'tenancies', (string)$record['tenancyId'], ['tenantId' => $tenantId]);
            $pdo->commit();
            flash('success', 'Tenancy ended and the unit marked vacant. Historical tenant and tenancy records were retained.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('error', $e instanceof RuntimeException ? $e->getMessage() : 'The tenancy could not be ended.');
        }
        redirect(base_url('?page=tenants'));
    }
}

$edit = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $stmt = $pdo->prepare("SELECT t.tenantId, t.fullName, t.phone, t.email, tn.unitId, tn.startDate AS leaseStart, tn.endDate AS leaseEnd, tn.cautionFee FROM tenants t JOIN tenancies tn ON tn.tenantId = t.tenantId JOIN units u ON u.unitId = tn.unitId JOIN properties p ON p.propertyId = u.propertyId WHERE t.tenantId = ? AND p.managerId = ? AND tn.status = 'active'");
    $stmt->execute([$editId, $userId]);
    $edit = $stmt->fetch() ?: null;
}

$unitStmt = $pdo->prepare("SELECT u.unitId, u.unitNumber, u.annualRent, p.name AS propertyName FROM units u JOIN properties p ON p.propertyId = u.propertyId WHERE p.managerId = ? AND (u.status = 'vacant' OR EXISTS (SELECT 1 FROM tenancies tn JOIN tenants t ON t.tenantId = tn.tenantId WHERE tn.unitId = u.unitId AND tn.status = 'active' AND t.tenantId = ?)) ORDER BY p.name, u.unitNumber");
$unitStmt->execute([$userId, (int)($edit['tenantId'] ?? 0)]);
$availableUnits = $unitStmt->fetchAll();

$listStmt = $pdo->prepare("SELECT t.tenantId, t.fullName, t.phone, t.email, t.leaseStart, t.leaseEnd, t.cautionFee, tn.status AS tenancyStatus, u.unitNumber, p.name AS propertyName FROM tenants t LEFT JOIN tenancies tn ON tn.tenantId = t.tenantId AND tn.status = 'active' LEFT JOIN units u ON u.unitId = tn.unitId LEFT JOIN properties p ON p.propertyId = u.propertyId WHERE EXISTS (SELECT 1 FROM tenancies history JOIN units hu ON hu.unitId = history.unitId JOIN properties hp ON hp.propertyId = hu.propertyId WHERE history.tenantId = t.tenantId AND hp.managerId = ?) ORDER BY t.createdAt DESC");
$listStmt->execute([$userId]);
$tenants = $listStmt->fetchAll();

require dirname(__DIR__) . '/views/layouts/header.php';
require dirname(__DIR__) . '/views/tenants/index.php';
require dirname(__DIR__) . '/views/layouts/footer.php';
