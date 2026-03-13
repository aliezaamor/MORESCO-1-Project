<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$host = env('YEASTAR_HOST', '10.209.80.8');
$port = env('YEASTAR_API_PORT', 5038);

echo "Connecting to {$host}:{$port}\n";

$socket = @fsockopen($host, $port, $errno, $errstr, 5);

if (!$socket) {
    echo "Failed: $errstr ($errno)\n";
} else {
    echo "Connected successfully to {$host}!\n";
    fclose($socket);
}
