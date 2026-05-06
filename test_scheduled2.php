<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$messages = \App\Models\Message::orderBy('id', 'desc')
    ->take(5)
    ->with('recipients')
    ->get();

foreach ($messages as $msg) {
    echo "ID: {$msg->id}\n";
    echo "Content: {$msg->content}\n";
    echo "Is Scheduled: " . ($msg->is_scheduled ? 'Yes' : 'No') . "\n";
    echo "Scheduled: {$msg->scheduled_at}\n";
    echo "Created: {$msg->created_at}\n";
    echo "Recipients Pending: " . $msg->recipients->where('status', 'pending')->count() . "\n";
    echo "-----------------\n";
}
