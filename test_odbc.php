<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $service = new App\Services\MorescoDbService();
    $pdo = $service->getConnection();
    echo "Connected\n";
    // Check if Inquiries_Log_Test exists
    try {
        $stmt = $pdo->query("SELECT TOP 1 * FROM dbo.Inquiries_Log_Test");
        if ($stmt) echo "Inquiries_Log_Test exists\n";
    } catch (\Exception $e) {
        echo "Inquiries_Log_Test absent: " . $e->getMessage() . "\n";
        // Create it
        echo "Attempting to create Inquiries_Log_Test...\n";
        // To precisely match the schema of Inquiries_Log, we can SELECT INTO
        $pdo->exec("SELECT * INTO dbo.Inquiries_Log_Test FROM dbo.Inquiries_Log WHERE 1=0");
        echo "Table created successfully.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
