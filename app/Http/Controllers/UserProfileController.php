<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Image;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function show(string $nick)
    {
        $user = User::where('nick', $nick)->firstOrFail();

        $images = Image::with(['user', 'comments.user'])
            ->withCount('likes')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        return inertia('User/Profile', [
            'user' => $user,
            'images' => $images,
        ]);
    }

    public function search(Request $request)
    {
        $q = $request->input('q');
        $users = [];
        if ($q) {
            $users = User::where('name', 'like', "%{$q}%")
                ->orWhere('surname', 'like', "%{$q}%")
                ->orWhere('nick', 'like', "%{$q}%")
                ->limit(50)
                ->get();
        }

        // If the client expects JSON (AJAX), return raw JSON to avoid Inertia navigation
        if ($request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json($users);
        }

        return inertia('User/Profile', [
            'searchResults' => $users,
        ]);
    }
}

