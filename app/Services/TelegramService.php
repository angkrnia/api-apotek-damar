<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramService
{
    public static function sendMessage(string $message, string $parseMode = 'HTML'): bool
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');
        $maxLength = 4000;

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $prefix = app()->environment('local', 'development') ? '[DEV] ' : '';

        // Gabungkan prefix dengan pesan
        $fullMessage = $prefix . $message;

        // Pisahkan dengan aman per baris agar tidak memotong tag HTML
        $lines = explode("\n", $fullMessage);
        $messages = [];
        $current = '';

        foreach ($lines as $line) {
            if (strlen($current . $line . "\n") > $maxLength) {
                $messages[] = trim($current);
                $current = $line . "\n";
            } else {
                $current .= $line . "\n";
            }
        }

        if ($current !== '') {
            $messages[] = trim($current);
        }

        $allSuccess = true;

        foreach ($messages as $part) {
            $response = Http::asForm()->post($url, [
                'chat_id' => $chatId,
                'text' => $part,
                'parse_mode' => $parseMode,
            ]);

            if (!$response->successful() || $response->json('ok') !== true) {
                $allSuccess = false;
            }
        }

        return $allSuccess;
    }
}
