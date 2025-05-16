<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class BackupAndSendToTelegram extends Command
{
    protected $signature = 'backup:telegram';
    protected $description = 'Backup DB dari server lain dan kirim ke Telegram';

    public function handle()
    {
        // === Konfigurasi ===
        $host = 'remote-db-host.com';
        $port = 3306;
        $username = 'db_user';
        $password = 'db_password';
        $database = 'your_db_name';

        $fileName = 'backup_' . now()->format('Y_m_d_H_i_s') . '.sql';
        $localPath = storage_path("app/{$fileName}");

        // === Dump dari remote database ===
        $dumpCommand = "mysqldump -h {$host} -P {$port} -u{$username} -p'{$password}' {$database} > {$localPath}";
        exec($dumpCommand, $output, $resultCode);

        if ($resultCode !== 0) {
            $this->error("Backup gagal. Code: $resultCode");
            return Command::FAILURE;
        }

        $this->info("Backup berhasil: {$fileName}");

        // === Kirim ke Telegram ===
        $botToken = 'YOUR_TELEGRAM_BOT_TOKEN';
        $chatId = 'YOUR_CHAT_ID'; // bisa berupa ID pribadi atau grup

        try {
            $response = Http::attach(
                'document',
                file_get_contents($localPath),
                $fileName
            )->post("https://api.telegram.org/bot{$botToken}/sendDocument", [
                'chat_id' => $chatId,
                'caption' => '🗄️ Backup DB: ' . $fileName,
            ]);

            if ($response->successful()) {
                $this->info('Backup berhasil dikirim ke Telegram.');
            } else {
                $this->error('Gagal kirim ke Telegram.');
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }

        // (Optional) hapus file setelah upload
        unlink($localPath);

        return Command::SUCCESS;
    }
}
