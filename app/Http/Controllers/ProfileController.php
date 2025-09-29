<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Tweet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
// 🔽 追加
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

     /**
   * Display the specified resource.
   */
  public function show(User $user)
  {
    if (auth()->user()->is($user)) {
      $tweets = Tweet::query()
        ->where('user_id', $user->id)  // 自分のツイート
        ->orWhereIn('user_id', $user->follows->pluck('id')) // フォローしているユーザーのツイート
        ->latest()
        ->paginate(10);
    } else {
      // 他のユーザーの場合、そのユーザーのツイートのみを取得
      $tweets = $user
        ->tweets()
        ->latest()
        ->paginate(10);
    }

    // ユーザーのフォロワーとフォローしているユーザーを取得
    $user->load(['follows', 'followers']);

    return view('profile.show', compact('user', 'tweets'));
  }

}
