<?php
declare(strict_types=1);
require_login();
require_role('manager');

$title = 'Unit Management';
$pdo = db();
$userId = (int) current_user()['userId'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $unitId = filter_input(INPUT_POST, 'unitId', FILTER_VALIDATE_INT) ?: 0;
    $propertyId = filter_input(INPUT_POST, 'propertyId', FILTER_VALIDATE_INT) ?: 0;
    $unitNumber = trim((string)($_POST['unitNumber'] ?? ''));
    $status = (string)($_POST['status'] ?? 'vacant');
    $rentInput = trim((string)($_POST['annualRent'] ?? ''));

    if ($action === 'save') {
        if ($propertyId < 1 || $unitNumber === '' || !is_numeric($rentInput) || (float)$rentInput < 0 || !in_array($status, ['vacant','occupied'], true)) {
            $error = 'Choose a property, enter a unit number, a valid non-negative rent, and a valid occupancy status.';
        } elseif (strlen($unitNumber) > 20) {
            $error = 'The unit number must not exceed 20 characters.';
        } else {
            $check = $pdo->prepare('SELECT propertyId FROM properties WHERE propertyId = ? AND managerId = ?');
            $check->execute([$propertyId, $userId]);
            if (!$check->fetchColumn()) {
                $error = 'The selected property is not available under your account.';
            } else {
                try {
                    if ($unitId > 0) {
                        $stmt = $pdo->prepare('UPDATE units SET propertyId = ?, unitNumber = ?, status = ?, annualRent = ? WHERE unitId = ? AND propertyId IN (SELECT propertyId FROM properties WHERE managerId = ?)');
                        $stmt->execute([$propertyId, $unitNumber, $status, number_format((float)$rentInput, 2, '.', ''), $unitId, $userId]);
                        audit('update', 'units', (string)$unitId, ['unitNumber' => $unitNumber]);
                        flash('success', 'Unit details updated.');
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO units (propertyId, unitNumber, status, annualRent) VALUES (?, ?, ?, ?)');
                        $stmt->execute([$propertyId, $unitNumber, $status, number_format((float)$rentInput, 2, '.', '')]);
                        audit('create', 'units', (string)$pdo->lastInsertId(), ['unitNumber' => $unitNumber]);
                        flash('success', 'Unit registered successfully.');
                    }
                    redirect(base_url('?page=units'));
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') $error = 'That unit number already exists for the selected property.';
                    else throw $e;
                }
            }
        }
    } elseif ($action === 'delete' && $unitId > 0) {
        try {
            $stmt = $pdo->prepare('DELETE FROM units WHERE unitId = ? AND propertyId IN (SELECT propertyId FROM properties WHERE managerId = ?)');
            $stmt->execute([$unitId, $userId]);
            if ($stmt->rowCount()) {
                audit('delete', 'units', (string)$unitId);
                flash('success', 'Unit deleted.');
            } else {
                flash('error', 'Unit not found or unavailable.');
            }
        } catch (PDOException $e) {
            flash('error', 'This unit cannot be deleted because it is linked to a tenant, tenancy, payment or other record.');
        }
        redirect(base_url('?page=units'));
    }
}

$propertiesStmt = $pdo->prepare('SELECT propertyId, name FROM properties WHERE managerId = ? ORDER BY name');
$propertiesStmt->execute([$userId]);
$properties = $propertiesStmt->fetchAll();

$edit = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $stmt = $pdo->prepare('SELECT u.unitId, u.propertyId, u.unitNumber, u.status, u.annualRent FROM units u JOIN properties p ON p.propertyId = u.propertyId WHERE u.unitId = ? AND p.managerId = ?');
    $stmt->execute([$editId, $userId]);
    $edit = $stmt->fetch() ?: null;
}
$stmt = $pdo->prepare('SELECT u.unitId, u.propertyId, u.unitNumber, u.status, u.annualRent, p.name AS propertyName FROM units u JOIN properties p ON p.propertyId = u.propertyId WHERE p.managerId = ? ORDER BY p.name, u.unitNumber');
$stmt->execute([$userId]);
$units = $stmt->fetchAll();

require dirname(__DIR__) . '/views/layouts/header.php';
require dirname(__DIR__) . '/views/units/index.php';
require dirname(__DIR__) . '/views/layouts/footer.php';
