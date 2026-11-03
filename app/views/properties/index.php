<main class="container">
<div class="page-heading"><div><h1>Property Management</h1><p>Register and manage the rental properties under your account.</p></div><a class="button-link" href="<?= e(base_url('?page=units')) ?>">Manage units</a></div>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<section class="panel">
<h2><?= $edit ? 'Edit property' : 'Register a property' ?></h2>
<form method="post" action="<?= e(base_url('?page=properties' . ($edit ? '&edit=' . (int)$edit['propertyId'] : ''))) ?>">
<?= csrf_field() ?><input type="hidden" name="action" value="save">
<?php if ($edit): ?><input type="hidden" name="propertyId" value="<?= (int)$edit['propertyId'] ?>"><?php endif; ?>
<label for="name">Property name</label><input id="name" name="name" maxlength="100" required value="<?= e($edit['name'] ?? '') ?>" placeholder="e.g. Sunrise Apartments">
<label for="address">Property address</label><textarea id="address" name="address" maxlength="255" rows="2" required placeholder="Street, area, city"><?= e($edit['address'] ?? '') ?></textarea>
<button type="submit"><?= $edit ? 'Save changes' : 'Register property' ?></button>
<?php if ($edit): ?> <a class="button-link" href="<?= e(base_url('?page=properties')) ?>">Cancel</a><?php endif; ?>
</form></section>
<section class="panel"><h2>Registered properties</h2>
<?php if (!$properties): ?><p>No properties have been registered yet. Use the form above to add your first property.</p><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Property</th><th>Address</th><th>Units</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($properties as $property): ?><tr><td><?= e($property['name']) ?></td><td><?= e($property['address']) ?></td><td><?= (int)$property['unitCount'] ?></td><td class="actions"><a href="<?= e(base_url('?page=properties&edit=' . (int)$property['propertyId'])) ?>">Edit</a><form method="post" action="<?= e(base_url('?page=properties')) ?>" onsubmit="return confirm('Delete this property? Properties with linked units cannot be deleted.');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="propertyId" value="<?= (int)$property['propertyId'] ?>"><button class="button-danger" type="submit">Delete</button></form></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section></main>
