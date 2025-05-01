<?php

namespace App\Http\Controllers\StockIn;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductUnits;
use App\Models\StockIn\StockInDetail;
use App\Models\StockIn\StockInHeader;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockInHeaderController extends Controller
{
    // index
    public function index(Request $request)
    {
        $query = StockInHeader::query();

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

    // store
    public function store(Request $request)
    {
        $request->validate([
            'source' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $stockEntryInHeader = StockInHeader::create([
            'source' => $request->source,
            'note' => $request->note,
            'created_by' => auth()->user()->fullname
        ]);

        return response()->json([
            'code'      => 201,
            'status'    => true,
            'data'      => $stockEntryInHeader
        ], 201);
    }

    // show
    public function show(StockInHeader $stockEntry)
    {
        $stockEntry->load(['productsLines']);
        return response()->json([
            'code'      => 200,
            'status'    => true,
            'data'      => $stockEntry
        ]);
    }

    // update
    public function update(Request $request, StockInHeader $stockEntry)
    {
        $request->validate([
            'source' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Jika statusnya bukan NEW maka tidak bisa commit
        if ($stockEntry->status != 'NEW') {
            return response()->json([
                'code'      => 400,
                'status'    => false,
                'message'   => 'Status stok masuk tidak valid.',
            ], 400);
        }

        $stockEntry->update([
            'source' => $request->source,
            'note' => $request->note,
            'updated_by' => auth()->user()->fullname
        ]);

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'message'   => 'Data stok masuk berhasil diubah.',
            'data'      => $stockEntry
        ]);
    }

    // destroy
    public function destroy(StockInHeader $stockEntry)
    {
        // Jika statusnya bukan NEW maka tidak bisa commit
        if ($stockEntry->status != 'NEW') {
            return response()->json([
                'code'      => 400,
                'status'    => false,
                'message'   => 'Status stok masuk tidak valid.',
            ], 400);
        }

        $stockEntry->delete();
        return response()->json([
            'code'      => 200,
            'status'    => true,
            'message'   => 'Data stok masuk berhasil dihapus.',
        ]);
    }

    // committed/posted stock
    public function committed(StockInHeader $stock)
    {
        DB::beginTransaction();
        try {
            // Jika statusnya bukan NEW maka tidak bisa commit
            if ($stock->status != 'NEW') {
                return response()->json([
                    'code'      => 400,
                    'status'    => false,
                    'message'   => 'Data stok masuk tidak dapat diselesaikan.',
                ], 400);
            }
            // Jika produk line masih kosong
            if (count($stock->productsLines) == 0) {
                return response()->json([
                    'code'      => 400,
                    'status'    => false,
                    'message'   => 'Data produk masih kosong.',
                ], 400);
            }

            $stock->update([
                'status' => 'COMMITED',
                'updated_by' => auth()->user()->fullname
            ]);

            foreach ($stock->productsLines as $line) {
                // Update stock produknya
                $product = Product::find($line->product_id);
                if (!$product) {
                    continue; // Skip jika produk tidak ditemukan
                }

                if ($line->quantity >= 0) {
                    // Cek conversion dari relasi product_unit_id
                    $productUnit = ProductUnits::find($line->product_unit_id);
                    if (!$productUnit) {
                        continue; // Skip jika product unit tidak ditemukan
                    }
                    // Stok bertambah
                    $product->increment('base_stock', abs($line->quantity * $productUnit->conversion_to_base));
                    // Update harga beli
                    $product->purchase_price = $line->buy_price / max(1, $productUnit->conversion_to_base);
                    $product->save();

                    // Masukkan ke stock movement
                    StockMovement::create([
                        'product_id' => $product->id,
                        'movement_type' => 'IN', // Barang masuk
                        'qty_in' => $line->quantity * $productUnit->conversion_to_base,
                        'qty_out' => 0,
                        'remaining' => $product->base_stock,
                        'reference_type' => 'Stok Masuk ' . now()->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s'),
                        'reference_id' => $stock->id,
                        'note' => $stock->note,
                        'created_by' => auth()->user()->fullname
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'code'      => 200,
                'status'    => true,
                'message'   => 'Data stok masuk berhasil diselesaikan.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return response()->json([
                'code'      => 500,
                'status'    => false,
                'message'   => 'Data stok masuk gagal diselesaikan.',
            ], 500);
        }
    }

    public function cancel(Request $request, StockInHeader $stock)
    {
        $request->validate([
            'note' => ['required', 'string', 'max:255']
        ]);

        // Jika statusnya bukan success
        if ($stock->status == 'CANCEL') {
            return response()->json([
                'code'      => 400,
                'status'    => false,
                'message'   => 'Stok Masuk tidak dapat dibatalkan.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $stockProductLine = StockInDetail::with(['product', 'productUnit'])
                ->where('stock_in_id', $stock->id)
                ->get();

            foreach ($stockProductLine as $productLine) {
                // Ambil qty dan konversi ke stok dasar
                $qty = $productLine->quantity;
                $conversion = ProductUnits::find($productLine->product_unit_id)->conversion_to_base;
                $restoreQty = $qty * $conversion;

                // Ambil produk
                $product = Product::find($productLine->product_id);

                // Kembalikan ke base stock
                $product->base_stock -= $restoreQty;
                $product->save();

                // Update note pada detail penjualan
                $productLine->note = $request->note;
                $productLine->updated_by = auth()->user()->fullname;
                $productLine->status = 'CANCEL';
                $productLine->save();

                // Catat ke stock movement
                StockMovement::create([
                    'product_id'     => $product->id,
                    'movement_type'  => 'OUT', // Karena stok masuk kembali
                    'qty_in'         => 0,
                    'qty_out'        => $restoreQty,
                    'remaining'      => $product->base_stock,
                    'reference_type' => 'Cancel Stok Masuk Asal: ' . $stock->source,
                    'note'           => $request->note ?? 'Pembatalan Stok Masuk',
                    'created_by'     => auth()->user()->fullname,
                ]);
            }

            // Update status penjualan
            $stock->status = 'CANCEL';
            $stock->updated_by = auth()->user()->fullname;
            $stock->save();

            DB::commit();

            return response()->json([
                'code'      => 200,
                'status'    => true,
                'message'   => 'Stok Masuk berhasil di cancel. Stok dikembalikan.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return response()->json([
                'code'      => 500,
                'status'    => false,
                'message'   => 'Gagal cancel stok masuk.',
            ], 500);
        }
    }
}
