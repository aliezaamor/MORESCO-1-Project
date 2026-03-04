<?php

// Save this as `test_keywords.php` in the root of your MORESCO-1-Project folder.
// You can run it from your terminal using:
// php artisan tinker test_keywords.php

$service = app(\App\Services\SmsProcessingService::class);

echo "Simulating incoming text from +639123456789 with keyword 'BILL'...\n";
$result = $service->processIncomingMessage('+639123456789', 'BILL');

echo "\n--- SYSTEM RESPONSE ---\n";
if ($result['auto_reply']) {
    echo $result['auto_reply']->content . "\n";
} else {
    echo "No auto-reply generated.\n";
}

echo "\n-----------------------\n";
echo "Test complete. Check the database to see the recorded messages!\n";
