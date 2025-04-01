<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductUnits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CartController extends Controller
{
    // List cart by session id, user agent, dan ip address
    public function index(Request $request)
    {
        $query = Cart::query();

        if (isset($request['search']) && !empty($request['search'])) {
            $textSearch =  $request['search'];
            $query->keywordSearch($textSearch);
        }

        $query->where('session_id', getSessionId($request))
            ->where('status', 'PENDING')
            ->orderBy('created_at', 'desc');

        if (isset($request['limit']) || isset($request['page'])) {
            $limit = $request['limit'] ?? 10;
            $result = $query->with(['product', 'productUnit.unit'])->paginate($limit)->appends(request()->query());
        } else {
            $result = $query->with(['product', 'productUnit.unit'])->get();
        }

        $data = [
            'subtotal' => $result->sum('subtotal'),
            'status' => $result->first()?->status ?? null,
            'carts' => $result,
        ];

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'data'      => $data
        ]);
    }

    // Add to cart
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_unit_id' => ['required', Rule::exists('product_units', 'id')->where('product_id', $request->product_id)],
            'quantity' => ['required', 'numeric', 'min:0'],
            'new_price' => ['nullable', 'numeric'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        DB::beginTransaction();
        try {
            // Cek stoknya berdasarkan product_unit_id ambil conversion lalu bandingkan dengan product base_stock dengan product_id
            $product = Product::find($request->product_id);
            $productUnit = ProductUnits::find($request->product_unit_id);
            $productBaseStock = $product->base_stock;
            $productUnitConversion = $productUnit->conversion_to_base;

            if ($productUnitConversion * $request->quantity > $productBaseStock) {
                DB::rollBack();
                return response()->json([
                    'code'      => 400,
                    'status'    => false,
                    'message'   => "Stok produk $product->name tidak mencukupi",
                ], 400);
            }

            // Jika ada new_price, unitPrice timpa, jika tidak ada maka ambil new_price dari product_unit_id
            if (isset($request->new_price) && !empty($request->new_price) && $request->new_price != null) {
                $unitPrice = $request->new_price;
            } else {
                $unitPrice = $productUnit->new_price;
            }

            // Jika produk sudah ada di keranjang berdasarkan session id, user agent dan ip address, maka update dan tambah quantity
            $existingProduct = Cart::where('session_id', getSessionId($request))
                ->where('user_agent', $request->header('User-Agent') ?? 'unknown')
                ->where('ip_address', $request->ip() ?? '0.0.0.0')
                ->where('product_id', $request->product_id)
                ->where('product_unit_id', $request->product_unit_id)
                ->where('status', 'PENDING')
                ->first();

            if ($existingProduct) {
                // Cek stoknya
                if ($productUnitConversion * ($existingProduct->quantity + $request->quantity) > $productBaseStock) {
                    DB::rollBack();
                    return response()->json([
                        'code'      => 400,
                        'status'    => false,
                        'message'   => "Stok produk $product->name tidak mencukupi",
                    ], 400);
                }
                // Update keranjang
                $existingProduct->update([
                    'quantity' => $existingProduct->quantity + $request->quantity,
                    'subtotal' => $existingProduct->subtotal + ($request->quantity * $existingProduct->unit_price),
                    'updated_by' => auth()->user()->fullname
                ]);
            } else {
                // Masukan ke keranjang
                Cart::create([
                    'session_id' => getSessionId($request),
                    'user_agent' => $request->header('User-Agent') ?? 'unknown',
                    'ip_address' => $request->ip() ?? '0.0.0.0',
                    'product_id' => $request->product_id,
                    'product_unit_id' => $request->product_unit_id,
                    'quantity' => $request->quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $unitPrice * $request->quantity,
                    'note' => $request->note,
                    'created_by' => auth()->user()->fullname,
                ]);
            }

            DB::commit();

            return response()->json([
                'code'      => 201,
                'status'    => true,
                'message'   => 'Berhasil menambahkan ke keranjang.'
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return response()->json([
                'code'      => 500,
                'status'    => false,
                'message'   => 'Gagal menambahkan ke keranjang.',
            ], 500);
        }
    }

    // Decrement qty cart
    public function update(Request $request, Cart $cart)
    {
        $request->validate([
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        // Jika quantity 0 maka hapus produk
        if ($request->quantity == 0) {
            $cart->delete();
            return response()->json([
                'code'      => 200,
                'status'    => true,
                'message'   => 'Jumlah produk berhasil diubah.'
            ], 200);
        }

        $product = Product::find($cart->product_id);
        $productUnit = ProductUnits::find($cart->product_unit_id);
        $productBaseStock = $product->base_stock;
        $productUnitConversion = $productUnit->conversion_to_base;

        if ($productUnitConversion * $request->quantity > $productBaseStock) {
            DB::rollBack();
            return response()->json([
                'code'      => 400,
                'status'    => false,
                'message'   => "Stok produk $product->name tidak mencukupi",
            ], 400);
        }

        // Jika ada unit_price maka update unit price dan kalikan dengan subtotal
        if (isset($request->unit_price) && !empty($request->unit_price) && $request->unit_price != null) {
            $cart->update([
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'subtotal' => $request->unit_price * $request->quantity,
                'note' => $request->note
            ]);
            return response()->json([
                'code'      => 200,
                'status'    => true,
                'message'   => 'Keranjang produk berhasil diubah.'
            ], 200);
        }

        $cart->update([
            'quantity' => $request->quantity,
            'subtotal' => $productUnit->new_price * $request->quantity,
        ]);

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'message'   => 'Keranjang produk berhasil diubah.'
        ], 200);
    }

    // Remove cart item
    public function destroy(Request $request, Cart $cart)
    {
        $cart->delete();
        return response()->json([
            'code'      => 200,
            'status'    => true,
            'message'   => 'Berhasil menghapus produk dari keranjang.'
        ], 200);
    }

    // Remove all carts
    public function removeCarts(Request $request)
    {
        $carts = Cart::where('session_id', getSessionId($request))
            ->where('status', 'PENDING')
            ->get(); // Ambil dulu datanya

        foreach ($carts as $cart) {
            $cart->delete(); // Hapus satu per satu
        }

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'message'   => 'Berhasil menghapus produk.'
        ], 200);
    }
}
