<?php

namespace App\Http\Controllers\Opname;

use App\Http\Controllers\Controller;
use App\Models\Opname\OpnameDetail;
use App\Models\Opname\OpnameHeader;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OpnameDetailController extends Controller
{
    // index
    public function index(Request $request)
    {
        $query = OpnameDetail::query();

        // Jika ada search
        if (isset($request['search']) && !empty($request['search'])) {
            $textSearch =  $request['search'];
            $query->keywordSearch($textSearch);
        }

        $query->orderBy('created_at', 'desc');

        $query->where('opname_header_id', $request->opname);

        if (isset($request['limit']) || isset($request['page'])) {
            $limit = $request['limit'] ?? 10;
            $result = $query->with(['product.units'])->paginate($limit)->appends(request()->query());
        } else {
            $result = $query->with(['product.units'])->get();
        }

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'data'      => $result
        ]);
    }

    // store
    public function store(Request $request, OpnameHeader $opname)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'qty_real' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Jika statusnya bukan NEW tidak boleh tambah
        if ($opname->status != 'NEW') {
            return response()->json([
                'code'      => 400,
                'status'    => false,
                'message'   => 'Status opname tidak valid.',
            ], 400);
        }

        // Jika produk line sudah ditambahkan sebelumnya
        if (OpnameDetail::where('opname_header_id', $opname->id)->where('product_id', $request->product_id)->exists()) {
            return response()->json([
                'code'      => 400,
                'status'    => false,
                'message'   => 'Produk sudah ditambahkan sebelumnya.',
            ], 400);
        }

        $qtySystem = Product::find($request->product_id)->base_stock;
        $qtyDiff = $request->qty_real - $qtySystem;

        $opnameHeader = OpnameDetail::create([
            'opname_header_id' => $opname->id,
            'product_id' => $request->product_id,
            'qty_system' => $qtySystem,
            'qty_real' => $request->qty_real,
            'qty_diff' => $qtyDiff,
            'note' => $request->note,
            'created_by' => auth()->user()->fullname
        ]);

        return response()->json([
            'code'      => 201,
            'status'    => true,
            'data'      => $opnameHeader
        ], 201);
    }

    // update
    public function update(Request $request, OpnameHeader $opname, OpnameDetail $line)
    {
        $request->validate([
            'qty_real' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Jika statusnya bukan NEW tidak boleh tambah
        if ($opname->status != 'NEW') {
            return response()->json([
                'code'      => 400,
                'status'    => false,
                'message'   => 'Status opname tidak valid.',
            ], 400);
        }

        $qtySystem = Product::find($request->product_id)->base_stock;
        $qtyDiff = $request->qty_real - $qtySystem;

        $line->update([
            'qty_system' => $qtySystem,
            'qty_real' => $request->qty_real,
            'qty_diff' => $qtyDiff,
            'note' => $request->note,
            'updated_by' => auth()->user()->fullname
        ]);

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'message'   => 'Produk berhasil diubah.',
            'data'      => $opname
        ]);
    }

    // destroy
    public function destroy(OpnameHeader $opname, OpnameDetail $line)
    {
        // Jika statusnya bukan NEW tidak boleh tambah
        if ($opname->status != 'NEW') {
            return response()->json([
                'code'      => 400,
                'status'    => false,
                'message'   => 'Status opname tidak valid.',
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
