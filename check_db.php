<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

$columns = ['name', 'username', 'email', 'password', 'role', 'address', 'position'];
$results = [];
foreach ($columns as $column) {
    $results[$column] = Schema::hasColumn('users', $column) ? 'exists' : 'missing';
}

echo json_encode($results, JSON_PRETTY_PRINT);
