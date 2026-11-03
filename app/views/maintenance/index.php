<main class="container">
<div class="page-heading"><div><h1>Maintenance Requests</h1><p>Register repair issues for active tenancies and track their progress.</p></div><a class="button-link" href="<?= e(base_url('?page=tenants')) ?>">Manage tenants</a></div>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<section class="panel"><h2>Register a maintenance request</h2>
<?php if (!$activeTenancies): ?><p>There are no active tenancies available. Register a tenant and create an active tenancy before adding a request. <a href="<?= e(base_url('?page=tenants')) ?>">Manage tenants</a>.</p><?php else: ?>
<form method="post" action="<?= e(base_url('?page=maintenance')) ?>">
<?= csrf_field() ?><input type="hidden" name="action" value="create">
<label for="tenancyId">Tenant, property and unit</label><select id="tenancyId" name="tenancyId" required><option value="">Select an active tenancy</option><?php foreach ($activeTenancies as $item): ?><option value="<?= (int)$item['tenancyId'] ?>"><?= e($item['fullName'] . ' — ' . $item['propertyName'] . ' / ' . $item['unitNumber']) ?></option><?php endforeach; ?></select>
<label for="description">Describe the maintenance problem</label><textarea id="description" name="description" rows="5" maxlength="5000" required placeholder="Example: The kitchen sink tap is leaking and needs repair."></textarea>
<p class="form-hint">The request will be saved with Pending status. You can update its status as work progresses.</p><button type="submit">Register maintenance request</button>
</form><?php endif; ?></section>
<section class="panel"><h2>Maintenance request history</h2>
<?php if (!$requests): ?><p>No maintenance requests have been registered yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Date reported</th><th>Tenant</th><th>Property / Unit</th><th>Description</th><th>Status</th><th>Update status</th></tr></thead><tbody>
<?php foreach ($requests as $item): ?><tr><td><?= e($item['dateSubmitted']) ?></td><td><?= e($item['fullName']) ?></td><td><?= e($item['propertyName'] . ' / ' . $item['unitNumber']) ?></td><td><?= nl2br(e($item['description'])) ?></td><td><span class="status <?= $item['status'] === 'resolved' ? 'vacant' : ($item['status'] === 'in_progress' ? 'occupied' : '') ?>"><?= e(ucwords(str_replace('_', ' ', $item['status']))) ?></span></td><td><form method="post" action="<?= e(base_url('?page=maintenance')) ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="requestId" value="<?= (int)$item['requestId'] ?>"><select name="status" aria-label="New status for request <?= (int)$item['requestId'] ?>"><option value="pending" <?= $item['status'] === 'pending' ? 'selected' : '' ?>>Pending</option><option value="in_progress" <?= $item['status'] === 'in_progress' ? 'selected' : '' ?>>In progress</option><option value="resolved" <?= $item['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option></select><button type="submit">Save</button></form></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
</main>
