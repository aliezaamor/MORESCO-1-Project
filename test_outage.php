<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$srv = app(App\Services\MorescoDbService::class);
$pdo = $srv->getConnection();
$stmt = $pdo->query('SELECT TOP 1 * FROM dbo.VW_WORKORDERS_LIST');
print_r($stmt->fetch(\PDO::FETCH_ASSOC));
