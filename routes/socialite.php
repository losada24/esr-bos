<?php
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Redirect;
use App\Models\User;

Route::get('/auth/{provider}/redirect', function (string $provider) {
  return Socialite::driver($provider)->redirect();
})->whereIn('provider', ['google', 'facebook', 'github']);

Route::get('/auth/{provider}/callback', function (string $provider) {
  $socialUser = Socialite::driver($provider)->user();
  $user = User::where('email', $socialUser->getEmail())->first();
  if (!$user) {
    return Redirect::route('login')
      ->with('error', 'This email does not have access.');
  }

  $user->update([
    'provider' => $provider,
    'provider_id' => $socialUser->id,
    'provider_token' => $socialUser->token
  ]);

  Auth::login($user);
  return redirect('/');
})->whereIn('provider', ['google', 'facebook', 'github']);
