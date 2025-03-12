<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query();

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
            'image' => ['nullable', 'string', 'max:255'],
        ]);

        $data = Category::create([
            'name' => $request->name,
            'image' => $request->image,
        ]);

        return response()->json([
            'code'      => 201,
            'status'    => true,
            'message'   => 'Kategori baru berhasil ditambahkan.',
            'data'      => [
                'category' => $data
            ]
        ], 201);
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:50'],
                'image' => ['nullable', 'string', 'max:255'],
            ]);

            $data = Category::find($id);
            $data->name = $request->name;
            $data->image = $request->image;
            $data->save();

            return response()->json([
                'code'      => 200,
                'status'    => true,
                'message'   => 'Kategori berhasil diubah.',
                'data'      => [
                    'category' => $data
                ]
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code'      => 404,
                'status'    => false,
                'message'   => 'Kategori tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'code'      => 500,
                'status'    => false,
                'message'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Category::findOrFail($id)->delete();
            return response()->json([
                'code'      => 200,
                'status'    => true,
                'message'   => 'Kategori berhasil dihapus.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code'      => 404,
                'status'    => false,
                'message'   => 'Kategori tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'code'      => 500,
                'status'    => false,
                'message'   => $e->getMessage()
            ], 500);
        }
    }
}
