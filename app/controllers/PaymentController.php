<?php
declare(strict_types=1);
require_login();
require_role('manager');

$title = 'Rent Payments and Arrears';
$pdo = db();
$userId = (int) current_user()['userId'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'record') {
        $tenancyId = filter_input(INPUT_POST, 'tenancyId', FILTER_VALIDATE_INT) ?: 0;
        $amountInput = trim((string)($_POST['amount'] ?? ''));
        $paymentDate = trim((string)($_POST['paymentDate'] ?? ''));
        $periodCovered = trim((string)($_POST['periodCovered'] ?? ''));
        $method = (string)($_POST['method'] ?? '');
        $receiptNumber = trim((string)($_POST['receiptNumber'] ?? ''));
        $date = DateTime::createFromFormat('!Y-m-d', $paymentDate);
        $validDate = $date && $date->format('Y-m-d') === $paymentDate;

        if ($tenancyId < 1 || !is_numeric($amountInput) || (float)$amountInput <= 0 || (float)$amountInput > 99999999.99) {
            $error = 'Select an active tenancy and enter a payment amount greater than zero.';
        } elseif (!$validDate || $paymentDate > date('Y-m-d')) {
            $error = 'Enter a valid payment date that is not in the future.';
        } elseif (!in_array($method, ['cash', 'transfer', 'other'], true)) {
            $error = 'Choose a valid payment method.';
        } elseif (strlen($periodCovered) > 50 || strlen($receiptNumber) > 50) {
            $error = 'The period covered and receipt/reference fields must not exceed 50 characters.';
        } else {
            $owner = $pdo->prepare("SELECT tn.tenancyId, tn.status, t.fullName, u.unitNumber, p.name AS propertyName FROM tenancies tn JOIN tenants t ON t.tenantId = tn.tenantId JOIN units u ON u.unitId = tn.unitId JOIN properties p ON p.propertyId = u.propertyId WHERE tn.tenancyId = ? AND p.managerId = ? AND tn.status = 'active'");
            $owner->execute([$tenancyId, $userId]);
            $tenancy = $owner->fetch();
            if (!$tenancy) {
                $error = 'The selected tenancy is not active or does not belong to your managed properties.';
            } else {
                try {
                    $insert = $pdo->prepare('INSERT INTO payments (tenancyId, amount, paymentDate, periodCovered, method, receiptNumber, createdBy) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $insert->execute([
                        $tenancyId,
                        number_format((float)$amountInput, 2, '.', ''),
                        $paymentDate,
                        $periodCovered !== '' ? $periodCovered : null,
                        $method,
                        $receiptNumber !== '' ? $receiptNumber : null,
                        $userId,
                    ]);
                    $paymentId = (int)$pdo->lastInsertId();
                    audit('create', 'payments', (string)$paymentId, ['tenancyId' => $tenancyId, 'amount' => number_format((float)$amountInput, 2, '.', ''), 'method' => $method]);
                    flash('success', 'Payment recorded successfully. The payment history and balance summary have been updated.');
                    redirect(base_url('?page=payments'));
                } catch (PDOException $e) {
                    error_log('Payment record failed: ' . $e->getMessage());
                    $error = $e->getCode() === '23000'
                        ? 'That receipt/reference number is already in use. Enter a different reference or leave it blank.'
                        : 'The payment could not be recorded. Check the database connection and try again.';
                }
            }
        }
    }
}

// Only show active tenancies belonging to this manager.
$tenancyStmt = $pdo->prepare("SELECT tn.tenancyId, tn.startDate, tn.endDate, t.fullName, u.unitNumber, u.annualRent, p.name AS propertyName, COALESCE((SELECT SUM(pay.amount) FROM payments pay WHERE pay.tenancyId = tn.tenancyId), 0) AS totalPaid FROM tenancies tn JOIN tenants t ON t.tenantId = tn.tenantId JOIN units u ON u.unitId = tn.unitId JOIN properties p ON p.propertyId = u.propertyId WHERE p.managerId = ? AND tn.status = 'active' ORDER BY t.fullName");
$tenancyStmt->execute([$userId]);
$activeTenancies = $tenancyStmt->fetchAll();

// Estimated rent due is based on whole annual rental periods that have started during the tenancy.
$today = new DateTimeImmutable('today');
foreach ($activeTenancies as &$item) {
    $start = new DateTimeImmutable($item['startDate']);
    $end = new DateTimeImmutable($item['endDate']);
    $through = $today < $end ? $today : $end;
    $yearsDue = 0;
    if ($start <= $through) {
        // Count each annual rent anniversary reached, including the start date's first year.
        $yearsDue = (int)$start->diff($through)->y + 1;
    }
    $item['rentDue'] = $yearsDue * (float)$item['annualRent'];
    $item['balance'] = max(0, $item['rentDue'] - (float)$item['totalPaid']);
    $item['credit'] = max(0, (float)$item['totalPaid'] - $item['rentDue']);
}
unset($item);

$summary = $pdo->prepare('SELECT COUNT(*) AS paymentCount, COALESCE(SUM(pay.amount), 0) AS totalReceived FROM payments pay JOIN tenancies tn ON tn.tenancyId = pay.tenancyId JOIN units u ON u.unitId = tn.unitId JOIN properties p ON p.propertyId = u.propertyId WHERE p.managerId = ?');
$summary->execute([$userId]);
$paymentSummary = $summary->fetch();

$historyStmt = $pdo->prepare('SELECT pay.paymentId, pay.amount, pay.paymentDate, pay.periodCovered, pay.method, pay.receiptNumber, t.fullName, u.unitNumber, p.name AS propertyName FROM payments pay JOIN tenancies tn ON tn.tenancyId = pay.tenancyId JOIN tenants t ON t.tenantId = tn.tenantId JOIN units u ON u.unitId = tn.unitId JOIN properties p ON p.propertyId = u.propertyId WHERE p.managerId = ? ORDER BY pay.paymentDate DESC, pay.paymentId DESC LIMIT 200');
$historyStmt->execute([$userId]);
$payments = $historyStmt->fetchAll();

require dirname(__DIR__) . '/views/layouts/header.php';
require dirname(__DIR__) . '/views/payments/index.php';
require dirname(__DIR__) . '/views/layouts/footer.php';
