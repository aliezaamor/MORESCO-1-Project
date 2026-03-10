<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$svc = app(\App\Services\MorescoDbService::class);
$pdo = $svc->getConnection();
$stmt = $pdo->query("SELECT TOP 5 * FROM dbo.VW_WORKORDERS_LIST WHERE account_id IS NULL AND power_interruption IS NOT NULL");
echo json_encode($stmt->fetchAll(\PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
