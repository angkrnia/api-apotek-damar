<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale\SaleDetail;
use App\Models\Sale\SaleHeader;
use App\Models\StockIn\StockInDetail;
use App\Models\StockIn\StockInHeader;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChartController extends Controller
{
    public function getTransactionSummary(Request $request)
    {
        // Ambil date range start dan end, default ke hari ini jika tidak ada
        $start = Carbon::parse($request->input('start', Carbon::createFromTimestamp(0)))->startOfDay();
        $end = Carbon::parse($request->input('end', now()->endOfDay()))->endOfDay();

        // Query ke SaleHeader dengan filter tanggal
        $summary = SaleHeader::where('status', 'SUCCESS')->whereBetween('created_at', [$start, $end])
            ->selectRaw('
            COUNT(id) as total_transaction,
            SUM(grand_total) as total_sales
        ')
            ->first();

        // Query total quantity dari SaleDetail
        $totalQuantity = SaleDetail::whereHas('sale', function ($query) use ($start, $end) {
            $query->whereStatus('SUCCESS')->whereBetween('created_at', [$start, $end]);
        })->sum('quantity');

        $totalProduct = SaleDetail::whereHas('sale', function ($query) use ($start, $end) {
            $query->whereStatus('SUCCESS')->whereBetween('created_at', [$start, $end]);
        })->distinct('product_sku')->count('product_sku');

        // Total Keuntungan
        $totalProfit = SaleHeader::where('status', 'SUCCESS')->whereBetween('created_at', [$start, $end])->sum('grand_total');

        return response()->json([
            'code' => 200,
            'status' => true,
            'data' => [
                'total_transaction' => intval($summary->total_transaction) ?? 0, // Jumlah transaksi
                'total_sales' => $summary->total_sales ?? 0, // Total penjualan
                'total_quantity' => intval($totalQuantity) ?? 0, // Jumlah qty produk terjual
                'total_product' => intval($totalProduct) ?? 0, // Jumlah produk terjual
                'total_profit' => $totalProfit ?? 0
            ]
        ]);
    }

    public function getProductSummary(Request $request)
    {
        // Hitung jumlah produk unik
        $totalUniqueProducts = Product::where('is_active', 1)->count();

        // Hitung total stok yang masih tersedia (base_stock yang lebih dari 0)
        $totalStockOnHand = Product::where('is_active', 1)->sum('base_stock');

        // Hitung jumlah produk yang stoknya habis (base_stock = 0)
        $totalOutOfStockProducts = Product::where('is_active', 1)
            ->where('base_stock', 0)
            ->count();

        // Hitung jumlah produk yang stoknya kritis (anggap stok di bawah 10 dianggap kritis)
        $criticalStockThreshold = 3;
        $totalCriticalStockProducts = Product::where('is_active', 1)
            ->whereBetween('base_stock', [1, $criticalStockThreshold])
            ->count();

        // Hitung harga modal semua produk
        $totalProductCost = Product::where('is_active', 1)->sum('purchase_price');

        return response()->json([
            'code' => 200,
            'status' => true,
            'data' => [
                'total_unique_products' => intval($totalUniqueProducts), // Jumlah produk unik yang ada
                'total_stock_onhand' => intval($totalStockOnHand), // Total qty semua produk yang masih tersedia
                'total_out_of_stock_products' => intval($totalOutOfStockProducts), // Jumlah produk yang stoknya habis
                'total_critical_stock_products' => intval($totalCriticalStockProducts), // Jumlah produk dengan stok kritis
                'total_product_cost' => $totalProductCost, // Total harga modal semua produk
            ]
        ]);
    }

    public function getTransactionDateByDate(Request $request)
    {
        $dates = collect();
        for ($i = 14; $i >= 0; $i--) {
            $dates->push(now()->subDays($i)->format('Y-m-d'));
        }

        // Ambil data dari model Sales dan SalesProductLine
        // $data = SaleHeader::where('status', 'SUCCESS')->select(
        //     DB::raw('DATE(sales.created_at) as date'),
        //     DB::raw('COUNT(*) as total'),
        //     DB::raw('SUM(sales.grand_total) as total_sales'),
        //     DB::raw('SUM(sales_product_line.product_unit_cost * sales_product_line.quantity) as total_cost'), // Total harga modal
        //     DB::raw('SUM(sales.grand_total - (sales_product_line.product_unit_cost * sales_product_line.quantity)) as total_profit') // Menghitung total keuntungan
        // )
        //     ->join('sales_product_line', 'sales_product_line.sale_id', '=', 'sales.id') // Gabungkan dengan sales_product_line berdasarkan sale_id
        //     ->where('sales.created_at', '>=', now()->subDays(30))
        //     ->groupBy(DB::raw('DATE(sales.created_at)'))
        //     ->orderBy(DB::raw('DATE(sales.created_at)', 'asc'))
        //     ->get()
        //     ->keyBy('date');
        $data = SaleHeader::from('sales as sales')
            ->join('sales_product_line', 'sales_product_line.sale_id', '=', 'sales.id')
            ->where('sales.status', 'SUCCESS')
            ->where('sales.created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(sales.created_at) as date'),
                DB::raw('COUNT(DISTINCT sales.id) as total'),
                DB::raw('SUM(DISTINCT sales.grand_total) as total_sales'),
                DB::raw('SUM(sales_product_line.product_unit_cost * sales_product_line.quantity) as total_cost')
            )
            ->groupBy(DB::raw('DATE(sales.created_at)'))
            ->orderBy(DB::raw('DATE(sales.created_at)'), 'asc')
            ->get()
            ->map(function ($row) {
                $row->total_profit = $row->total_sales - $row->total_cost;
                return $row;
            })
            ->keyBy('date');

        // Gabungkan semua tanggal dengan data Sales
        $history = $dates->map(function ($date) use ($data) {
            return [
                'date' => Carbon::parse($date)->format('j M y'), // Format tanggal
                'total' => $data[$date]->total ?? 0,            // Ambil total transaksi, default 0 jika tidak ada data
                'total_sales' => $data[$date]->total_sales ?? 0,   // Ambil total sales, default 0 jika tidak ada data
                'total_cost' => $data[$date]->total_cost ?? 0,     // Ambil total harga modal, default 0 jika tidak ada data
                'total_profit' => $data[$date]->total_profit ?? 0, // Ambil total keuntungan, default 0 jika tidak ada data
            ];
        });

        // Return response sebagai JSON
        return response()->json([
            'code' => 200,
            'status' => true,
            'data' => $history,
        ]);
    }

    public function getSalesSummary(Request $request)
    {
        // Total Keuntungan Rp
        // Total Pembelian Rp
        // Produk Dibeli
        // QTY Produk Dibeli

        $saleIds = SaleHeader::from('sales as sales')
            ->where('sales.status', 'SUCCESS')
            ->when(request('start'), fn($q, $start) => $q->where('sales.created_at', '>=', \Carbon\Carbon::parse($start)->startOfDay()))
            ->when(request('end'), fn($q, $end) => $q->where('sales.created_at', '<=', \Carbon\Carbon::parse($end)->endOfDay()))
            ->pluck('sales.id');

        // Total sales dari ID yang sama
        $totalSales = SaleHeader::whereIn('id', $saleIds)->sum('grand_total');

        // Total cost dari product line yang terhubung ke sales yang sama
        $totalCost = DB::table('sales_product_line')
            ->whereIn('sale_id', $saleIds)
            ->select(DB::raw('SUM(product_unit_cost * quantity) as total_cost'))
            ->value('total_cost');

        // Total pembelian (stok masuk)
        $stockInIds = StockInHeader::from('stock_in as stock_in')
            ->where('stock_in.status', 'COMMITED')
            ->when(request('start'), fn($q, $start) => $q->where('stock_in.created_at', '>=', \Carbon\Carbon::parse($start)->startOfDay()))
            ->when(request('end'), fn($q, $end) => $q->where('stock_in.created_at', '<=', \Carbon\Carbon::parse($end)->endOfDay()))
            ->pluck('stock_in.id');

        $totalBuyPrice = DB::table('stock_in_product_line')
            ->whereIn('stock_in_id', $stockInIds)
            ->select(DB::raw('SUM(buy_price * quantity) as total_cost'))
            ->value('total_cost');

        $totalBuyProducts = StockInDetail::whereIn('stock_in_id', $stockInIds)
            ->distinct('product_id')
            ->count('product_id');

        $totalBuyQuantity = SaleDetail::whereIn('sale_id', $saleIds)->sum('quantity');

        $data = (object) [
            'total_profit' => (float) $totalSales - (float) $totalCost,
            'total_buy_price' => (float) $totalBuyPrice,
            'total_buy_product' => (int) $totalBuyProducts,
            'total_buy_quantity' => (int) $totalBuyQuantity
        ];

        // Return response sebagai JSON
        return response()->json([
            'code' => 200,
            'status' => true,
            'data' => $data,
        ]);
    }
}
