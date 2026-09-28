<?php

use Illuminate\Support\Facades\Route;
use Modules\TelegramBot\Http\Controllers\WebhookController;
use Modules\TelegramBot\Http\Controllers\BusinessSecretaryController;

Route::post('/webhooks/telegram', [WebhookController::class, 'handle'])->name('telegram.webhook');

// Telegram Business AI Secretary Bot Routes
Route::post('/webhooks/telegram-secretary', [BusinessSecretaryController::class, 'handle'])->name('telegram.secretary.webhook');
Route::get('/webhooks/telegram-secretary/set', [BusinessSecretaryController::class, 'setWebhook'])->name('telegram.secretary.set');
Route::get('/webhooks/telegram-secretary/info', [BusinessSecretaryController::class, 'webhookInfo'])->name('telegram.secretary.info');
