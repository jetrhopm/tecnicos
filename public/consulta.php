<?php

declare(strict_types=1);

$_SERVER['REQUEST_URI'] = dirname($_SERVER['SCRIPT_NAME']) . '/consulta' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
require __DIR__ . '/index.php';
