<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AutoCheckBestSeller extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:bestseller';

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
        TelegramService::sendMessage(strip_tags($this->generateBestSellerReport(Carbon::now()->subYear(), 'Sepanjang Waktu')), 'HTML');
        TelegramService::sendMessage(strip_tags($this->generateBestSellerReport(Carbon::now()->subMonth(), '1 Bulan')), 'HTML');
        TelegramService::sendMessage(strip_tags($this->generateBestSellerReport(Carbon::now()->subWeek(), '1 Minggu')), 'HTML');
        TelegramService::sendMessage(strip_tags($this->generateBestSellerReport(Carbon::now()->startOfDay(), 'Hari Ini')), 'HTML');
        return 0;
    }

    function generateBestSellerReport(Carbon $rangeStart, string $label): string
    {
        $now = Carbon::now();

        $products = DB::table('sales_product_line as line')
            ->join('sales as header', 'line.sale_id', '=', 'header.id')
            ->selectRaw("
            COALESCE(line.product_name, line.product_sku) as name,
            SUM(line.quantity) as total_quantity,
            SUM(line.quantity * line.product_unit_price) as total_sales,
            SUM(line.quantity * line.product_unit_cost) as total_cost
        ")
            ->where('header.status', 'SUCCESS')
            ->whereBetween('header.created_at', [$rangeStart, $now])
            ->groupBy('name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        if ($products->isEmpty()) {
            return "<strong>🩺 Obat Paling Laku ({$label})</strong>\nTidak ada data.\n";
        }

        $message = "<strong>🩺 Obat Paling Laku ({$label})</strong>\n";

        foreach ($products as $product) {
            $profit = $product->total_sales - $product->total_cost;

            $message .= "💊 " . Str::limit($product->name, 25) . "\n";
            $message .= "🛒 Qty: {$product->total_quantity}\n";
            $message .= "💰 Total: Rp " . number_format($product->total_sales, 0, ',', '.') . "\n";
            $message .= "🧾 HPP: Rp " . number_format($product->total_cost, 0, ',', '.') . "\n";
            $message .= "📈 Profit: Rp " . number_format($profit, 0, ',', '.') . "\n\n";
        }

        return $message;
    }
}
