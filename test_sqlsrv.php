<?php
/**
 * Verify UTF-8 fix: ensure JSON encoding of 100 members succeeds.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MorescoDbService;

$service = new MorescoDbService();
$members = $service->getMembers(null, 100, 0);

$json = json_encode($members);

if ($json === false) {
    echo "✗ json_encode FAILED: " . json_last_error_msg() . "\n";
} else {
    echo "✓ json_encode SUCCESS — " . count($members) . " members encoded cleanly.\n";
    $first = $members[0] ?? null;
    if ($first) {
        echo "  Sample: [{$first['id']}] {$first['name']} | {$first['phone_number']} | {$first['status']}\n";
    }
}
