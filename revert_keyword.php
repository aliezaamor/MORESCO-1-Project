<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Initial format before my change
$originalFormat = <<<TEXT
MORESCO-1: {account}, {name}

Your current bill is {bill_amount}.
Billing Period: {billing_period}.
Due Date: {due_date}.
TEXT;

$originalNoBalance = <<<TEXT
MORESCO-1: {account}, {name}

You have no outstanding balance. Thank you for your payment.
TEXT;

$keyword = \App\Models\Keyword::where('keyword', 'BILL')->first();
if ($keyword) {
    $actionData = $keyword->action_data ?? [];
    $actionData['has_balance'] = $originalFormat;
    $actionData['no_balance'] = $originalNoBalance;
    
    $keyword->action_data = $actionData;
    $keyword->save();
    echo "Reverted BILL keyword to original format.\n";
} else {
    echo "Keyword BILL not found.\n";
}
