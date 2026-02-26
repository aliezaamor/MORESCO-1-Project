<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Schema;
$columns = ['name', 'username', 'email', 'password', 'role', 'address', 'position'];
foreach ($columns as $column) {
    echo $column . ": " . (Schema::hasColumn('users', $column) ? 'OK' : 'MISSING') . "\n";
}
