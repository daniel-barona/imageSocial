<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Image;

class ImageController extends Controller
{
    public function index()
    {
        $images = Image::with([
            'user',
            'comments.user',
        ])
        ->withCount('likes')
        ->latest()
        ->paginate(10);

        return Inertia::render('Dashboard', [
            'images' => $images,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => ['nullable', 'string', 'max:280'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
        }
        Image::create([
            'user_id' => $request->user()->id,
            'description' => $request->input('description'),
            'image' => $imagePath,
        ]);
        return redirect()->back();
    }

    public function update(Request $request, Image $image)
    {
        abort_if($image->user_id !== $request->user()->id, 403);

        $request->validate([
            'description' => ['nullable', 'string', 'max:280'],
        ]);

        $image->update([
            'description' => $request->input('description'),
        ]);

        return back();
    }

    public function destroy(Request $request, Image $image)
    {
        abort_if($image->user_id !== $request->user()->id, 403);

        // delete stored file if exists
        if ($image->image) {
            try {
                \Storage::disk('public')->delete($image->image);
            } catch (\Exception $e) {
                // ignore
            }
        }

        $image->delete();

        return back();
    }
}
