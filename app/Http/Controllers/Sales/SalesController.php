<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sale\SaleDetail;
use App\Models\Sale\SaleHeader;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $query = SaleHeader::query();

        // Jika ada search
        if (isset($request['search']) && !empty($request['search'])) {
            $textSearch =  $request['search'];
            $query->keywordSearch($textSearch);
        }

        $query->orderBy('created_at', 'desc');

        if (isset($request['limit']) || isset($request['page'])) {
            $limit = $request['limit'] ?? 10;
            $result = $query->paginate($limit)->appends(request()->query());
        } else {
            $result = $query->get();
        }

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'data'      => $result
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
