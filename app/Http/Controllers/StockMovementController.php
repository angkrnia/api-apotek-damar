<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
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

    public function show(Request $request, Product $product)
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
}
