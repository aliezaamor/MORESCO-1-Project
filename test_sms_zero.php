<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test with another account that we know has no balance, like 01040436 (from logs, it was recently queried)
$service = app(\App\Services\SmsProcessingService::class);
$result = $service->processIncomingMessage('+639123456789', 'BILL 01040436');

echo "Incoming Message Saved:\n";

if ($result['auto_reply']) {
    echo "Auto Reply Generated for account 01040436:\n";
    echo $result['auto_reply']->content . "\n";
} else {
    echo "No auto-reply generated.\n";
}
