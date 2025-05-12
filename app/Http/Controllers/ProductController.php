<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductUnits;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if (isset($request['search'])) {
            $searchKeyword = $request['search'];
            $query->keywordSearch($searchKeyword);
        }

        $query->orderBy('name', 'asc');

        if (isset($request['limit']) || isset($request['page'])) {
            $limit = $request['limit'] ?? 10;
            $result = $query->with(['group', 'category', 'units'])->paginate($limit)->appends(request()->query());
        } else {
            $result = $query->with(['group', 'category', 'units'])->get();
        }

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'data'      => $result
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sku'  => ['required', 'string', 'max:100', 'unique:products,sku'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'group_id' => ['nullable', 'exists:groups,id'],
            'image' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'side_effect' => ['nullable', 'string', 'max:255'],
            'rack_location' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'dosage' => ['nullable', 'string', 'max:100'],
            'purchase_price' => ['required', 'numeric'],
            'indication' => ['nullable', 'string', 'max:255'],
            'is_need_receipt' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'base_stock' => ['required', 'numeric'],
            'units' => ['required', 'array'],
            'units.*.unit_id' => ['required', 'exists:units,id'],
            'units.*.conversion_to_base' => ['required', 'numeric'],
            'units.*.description' => ['required', 'string', 'max:255'],
            'units.*.is_base' => ['required', 'boolean'],
            'units.*.sell_price' => ['required', 'numeric'],
            'units.*.new_price' => ['required', 'numeric'],
        ]);

        DB::beginTransaction();

        try {
            $product = Product::create([
                'name' => $request->name,
                'sku' => $request->sku,
                'category_id' => $request->category_id,
                'group_id' => $request->group_id,
                'base_unit_id' => data_get($request, 'units.0.unit_id', null),
                'base_stock' => $request->base_stock,
                'barcode' => $request->barcode,
                'image' => $request->image,
                'type' => $request->type,
                'side_effect' => $request->side_effect,
                'rack_location' => $request->rack_location,
                'description' => $request->description,
                'dosage' => $request->dosage,
                'purchase_price' => $request->purchase_price,
                'indication' => $request->indication,
                'is_need_receipt' => $request->is_need_receipt,
                'is_active' => $request->is_active,
                'created_by' => auth()->user()->fullname
            ]);

            $units = collect($request->units);

            $productUnits = $units->map(function ($unit) use ($product) {
                return [
                    'product_id' => $product->id,
                    'unit_id' => $unit['unit_id'],
                    'conversion_to_base' => $unit['conversion_to_base'],
                    'is_base' => $unit['is_base'],
                    'description' => $unit['description'],
                    'sell_price' => $unit['sell_price'],
                    'new_price' => $unit['new_price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                    'created_by' => auth()->user()->fullname
                ];
            })->toArray();

            ProductUnits::insert($productUnits);

            // Stock movement
            StockMovement::create([
                'product_id' => $product->id,
                'movement_type' => 'IN',
                'qty_in' => $request->base_stock,
                'qty_out' => 0,
                'remaining' => $request->base_stock,
                'reference_type' => 'Initial stock',
                'note' => 'Initial stock',
                'created_by' => auth()->user()->fullname
            ]);

            DB::commit();

            return response()->json([
                'code'      => 201,
                'status'    => true,
                'message'   => 'Produk baru berhasil ditambahkan.',
                'data'      => [
                    'product' => $product
                ]
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'code'      => 500,
                'status'    => false,
                'message'   => $th->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sku'  => ['required', 'string', 'max:100', Rule::unique('products')->ignore($product->id)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'group_id' => ['nullable', 'exists:groups,id'],
            'image' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'side_effect' => ['nullable', 'string', 'max:255'],
            'rack_location' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'dosage' => ['nullable', 'string', 'max:100'],
            'purchase_price' => ['required', 'numeric'],
            'indication' => ['nullable', 'string', 'max:255'],
            'is_need_receipt' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'base_stock' => ['required', 'numeric'],
            'units' => ['required', 'array'],
            'units.*.unit_id' => ['required', 'exists:units,id'],
            'units.*.conversion_to_base' => ['required', 'numeric'],
            'units.*.description' => ['required', 'string', 'max:255'],
            'units.*.is_base' => ['required', 'boolean'],
            'units.*.sell_price' => ['required', 'numeric'],
            'units.*.new_price' => ['required', 'numeric'],
        ]);

        $fields = [
            'name',
            'sku',
            'category_id',
            'group_id',
            'image',
            'barcode',
            'type',
            'side_effect',
            'rack_location',
            'description',
            'purchase_price',
            'dosage',
            'indication',
            'is_need_receipt',
            'is_active'
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $product->$field = $request->$field;
            }
        }

        $product->save();

        // Units
        $units = collect($request->units);

        // Units
        $units = collect($request->units);

        $incomingUnitIds = $units->pluck('unit_id')->toArray();

        // Ambil unit yang sudah ada di database untuk produk ini
        $existingUnits = ProductUnits::where('product_id', $product->id)
            ->pluck('unit_id')
            ->toArray();

        $unitsToInsert = [];
        $unitsToUpdate = [];

        foreach ($units as $unit) {
            $data = [
                'product_id' => $product->id,
                'unit_id' => $unit['unit_id'],
                'conversion_to_base' => $unit['conversion_to_base'],
                'is_base' => $unit['is_base'],
                'description' => $unit['description'],
                'sell_price' => $unit['sell_price'],
                'new_price' => $unit['new_price'],
                'created_by' => auth()->user()->fullname,
                'updated_at' => now(),
            ];

            if (in_array($unit['unit_id'], $existingUnits)) {
                // Jika unit sudah ada, update saja
                $data['updated_by'] = auth()->user()->fullname;
                $unitsToUpdate[] = $data;
            } else {
                // Jika unit baru, tambahkan ke insert list
                $data['created_at'] = now();
                $unitsToInsert[] = $data;
            }
        }

        // Insert unit baru
        if (!empty($unitsToInsert)) {
            ProductUnits::insert($unitsToInsert);
        }

        // Update unit yang sudah ada
        if (!empty($unitsToUpdate)) {
            foreach ($unitsToUpdate as $updateData) {
                ProductUnits::where('product_id', $product->id)
                    ->where('unit_id', $updateData['unit_id'])
                    ->update($updateData);
            }
        }

        // Hapus unit yang tidak ada di request
        ProductUnits::where('product_id', $product->id)
            ->whereNotIn('unit_id', $incomingUnitIds)
            ->delete();

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'message'   => 'Produk berhasil diperbarui.',
            'data'      => [
                'product' => $product
            ]
        ], 200);
    }
}
