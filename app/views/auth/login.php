<main class="container narrow"><section class="panel"><h1>Sign in</h1><p>Use the account created by the system manager.</p>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= e(base_url('?page=login')) ?>" autocomplete="on"><?= csrf_field() ?>
<label for="username">Username</label><input id="username" name="username" required maxlength="50" autocomplete="username">
<label for="password">Password</label><input id="password" type="password" name="password" required autocomplete="current-password">
<button type="submit">Sign in</button></form></section></main>
