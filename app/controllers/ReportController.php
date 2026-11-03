<?php
declare(strict_types=1);
require_login();
require_role('manager');
$title = 'Occupancy and Financial Reports';
$moduleDescription = 'occupancy summaries, rent collection, arrears, and maintenance reports';
require dirname(__DIR__) . '/views/layouts/header.php';
require dirname(__DIR__) . '/views/module_placeholder.php';
require dirname(__DIR__) . '/views/layouts/footer.php';
