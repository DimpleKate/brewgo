<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ProfilePictureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->only(['name', 'email', 'phone', 'address']);

        if ($request->filled('password')) {
            $data['password'] = $request->string('password');
        }

        $user->update($data);

        return redirect()->route('profile.edit')->with('success', 'Your profile has been updated.');
    }

    public function updateAvatar(Request $request, ProfilePictureService $profilePictures): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'avatar.required' => 'Choose a profile picture to upload.',
            'avatar.image' => 'Upload a valid JPG, JPEG, PNG, or WebP image.',
            'avatar.mimes' => 'Your profile picture must be a JPG, JPEG, PNG, or WebP file.',
            'avatar.max' => 'Your profile picture may not be larger than 5 MB.',
            'avatar.uploaded' => 'The image could not be uploaded. It may be larger than the server allows.',
        ]);

        $user = $request->user();
        $previous = $user->avatar_path;

        try {
            $path = $profilePictures->store($request->file('avatar'));
        } catch (Throwable) {
            return redirect()->route('profile.edit')->with('error', 'We could not save your profile picture. Please try again.');
        }

        $user->update(['avatar_path' => $path]);

        if ($previous !== null) {
            $profilePictures->delete($previous);
        }

        return redirect()->route('profile.edit')->with('success', 'Your profile picture has been updated.');
    }

    public function destroyAvatar(Request $request, ProfilePictureService $profilePictures): RedirectResponse
    {
        $user = $request->user();
        $profilePictures->delete($user->avatar_path);
        $user->update(['avatar_path' => null]);

        return redirect()->route('profile.edit')->with('success', 'Your profile picture has been removed.');
    }
}
