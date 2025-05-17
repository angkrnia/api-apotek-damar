<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $query = Unit::query();

        if (isset($request['search'])) {
            $searchKeyword = $request['search'];
            $query->keywordSearch($searchKeyword);
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

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $unit = Unit::create($request->all());

        return response()->json([
            'code'      => 201,
            'status'    => true,
            'message'   => 'Satuan baru berhasil ditambahkan.',
            'data'      => [
                'unit' => $unit
            ]
        ], 201);
    }

    public function update(Request $request, Unit $unit)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $unit->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'message'   => 'Satuan berhasil diperbarui.',
            'data'      => [
                'unit' => $unit
            ]
        ]);
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'message'   => 'Satuan berhasil dihapus.'
        ]);
    }
}
