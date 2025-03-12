<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $query = Group::query();

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

        $group = Group::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'code'      => 201,
            'status'    => true,
            'message'   => 'Group baru berhasil ditambahkan.',
            'data'      => [
                'group' => $group
            ]
        ], 201);
    }

    public function update(Request $request, Group $group)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $group->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'message'   => 'Group berhasil diperbarui.',
            'data'      => [
                'group' => $group
            ]
        ]);
    }

    public function destroy(Group $group)
    {
        $group->delete();

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'message'   => 'Group berhasil dihapus.'
        ]);
    }
}
