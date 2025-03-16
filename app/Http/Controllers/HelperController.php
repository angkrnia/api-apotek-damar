<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Group;
use App\Models\Product;
use App\Models\ProductUnits;
use App\Models\Sale\SaleHeader;
use App\Models\StockMovement;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HelperController extends Controller
{
    public function categoryList(Request $request)
    {
        return response()->json([
            'code'      => 200,
            'status'    => true,
            'data'      => Category::all()
        ]);
    }

    public function groupList(Request $request)
    {
        return response()->json([
            'code'      => 200,
            'status'    => true,
            'data'      => Group::all()
        ]);
    }

    public function unitList(Request $request)
    {
        return response()->json([
            'code'      => 200,
            'status'    => true,
            'data'      => Unit::all()
        ]);
    }

    public function medicineList(Request $request)
    {
        $data = Product::query()
            ->when($request->filled('search'), fn($query) => $query->keywordSearch($request->input('search')))
            ->when($request->filled('category_id'), fn($query) => $query->where('category_id', $request->input('category_id')))
            ->when($request->filled('group_id'), fn($query) => $query->where('group_id', $request->input('group_id')))
            ->where('is_active', true)
            ->with('units')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'name', 'slug', 'base_stock', 'image']);

        return response()->json([
            'code'   => 200,
            'status' => true,
            'data'   => $data
        ]);
    }

    public function stockMovements(Request $request, Product $product)
    {
        $query = StockMovement::query();

        // Jika ada query start
        if (isset($request['start']) && !empty($request['start'])) {
            $start = $request['start'];
            $query->where('created_at', '>=', $start);
        }

        // Jika ada query end
        if (isset($request['end']) && !empty($request['end'])) {
            $end = $request['end'];
            $query->where('created_at', '<=', $end);
        }

        Log::info($product);

        $query->where('product_id', $product->id);

        $query->orderBy('created_at', 'desc');

        // Jika ada query limit atau page
        if (isset($request['limit']) || isset($request['page'])) {
            $limit = $request['limit'] ?? 10;
            $result = $query->with(['product'])->paginate($limit)->appends(request()->query());
        } else {
            $result = $query->with(['product'])->get();
        }

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'data'      => $result
        ]);
    }

    public function receiptNumber(Request $request, $trx)
    {
        $sale = SaleHeader::with('productsLines')->where('receipt_number', $trx)->firstOrFail();
        return response()->json([
            'code'      => 200,
            'status'    => true,
            'data'      => $sale
        ]);
    }
}
