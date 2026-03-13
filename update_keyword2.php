<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Fixed format
$format = <<<TEXT
Account:
{account}
{account_status}
 Latest Bill
{billing_period}
{bill_amount}
 Due: {due_date}
{dynamic_balance} Last Payment
{last_payment_amount}
 {last_payment_date}
OR Number:
{or_number}
TEXT;

$keyword = \App\Models\Keyword::where('keyword', 'BILL')->first();
if ($keyword) {
    $actionData = $keyword->action_data ?? [];
    $actionData['has_balance'] = $format;
    $actionData['no_balance'] = $format; // Both use the same base layout now, differences handled in dynamic padding
    
    $keyword->action_data = $actionData;
    $keyword->save();
    echo "Updated BILL keyword with dynamic balance format.\n";
} else {
    echo "Keyword BILL not found.\n";
}
