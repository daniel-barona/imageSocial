<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Image;
use App\Models\Comment;

class CommentController extends Controller
{
    public function store(Request $request, Image $image)
    {
        $request->validate([
            'content' => 'required|string|max:280',
        ]);
        $image->comments()->create([
            'user_id' => auth()->id(),
            'content' => $request->content,
        ]);
        return back();
    }
    public function update(Request $request, Comment $comment){
        abort_if($comment->user_id !== auth()->id(), 403);

        $request->validate([
            'content' => 'required|string|max:280',
        ]);

        $comment->update([
            'content' => $request->content,
        ]);

        return back();
    }
    public function destroy(Comment $comment)
    {
        abort_if($comment->user_id !== auth()->id(), 403);

        $comment->delete();

        return back();
    }
}
