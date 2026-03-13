<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(\App\Services\SmsProcessingService::class);
$result = $service->processIncomingMessage('+639123456789', 'BILL 05021320');

echo "Incoming Message Saved:\n";
echo json_encode($result['incoming']->toArray(), JSON_PRETTY_PRINT) . "\n\n";

if ($result['auto_reply']) {
    echo "Auto Reply Generated:\n";
    echo $result['auto_reply']->content . "\n";
} else {
    echo "No auto-reply generated.\n";
}
