<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramService
{
    public static function sendMessage(string $message, string $parseMode = 'HTML'): bool
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $prefix = app()->environment('local', 'development') ? '[DEV] ' : '';

        $response = Http::asForm()->post($url, [
            'chat_id' => $chatId,
            'text' => $prefix . $message,
            'parse_mode' => $parseMode,
        ]);

        return $response->successful() && $response->json('ok') === true;
    }
}
