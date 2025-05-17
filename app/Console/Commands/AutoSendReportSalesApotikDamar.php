<?php

namespace App\Console\Commands;

use App\Models\Sale\SaleDetail;
use App\Models\Sale\SaleHeader;
use App\Models\StockIn\StockInDetail;
use App\Models\StockIn\StockInHeader;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoSendReportSalesApotikDamar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:apotekdamar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Report Sales Apotik Damar';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startJakarta = Carbon::now('Asia/Jakarta')->startOfDay();
        $endJakarta = Carbon::now('Asia/Jakarta')->endOfDay();

        // Konversi ke UTC
        $startUtc = $startJakarta->copy()->setTimezone('UTC');
        $endUtc = $endJakarta->copy()->setTimezone('UTC');

        $saleIds = SaleHeader::from('sales as sales')
            ->where('sales.status', 'SUCCESS')
            ->whereBetween('sales.created_at', [$startUtc, $endUtc])
            ->pluck('sales.id');

        // Total sales dari ID yang sama
        $totalSales = SaleHeader::whereIn('id', $saleIds)->sum('grand_total');

        // Total cost dari product line yang terhubung ke sales yang sama
        $totalCost = DB::table('sales_product_line')
            ->whereIn('sale_id', $saleIds)
            ->select(DB::raw('SUM(product_unit_cost * quantity) as total_cost'))
            ->value('total_cost');

        // Total QTY Terjual
        $totalQuantity = SaleDetail::whereIn('sale_id', $saleIds)->sum('quantity');

        // Total pembelian (stok masuk)
        $stockInIds = StockInHeader::from('stock_in as stock_in')
            ->where('stock_in.status', 'COMMITED')
            ->whereBetween('stock_in.created_at', [$startUtc, $endUtc])
            ->pluck('stock_in.id');

        $totalBuyPrice = DB::table('stock_in_product_line')
            ->whereIn('stock_in_id', $stockInIds)
            ->select(DB::raw('SUM(buy_price * quantity) as total_cost'))
            ->value('total_cost');

        $totalBuyProducts = StockInDetail::whereIn('stock_in_id', $stockInIds)
            ->distinct('product_id')
            ->count('product_id');

        $totalBuyQuantity = StockInDetail::whereIn('stock_in_id', $stockInIds)->sum('quantity');

        $data = (object) [
            'total_sales' => (float) $totalSales,
            'total_profit' => (float) $totalSales - (float) $totalCost,
            'total_quantity' => (int) $totalQuantity,
            'total_buy_price' => (float) $totalBuyPrice,
            'total_buy_product' => (int) $totalBuyProducts,
            'total_buy_quantity' => (int) $totalBuyQuantity
        ];

        $message = "<strong>Laporan Harian Apotek Damar</strong>\n" .
            "📅 Tanggal: " . \Carbon\Carbon::now('Asia/Jakarta')->format('d M Y, H:i') . "\n" .
            "💰 Total Sales: " . convertRp($data->total_sales) . "\n" .
            "📈 Profit: " . convertRp($data->total_profit) . "\n" .
            "🛒 Qty Terjual: {$data->total_quantity}\n" .
            "📦 Produk Masuk: {$data->total_buy_product}\n" .
            "📦 Qty Masuk: {$data->total_buy_quantity}\n" .
            "💵 Total: " . convertRp($data->total_buy_price) . "\n";

        TelegramService::sendMessage($message);
        return Command::SUCCESS;
    }
}
