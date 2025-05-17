<?php

namespace App\Http\Controllers\Opname;

use App\Http\Controllers\Controller;
use App\Models\Opname\OpnameHeader;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OpnameHeaderController extends Controller
{
    // index
    public function index(Request $request)
    {
        $query = OpnameHeader::query();

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
            'code' => ['required', 'string', 'max:100', 'unique:stock_opname,code'],
            'pic' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $opnameHeader = OpnameHeader::create([
            'date' => now(),
            'code' => $request->code,
            'pic' => $request->pic,
            'note' => $request->note,
            'created_by' => auth()->user()->fullname
        ]);

        return response()->json([
            'code'      => 201,
            'status'    => true,
            'data'      => $opnameHeader
        ], 201);
    }

    // show
    public function show(OpnameHeader $opname)
    {
        $opname->load(['productsLines']);
        return response()->json([
            'code'      => 200,
            'status'    => true,
            'data'      => $opname
        ]);
    }

    // update
    public function update(Request $request, OpnameHeader $opname)
    {
        $request->validate([
            'pic' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Jika statusnya bukan NEW maka tidak bisa commit
        if ($opname->status != 'NEW') {
            return response()->json([
                'code'      => 400,
                'status'    => false,
                'message'   => 'Status opname tidak valid.',
            ], 400);
        }

        $opname->update([
            'pic' => $request->pic,
            'note' => $request->note,
            'updated_by' => auth()->user()->fullname
        ]);

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'message'   => 'Data opname berhasil diubah.',
            'data'      => $opname
        ]);
    }

    // destroy
    public function destroy(OpnameHeader $opname)
    {
        // Jika statusnya bukan NEW maka tidak bisa commit
        if ($opname->status != 'NEW') {
            return response()->json([
                'code'      => 400,
                'status'    => false,
                'message'   => 'Status opname tidak valid.',
            ], 400);
        }

        $opname->delete();
        return response()->json([
            'code'      => 200,
            'status'    => true,
            'message'   => 'Data opname berhasil dihapus.',
        ]);
    }

    // committed/posted stock
    public function committed(OpnameHeader $opname)
    {
        DB::beginTransaction();
        try {
            // Jika statusnya bukan NEW maka tidak bisa commit
            if ($opname->status != 'NEW') {
                return response()->json([
                    'code'      => 400,
                    'status'    => false,
                    'message'   => 'Data opname tidak dapat diselesaikan.',
                ], 400);
            }
            // Jika produk line masih kosong
            if (count($opname->productsLines) == 0) {
                DB::rollBack();
                return response()->json([
                    'code'      => 400,
                    'status'    => false,
                    'message'   => 'Data produk line masih kosong.',
                ], 400);
            }

            $opname->update([
                'status' => 'COMMITED',
                'updated_by' => auth()->user()->fullname
            ]);

            foreach ($opname->productsLines as $line) {
                // Cek apakah produk line memiliki transaksi?
                if ($line->has_transaction) {
                    DB::rollBack();
                    return response()->json([
                        'code'      => 400,
                        'status'    => false,
                        'message'   => 'Terdapat produk yang sudah memiliki transaksi. Silakan update stok untuk produk [' . $line->product->name . ']',
                    ], 400);
                }

                // Update stock produknya
                $product = Product::find($line->product_id);
                if (!$product) {
                    continue; // Skip jika produk tidak ditemukan
                }

                if ($line->qty_diff > 0) {
                    // Stok bertambah (kelebihan saat opname)
                    $product->increment('base_stock', abs($line->qty_diff));

                    // Masukkan ke stock movement
                    StockMovement::create([
                        'product_id' => $product->id,
                        'movement_type' => 'IN', // Barang masuk
                        'qty_in' => $line->qty_diff,
                        'qty_out' => 0,
                        'remaining' => $product->base_stock,
                        'reference_type' => 'Stock Opname #' . $opname->code . ' - QTY Real: ' . $product->base_stock,
                        'note' => $opname->note,
                        'created_by' => auth()->user()->fullname
                    ]);
                } else if ($line->qty_diff < 0) {
                    // Stok berkurang (kekurangan saat opname)
                    $product->decrement('base_stock', abs($line->qty_diff));

                    // Masukkan ke stock movement
                    StockMovement::create([
                        'product_id' => $product->id,
                        'movement_type' => 'OUT', // Barang keluar
                        'qty_in' => 0,
                        'qty_out' => $line->qty_diff,
                        'remaining' => $product->base_stock,
                        'reference_type' => 'Stock Opname #' . $opname->code . ' - QTY Real: ' . $product->base_stock,
                        'note' => $opname->note,
                        'created_by' => auth()->user()->fullname
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'code'      => 200,
                'status'    => true,
                'message'   => 'Stock Opname berhasil diselesaikan.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return response()->json([
                'code'      => 500,
                'status'    => false,
                'message'   => 'Data opname gagal diselesaikan.',
            ], 500);
        }
    }
}
