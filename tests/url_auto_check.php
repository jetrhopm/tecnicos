<?php

declare(strict_types=1);

$_SERVER['HTTP_HOST'] = '192.168.1.130';
$_SERVER['SCRIPT_NAME'] = '/tecnico/public/index.php';
$_SERVER['HTTPS'] = 'off';

require_once __DIR__ . '/../app/bootstrap.php';

echo 'BASE=' . request_base_url() . PHP_EOL;
echo 'URL=' . url('/ordenes') . PHP_EOL;
