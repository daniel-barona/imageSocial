<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;


class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request){
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'surname' => ['sometimes', 'string', 'max:255'],
            'nick' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('users')->ignore($request->user()->id),
            ],
            'image' => ['nullable', 'image', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ]);
        if ($request->boolean('remove_image')) {
            if ($request->user()->image) {
                Storage::disk('public')->delete($request->user()->image);
            }
        $validated['image'] = null;
        }

        //$data = $request->only('name', 'surname', 'nick');

        if ($request->hasFile('image')) {
            // borra la anterior si existe
            if ($request->user()->image) {
                Storage::disk('public')->delete($request->user()->image);
            }

            $validated['image'] = $request->file('image')->store('avatars', 'public');
        }

        $request->user()->update($validated);

        return back()->with('status', 'profile-updated');
    }


    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
