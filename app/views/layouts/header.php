<?php $title = $title ?? 'Property Rental Management'; ?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($title) ?> | Property Rental</title><link rel="stylesheet" href="<?= e(base_url('assets/css/app.css')) ?>"></head>
<body><header class="site-header"><a class="brand" href="<?= e(base_url('?page=dashboard')) ?>">Property Rental Manager</a><nav>
<?php if (current_user()): ?>
<a href="<?= e(base_url('?page=dashboard')) ?>">Dashboard</a>
<?php if (current_user()['role'] === 'manager'): ?><a href="<?= e(base_url('?page=properties')) ?>">Properties</a><a href="<?= e(base_url('?page=tenants')) ?>">Tenants</a><a href="<?= e(base_url('?page=units')) ?>">Units</a><a href="<?= e(base_url('?page=payments')) ?>">Payments</a><a href="<?= e(base_url('?page=maintenance')) ?>">Maintenance</a><a href="<?= e(base_url('?page=disputes')) ?>">Deposit disputes</a><a href="<?= e(base_url('?page=reports')) ?>">Reports</a><?php else: ?><a href="<?= e(base_url('?page=maintenance')) ?>">Maintenance</a><?php endif; ?>
<form method="post" action="<?= e(base_url('?page=logout')) ?>" class="inline-form"><?= csrf_field() ?><button class="link-button" type="submit">Sign out (<?= e(current_user()['fullName']) ?>)</button></form>
<?php endif; ?></nav></header>
<?php if ($message = flash('success')): ?><div class="notice success"><?= e($message) ?></div><?php endif; ?>
<?php if ($message = flash('error')): ?><div class="notice error"><?= e($message) ?></div><?php endif; ?>
