<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Message;

$latest = Message::where('type', 'auto_reply')->with('recipients')->latest()->take(5)->get();

echo "--- Latest Auto-Replies Status ---\n";
foreach ($latest as $m) {
    $status = $m->recipients->first()->status ?? 'N/A';
    echo "ID: {$m->id} | Time: {$m->created_at} | Status: {$status} | Content: " . substr($m->content, 0, 30) . "...\n";
}
