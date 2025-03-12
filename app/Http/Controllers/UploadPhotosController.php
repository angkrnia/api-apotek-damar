<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UploadPhotosController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'images.*' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $uploadedImages = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                $imageName = date('md_His') . '_' . $imageFile->getClientOriginalName();
                $imageFile->move(public_path('upload'), $imageName);
                $imageUrl = url('upload/' . $imageName);

                $uploadedImages[] = [
                    'name' => $imageName,
                    'url' => $imageUrl
                ];
            }
        }

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Foto berhasil diupload.',
            'data' => [
                'images' => $uploadedImages
            ]
        ]);
    }
}
