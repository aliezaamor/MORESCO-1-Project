<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$kws = \App\Models\Keyword::all();
foreach($kws as $k) {
    $data = json_encode($k->toArray(), JSON_PRETTY_PRINT);
    if (str_contains($data, '2,350') || str_contains($data, 'outstanding balance') || str_contains($data, 'Feb 2026')) {
        echo "FOUND IN KEYWORD ID: {$k->id} -> {$k->keyword}\n";
        echo $data . "\n\n";
    }
}

$msgs = \App\Models\Message::where('content', 'LIKE', '%2,350%')->get();
foreach($msgs as $m) {
    echo "FOUND IN MESSAGE ID: {$m->id}\n";
    echo json_encode($m->toArray(), JSON_PRETTY_PRINT) . "\n\n";
}

echo "Search complete.\n";
