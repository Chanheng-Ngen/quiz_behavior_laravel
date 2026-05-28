<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Image;

class UploadImageController extends Controller
{
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);
        $path = $request->file('image')->store('images', 'public');

        $url = asset('storage/' . $path);

        $image = Image::create([
            'url' => $url
        ]);

        return response()->json([
            'message' => 'Image uploaded successfully',
            'data' => $image
        ]);

    }
}
