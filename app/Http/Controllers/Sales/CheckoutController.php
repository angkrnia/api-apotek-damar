<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductUnits;
use App\Models\Sale\SaleDetail;
use App\Models\Sale\SaleHeader;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        $request->validate([
            'payment_method' => ['required', 'string', 'max:100'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        DB::beginTransaction();
        try {
            $carts = Cart::with(['product', 'productUnit.unit'])->where('session_id', getSessionId($request))->where('status', 'PENDING')->get();
            // Jika tidak ada produk di keranjang
            if ($carts->isEmpty()) {
                return response()->json([
                    'code'      => 404,
                    'status'    => false,
                    'message'   => 'Tidak ada produk dalam keranjang.',
                ], 404);
            }

            // Jika paid amount kurang dari grand total
            if ($request->paid_amount < $carts->sum('subtotal')) {
                return response()->json([
                    'code'      => 400,
                    'status'    => false,
                    'message'   => 'Jumlah pembayaran kurang dari grand total.',
                ], 400);
            }

            // Insert sale header dulu
            $saleHeader = SaleHeader::create([
                'payment_method' => $request->payment_method,
                'grand_total' => $carts->sum('subtotal'),
                'paid_amount' => $request->paid_amount,
                'change' => $request->paid_amount - $carts->sum('subtotal'),
                'note' => $request->note,
                'created_by' => auth()->user()->fullname,
                'cashier_id' => auth()->user()->id,
            ]);

            // Insert header detail sesuai yang ada di carts
            $data = [];
            $movementBuffer = [];

            foreach ($carts as $cart) {
                $productUnit = ProductUnits::find($cart->product_unit_id);
                $masterProduct = Product::find($cart->product_id);
                $qtyOut = $productUnit->conversion_to_base * $cart->quantity;

                // 1. Masukkan ke sale detail
                $data[] = [
                    'sale_id'             => $saleHeader->id,
                    'cart_id'             => $cart->id,
                    'product_name'        => $cart->product->name,
                    'product_sku'         => $cart->product->sku,
                    'product_unit'        => $productUnit->unit->name ?? '-',
                    'product_unit_price'  => $cart->unit_price,
                    'product_unit_cost'   => $cart->product->purchase_price * $productUnit->conversion_to_base,
                    'quantity'            => $cart->quantity,
                    'subtotal'            => $cart->subtotal,
                    'note'                => $cart->note ?? '',
                    'created_by'          => auth()->user()->fullname,
                    'created_at'          => now(),
                ];

                // 2. Update cart
                $cart->status = 'CHECKED_OUT';
                $cart->updated_by = auth()->user()->fullname;
                $cart->save();

                // 3. Akumulasi untuk stok movement
                if (!isset($movementBuffer[$cart->product_id])) {
                    $movementBuffer[$cart->product_id] = [
                        'qty_out' => 0,
                        'notes' => [],
                    ];
                }
                $movementBuffer[$cart->product_id]['qty_out'] += $qtyOut;
                if ($cart->note) {
                    $movementBuffer[$cart->product_id]['notes'][] = $cart->note;
                }

                // 4. Cek stok per cart
                if ($masterProduct->base_stock < $qtyOut) {
                    DB::rollBack();
                    return response()->json([
                        'code'      => 400,
                        'status'    => false,
                        'message'   => "Stok produk {$masterProduct->name} tidak mencukupi.",
                    ], 400);
                }

                // 5. Kurangi stok
                $masterProduct->decrement('base_stock', $qtyOut);
            }

            // 6. Masukkan stock movement sekali per produk
            foreach ($movementBuffer as $productId => $movement) {
                $product = Product::find($productId);
                StockMovement::create([
                    'product_id'       => $productId,
                    'movement_type'    => 'OUT',
                    'qty_in'           => 0,
                    'qty_out'          => $movement['qty_out'],
                    'remaining'        => $product->base_stock,
                    'reference_type'   => 'Penjualan #' . $saleHeader->receipt_number,
                    'note'             => implode(' | ', $movement['notes']),
                    'created_by'       => auth()->user()->fullname
                ]);
            }
            SaleDetail::insert($data);

            DB::commit();

            return response()->json([
                'code'      => 201,
                'status'    => true,
                'message'   => 'Checkout berhasil.',
                'data'      => $saleHeader
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return response()->json([
                'code'      => 500,
                'status'    => false,
                'message'   => 'Gagal melakukan checkout.',
            ], 500);
        }
    }

    public function success(Request $request, SaleHeader $sale)
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:255']
        ]);

        $sale->update([
            'status' => 'SUCCESS',
            'note' => $request->note,
            'updated_by' => auth()->user()->fullname,
        ]);

        return response()->json([
            'code'      => 201,
            'status'    => true,
            'message'   => 'Transaksi berhasil.',
        ], 201);
    }
}
