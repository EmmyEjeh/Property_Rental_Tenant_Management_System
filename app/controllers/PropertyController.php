<?php
declare(strict_types=1);
require_login();
require_role('manager');

$title = 'Property Management';
$pdo = db();
$userId = (int) current_user()['userId'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $name = trim((string)($_POST['name'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    $id = filter_input(INPUT_POST, 'propertyId', FILTER_VALIDATE_INT) ?: 0;

    if ($action === 'save') {
        if ($name === '' || $address === '') {
            $error = 'Enter both the property name and address.';
        } elseif (strlen($name) > 100 || strlen($address) > 255) {
            $error = 'The property name or address is too long.';
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE properties SET name = ?, address = ? WHERE propertyId = ? AND managerId = ?');
                $stmt->execute([$name, $address, $id, $userId]);
                audit('update', 'properties', (string)$id, ['name' => $name]);
                flash('success', 'Property details updated.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO properties (name, address, managerId) VALUES (?, ?, ?)');
                $stmt->execute([$name, $address, $userId]);
                $newId = (string)$pdo->lastInsertId();
                audit('create', 'properties', $newId, ['name' => $name]);
                flash('success', 'Property registered successfully.');
            }
            redirect(base_url('?page=properties'));
        }
    } elseif ($action === 'delete' && $id > 0) {
        try {
            $stmt = $pdo->prepare('DELETE FROM properties WHERE propertyId = ? AND managerId = ?');
            $stmt->execute([$id, $userId]);
            if ($stmt->rowCount()) {
                audit('delete', 'properties', (string)$id);
                flash('success', 'Property deleted.');
            } else {
                flash('error', 'Property not found or you do not have permission to delete it.');
            }
        } catch (PDOException $e) {
            flash('error', 'This property cannot be deleted because it still has linked units or records. Remove or reassign those records first.');
        }
        redirect(base_url('?page=properties'));
    }
}

$edit = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $stmt = $pdo->prepare('SELECT propertyId, name, address FROM properties WHERE propertyId = ? AND managerId = ?');
    $stmt->execute([$editId, $userId]);
    $edit = $stmt->fetch() ?: null;
}
$stmt = $pdo->prepare('SELECT p.propertyId, p.name, p.address, p.createdAt, COUNT(u.unitId) AS unitCount FROM properties p LEFT JOIN units u ON u.propertyId = p.propertyId WHERE p.managerId = ? GROUP BY p.propertyId, p.name, p.address, p.createdAt ORDER BY p.createdAt DESC');
$stmt->execute([$userId]);
$properties = $stmt->fetchAll();

require dirname(__DIR__) . '/views/layouts/header.php';
require dirname(__DIR__) . '/views/properties/index.php';
require dirname(__DIR__) . '/views/layouts/footer.php';
