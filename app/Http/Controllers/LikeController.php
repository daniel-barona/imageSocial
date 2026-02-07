<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Image;
use App\Models\Like;
use App\Models\User;

class LikeController extends Controller
{
    public function toggle(Image $image){
        $like = $image->likes()
            ->where('user_id', auth()->user()->id)
            ->first();
        if ($like) {
            $like->delete();
        } else {
            $image->likes()->create([
                'user_id' => auth()->user()->id,
            ]);
        }
        return back();
    }
}
