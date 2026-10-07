<?php

use App\Models\OrderMessage;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

// The same clean-up the website runs once an hour; handy to run by hand.
Artisan::command('messages:clean-up', function () {
    [$deleted, $removed] = OrderMessage::cleanUp();
    $this->info("{$deleted} messages in quiet chats deleted, {$removed} deleted messages removed for good.");
})->purpose('Delete quiet chats and remove old deleted messages');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();
