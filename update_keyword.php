<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$template = <<<TEXT
Account:
{account}
{account_status}
 Latest Bill
{billing_period}
{bill_amount}
 Due: {due_date}
Running Balance:
{balance}
 Last Payment
{last_payment_amount}
 {last_payment_date}
OR Number:
{or_number}
TEXT;

$keyword = \App\Models\Keyword::where('keyword', 'BILL')->first();
if ($keyword) {
    $actionData = $keyword->action_data ?? [];
    $actionData['has_balance'] = $template;
    $actionData['no_balance'] = $template;
    
    $keyword->action_data = $actionData;
    $keyword->save();
    echo "Updated BILL keyword successfully.\n";
} else {
    echo "Keyword BILL not found.\n";
}
