<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCheckStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $products = Product::where('is_active', 1)
            ->where('base_stock', '<=', 1)
            ->get();

        if ($products->isEmpty()) {
            $this->info('Tidak ada produk dengan stok kritis.');
            return;
        }

        $tanggal = now()->format('d-M-Y');
        $message = "⚠️ <strong>STOK OBAT KRITIS [$tanggal]</strong>\n";
        $message .= "Beberapa obat berikut hampir habis:\n\n";

        foreach ($products as $product) {
            $message .= "💊 {$product->name} — Tersisa {$product->base_stock} pcs\n";
        }

        TelegramService::sendMessage($message);
        $this->info('Pesan Telegram berhasil dikirim.');
    }
}
