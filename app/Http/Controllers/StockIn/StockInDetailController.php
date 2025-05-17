<?php

namespace App\Http\Controllers\StockIn;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductUnits;
use App\Models\StockIn\StockInDetail;
use App\Models\StockIn\StockInHeader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StockInDetailController extends Controller
{
    // index
    public function index(Request $request)
    {
        $query = StockInDetail::query();

        // Jika ada search
        if (isset($request['search']) && !empty($request['search'])) {
            $textSearch =  $request['search'];
            $query->keywordSearch($textSearch);
        }

        $query->orderBy('created_at', 'desc');

        $query->where('stock_in_id', $request->stock);

        if (isset($request['limit']) || isset($request['page'])) {
            $limit = $request['limit'] ?? 10;
            $result = $query->with(['product', 'productUnit.unit'])->paginate($limit)->appends(request()->query());
        } else {
            $result = $query->with(['product', 'productUnit.unit'])->get();
        }

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'data'      => $result
        ]);
    }

    // store
    public function store(Request $request, StockInHeader $stock)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_unit_id' => ['required', Rule::exists('product_units', 'id')->where('product_id', $request->product_id)],
            'quantity' => ['required', 'numeric', 'min:0'],
            'buy_price' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Jika statusnya bukan NEW tidak boleh tambah
        if ($stock->status != 'NEW') {
            return response()->json([
                'code'      => 400,
                'status'    => false,
                'message'   => 'Status stok masuk tidak valid.',
            ], 400);
        }

        // Jika produk line sudah ditambahkan sebelumnya
        if (StockInDetail::where('stock_in_id', $stock->id)->where('product_id', $request->product_id)->exists()) {
            return response()->json([
                'code'      => 400,
                'status'    => false,
                'message'   => 'Produk sudah ditambahkan sebelumnya.',
            ], 400);
        }

        $product = Product::find($request->product_id);
        $productUnit = ProductUnits::find($request->product_unit_id);

        $lastBuyPrice = optional($product)->purchase_price ?? 0;
        $lastSellPrice = optional($productUnit)->new_price ?? 0;

        $stockDetail = StockInDetail::create([
            'stock_in_id' => $stock->id,
            'product_id' => $request->product_id,
            'product_unit_id' => $request->product_unit_id,
            'quantity' => $request->quantity,
            'buy_price' => $request->buy_price,
            'note' => $request->note,
            'status' => 'NEW',
            'last_buy_price' => $lastBuyPrice,
            'last_sell_price' => $lastSellPrice,
            'created_by' => auth()->user()->fullname
        ]);

        return response()->json([
            'code'      => 201,
            'status'    => true,
            'data'      => $stockDetail
        ], 201);
    }

    // update
    public function update(Request $request, StockInHeader $stock, StockInDetail $line)
    {
        $request->validate([
            'quantity' => ['required', 'numeric', 'min:0'],
            'buy_price' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Jika statusnya bukan NEW tidak boleh tambah
        if ($stock->status != 'NEW') {
            return response()->json([
                'code'      => 400,
                'status'    => false,
                'message'   => 'Status stok masuk tidak valid.',
            ], 400);
        }

        $line->update([
            'quantity' => $request->quantity,
            'buy_price' => $request->buy_price,
            'note' => $request->note,
            'updated_by' => auth()->user()->fullname
        ]);

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'message'   => 'Produk berhasil diubah.',
            'data'      => $stock
        ]);
    }

    // destroy
    public function destroy(StockInHeader $stock, StockInDetail $line)
    {
        // Jika statusnya bukan NEW tidak boleh tambah
        if ($stock->status != 'NEW') {
            return response()->json([
                'code'      => 400,
                'status'    => false,
                'message'   => 'Status stok masuk tidak valid.',
            ], 400);
        }

        $line->delete();
        return response()->json([
            'code'      => 200,
            'status'    => true,
            'message'   => 'Produk berhasil dihapus.',
        ]);
    }
}
