<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sale\SaleDetail;
use App\Models\Sale\SaleHeader;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $query = SaleHeader::query();
        $items = null;

        // Jika ada search
        if (isset($request['search']) && !empty($request['search'])) {
            $textSearch =  $request['search'];
            $query->keywordSearch($textSearch);
        }

        // Jika ada start_date atau end_date
        if (
            (isset($request['start_date']) && !empty($request['start_date'])) ||
            (isset($request['end_date']) && !empty($request['end_date']))
        ) {
            $start_date = Carbon::parse($request['start_date'], 'Asia/Jakarta')->startOfDay()->timezone('UTC');
            $end_date = Carbon::parse($request['end_date'], 'Asia/Jakarta')->endOfDay()->timezone('UTC');

            $query->whereBetween('created_at', [$start_date, $end_date]);

            $items = $query->where('status', 'SUCCESS')->get();
        }

        // Jika ada cashier
        if (isset($request['cashier']) && !empty($request['cashier'])) {
            $cashier = $request['cashier'];
            $query->where('cashier_id', $cashier);

            $items = $query->where('status', 'SUCCESS')->get();
        }

        // Jika ada payment_method
        if (isset($request['payment_method']) && !empty($request['payment_method'])) {
            $payment_method = $request['payment_method'];
            $query->where('payment_method', $payment_method);

            $items = $query->where('status', 'SUCCESS')->get();
        }

        // Jika ada payment_status
        if (isset($request['payment_status']) && !empty($request['payment_status'])) {
            $payment_status = $request['payment_status'];
            $query->where('status', $payment_status);

            $items = $query->where('status', 'SUCCESS')->get();
        }

        $query->orderBy('created_at', 'desc');

        if (isset($request['limit']) || isset($request['page'])) {
            $limit = $request['limit'] ?? 10;
            $result = $query->paginate($limit)->appends(request()->query());
        } else {
            $result = $query->get();
        }

        $summary = [
            'total_transaction' => $items ? $items->count() : 0,
            'total_amount'      => $items ? $items->sum('grand_total') : 0,
            'total_products'    => $items ? $items->sum('total_products') : 0,
        ];

        $response = $result->toArray();
        $response['summary'] = $summary;

        return response()->json([
            'code' => 200,
            'status' => true,
            'data' => $response
        ]);
    }

    public function cancel(Request $request, SaleHeader $sale)
    {
        $request->validate([
            'note' => ['required', 'string', 'max:255']
        ]);

        // Jika statusnya bukan success
        if ($sale->status == 'CANCELED') {
            return response()->json([
                'code'      => 400,
                'status'    => false,
                'message'   => 'Transaksi tidak dapat dibatalkan.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $saleProductLine = SaleDetail::with(['cart.product', 'cart.productUnit'])
                ->where('sale_id', $sale->id)
                ->get();

            foreach ($saleProductLine as $productLine) {
                $cart = $productLine->cart;

                // Ambil qty dan konversi ke stok dasar
                $qty = $cart->quantity;
                $conversion = $cart->productUnit->conversion_to_base;
                $restoreQty = $qty * $conversion;

                // Ambil produk
                $product = $cart->product;

                // Kembalikan ke base stock
                $product->base_stock += $restoreQty;
                $product->save();

                // Update note pada detail penjualan
                $productLine->note = $request->note;
                $productLine->save();

                // Catat ke stock movement
                StockMovement::create([
                    'product_id'     => $product->id,
                    'movement_type'  => 'IN', // Karena stok masuk kembali
                    'qty_in'         => $restoreQty,
                    'qty_out'        => 0,
                    'remaining'      => $product->base_stock,
                    'reference_type' => 'Cancel Penjualan #' . $sale->receipt_number,
                    'note'           => $request->note ?? 'Pembatalan penjualan',
                    'created_by'     => auth()->user()->fullname,
                ]);
            }

            // Update status penjualan
            $sale->status = 'CANCELED';
            $sale->note = $request->note;
            $sale->updated_by = auth()->user()->fullname;
            $sale->save();

            DB::commit();

            return response()->json([
                'code'      => 200,
                'status'    => true,
                'message'   => 'Transaksi berhasil di cancel.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return response()->json([
                'code'      => 500,
                'status'    => false,
                'message'   => 'Gagal cancel transaksi.',
            ], 500);
        }
    }
}
