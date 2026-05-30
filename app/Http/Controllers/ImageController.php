<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Image;
use App\Models\Question;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function uploadImage(Request $request,int  $question_id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $question = Question::with('quiz')->findOrFail($question_id);

        if ($question->quiz->creator_id !== Auth::id()) {
            return response()->json([
                'message' => 'Forbidden. You do not own this question.'
            ], 403);
        }

        $path = $request->file('image')->store('images', 'public');
        $url  = asset('storage/' . $path);

        $image = Image::create([
            'url'         => $url,
            'question_id' => $question_id
        ]);

        return response()->json([
            'result' => true,
            'message' => 'Image uploaded successfully',
            'data'    => $image
        ]);
    }

    public function updateImage(Request $request, int $question_id, int $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $question = Question::with('quiz')->findOrFail($question_id);

        if ($question->quiz->creator_id !== Auth::id()) {
            return response()->json([
                'message' => 'Forbidden. You do not own this question.'
            ], 403);
        }

        $image = Image::where('id', $id)
                      ->where('question_id', $question_id)
                      ->firstOrFail();

        // Delete old image file from storage
        $oldPath = str_replace(asset('storage/'), '', $image->url);
        Storage::disk('public')->delete($oldPath);

        // Store new image
        $newPath = $request->file('image')->store('images', 'public');
        $newUrl  = asset('storage/' . $newPath);

        $image->update(['url' => $newUrl]);

        return response()->json([
            'result' => true,
            'message' => 'Image updated successfully',
            'data'    => $image
        ]);
    }

    public function deleteImage(int $question_id, int $id)
    {
        $question = Question::with('quiz')->findOrFail($question_id);

        if ($question->quiz->creator_id !== Auth::id()) {
            return response()->json([
                'message' => 'Forbidden. You do not own this question.'
            ], 403);
        }

        $image = Image::where('id', $id)
                      ->where('question_id', $question_id)
                      ->firstOrFail();

        // Delete image file from storage
        $path = str_replace(asset('storage/'), '', $image->url);
        Storage::disk('public')->delete($path);

        $image->delete();

        return response()->json([
            'result' => true,
            'message' => 'Image deleted successfully'
        ]);
    }
}