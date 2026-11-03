<main class="container">
<div class="page-heading"><div><h1>Unit Management</h1><p>Add rental units to a registered property and manage rent and occupancy.</p></div><a class="button-link" href="<?= e(base_url('?page=properties')) ?>">Manage properties</a></div>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<section class="panel"><h2><?= $edit ? 'Edit unit' : 'Register a unit' ?></h2>
<?php if (!$properties): ?><p>You must register a property before adding units. <a href="<?= e(base_url('?page=properties')) ?>">Register a property</a>.</p><?php else: ?>
<form method="post" action="<?= e(base_url('?page=units' . ($edit ? '&edit=' . (int)$edit['unitId'] : ''))) ?>">
<?= csrf_field() ?><input type="hidden" name="action" value="save">
<?php if ($edit): ?><input type="hidden" name="unitId" value="<?= (int)$edit['unitId'] ?>"><?php endif; ?>
<label for="propertyId">Property</label><select id="propertyId" name="propertyId" required><option value="">Select a property</option><?php foreach ($properties as $property): ?><option value="<?= (int)$property['propertyId'] ?>" <?= (int)($edit['propertyId'] ?? 0) === (int)$property['propertyId'] ? 'selected' : '' ?>><?= e($property['name']) ?></option><?php endforeach; ?></select>
<label for="unitNumber">Unit number</label><input id="unitNumber" name="unitNumber" maxlength="20" required value="<?= e($edit['unitNumber'] ?? '') ?>" placeholder="e.g. Flat A1">
<label for="annualRent">Yearly rent</label><input id="annualRent" name="annualRent" type="number" min="0" step="0.01" required value="<?= e(isset($edit['annualRent']) ? (string)$edit['annualRent'] : '') ?>" placeholder="e.g. 1800000">
<label for="status">Occupancy status</label><select id="status" name="status"><option value="vacant" <?= ($edit['status'] ?? 'vacant') === 'vacant' ? 'selected' : '' ?>>Vacant</option><option value="occupied" <?= ($edit['status'] ?? '') === 'occupied' ? 'selected' : '' ?>>Occupied</option></select>
<button type="submit"><?= $edit ? 'Save changes' : 'Register unit' ?></button><?php if ($edit): ?> <a class="button-link" href="<?= e(base_url('?page=units')) ?>">Cancel</a><?php endif; ?>
</form><?php endif; ?></section>
<section class="panel"><h2>Registered units</h2><?php if (!$units): ?><p>No units have been registered yet.</p><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Property</th><th>Unit</th><th>Yearly rent</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($units as $unit): ?><tr><td><?= e($unit['propertyName']) ?></td><td><?= e($unit['unitNumber']) ?></td><td><?= number_format((float)$unit['annualRent'], 2) ?></td><td><span class="status <?= e($unit['status']) ?>"><?= e(ucfirst($unit['status'])) ?></span></td><td class="actions"><a href="<?= e(base_url('?page=units&edit=' . (int)$unit['unitId'])) ?>">Edit</a><form method="post" action="<?= e(base_url('?page=units')) ?>" onsubmit="return confirm('Delete this unit? Units linked to tenants or other records cannot be deleted.');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="unitId" value="<?= (int)$unit['unitId'] ?>"><button class="button-danger" type="submit">Delete</button></form></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section></main>
