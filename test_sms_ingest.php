<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$msgs = App\Models\Message::latest()->take(3)->get()->toArray();
print_r($msgs);
