<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BackupAndSendToTelegram extends Command
{
    protected $signature = 'backup:db';
    protected $description = 'Backup DB dari server lain dan kirim ke Telegram';

    public function handle()
    {
        // === Konfigurasi ===
        $host = env('DB_HOST', 'localhost');
        $port = 3306;
        $username = env('DB_USERNAME', 'root');
        $password = env('DB_PASSWORD', '');
        $database = env('DB_DATABASE', 'apotek_damar');
        $startDuration = microtime(true);

        $fileName = $database . '_' . now()->setTimezone('Asia/Jakarta')->format('Y_m_d_H_i_s') . '.sql';
        $localPath = storage_path("app/{$fileName}");

        // === Dump dari remote database ===
        $passwordPart = $password === '' ? '' : "-p{$password}";
        $ignoreTable = "{$database}.log_request";

        $dumpCommand = "mysqldump -h {$host} -P {$port} -u{$username} {$passwordPart} {$database} --ignore-table={$ignoreTable} > \"{$localPath}\"";

        // Tambah redirect error ke output supaya mudah debug
        exec($dumpCommand . ' 2>&1', $output, $resultCode);

        if ($resultCode !== 0) {
            $endDuration = executionTime($startDuration);
            $this->info("Durasi: {$endDuration}");
            insertLogCron("FAILED: Backup DB - Durasi: {$endDuration} - {$dumpCommand} - " . implode("\n", $output));
            return Command::FAILURE;
        }

        $this->info("Backup berhasil: {$fileName}");

        // === Kirim ke Telegram ===
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        $endDuration = executionTime($startDuration);

        try {
            $response = Http::attach(
                'document',
                file_get_contents($localPath),
                $fileName
            )->post("https://api.telegram.org/bot{$botToken}/sendDocument", [
                'chat_id' => $chatId,
                'caption' => "🗄️ Backup DB: {$fileName}\nDurasi: {$endDuration}"
            ]);

            if ($response->successful()) {
                $this->info('Backup berhasil dikirim ke Telegram.');
            } else {
                $this->error('Gagal kirim ke Telegram.');
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }

        $endDuration = executionTime($startDuration);
        $this->info("Durasi: {$endDuration}");
        insertLogCron("Backup DB: {$fileName} - Durasi: {$endDuration}");
        return Command::SUCCESS;
    }
}
